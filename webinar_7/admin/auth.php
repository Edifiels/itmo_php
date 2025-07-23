<?php
// auth.php - Система авторизации для админки
session_start();
require_once '../functions.php';

/**
 * Аутентификация администратора
 */
function authenticateAdmin($username, $password) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Обновляем время последнего входа
            $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
            
            return $admin;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Auth error: " . $e->getMessage());
        return false;
    }
}

/**
 * Проверка авторизации администратора
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Получение данных текущего администратора
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'email' => $_SESSION['admin_email'] ?? ''
    ];
}

/**
 * Требовать авторизацию (перенаправление если не авторизован)
 */
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Выход из системы
 */
function logoutAdmin() {
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
    header('Location: login.php?message=logged_out');
    exit;
}



/**
 * Создание нового администратора
 */
function createAdmin($username, $password, $email) {
    try {
        $pdo = getDatabaseConnection();
        
        // Проверяем, не существует ли уже такой пользователь
        $checkStmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        
        if ($checkStmt->fetch()) {
            return false; // Пользователь уже существует
        }
        
        // Создаем нового администратора
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
        
        return $stmt->execute([$username, $passwordHash, $email]);
    } catch (PDOException $e) {
        error_log("Error creating admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Обработка выхода
 */
if (isset($_GET['logout'])) {
    logoutAdmin();
}

/**
 * Обработка входа
 */
$loginError = '';
$loginMessage = '';

// Проверяем сообщения
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'logged_out':
            $loginMessage = 'Вы успешно вышли из системы';
            break;
        case 'access_denied':
            $loginError = 'Для доступа к этой странице необходима авторизация';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $loginError = 'Заполните все поля';
    } else {
        $admin = authenticateAdmin($username, $password);
        if ($admin) {
            // Сохраняем данные в сессию
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Перенаправляем в админку
            $redirectTo = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirectTo);
            exit;
        } else {
            $loginError = 'Неверные логин или пароль';
            // Можно добавить логирование попыток входа
            error_log("Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    }
}

/**
 * Сохранение URL для перенаправления после входа
 */
function saveRedirectUrl($url) {
    $_SESSION['redirect_after_login'] = $url;
}

/**
 * Проверка времени сессии (автоматический выход)
 */
function checkSessionTimeout($timeoutMinutes = 60) {
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        if ($inactiveTime > ($timeoutMinutes * 60)) {
            logoutAdmin();
        }
    }
    $_SESSION['last_activity'] = time();
}

// Проверяем таймаут сессии для авторизованных пользователей
if (isAdminLoggedIn()) {
    checkSessionTimeout();
}
?>