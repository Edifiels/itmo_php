<?php
require_once 'functions.php';

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>❌ Ошибка подключения к базе данных</h1>";
    echo "<p>Проверьте настройки в config/database.php или запустите <a href='database/migration.php'>миграцию данных</a></p>";
    echo "</body></html>";
    exit;
}

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
                <h3><?php echo $stats['articles'] ?></h3>
                <p>Статей</p>
            </div>
            <div class="stat-item">
                <h3><?php echo formatViews($stats['views']) ?></h3>
                <p>Просмотров</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $stats['authors'] ?></h3>
                <p>Авторов</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $stats['categories'] ?></h3>
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
                            <a href="article.php?id=<?php echo $article['id'] ?>">
                                <?php echo htmlspecialchars($article['title']) ?>
                            </a>
                        </h3>
                        <p class="article-excerpt">
                            <?php echo htmlspecialchars($article['excerpt']) ?>
                        </p>
                    </div>
                    
                    <div class="article-meta">
                        <span>👤 <?php echo htmlspecialchars($article['author']['name']) ?></span>
                        <span>📁 <?php echo htmlspecialchars($article['category']) ?></span>
                        <span>📅 <?php echo formatDate($article['date']) ?></span>
                    </div>
                    
                    <?php echo renderTags($article['tags']) ?>
                    
                    <div class="article-stats">
                        <span>👁️ <?php echo formatViews($article['views']) ?></span>
                        <span>⏱️ <?php echo $article['reading_time'] ?> мин</span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Все статьи -->
        <section class="articles">
            <h2>📚 Все статьи (<?php echo count($allArticles) ?>)</h2>
            
            <?php if (empty($allArticles)): ?>
                <div class="no-articles">
                    <h3>📝 Статей пока нет</h3>
                    <p>Создайте первую статью в <a href="admin.php">админ-панели</a></p>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($allArticles as $article): ?>
                    <article class="article-card">
                        <div class="article-header">
                            <h3 class="article-title">
                                <a href="article.php?id=<?php echo $article['id'] ?>">
                                    <?php echo htmlspecialchars($article['title']) ?>
                                </a>
                            </h3>
                            <p class="article-excerpt">
                                <?php echo htmlspecialchars($article['excerpt']) ?>
                            </p>
                        </div>
                        
                        <div class="article-meta">
                            <span>👤 <?php echo htmlspecialchars($article['author']['name']) ?></span>
                            <span>📁 <?php echo htmlspecialchars($article['category']) ?></span>
                            <span>📅 <?php echo formatDate($article['date']) ?></span>
                        </div>
                        
                        <div class="article-tags">
                            <?php echo renderTags($article['tags']) ?>
                        </div>
                        
                        <div class="article-stats">
                            <span>👁️ <?php echo formatViews($article['views']) ?></span>
                            <span>⏱️ <?php echo $article['reading_time'] ?> мин</span>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
</body>
</html>