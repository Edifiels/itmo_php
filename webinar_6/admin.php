<?php
require_once 'functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>❌ Ошибка подключения к базе данных</h1>";
    echo "<p>Проверьте настройки в config/database.php или запустите <a href='database/migration.php'>миграцию данных</a></p>";
    echo "<p><a href='index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}

// Обработка действий
$message = '';
$error = '';

// Создание новой статьи
if (isset($_POST['create'])) {
    $articleData = [
        'title' => $_POST['title'],
        'content' => $_POST['content'],
        'excerpt' => $_POST['excerpt'],
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
        'title' => $_POST['title'],
        'content' => $_POST['content'],
        'excerpt' => $_POST['excerpt'],
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление статьями | IT Blog</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #718096;
            color: white;
        }
        .btn-danger {
            background: #e53e3e;
            color: white;
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
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">← Назад на главную</a>
        </nav>
        
        <header>
            <h1>📝 Управление статьями</h1>
        </header>
        
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
                    <?php if ($editingArticle): ?>
                    <input type="hidden" name="article_id" value="<?php echo $editingArticle['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" name="title" id="title" required 
                               value="<?php echo htmlspecialchars($editingArticle['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Краткое описание *</label>
                        <textarea name="excerpt" id="excerpt" required><?php echo htmlspecialchars($editingArticle['excerpt'] ?? '') ?></textarea>
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
                                <?php echo htmlspecialchars($author['name']) ?>
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
                        <input type="number" name="reading_time" id="reading_time" min="1" 
                               value="<?php echo $editingArticle['reading_time'] ?? 5 ?>">
                        <small style="color: #718096;">Оставьте пустым для автоматического расчета</small>
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
                        <a href="admin.php" class="btn btn-secondary">❌ Отменить</a>
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
                                🏷️ <?php echo count($article['tags']) ?> тегов
                            </p>
                        </div>
                        
                        <div class="article-actions">
                            <a href="article.php?id=<?php echo $article['id'] ?>" class="btn btn-secondary" title="Просмотр">👁️</a>
                            <a href="admin.php?edit=<?php echo $article['id'] ?>" class="btn btn-primary" title="Редактировать">✏️</a>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Вы уверены, что хотите удалить статью \'<?php echo htmlspecialchars($article['title']) ?>\'?');">
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

        // Автоматическое заполнение slug (если понадобится в будущем)
        document.getElementById('title').addEventListener('input', function() {
            // Можно добавить превью slug
        });
    </script>
</body>
</html>