<?php
require_once 'data.php';
require_once 'functions.php';

// Получаем ID статьи
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем статью
$article = getArticle($articleId);

// Если статья не найдена
if (!$article) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Статья не найдена</h1>";
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
    <title><?= htmlspecialchars($article['title']) ?> | IT Blog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">← Назад к статьям</a>
        </nav>
        
        <!-- Заголовок статьи -->
        <header class="article-header">
            <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
            <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
            
            <div class="article-info">
                <div class="info-item">
                    <strong>Автор:</strong> <?= htmlspecialchars($article['author']['name']) ?>
                </div>
                <div class="info-item">
                    <strong>Категория:</strong> <?= htmlspecialchars($article['category']) ?>
                </div>
                <div class="info-item">
                    <strong>Дата:</strong> <?= formatDate($article['date']) ?>
                </div>
                <div class="info-item">
                    <strong>Время чтения:</strong> <?= $article['reading_time'] ?> мин
                </div>
            </div>
            
            <div class="article-tags">
                <?= renderTags($article['tags']) ?>
            </div>
        </header>
        
        <!-- Содержимое статьи -->
        <main class="article-content">
            <div class="article-text">
                <?= nl2br(htmlspecialchars($article['content'])) ?>
            </div>
        </main>
        
        <!-- Автор -->
        <section class="author-info">
            <h3>Об авторе</h3>
            <div class="author-card">
                <div class="author-avatar">
                    <?= strtoupper(substr($article['author']['name'], 0, 1)) ?>
                </div>
                <div class="author-details">
                    <h4><?= htmlspecialchars($article['author']['name']) ?></h4>
                    <p><?= htmlspecialchars($article['author']['email']) ?></p>
                </div>
            </div>
        </section>
        
        <!-- Статистика просмотров -->
        <section class="article-stats-section">
            <p>👁️ Эту статью просмотрели <strong><?= formatViews($article['views']) ?></strong> раз</p>
        </section>
        
        <!-- Похожие статьи -->
        <?php if (!empty($similarArticles)): ?>
        <section class="similar-articles">
            <h3>📖 Похожие статьи</h3>
            <div class="similar-grid">
                <?php foreach ($similarArticles as $similar): ?>
                <article class="similar-card">
                    <h4>
                        <a href="article.php?id=<?= $similar['id'] ?>">
                            <?= htmlspecialchars($similar['title']) ?>
                        </a>
                    </h4>
                    <p class="similar-meta">
                        👁️ <?= formatViews($similar['views']) ?> • 
                        ⏱️ <?= $similar['reading_time'] ?> мин
                    </p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</body>
</html>