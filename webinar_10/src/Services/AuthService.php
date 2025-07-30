<?php
namespace Blog\Services;

use PDO;

class AuthService {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Аутентификация администратора с защитой от брутфорса
     */
    public function authenticate($username, $password) {
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifier = $username . '_' . $userIP;
        
        // Проверяем rate limiting (5 попыток за 15 минут)
        if (!SecurityService::checkRateLimit('login', $identifier, 5, 900)) {
            error_log("Login rate limit exceeded for user: $username from IP: $userIP");
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Обновляем время последнего входа
                $updateStmt = $this->pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                // Очищаем счетчик попыток при успешном входе
                $this->clearRateLimit('login', $identifier);
                
                return $admin;
            } else {
                // Увеличиваем счетчик неудачных попыток
                SecurityService::incrementRateLimit('login', $identifier);
                
                // Логируем попытку входа
                error_log("Failed login attempt for username: $username from IP: $userIP");
                
                return false;
            }
        } catch (\PDOException $e) {
            error_log("Auth error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка авторизации администратора
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']) && 
               isset($_SESSION['admin_username']) &&
               isset($_SESSION['session_token']) &&
               $this->validateSessionToken();
    }
    
    /**
     * Валидация токена сессии
     */
    private function validateSessionToken() {
        if (!isset($_SESSION['session_token']) || !isset($_SESSION['session_created'])) {
            return false;
        }
        
        // Проверяем время создания токена (максимум 8 часов)
        if (time() - $_SESSION['session_created'] > 28800) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Получение данных текущего администратора
     */
    public function getCurrentAdmin() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'email' => $_SESSION['admin_email'] ?? ''
        ];
    }
    
    /**
     * Вход в систему с генерацией токена сессии
     */
    public function login($admin) {
        // Регенерируем ID сессии для безопасности
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['last_activity'] = time();
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
        $_SESSION['session_created'] = time();
        $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Генерируем CSRF токен для админки
        SecurityService::generateCSRFToken();
    }
    
    /**
     * Выход из системы
     */
    public function logout() {
        // Логируем выход
        if (isset($_SESSION['admin_username'])) {
            error_log("Admin logout: " . $_SESSION['admin_username']);
        }
        
        // Очищаем сессию
        $_SESSION = array();
        
        // Удаляем cookie сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Требовать авторизацию (перенаправление если не авторизован)
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            // Сохраняем URL для редиректа после входа
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header('Location: login.php?message=access_denied');
            exit;
        }
    }
    
    /**
     * Проверка времени сессии (автоматический выход)
     */
    public function checkSessionTimeout($timeoutMinutes = 60) {
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > ($timeoutMinutes * 60)) {
                $this->logout();
                header('Location: login.php?message=timeout');
                exit;
            }
        }
        
        // Проверяем смену IP адреса (дополнительная безопасность)
        if (isset($_SESSION['login_ip']) && 
            $_SESSION['login_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            error_log("Session IP changed for admin: " . ($_SESSION['admin_username'] ?? 'unknown'));
            $this->logout();
            header('Location: login.php?message=security_error');
            exit;
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Создание нового администратора
     */
    public function createAdmin($username, $password, $email) {
        try {
            // Проверяем, не существует ли уже такой пользователь
            $checkStmt = $this->pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            $checkStmt->execute([$username, $email]);
            
            if ($checkStmt->fetch()) {
                return false; // Пользователь уже существует
            }
            
            // Создаем нового администратора
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
            
            return $stmt->execute([$username, $passwordHash, $email]);
        } catch (\PDOException $e) {
            error_log("Error creating admin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка может ли пользователь попытаться войти
     */
    public function canAttemptLogin($username) {
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifier = $username . '_' . $userIP;
        
        return SecurityService::checkRateLimit('login', $identifier, 5, 900);
    }
    
    /**
     * Получение количества оставшихся попыток входа
     */
    public function getRemainingLoginAttempts($username) {
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifier = $username . '_' . $userIP;
        $key = "rate_limit_login_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            return 5;
        }
        
        $data = $_SESSION[$key];
        
        // Сброс если прошло много времени
        if (time() - $data['first_attempt'] > 900) {
            return 5;
        }
        
        return max(0, 5 - $data['attempts']);
    }
    
    /**
     * Очистка rate limiting для пользователя
     */
    private function clearRateLimit($action, $identifier) {
        $key = "rate_limit_{$action}_{$identifier}";
        unset($_SESSION[$key]);
    }
    
    /**
     * Проверка силы пароля
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Пароль должен содержать минимум 8 символов';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать строчные буквы';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать заглавные буквы';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать цифры';
        }
        
        return $errors;
    }
}
?>