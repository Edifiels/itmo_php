<?php
// login.php - Страница входа в админку
require_once 'auth.php';

// Если уже авторизован, перенаправляем в админку
if (isAdminLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | IT Blog</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 2rem;
            padding: 2rem;
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
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
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
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Вход в админ-панель</h1>
            <p>Управление контентом IT Blog</p>
        </div>
        
        <?php if ($loginMessage): ?>
        <div class="message">✅ <?php echo htmlspecialchars($loginMessage) ?></div>
        <?php endif; ?>
        
        <?php if ($loginError): ?>
        <div class="error">❌ <?php echo htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">            
            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" name="username" id="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" name="password" id="password" required
                       autocomplete="current-password">
            </div>
            
            <button type="submit" name="login" class="btn-login" id="loginBtn">
                <span class="btn-text">🚀 Войти в систему</span>
                <div class="loading">
                    <div class="spinner"></div>
                </div>
            </button>
        </form>
        
        <div class="login-info">
            <h4>📋 Тестовые учетные данные:</h4>
            <strong>Администратор:</strong><br>
            Логин: <code>admin</code><br>
            Пароль: <code>admin123</code><br><br>
            
            <strong>Модератор:</strong><br>
            Логин: <code>moderator</code><br>
            Пароль: <code>admin123</code>
        </div>
        
        <div class="security-features">
            <h4>🛡️ Безопасность:</h4>
            <ul>
                <li>Хэширование паролей</li>
                <li>Автоматический выход через 60 минут</li>
                <li>Логирование попыток входа</li>
            </ul>
        </div>
    </div>
    
    <div class="back-link">
        <a href="../index.php">← Вернуться к блогу</a>
    </div>

    <script>
        // Обработка формы входа
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            // Показываем индикатор загрузки
            btnText.style.display = 'none';
            loading.style.display = 'block';
            btn.disabled = true;
            
            // Если форма невалидна, возвращаем исходное состояние
            setTimeout(() => {
                if (!btn.closest('form').checkValidity()) {
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
                if (form.checkValidity()) {
                    form.submit();
                }
            }
        });
        
        // Убираем ошибки при вводе
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorDiv = document.querySelector('.error');
                if (errorDiv) {
                    errorDiv.style.opacity = '0.5';
                }
            });
        });
    </script>
</body>
</html>