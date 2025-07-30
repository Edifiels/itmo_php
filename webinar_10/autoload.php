<?php
/**
 * autoload.php - Автозагрузка классов и инициализация безопасности для блога (Занятие 10)
 */
use Blog\Services\SecurityService;

// Автозагрузка классов
spl_autoload_register(function ($className) {
    // Преобразуем namespace в путь к файлу
    $className = str_replace('\\', '/', $className);
    
    // Убираем префикс Blog\ если есть
    if (strpos($className, 'Blog/') === 0) {
        $className = substr($className, 5);
    }
    
    $file = __DIR__ . '/src/' . $className . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Подключаем конфигурацию
require_once __DIR__ . '/config/database.php';

// Устанавливаем временную зону
date_default_timezone_set('Europe/Moscow');

// Настройки безопасности PHP
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Создаем папку для логов если её нет
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Функция инициализации приложения
function initializeApp() {
    // Инициализируем безопасную сессию только если мы не в API
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
    
    if (!$isApiRequest && $currentScript !== 'sitemap.php' && $currentScript !== 'robots.php') {
        SecurityService::initSecureSession();
        
        // Очищаем старые данные rate limiting периодически
        if (rand(1, 100) <= 5) { // 5% вероятность
            SecurityService::cleanupRateLimit();
        }
    }
    
    // Устанавливаем заголовки безопасности
    setSecurityHeaders();
}

// Функция установки заголовков безопасности
function setSecurityHeaders() {
    // Защита от XSS
    header('X-XSS-Protection: 1; mode=block');
    
    // Защита от MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Защита от clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Content Security Policy (базовый)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline'; " .
           "style-src 'self' 'unsafe-inline'; " .
           "img-src 'self' data:; " .
           "font-src 'self'";
    header("Content-Security-Policy: $csp");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Удаляем версию PHP из заголовков
    header_remove('X-Powered-By');
}

// Инициализация при подключении файла
if (!defined('AUTOLOAD_INITIALIZED')) {
    define('AUTOLOAD_INITIALIZED', true);
    initializeApp();
}

// Функция логирования для безопасности
function logSecurityEvent($message, $level = 'INFO') {
    $logFile = __DIR__ . '/logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = "[$timestamp] [$level] IP: $ip | $message | User-Agent: " . substr($userAgent, 0, 200) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Обработчик ошибок для безопасности
set_error_handler(function($severity, $message, $file, $line) {
    // Логируем только серьезные ошибки
    if ($severity & (E_ERROR | E_WARNING | E_PARSE)) {
        error_log("PHP Error: $message in $file on line $line");
    }
    
    // Не показываем ошибки пользователям в продакшене
    return true;
});

// Обработчик исключений
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    // Показываем безопасную страницу ошибки
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    
    echo "<!DOCTYPE html><html><head><title>Ошибка</title></head><body>";
    echo "<h1>Временная техническая ошибка</h1>";
    echo "<p>Попробуйте обновить страницу через несколько минут.</p>";
    echo "<p><a href='/'>← Вернуться на главную</a></p>";
    echo "</body></html>";
    exit;
});

// Функция проверки HTTPS в продакшене
function enforceHTTPS() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        if (!in_array($_SERVER['SERVER_PORT'], [80, 8080, 3000])) { // Исключения для разработки
            $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirectURL", true, 301);
            exit;
        }
    }
}

// Функция защиты от SQL инъекций (дополнительная проверка GET/POST)
function sanitizeRequestData() {
    $suspiciousPatterns = [
        '/union\s+select/i',
        '/drop\s+table/i',
        '/insert\s+into/i',
        '/delete\s+from/i',
        '/<script/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i'
    ];
    
    $checkData = function($data) use ($suspiciousPatterns) {
        if (is_array($data)) {
            foreach ($data as $value) {
                if (is_string($value)) {
                    foreach ($suspiciousPatterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            logSecurityEvent("Suspicious input detected: " . substr($value, 0, 100), 'WARNING');
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    };
    
    // Проверяем GET и POST данные
    if (!$checkData($_GET) || !$checkData($_POST)) {
        http_response_code(400);
        die('Bad Request');
    }
}

// Выполняем проверки безопасности
sanitizeRequestData();

// В продакшене можно раскомментировать для принудительного HTTPS
// enforceHTTPS();
?>