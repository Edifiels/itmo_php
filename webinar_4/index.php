<?php
require_once 'blog_data.php';
require_once 'blog_functions.php';

// Получаем все статьи с полной информацией
$allArticles = [];
foreach ($articles as $articleId => $article) {
    $allArticles[] = getArticleWithRelations($articleId);
}

// Сортируем по дате публикации (новые сначала)
$allArticles = sortArticlesByDate($allArticles);

// Получаем рекомендуемые статьи
$featuredArticles = array_filter($allArticles, function($article) {
    return $article['featured'] === true;
});

// Статистика блога
$blogStats = calculateBlogStats($articles);
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
                <div class="stat-number"><?php echo $blogStats['total_articles'] ?></div>
                <div>Статей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo formatViews($blogStats['total_views']) ?></div>
                <div>Просмотров</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $blogStats['total_authors'] ?></div>
                <div>Авторов</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $blogStats['total_categories'] ?></div>
                <div>Категорий</div>
            </div>
        </section>

        <!-- Рекомендуемые статьи -->
        <?php if (!empty($featuredArticles)): ?>
        <section class="featured-section">
            <h2 class="section-title">⭐ Рекомендуемые статьи</h2>
            <div class="articles-grid">
                <?php foreach ($featuredArticles as $article): ?>
                    <?php echo generateArticleCard($article, true) ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Все статьи -->
        <section>
            <h2 class="section-title">📚 Все статьи</h2>
            <div class="articles-grid">
                <?php foreach ($allArticles as $article): ?>
                    <?php echo generateArticleCard($article) ?>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>