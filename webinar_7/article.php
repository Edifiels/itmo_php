<?php
// article.php - Обновленная страница статьи с комментариями
session_start();
require_once 'functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>❌ Ошибка подключения к базе данных</h1>";
    echo "<p><a href='index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}

// Получаем ID статьи
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем статью
$article = getArticle($articleId);

// Если статья не найдена
if (!$article) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>Статья не найдена</title></head><body>";
    echo "<div style='text-align: center; padding: 3rem;'>";
    echo "<h1>📄 Статья не найдена</h1>";
    echo "<p>Возможно, статья была удалена или вы перешли по неверной ссылке.</p>";
    echo "<a href='index.php' style='display: inline-block; background: #667eea; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 6px; margin-top: 1rem;'>← Вернуться к статьям</a>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Увеличиваем просмотры
$currentViews = incrementViews($articleId);
$article['views'] = $currentViews;

// Получаем похожие статьи
$similarArticles = getSimilarArticles($articleId);

// Обработка добавления комментария
$commentMessage = '';
$commentError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authorName = sanitizeString($_POST['author_name'] ?? '');
    $authorEmail = trim($_POST['author_email'] ?? '');
    $commentContent = sanitizeString($_POST['comment_content'] ?? '');
    
    // Валидация комментария
    $commentData = [
        'author_name' => $authorName,
        'author_email' => $authorEmail,
        'content' => $commentContent
    ];
    
    $errors = validateCommentData($commentData);
    
    if (empty($errors)) {
        $added = addComment($articleId, $authorName, $authorEmail, $commentContent);
        if ($added) {
            $commentMessage = 'Спасибо! Ваш комментарий отправлен на модерацию.';
            // Очищаем поля формы после успешной отправки
            $_POST = [];
        } else {
            $commentError = 'Ошибка при добавлении комментария. Попробуйте еще раз.';
        }
    } else {
        $commentError = implode(', ', $errors);
    }
}

// Получаем комментарии
$comments = getArticleComments($articleId);
$commentsCount = count($comments);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']) ?> | IT Blog</title>
    <link rel="stylesheet" href="style.css">
    <meta name="description" content="<?php echo htmlspecialchars($article['excerpt']) ?>">
    <meta name="author" content="<?php echo htmlspecialchars($article['author']['name']) ?>">
</head>
<body>
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">← Назад к статьям</a>
        </nav>
        
        <!-- Заголовок статьи -->
        <header class="article-header">
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']) ?></h1>
            <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt']) ?></p>
            
            <div class="article-info">
                <div class="info-item">
                    <strong>Автор:</strong> <?php echo htmlspecialchars($article['author']['name']) ?>
                </div>
                <div class="info-item">
                    <strong>Категория:</strong> <?php echo htmlspecialchars($article['category']) ?>
                </div>
                <div class="info-item">
                    <strong>Дата:</strong> <?php echo formatDate($article['date']) ?>
                </div>
                <div class="info-item">
                    <strong>Время чтения:</strong> <?php echo $article['reading_time'] ?> мин
                </div>
            </div>
            
            <div class="article-tags">
                <?php echo renderTags($article['tags']) ?>
            </div>
        </header>
        
        <!-- Содержимое статьи -->
        <main class="article-content">
            <div class="article-text">
                <?php echo nl2br(htmlspecialchars($article['content'])) ?>
            </div>
        </main>
        
        <!-- Автор -->
        <section class="author-info">
            <h3>Об авторе</h3>
            <div class="author-card">
                <div class="author-avatar">
                    <?php echo strtoupper(substr($article['author']['name'], 0, 1)) ?>
                </div>
                <div class="author-details">
                    <h4><?php echo htmlspecialchars($article['author']['name']) ?></h4>
                    <p><?php echo htmlspecialchars($article['author']['email']) ?></p>
                    <?php if (!empty($article['author']['bio'])): ?>
                    <p class="author-bio"><?php echo htmlspecialchars($article['author']['bio']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Статистика просмотров -->
        <section class="article-stats-section">
            <p>👁️ Эту статью просмотрели <strong><?php echo formatViews($article['views']) ?></strong> раз</p>
        </section>
        
        <!-- Комментарии -->
        <section class="comments-section">
            <h3>💬 Комментарии (<?php echo $commentsCount ?>)</h3>
            
            <?php if ($commentMessage): ?>
            <div class="message">✅ <?php echo htmlspecialchars($commentMessage) ?></div>
            <?php endif; ?>
            
            <?php if ($commentError): ?>
            <div class="error">❌ <?php echo htmlspecialchars($commentError) ?></div>
            <?php endif; ?>
            
            <!-- Форма добавления комментария -->
            <div class="comment-form">
                <h4>Добавить комментарий</h4>
                <form method="POST" id="commentForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author_name">Ваше имя *</label>
                            <input type="text" name="author_name" id="author_name" required maxlength="100"
                                   value="<?php echo htmlspecialchars($_POST['author_name'] ?? '') ?>"
                                   placeholder="Введите ваше имя">
                        </div>
                        
                        <div class="form-group">
                            <label for="author_email">Email *</label>
                            <input type="email" name="author_email" id="author_email" required maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['author_email'] ?? '') ?>"
                                   placeholder="ваш@email.com">
                            <small>Email не будет опубликован</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment_content">Комментарий *</label>
                        <textarea name="comment_content" id="comment_content" required 
                                  rows="4" minlength="10" maxlength="1000"
                                  placeholder="Напишите ваш комментарий..."><?php echo htmlspecialchars($_POST['comment_content'] ?? '') ?></textarea>
                        <small>Минимум 10 символов, максимум 1000</small>
                    </div>
                    
                    <button type="submit" name="add_comment" class="btn btn-primary">
                        💬 Отправить комментарий
                    </button>
                </form>
            </div>
            
            <!-- Список комментариев -->
            <?php if (!empty($comments)): ?>
            <div class="comments-list">
                <h4>Комментарии читателей:</h4>
                <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <div class="comment-header">
                        <div class="comment-author">
                            <div class="comment-avatar">
                                <?php echo strtoupper(substr($comment['author_name'], 0, 1)) ?>
                            </div>
                            <div class="comment-author-info">
                                <span class="comment-author-name">
                                    <?php echo htmlspecialchars($comment['author_name']) ?>
                                </span>
                                <span class="comment-date">
                                    <?php echo formatDateTime($comment['created_at']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['content'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-comments">
                <p>Пока нет комментариев. Будьте первым!</p>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Похожие статьи -->
        <?php if (!empty($similarArticles)): ?>
        <section class="similar-articles">
            <h3>📖 Похожие статьи</h3>
            <div class="similar-grid">
                <?php foreach ($similarArticles as $similar): ?>
                <article class="similar-card">
                    <h4>
                        <a href="article.php?id=<?php echo $similar['id'] ?>">
                            <?php echo htmlspecialchars($similar['title']) ?>
                        </a>
                    </h4>
                    <p class="similar-meta">
                        👤 <?php echo htmlspecialchars($similar['author_name']) ?> •
                        👁️ <?php echo formatViews($similar['views']) ?> • 
                        ⏱️ <?php echo $similar['reading_time'] ?> мин
                    </p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Дополнительные действия -->
        <section class="action-section">
            <h3>🎯 Понравилась статья?</h3>
            <p>Читайте больше статей в нашем блоге!</p>
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">📚 Все статьи</a>
                <a href="search.php" class="btn btn-secondary">🔍 Поиск</a>
                <a href="admin/index.php" class="btn btn-success">✏️ Админ-панель</a>
            </div>
        </section>
    </div>

    <script>
        // Обработка формы комментариев
        document.getElementById('commentForm').addEventListener('submit', function(e) {
            const nameField = document.getElementById('author_name');
            const emailField = document.getElementById('author_email');
            const contentField = document.getElementById('comment_content');
            
            // Простая валидация на клиенте
            if (nameField.value.trim().length < 2) {
                alert('Имя должно содержать минимум 2 символа');
                nameField.focus();
                e.preventDefault();
                return false;
            }
            
            if (!emailField.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                alert('Введите корректный email адрес');
                emailField.focus();
                e.preventDefault();
                return false;
            }
            
            if (contentField.value.trim().length < 10) {
                alert('Комментарий должен содержать минимум 10 символов');
                contentField.focus();
                e.preventDefault();
                return false;
            }
            
            // Показываем индикатор отправки
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Отправляем...';
            submitBtn.disabled = true;
            
            // На случай если что-то пойдет не так, возвращаем кнопку через 10 секунд
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        // Автоматическое скрытие сообщений через 5 секунд
        setTimeout(() => {
            const messages = document.querySelectorAll('.message, .error');
            messages.forEach(msg => {
                msg.style.opacity = '0.8';
                setTimeout(() => {
                    msg.style.display = 'none';
                }, 3000);
            });
        }, 5000);
        
        // Подсчет символов в textarea
        const textarea = document.getElementById('comment_content');
        const small = textarea.nextElementSibling;
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            const remaining = 1000 - length;
            
            if (remaining < 100) {
                small.style.color = remaining < 0 ? '#e53e3e' : '#f56565';
                small.textContent = `Осталось символов: ${remaining}`;
            } else {
                small.style.color = '#718096';
                small.textContent = 'Минимум 10 символов, максимум 1000';
            }
        });
        
        // Плавная прокрутка к комментариям по хештегу
        if (window.location.hash === '#comments') {
            document.querySelector('.comments-section').scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>