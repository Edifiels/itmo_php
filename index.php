<?php
require_once 'blog_data.php';

// Получаем все статьи с полной информацией
$allArticles = [];
foreach ($articles as $articleId => $article) {
    $allArticles[] = getArticleWithRelations($articleId);
}

// Сортируем по дате публикации (новые сначала)
usort($allArticles, function($a, $b) {
    return strtotime($b['dates']['published']) - strtotime($a['dates']['published']);
});

// Получаем рекомендуемые статьи
$featuredArticles = array_filter($allArticles, function($article) {
    return $article['featured'] === true;
});

// Статистика блога
$totalViews = array_sum(array_column($allArticles, 'meta.views'));
$totalArticles = count($allArticles);
$totalAuthors = count($authors);
$totalCategories = count($categories);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Blog - Статьи о веб-разработке</title>
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1>🚀 IT Development Blog</h1>
                <p>Статьи о современной веб-разработке, PHP, JavaScript и не только</p>
            </div>
        </div>
    </header>

    <main class="container">
        <!-- Статистика блога -->
        <section class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $totalArticles ?></div>
                <div>Статей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalViews) ?></div>
                <div>Просмотров</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalAuthors ?></div>
                <div>Авторов</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalCategories ?></div>
                <div>Категорий</div>
            </div>
        </section>

        <!-- Рекомендуемые статьи -->
        <?php if (!empty($featuredArticles)): ?>
        <section class="featured-section">
            <h2 class="section-title">⭐ Рекомендуемые статьи</h2>
            <div class="articles-grid">
                <?php foreach ($featuredArticles as $article): ?>
                <article class="article-card">
                    <div class="article-image">
                        📖 <?= htmlspecialchars($article['title']) ?>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                        <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
                        
                        <div class="article-meta">
                            <span>👤 <?= htmlspecialchars($article['author']['name']) ?></span>
                            <span class="category-badge"><?= htmlspecialchars($article['category']['name']) ?></span>
                        </div>
                        
                        <div class="article-tags">
                            <?php foreach ($article['tags'] as $tag): ?>
                                <span class="tag">#<?= htmlspecialchars($tag['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="article-stats">
                            <span>👁️ <?= number_format($article['meta']['views']) ?> просмотров</span>
                            <span>❤️ <?= $article['meta']['likes'] ?> лайков</span>
                            <span>⏱️ <?= $article['meta']['reading_time'] ?> мин</span>
                        </div>
                        
                        <a href="article.php?id=<?= $article['id'] ?>" class="read-more">Читать далее →</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Все статьи -->
        <section>
            <h2 class="section-title">📚 Все статьи</h2>
            <div class="articles-grid">
                <?php foreach ($allArticles as $article): ?>
                <article class="article-card">
                    <div class="article-image">
                        📄 <?= htmlspecialchars($article['category']['name']) ?>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                        <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
                        
                        <div class="article-meta">
                            <span>👤 <?= htmlspecialchars($article['author']['name']) ?></span>
                            <span>📅 <?= date('d.m.Y', strtotime($article['dates']['published'])) ?></span>
                        </div>
                        
                        <div class="article-tags">
                            <?php foreach ($article['tags'] as $tag): ?>
                                <span class="tag">#<?= htmlspecialchars($tag['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="article-stats">
                            <span>👁️ <?= number_format($article['meta']['views']) ?></span>
                            <span>❤️ <?= $article['meta']['likes'] ?></span>
                            <span>💬 <?= $article['meta']['comments_count'] ?></span>
                            <span>⏱️ <?= $article['meta']['reading_time'] ?> мин</span>
                        </div>
                        
                        <a href="article.php?id=<?= $article['id'] ?>" class="read-more">Читать далее →</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
