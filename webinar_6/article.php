<?php
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']) ?> | IT Blog</title>
    <link rel="stylesheet" href="style.css">
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
        <section style="text-align: center; margin: 2rem 0; padding: 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
            <h3>🎯 Понравилась статья?</h3>
            <p>Читайте больше статей в нашем блоге!</p>
            <div style="margin-top: 1rem;">
                <a href="index.php" style="display: inline-block; background: #667eea; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; margin: 0.5rem;">📚 Все статьи</a>
                <a href="search.php" style="display: inline-block; background: #718096; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; margin: 0.5rem;">🔍 Поиск</a>
                <a href="admin.php" style="display: inline-block; background: #38a169; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; margin: 0.5rem;">✏️ Админ-панель</a>
            </div>
        </section>
    </div>

</body>
</html>