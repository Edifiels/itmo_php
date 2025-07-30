<?php
// admin/login.php - Страница входа в админку с защитой от брутфорса
require_once '../autoload.php';

use Blog\Services\AuthService;
use Blog\Services\SecurityService;

// Инициализация безопасной сессии
SecurityService::initSecureSession();

try {
    // Получаем подключение к БД
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Ошибка подключения к базе данных");
    }
    
    // Создаем сервис авторизации
    $authService = new AuthService($pdo);
    
    // Если уже авторизован, перенаправляем в админку
    if ($authService->isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
    
    // Обработка входа
    $loginError = '';
    $loginMessage = '';
    $remainingAttempts = 5;
    
    // Проверяем сообщения
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'logged_out':
                $loginMessage = 'Вы успешно вышли из системы';
                break;
            case 'timeout':
                $loginError = 'Время сессии истекло. Пожалуйста, войдите снова';
                break;
            case 'access_denied':
                $loginError = 'Для доступа к этой странице необходима авторизация';
                break;
            case 'security_error':
                $loginError = 'Обнаружена подозрительная активность. Войдите снова';
                break;
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        // Проверка CSRF токена
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($csrfToken)) {
            $loginError = 'Ошибка безопасности. Обновите страницу и попробуйте снова';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $loginError = 'Заполните все поля';
            } else {
                // Проверяем rate limiting перед попыткой аутентификации
                if (!$authService->canAttemptLogin($username)) {
                    $loginError = 'Слишком много неудачных попыток входа. Попробуйте через 15 минут';
                } else {
                    $admin = $authService->authenticate($username, $password);
                    if ($admin) {
                        // Выполняем вход
                        $authService->login($admin);
                        
                        // Перенаправляем в админку
                        $redirectTo = $_SESSION['redirect_after_login'] ?? 'index.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        header('Location: ' . $redirectTo);
                        exit;
                    } else {
                        $remainingAttempts = $authService->getRemainingLoginAttempts($username);
                        if ($remainingAttempts > 0) {
                            $loginError = "Неверные логин или пароль. Осталось попыток: $remainingAttempts";
                        } else {
                            $loginError = 'Превышено количество попыток входа. Попробуйте через 15 минут';
                        }
                        
                        // Логируем попытку входа
                        error_log("Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                    }
                }
            }
        }
        
        // Получаем актуальное количество попыток после обработки
        if (!empty($username)) {
            $remainingAttempts = $authService->getRemainingLoginAttempts($username);
        }
    }
    
} catch (Exception $e) {
    $loginError = 'Ошибка системы: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | IT Blog</title>
    <link rel="stylesheet" href="../style.css">
    
    <!-- SEO мета-теги для страницы входа -->
    <meta name="description" content="Вход в административную панель IT Blog для управления статьями и комментариями">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            margin: 2rem;
            padding: 2.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .login-header p {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input:invalid {
            border-color: #e53e3e;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Стили для сообщений */
        .message, .error {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .message {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            border-left: 4px solid #e53e3e;
        }
        
        .attempts-warning {
            background: #fefcbf;
            color: #744210;
            border-left: 4px solid #f6ad55;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .login-info {
            background: #e6fffa;
            color: #234e52;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            border-left: 4px solid #4fd1c7;
        }
        
        .login-info h4 {
            margin-bottom: 0.5rem;
            color: #2c7a7b;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }
        
        .back-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        .security-features {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #4a5568;
        }
        
        .security-features ul {
            margin: 0.5rem 0 0 1rem;
        }
        
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #38a169, #f56565);
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <header class="login-header">
            <h1>🔐 Вход в админ-панель</h1>
            <p>Управление контентом IT Blog</p>
        </header>
        
        <?php if ($loginMessage): ?>
        <div class="message" role="alert">✅ <?php echo htmlspecialchars($loginMessage) ?></div>
        <?php endif; ?>
        
        <?php if ($loginError): ?>
        <div class="error" role="alert">❌ <?php echo htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        
        <?php if ($remainingAttempts < 5 && $remainingAttempts > 0): ?>
        <div class="attempts-warning" role="alert">
            ⚠️ Осталось попыток входа: <strong><?php echo $remainingAttempts ?></strong>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($remainingAttempts / 5 * 100) ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm" novalidate>
            <!-- CSRF защита -->
            <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="username">Логин *</label>
                <input type="text" name="username" id="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username"
                       aria-describedby="username-help"
                       <?php echo $remainingAttempts <= 0 ? 'disabled' : '' ?>>
                <small id="username-help" style="color: #718096; font-size: 0.8rem;">
                    Используйте ваш логин администратора
                </small>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль *</label>
                <input type="password" name="password" id="password" required
                       autocomplete="current-password"
                       aria-describedby="password-help"
                       <?php echo $remainingAttempts <= 0 ? 'disabled' : '' ?>>
                <small id="password-help" style="color: #718096; font-size: 0.8rem;">
                    Введите пароль администратора
                </small>
            </div>
            
            <button type="submit" name="login" class="btn-login" id="loginBtn" 
                    <?php echo $remainingAttempts <= 0 ? 'disabled' : '' ?>>
                <span class="btn-text">
                    <?php if ($remainingAttempts <= 0): ?>
                        🚫 Доступ заблокирован
                    <?php else: ?>
                        🚀 Войти в систему
                    <?php endif; ?>
                </span>
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </button>
        </form>
        
        <?php if ($remainingAttempts > 0): ?>
        <div class="login-info">
            <h4>📋 Тестовые учетные данные:</h4>
            <strong>Администратор:</strong><br>
            Логин: <code>admin</code><br>
            Пароль: <code>admin123</code><br><br>
            
            <strong>Модератор:</strong><br>
            Логин: <code>moderator</code><br>
            Пароль: <code>admin123</code>
        </div>
        <?php endif; ?>
        
        <div class="security-features">
            <h4>🛡️ Безопасность (Занятие 10):</h4>
            <ul>
                <li>CSRF защита от подделки запросов</li>
                <li>Защита от брутфорс атак (5 попыток за 15 минут)</li>
                <li>Безопасные сессии с HttpOnly флагами</li>
                <li>Автоматический выход через 60 минут</li>
                <li>Проверка смены IP адреса</li>
            </ul>
        </div>
    </div>
    
    <div class="back-link">
        <a href="../index.php">← Вернуться к блогу</a>
    </div>

    <script>
        // Улучшенная обработка формы входа с защитой
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            // Простая валидация на клиенте
            if (!usernameField.value.trim()) {
                alert('Введите логин');
                usernameField.focus();
                e.preventDefault();
                return false;
            }
            
            if (!passwordField.value) {
                alert('Введите пароль');
                passwordField.focus();
                e.preventDefault();
                return false;
            }
            
            // Проверка на подозрительную активность (простая)
            if (usernameField.value.length > 50 || passwordField.value.length > 100) {
                alert('Подозрительно длинные данные');
                e.preventDefault();
                return false;
            }
            
            // Показываем индикатор загрузки
            btnText.style.display = 'none';
            loading.style.display = 'block';
            btn.disabled = true;
            
            // Если форма невалидна, возвращаем исходное состояние
            setTimeout(() => {
                if (!this.checkValidity()) {
                    btnText.style.display = 'block';
                    loading.style.display = 'none';
                    btn.disabled = false;
                }
            }, 100);
        });
        
        // Автофокус на первое пустое поле
        window.addEventListener('load', function() {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            if (usernameField.disabled) {
                return; // Если заблокировано, не фокусируемся
            }
            
            if (!usernameField.value) {
                usernameField.focus();
            } else {
                passwordField.focus();
            }
        });
        
        // Обработка Enter в полях
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                const loginBtn = document.getElementById('loginBtn');
                
                if (form.checkValidity() && !loginBtn.disabled) {
                    form.submit();
                }
            }
        });
        
        // Убираем ошибки при вводе
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Убираем класс invalid при вводе
                this.setAttribute('aria-invalid', 'false');
                
                // Уменьшаем интенсивность ошибок
                const errorDiv = document.querySelector('.error');
                if (errorDiv) {
                    errorDiv.style.opacity = '0.5';
                }
            });
            
            // Валидация при потере фокуса
            input.addEventListener('blur', function() {
                this.setAttribute('aria-invalid', !this.checkValidity());
            });
        });
        
        // Автоматическое обновление страницы если заблокирован (через 15 минут)
        <?php if ($remainingAttempts <= 0): ?>
        setTimeout(() => {
            location.reload();
        }, 15 * 60 * 1000); // 15 минут
        
        // Показываем обратный отсчет
        let timeLeft = 15 * 60; // 15 минут в секундах
        const updateTimer = () => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.title = `Разблокировка через ${minutes}:${seconds.toString().padStart(2, '0')} | IT Blog`;
            timeLeft--;
            
            if (timeLeft < 0) {
                location.reload();
            }
        };
        
        updateTimer();
        setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        // Логирование подозрительной активности на клиенте
        let suspiciousActivity = 0;
        
        document.addEventListener('keydown', (e) => {
            // Обнаружение автоматических инструментов
            if (e.isTrusted === false) {
                suspiciousActivity++;
            }
            
            // Слишком быстрый ввод
            if (suspiciousActivity > 10) {
                console.warn('Подозрительная активность обнаружена');
            }
        });
        
        // Улучшенная доступность
        document.addEventListener('DOMContentLoaded', function() {
            // Объявляем статус для screen readers
            const remainingAttempts = <?php echo $remainingAttempts ?>;
            if (remainingAttempts < 5) {
                const announcement = document.createElement('div');
                announcement.setAttribute('aria-live', 'assertive');
                announcement.setAttribute('aria-atomic', 'true');
                announcement.className = 'visually-hidden';
                
                if (remainingAttempts <= 0) {
                    announcement.textContent = 'Доступ к форме входа заблокирован на 15 минут';
                } else {
                    announcement.textContent = `Осталось ${remainingAttempts} попыток входа`;
                }
                
                document.body.appendChild(announcement);
            }
        });
    </script>
</body>
</html>