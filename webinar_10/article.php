<?php
// article.php - Страница статьи с SEO оптимизацией и защитой от спама
require_once 'autoload.php';

use Blog\Controllers\ArticleController;
use Blog\Controllers\CommentController;
use Blog\Repositories\ArticleRepository;
use Blog\Repositories\CommentRepository;
use Blog\Repositories\UserRepository;
use Blog\Services\HelperService;
use Blog\Services\SecurityService;
use Blog\Services\SEOService;

// Инициализация безопасной сессии
SecurityService::initSecureSession();

try {
    // Получаем подключение к БД
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Ошибка подключения к базе данных");
    }
    
    // Получаем ID статьи
    $articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($articleId <= 0) {
        header('Location: index.php');
        exit;
    }
    
    // Создаем репозитории
    $articleRepository = new ArticleRepository($pdo);
    $commentRepository = new CommentRepository($pdo);
    $userRepository = new UserRepository($pdo);
    
    // Создаем контроллеры
    $articleController = new ArticleController($articleRepository, $commentRepository, $userRepository);
    $commentController = new CommentController($commentRepository, $pdo);
    
    // Получаем статью и связанные данные
    $data = $articleController->show($articleId);
    
    if (!$data) {
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
    
    $article = $data['article'];
    $comments = $data['comments'];
    $similarArticles = $data['similarArticles'];
    
    // Генерация honeypot поля для защиты от спам-ботов
    $honeypot = SecurityService::generateHoneypot();
    
    // Обработка добавления комментария
    $commentMessage = '';
    $commentError = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
        $authorName = HelperService::sanitizeString($_POST['author_name'] ?? '');
        $authorEmail = trim($_POST['author_email'] ?? '');
        $commentContent = HelperService::sanitizeString($_POST['comment_content'] ?? '');
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        $result = $commentController->add(
            $articleId, 
            $authorName, 
            $authorEmail, 
            $commentContent, 
            $csrfToken,
            $honeypot
        );
        
        if ($result['success']) {
            $commentMessage = $result['message'];
            // Очищаем поля формы после успешной отправки
            $_POST = [];
        } else {
            $commentError = $result['message'];
        }
    }
    
    // Получаем обновленные комментарии
    $comments = $commentRepository->findByArticle($articleId);
    $commentsCount = count($comments);
    
    // Подготавливаем SEO данные
    $seoTitle = $article->getTitle() . ' | IT Blog';
    $seoDescription = $article->getExcerpt();
    $seoKeywords = implode(', ', $article->getTags());
    $currentUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Хлебные крошки
    $breadcrumbs = [
        ['name' => 'Главная', 'url' => '/'],
        ['name' => $article->getCategory(), 'url' => '/search.php?q=' . urlencode($article->getCategory())],
        ['name' => $article->getTitle(), 'url' => '']
    ];
    
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><title>Ошибка</title></head><body>";
    echo "<h1>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</h1>";
    echo "<p><a href='index.php'>← Назад к главной</a></p>";
    echo "</body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seoTitle) ?></title>
    
    <!-- SEO мета-теги -->
    <?php echo SEOService::generateMetaTags($seoTitle, $seoDescription, $seoKeywords, '', $currentUrl) ?>
    
    <!-- Стили -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Структурированные данные для статьи -->
    <?php echo SEOService::generateArticleSchema($article, $article->getAuthor(), $comments) ?>
    
    <!-- Структурированные данные для хлебных крошек -->
    <?php echo SEOService::generateBreadcrumbSchema($breadcrumbs) ?>
    
    <!-- Дополнительные SEO теги -->
    <link rel="prev" href="/" title="Главная страница">
    <meta name="article:author" content="<?php echo htmlspecialchars($article->getAuthor()['name']) ?>">
    <meta name="article:published_time" content="<?php echo date('c', strtotime($article->getCreatedAt())) ?>">
    <meta name="article:modified_time" content="<?php echo date('c', strtotime($article->getUpdatedAt() ?: $article->getCreatedAt())) ?>">
    <meta name="article:section" content="<?php echo htmlspecialchars($article->getCategory()) ?>">
    <?php foreach ($article->getTags() as $tag): ?>
    <meta name="article:tag" content="<?php echo htmlspecialchars($tag) ?>">
    <?php endforeach; ?>
</head>
<body>
    <div class="container">
        <!-- Хлебные крошки -->
        <nav class="breadcrumbs-wrapper" aria-label="breadcrumb">
            <?php echo SEOService::generateBreadcrumbs($breadcrumbs) ?>
        </nav>
        
        <!-- Заголовок статьи -->
        <header class="article-header" itemscope itemtype="https://schema.org/Article">
            <h1 class="article-title" itemprop="headline"><?php echo htmlspecialchars($article->getTitle()) ?></h1>
            <p class="article-excerpt" itemprop="description"><?php echo htmlspecialchars($article->getExcerpt()) ?></p>
            
            <div class="article-info">
                <div class="info-item" itemprop="author" itemscope itemtype="https://schema.org/Person">
                    <strong>Автор:</strong> <span itemprop="name"><?php echo htmlspecialchars($article->getAuthor()['name']) ?></span>
                </div>
                <div class="info-item" itemprop="articleSection">
                    <strong>Категория:</strong> <?php echo htmlspecialchars($article->getCategory()) ?>
                </div>
                <div class="info-item">
                    <strong>Дата:</strong> 
                    <time datetime="<?php echo date('c', strtotime($article->getDate())) ?>" itemprop="datePublished">
                        <?php echo HelperService::formatDate($article->getDate()) ?>
                    </time>
                </div>
                <div class="info-item">
                    <strong>Время чтения:</strong> 
                    <span itemprop="timeRequired" content="PT<?php echo $article->getReadingTime() ?>M">
                        <?php echo $article->getReadingTime() ?> мин
                    </span>
                </div>
            </div>
            
            <div class="article-tags" itemprop="keywords">
                <?php echo HelperService::renderTags($article->getTags()) ?>
            </div>
        </header>
        
        <!-- Содержимое статьи -->
        <main class="article-content">
            <div class="article-text" itemprop="articleBody">
                <?php echo nl2br(htmlspecialchars($article->getContent())) ?>
            </div>
        </main>
        
        <!-- Автор -->
        <section class="author-info" itemscope itemtype="https://schema.org/Person">
            <h2>Об авторе</h2>
            <div class="author-card">
                <div class="author-avatar">
                    <?php echo strtoupper(substr($article->getAuthor()['name'], 0, 1)) ?>
                </div>
                <div class="author-details">
                    <h3 itemprop="name"><?php echo htmlspecialchars($article->getAuthor()['name']) ?></h3>
                    <p itemprop="email"><?php echo htmlspecialchars($article->getAuthor()['email']) ?></p>
                    <?php if (!empty($article->getAuthor()['bio'])): ?>
                    <p class="author-bio" itemprop="description"><?php echo htmlspecialchars($article->getAuthor()['bio']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Статистика просмотров -->
        <section class="article-stats-section">
            <p>👁️ Эту статью просмотрели <strong><?php echo HelperService::formatViews($article->getViews()) ?></strong> раз</p>
        </section>
        
        <!-- Комментарии -->
        <section class="comments-section" id="comments">
            <h2>💬 Комментарии (<?php echo $commentsCount ?>)</h2>
            
            <?php if ($commentMessage): ?>
            <div class="message" role="alert">✅ <?php echo htmlspecialchars($commentMessage) ?></div>
            <?php endif; ?>
            
            <?php if ($commentError): ?>
            <div class="error" role="alert">❌ <?php echo htmlspecialchars($commentError) ?></div>
            <?php endif; ?>
            
            <!-- Форма добавления комментария -->
            <div class="comment-form">
                <h3>Добавить комментарий</h3>
                <form method="POST" id="commentForm" novalidate>
                    <!-- CSRF защита -->
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityService::generateCSRFToken() ?>">
                    
                    <!-- Honeypot поле для защиты от спам-ботов -->
                    <?php echo $honeypot['html'] ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author_name">Ваше имя *</label>
                            <input type="text" name="author_name" id="author_name" required maxlength="100"
                                   value="<?php echo htmlspecialchars($_POST['author_name'] ?? '') ?>"
                                   placeholder="Введите ваше имя"
                                   aria-describedby="name-help">
                            <small id="name-help">Только ваше настоящее имя</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="author_email">Email *</label>
                            <input type="email" name="author_email" id="author_email" required maxlength="150"
                                   value="<?php echo htmlspecialchars($_POST['author_email'] ?? '') ?>"
                                   placeholder="ваш@email.com"
                                   aria-describedby="email-help">
                            <small id="email-help">Email не будет опубликован</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment_content">Комментарий *</label>
                        <textarea name="comment_content" id="comment_content" required 
                                  rows="4" minlength="10" maxlength="1000"
                                  placeholder="Напишите ваш комментарий..."
                                  aria-describedby="content-help"><?php echo htmlspecialchars($_POST['comment_content'] ?? '') ?></textarea>
                        <small id="content-help">Минимум 10 символов, максимум 1000</small>
                    </div>
                    
                    <div class="form-group comment-guidelines">
                        <h4>📋 Правила комментирования:</h4>
                        <ul>
                            <li>Будьте вежливы и конструктивны</li>
                            <li>Не используйте оскорбления и спам</li>
                            <li>Комментарии проходят модерацию</li>
                            <li>HTML теги не поддерживаются</li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="add_comment" class="btn btn-primary" id="submit-comment">
                        💬 Отправить комментарий
                    </button>
                </form>
            </div>
            
            <!-- Список комментариев -->
            <?php if (!empty($comments)): ?>
            <div class="comments-list">
                <h3>Комментарии читателей:</h3>
                <?php foreach ($comments as $comment): ?>
                <article class="comment-item" itemscope itemtype="https://schema.org/Comment">
                    <header class="comment-header">
                        <div class="comment-author">
                            <div class="comment-avatar">
                                <?php echo strtoupper(substr($comment->getAuthorName(), 0, 1)) ?>
                            </div>
                            <div class="comment-author-info">
                                <span class="comment-author-name" itemprop="author" itemscope itemtype="https://schema.org/Person">
                                    <span itemprop="name"><?php echo htmlspecialchars($comment->getAuthorName()) ?></span>
                                </span>
                                <time class="comment-date" datetime="<?php echo date('c', strtotime($comment->getCreatedAt())) ?>" itemprop="dateCreated">
                                    <?php echo HelperService::formatDateTime($comment->getCreatedAt()) ?>
                                </time>
                            </div>
                        </div>
                    </header>
                    <div class="comment-content" itemprop="text">
                        <?php echo nl2br(htmlspecialchars($comment->getContent())) ?>
                    </div>
                </article>
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
            <h2>📖 Похожие статьи</h2>
            <div class="similar-grid">
                <?php foreach ($similarArticles as $similar): ?>
                <article class="similar-card" itemscope itemtype="https://schema.org/Article">
                    <h3 itemprop="headline">
                        <a href="article.php?id=<?php echo $similar['id'] ?>" itemprop="url">
                            <?php echo htmlspecialchars($similar['title']) ?>
                        </a>
                    </h3>
                    <p class="similar-meta">
                        <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                            👤 <span itemprop="name"><?php echo htmlspecialchars($similar['author_name']) ?></span>
                        </span> •
                        👁️ <?php echo HelperService::formatViews($similar['views']) ?> • 
                        ⏱️ <span itemprop="timeRequired" content="PT<?php echo $similar['reading_time'] ?>M"><?php echo $similar['reading_time'] ?> мин</span>
                    </p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Дополнительные действия -->
        <section class="action-section">
            <h2>🎯 Понравилась статья?</h2>
            <p>Читайте больше статей в нашем блоге!</p>
            <nav class="action-buttons" aria-label="Навигация по сайту">
                <a href="index.php" class="btn btn-primary">📚 Все статьи</a>
                <a href="search.php" class="btn btn-secondary">🔍 Поиск</a>
                <a href="admin/index.php" class="btn btn-success">✏️ Админ-панель</a>
            </nav>
        </section>
    </div>

    <script>
        // Улучшенная обработка формы комментариев с защитой от спама
        document.getElementById('commentForm').addEventListener('submit', function(e) {
            const nameField = document.getElementById('author_name');
            const emailField = document.getElementById('author_email');
            const contentField = document.getElementById('comment_content');
            const submitBtn = document.getElementById('submit-comment');
            
            // Простая валидация на клиенте
            if (nameField.value.trim().length < 2) {
                alert('Имя должно содержать минимум 2 символа');
                nameField.focus();
                e.preventDefault();
                return false;
            }
            
            // Проверка на подозрительные символы в имени
            if (/[<>\"']/.test(nameField.value)) {
                alert('Имя содержит недопустимые символы');
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
            
            // Проверка на слишком много заглавных букв (простая защита от спама)
            const uppercaseRatio = (contentField.value.length - contentField.value.toLowerCase().length) / contentField.value.length;
            if (uppercaseRatio > 0.6) {
                alert('Уменьшите количество заглавных букв в комментарии');
                contentField.focus();
                e.preventDefault();
                return false;
            }
            
            // Проверка на слишком много ссылок (защита от спама)
            const linkCount = (contentField.value.match(/http/gi) || []).length;
            if (linkCount > 2) {
                alert('Слишком много ссылок в комментарии');
                contentField.focus();
                e.preventDefault();
                return false;
            }
            
            // Показываем индикатор отправки
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Отправляем...';
            submitBtn.disabled = true;
            
            // На случай если что-то пойдет не так, возвращаем кнопку через 15 секунд
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 15000);
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
        
        // Подсчет символов в textarea с визуальной обратной связью
        const textarea = document.getElementById('comment_content');
        const small = textarea.nextElementSibling;
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            const remaining = 1000 - length;
            
            if (remaining < 100) {
                small.style.color = remaining < 0 ? '#e53e3e' : '#f56565';
                small.textContent = `Осталось символов: ${remaining}`;
            } else if (length < 10) {
                small.style.color = '#f56565';
                small.textContent = `Нужно еще ${10 - length} символов (минимум 10)`;
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
        
        // Улучшенная доступность
        document.addEventListener('DOMContentLoaded', function() {
            // Объявляем количество комментариев для screen readers
            const commentsCount = <?php echo $commentsCount ?>;
            if (commentsCount > 0) {
                const announcement = document.createElement('div');
                announcement.setAttribute('aria-live', 'polite');
                announcement.setAttribute('aria-atomic', 'true');
                announcement.className = 'visually-hidden';
                announcement.textContent = `На странице ${commentsCount} комментариев`;
                document.body.appendChild(announcement);
            }
            
            // Добавляем ARIA метки для лучшей доступности
            const commentForm = document.getElementById('commentForm');
            commentForm.setAttribute('aria-label', 'Форма добавления комментария');
            
            // Улучшаем валидацию в реальном времени
            const inputs = commentForm.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    this.setAttribute('aria-invalid', !this.checkValidity());
                });
            });
        });
    </script>
</body>
</html>