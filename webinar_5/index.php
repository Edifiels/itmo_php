<?php
require_once 'data.php';
require_once 'functions.php';

$allArticles = getAllArticles();
$stats = getBlogStats();
$popularArticles = getPopularArticles(3);
$recentArticles = getRecentArticles(5);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Blog - Статьи о веб-разработке</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🚀 IT Blog</h1>
            <p>Статьи о современной веб-разработке</p>
        </div>
    </header>

    <main class="container">
        <!-- Статистика -->
        <section class="stats">
            <div class="stat-item">
                <h3><?= $stats['articles'] ?></h3>
                <p>Статей</p>
            </div>
            <div class="stat-item">
                <h3><?= formatViews($stats['views']) ?></h3>
                <p>Просмотров</p>
            </div>
            <div class="stat-item">
                <h3><?= $stats['authors'] ?></h3>
                <p>Авторов</p>
            </div>
            <div class="stat-item">
                <h3><?= $stats['categories'] ?></h3>
                <p>Категорий</p>
            </div>
        </section>

        <!-- Поиск -->
        <section class="search-section">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Поиск статей..." class="search-input">
                <button type="submit" class="search-btn">Найти</button>
            </form>
            <p class="admin-link"><a href="admin.php">📝 Управление статьями</a></p>
        </section>

        <!-- Популярные статьи -->
        <?php if (!empty($popularArticles)): ?>
        <section class="popular-section">
            <h2>🔥 Популярные статьи</h2>
            <div class="articles-grid">
                <?php foreach ($popularArticles as $article): ?>
                <article class="article-card popular">
                    <div class="article-header">
                        <h3 class="article-title">
                            <a href="article.php?id=<?= $article['id'] ?>">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h3>
                        <p class="article-excerpt">
                            <?= htmlspecialchars($article['excerpt']) ?>
                        </p>
                    </div>
                    
                    <div class="article-meta">
                        <span>👤 <?= htmlspecialchars($article['author']['name']) ?></span>
                        <span>📁 <?= htmlspecialchars($article['category']) ?></span>
                        <span>📅 <?= formatDate($article['date']) ?></span>
                    </div>
                    
                    <?= renderTags($article['tags']) ?>
                    
                    <div class="article-stats">
                        <span>👁️ <?= formatViews($article['views']) ?></span>
                        <span>⏱️ <?= $article['reading_time'] ?> мин</span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Все статьи -->
        <section class="articles">
            <h2>📚 Все статьи</h2>
            <div class="articles-grid">
                <?php foreach ($allArticles as $article): ?>
                <article class="article-card">
                    <div class="article-header">
                        <h3 class="article-title">
                            <a href="article.php?id=<?= $article['id'] ?>">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h3>
                        <p class="article-excerpt">
                            <?= htmlspecialchars($article['excerpt']) ?>
                        </p>
                    </div>
                    
                    <div class="article-meta">
                        <span>👤 <?= htmlspecialchars($article['author']['name']) ?></span>
                        <span>📁 <?= htmlspecialchars($article['category']) ?></span>
                        <span>📅 <?= formatDate($article['date']) ?></span>
                    </div>
                    
                    <div class="article-tags">
                        <?= renderTags($article['tags']) ?>
                    </div>
                    
                    <div class="article-stats">
                        <span>👁️ <?= formatViews($article['views']) ?></span>
                        <span>⏱️ <?= $article['reading_time'] ?> мин</span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>