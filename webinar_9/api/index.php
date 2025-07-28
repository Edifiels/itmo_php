<?php
/**
 * Главный роутер для API блога
 * api/index.php
 */

// Подключаем конфигурацию и функции
require_once '../autoload.php';

// Настройки для API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Для CORS
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Version');

// Обработка предварительного запроса CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Получаем путь и метод запроса
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Убираем базовый путь к API
$basePath = '/webinar_9/api';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Функция для отправки JSON ответа
function sendJsonResponse($data, $httpCode = 200, $message = '') {
    http_response_code($httpCode);
    
    $response = [
        'status' => $httpCode >= 200 && $httpCode < 300 ? 'success' : 'error',
        'code' => $httpCode,
        'timestamp' => date('c') // ISO 8601 формат
    ];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Функция для получения JSON данных из тела запроса
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(null, 400, 'Некорректный JSON: ' . json_last_error_msg());
    }
    
    return $data ?: [];
}

// Функция для определения версии API
function getApiVersion() {
    // Проверяем заголовок
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (preg_match('/application\/vnd\.blog\.v(\d+)\+json/', $acceptHeader, $matches)) {
        return (int)$matches[1];
    }
    
    // Проверяем URL
    if (preg_match('#/v(\d+)/#', $path, $matches)) {
        return (int)$matches[1];
    }
    
    // Проверяем параметр запроса
    if (isset($_GET['version'])) {
        return (int)$_GET['version'];
    }
    
    // Версия по умолчанию
    return 1;
}

// Проверяем версию API
$apiVersion = getApiVersion();
$supportedVersions = [1, 2];

if (!in_array($apiVersion, $supportedVersions)) {
    sendJsonResponse([
        'supported_versions' => $supportedVersions,
        'latest_version' => max($supportedVersions)
    ], 400, "API версии $apiVersion не поддерживается");
}

// Добавляем заголовки версионирования
header("X-API-Version: $apiVersion");
if ($apiVersion < max($supportedVersions)) {
    header("X-API-Deprecated: true");
    header("X-API-Latest-Version: " . max($supportedVersions));
}

// Маршрутизация
try {
    // API для статей
    if (preg_match('#^/?articles(/.*)?$#', $path, $matches)) {
        $subPath = $matches[1] ?? '';
        include 'articles.php';
    }
    // API для комментариев  
    elseif (preg_match('#^/?comments(/.*)?$#', $path, $matches)) {
        $subPath = $matches[1] ?? '';
        include 'comments.php';
    }
    // API для категорий
    elseif (preg_match('#^/?categories(/.*)?$#', $path, $matches)) {
        $subPath = $matches[1] ?? '';
        include 'categories.php';
    }
    // Главная страница API
    elseif ($path === '' || $path === '/' || $path === '/index.php') {
        sendJsonResponse([
            'name' => 'IT Blog API',
            'version' => $apiVersion,
            'description' => 'RESTful API для блога о веб-разработке',
            'endpoints' => [
                'articles' => '/api/articles',
                'comments' => '/api/comments', 
                'categories' => '/api/categories'
            ],
            'documentation' => '/api/docs',
            'supported_versions' => $supportedVersions,
            'features' => [
                'CRUD операции для статей',
                'Модерация комментариев',
                'Поиск и фильтрация',
                'Пагинация',
                'Версионирование API'
            ]
        ], 200, 'Добро пожаловать в IT Blog API v' . $apiVersion);
    }
    // Документация API
    elseif ($path === '/docs' || $path === '/docs.php') {
        include 'docs.php';
    }
    // 404 для неизвестных маршрутов
    else {
        sendJsonResponse(null, 404, 'Эндпоинт не найден: ' . $path);
    }
    
} catch (Exception $e) {
    // Логируем ошибку
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Отправляем общую ошибку клиенту (не раскрываем детали)
    sendJsonResponse(null, 500, 'Внутренняя ошибка сервера');
}
?>