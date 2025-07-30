<?php
namespace Blog\Services;

class SecurityService {
    
    /**
     * Инициализация безопасных настроек сессии
     */
    public static function initSecureSession() {
        // Настройки безопасности сессии
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Устанавливаем имя сессии
        session_name('PHPSESSID_SECURE');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Регенерация ID сессии для безопасности
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
        
        // Обновляем ID сессии каждые 30 минут
        if (isset($_SESSION['last_regeneration']) && 
            time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } else if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Генерация CSRF токена
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Проверка CSRF токена
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Простая защита от брутфорса
     */
    public static function checkRateLimit($action, $identifier, $maxAttempts = 5, $timeWindow = 900) {
        $key = "rate_limit_{$action}_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Сброс счетчика если прошло много времени
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
            return true;
        }
        
        return $data['attempts'] < $maxAttempts;
    }
    
    /**
     * Увеличение счетчика попыток
     */
    public static function incrementRateLimit($action, $identifier) {
        $key = "rate_limit_{$action}_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $_SESSION[$key]['attempts']++;
    }
    
    /**
     * Проверка на спам в комментариях
     */
    public static function checkSpam($content, $authorEmail = '') {
        $spamWords = [
            'viagra', 'casino', 'porn', 'xxx', 'купить', 'дешево',
            'скидка', 'акция', 'кредит', 'займ', 'money', 'bitcoin'
        ];
        
        $content = mb_strtolower($content, 'UTF-8');
        
        // Проверка на спам-слова
        foreach ($spamWords as $word) {
            if (stripos($content, $word) !== false) {
                return true;
            }
        }
        
        // Проверка на слишком много ссылок
        if (substr_count($content, 'http') > 2) {
            return true;
        }
        
        // Проверка на подозрительные домены в email
        $suspiciousDomains = ['tempmail.', 'guerrillamail.', '10minutemail.'];
        foreach ($suspiciousDomains as $domain) {
            if (stripos($authorEmail, $domain) !== false) {
                return true;
            }
        }
        
        // Проверка на повторяющиеся символы
        if (preg_match('/(.)\1{10,}/', $content)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверка на дублирующиеся комментарии
     */
    public static function checkDuplicateComment($pdo, $articleId, $content, $authorEmail) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM comments 
            WHERE article_id = ? AND author_email = ? AND content = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$articleId, $authorEmail, $content]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Очистка старых данных rate limiting
     */
    public static function cleanupRateLimit() {
        if (!isset($_SESSION)) return;
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'rate_limit_') === 0 && is_array($value)) {
                if (isset($value['first_attempt']) && 
                    time() - $value['first_attempt'] > 3600) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }
    
    /**
     * Генерация honeypot поля
     */
    public static function generateHoneypot() {
        $fieldName = 'website_url_' . substr(md5(session_id()), 0, 8);
        return [
            'name' => $fieldName,
            'html' => '<input type="text" name="' . $fieldName . '" style="display:none !important;" tabindex="-1" autocomplete="off">'
        ];
    }
    
    /**
     * Проверка honeypot поля
     */
    public static function checkHoneypot($fieldName) {
        return empty($_POST[$fieldName]);
    }
}
?>