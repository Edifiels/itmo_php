<?php
namespace Blog\Services;

use PDO;

class AuthService {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Аутентификация администратора
     */
    public function authenticate($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Обновляем время последнего входа
                $updateStmt = $this->pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                return $admin;
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Auth error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка авторизации администратора
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
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
     * Вход в систему
     */
    public function login($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Выход из системы
     */
    public function logout() {
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
            header('Location: login.php');
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
}
?>