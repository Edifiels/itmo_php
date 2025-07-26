<?php
// admin.php - Обновленная админ-панель с авторизацией
require_once 'auth.php';
require_once '../functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Получаем данные администратора
$currentAdmin = getCurrentAdmin();

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>❌ Ошибка подключения к базе данных</h1>";
    echo "<p>Проверьте настройки в config/database.php или запустите <a href='database/migration.php'>миграцию данных</a></p>";
    echo "<p><a href='../index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}

// Обработка действий
$message = '';
$error = '';

// Создание новой статьи
if (isset($_POST['create'])) {
    
        $articleData = [
            'title' => sanitizeString($_POST['title']),
            'content' => sanitizeHTML($_POST['content']),
            'excerpt' => sanitizeString($_POST['excerpt']),
            'author_id' => (int)$_POST['author_id'],
            'category_id' => (int)$_POST['category_id'],
            'reading_time' => (int)$_POST['reading_time'],
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags']))),
            'date' => $_POST['date'] ?? date('Y-m-d')
        ];
        
        $errors = validateArticleData($articleData);
        if (empty($errors)) {
            $newId = createArticle($articleData);
            if ($newId) {
                $message = "Статья успешно создана с ID: $newId";
            } else {
                $error = 'Ошибка при создании статьи';
            }
        } else {
            $error = implode(', ', $errors);
        }
    
}

// Обновление статьи
if (isset($_POST['update'])) {

        $id = (int)$_POST['article_id'];
        $articleData = [
            'title' => sanitizeString($_POST['title']),
            'content' => sanitizeHTML($_POST['content']),
            'excerpt' => sanitizeString($_POST['excerpt']),
            'author_id' => (int)$_POST['author_id'],
            'category_id' => (int)$_POST['category_id'],
            'reading_time' => (int)$_POST['reading_time'],
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags']))),
            'date' => $_POST['date'] ?? date('Y-m-d')
        ];
        
        $errors = validateArticleData($articleData);
        if (empty($errors)) {
            if (updateArticle($id, $articleData)) {
                $message = "Статья ID $id успешно обновлена";
            } else {
                $error = 'Ошибка при обновлении статьи';
            }
        } else {
            $error = implode(', ', $errors);
        }
    
}

// Удаление статьи
if (isset($_POST['delete'])) {
  
        $id = (int)$_POST['article_id'];
        if (deleteArticle($id)) {
            $message = "Статья ID $id успешно удалена";
        } else {
            $error = 'Ошибка при удалении статьи';
        }
    
}

// Получение данных
$allArticles = getAllArticles();
$authors = getAuthors();
$categories = getCategories();
$editingArticle = null;

// Если редактируем статью
if (isset($_GET['edit'])) {
    $editingArticle = getArticle((int)$_GET['edit']);
    if (!$editingArticle) {
        $error = 'Статья для редактирования не найдена';
    }
}

// Получение статистики для dashboard
$stats = getBlogStats();
$pendingComments = getAllComments('pending');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление статьями | IT Blog</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
        }
        
        .admin-user a {
            color: #fed7d7;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .admin-user a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .admin-nav {
            background: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 2rem;
        }
        
        .admin-nav a {
            color: #4a5568;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: #667eea;
            color: white;
        }
        
        .admin-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .dashboard-card h3 {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-card.alert {
            border-left: 4px solid #f56565;
        }
        
        .dashboard-card.alert h3 {
            color: #f56565;
        }
        
        .admin-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .article-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .article-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .article-item:last-child {
            border-bottom: none;
        }
        
        .article-info h4 {
            margin-bottom: 0.5rem;
            color: #2d3748;
        }
        
        .article-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .message, .error {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .message {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
        }
        
        .db-status {
            background: #e6fffa;
            color: #234e52;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Заголовок админки -->
    <div class="admin-header">
        <div class="container">
            <div class="admin-title">
                <h1>📝 Админ-панель IT Blog</h1>
            </div>
            <div class="admin-user">
                <span>Привет, <strong><?php echo htmlspecialchars($currentAdmin['username']) ?>!</strong></span>
                <a href="?logout=1">Выйти</a>
            </div>
        </div>
    </div>
    
    <!-- Навигация админки -->
    <div class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php" class="active">📄 Статьи</a></li>
                <li><a href="comments_admin.php">💬 Комментарии 
                    <?php if (count($pendingComments) > 0): ?>
                    <span style="background: #f56565; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem;"><?php echo count($pendingComments) ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="../index.php" target="_blank">👁️ Просмотр сайта</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <!-- Dashboard статистика -->
        <div class="admin-dashboard">
            <div class="dashboard-card">
                <h3><?php echo $stats['articles'] ?></h3>
                <p>Статей</p>
            </div>
            <div class="dashboard-card">
                <h3><?php echo formatViews($stats['views']) ?></h3>
                <p>Просмотров</p>
            </div>
            <div class="dashboard-card">
                <h3><?php echo $stats['comments'] ?></h3>
                <p>Комментариев</p>
            </div>
            <div class="dashboard-card <?php echo count($pendingComments) > 0 ? 'alert' : '' ?>">
                <h3><?php echo count($pendingComments) ?></h3>
                <p>На модерации</p>
                <?php if (count($pendingComments) > 0): ?>
                <a href="comments_admin.php" style="font-size: 0.8rem; color: #f56565;">Модерировать →</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Статус БД -->
        <div class="db-status">
            🗄️ Работаем с MySQL базой данных | 
            <a href="config/database.php" style="color: #285e61;">Тест подключения</a>
        </div>
        
        <main>
            <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Форма создания/редактирования статьи -->
            <section class="admin-form">
                <h2><?php echo $editingArticle ? 'Редактировать статью' : 'Создать новую статью' ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken ?>">
                    <?php if ($editingArticle): ?>
                    <input type="hidden" name="article_id" value="<?php echo $editingArticle['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" name="title" id="title" required maxlength="255"
                               value="<?php echo htmlspecialchars($editingArticle['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Краткое описание *</label>
                        <textarea name="excerpt" id="excerpt" required maxlength="500"><?php echo htmlspecialchars($editingArticle['excerpt'] ?? '') ?></textarea>
                        <small style="color: #718096;">Краткое описание для списка статей</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Содержимое статьи *</label>
                        <textarea name="content" id="content" required style="min-height: 200px;"><?php echo htmlspecialchars($editingArticle['content'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="author_id">Автор *</label>
                        <select name="author_id" id="author_id" required>
                            <option value="">Выберите автора</option>
                            <?php foreach ($authors as $author): ?>
                            <option value="<?php echo $author['id'] ?>" <?php echo ($editingArticle['author_id'] ?? '') == $author['id'] ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($author['name']) ?> (<?php echo htmlspecialchars($author['email']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Категория *</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id'] ?>" <?php echo ($editingArticle['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Теги (через запятую)</label>
                        <input type="text" name="tags" id="tags" 
                               placeholder="PHP, MySQL, Backend"
                               value="<?php echo isset($editingArticle['tags']) ? htmlspecialchars(implode(', ', $editingArticle['tags'])) : '' ?>">
                        <small style="color: #718096;">Пример: PHP, MySQL, Backend</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reading_time">Время чтения (минут)</label>
                        <input type="number" name="reading_time" id="reading_time" min="1" max="120"
                               value="<?php echo $editingArticle['reading_time'] ?? 5 ?>">
                        <small style="color: #718096;">Автоматически рассчитывается при изменении содержимого</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Дата публикации</label>
                        <input type="date" name="date" id="date" 
                               value="<?php echo $editingArticle ? ($editingArticle['published_at'] ?? date('Y-m-d')) : date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="<?php echo $editingArticle ? 'update' : 'create' ?>" class="btn btn-primary">
                            <?php echo $editingArticle ? '💾 Обновить статью' : '✨ Создать статью' ?>
                        </button>
                        
                        <?php if ($editingArticle): ?>
                        <a href="index.php" class="btn btn-secondary">❌ Отменить</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
            
            <!-- Список существующих статей -->
            <section>
                <h2>📚 Все статьи (<?php echo count($allArticles) ?>)</h2>
                
                <?php if (empty($allArticles)): ?>
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <h3>📝 Статей пока нет</h3>
                    <p>Создайте первую статью используя форму выше</p>
                    <p style="margin-top: 1rem;">
                        <a href="database/migration.php" class="btn btn-secondary">🔄 Запустить миграцию данных</a>
                    </p>
                </div>
                <?php else: ?>
                <div class="article-list">
                    <?php foreach ($allArticles as $article): ?>
                    <div class="article-item">
                        <div class="article-info">
                            <h4><?php echo htmlspecialchars($article['title']) ?></h4>
                            <p>
                                👤 <?php echo htmlspecialchars($article['author']['name']) ?> | 
                                📁 <?php echo htmlspecialchars($article['category']) ?> | 
                                📅 <?php echo formatDate($article['date']) ?> | 
                                👁️ <?php echo formatViews($article['views']) ?> |
                                🏷️ <?php echo count($article['tags']) ?> тегов |
                                💬 <?php echo getCommentsCount($article['id']) ?> комментариев
                            </p>
                        </div>
                        
                        <div class="article-actions">
                            <a href="../article.php?id=<?php echo $article['id'] ?>" class="btn btn-secondary" title="Просмотр" target="_blank">👁️</a>
                            <a href="index.php?edit=<?php echo $article['id'] ?>" class="btn btn-primary" title="Редактировать">✏️</a>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Вы уверены, что хотите удалить статью \'<?php echo htmlspecialchars($article['title']) ?>\'? Это действие нельзя отменить.');">
                                <input type="hidden" name="article_id" value="<?php echo $article['id'] ?>">
                                <button type="submit" name="delete" class="btn btn-danger" title="Удалить">🗑️</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        // Автоматический расчет времени чтения
        document.getElementById('content').addEventListener('input', function() {
            const content = this.value;
            const words = content.trim().split(/\s+/).length;
            const readingTime = Math.max(1, Math.round(words / 200));
            
            const readingTimeInput = document.getElementById('reading_time');
            if (!readingTimeInput.value || readingTimeInput.value == 5) {
                readingTimeInput.value = readingTime;
            }
        });

        // Автоматическое заполнение slug превью
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            // Можно добавить превью slug
        });
        
        // Подтверждение удаления
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('button[name="delete"]')) {
                    if (!confirm('Вы уверены, что хотите удалить эту статью? Это действие нельзя отменить.')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
        
        // Показать уведомления и скрыть их через 5 секунд
        setTimeout(() => {
            const messages = document.querySelectorAll('.message, .error');
            messages.forEach(msg => {
                msg.style.opacity = '0.7';
            });
        }, 5000);
    </script>
</body>
</html>