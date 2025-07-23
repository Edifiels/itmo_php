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

// Получаем поисковый запрос
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Выполняем поиск
$searchResults = searchArticles($query);
$totalResults = count($searchResults);

// Получаем популярные теги для подсказок
$allTags = getAllTags();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск<?php echo $query ? ': ' . htmlspecialchars($query) : '' ?> | IT Blog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">← Назад на главную</a>
        </nav>
        
        <header class="search-header">
            <h1>🔍 Поиск по блогу</h1>
            
            <!-- Форма поиска -->
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Введите поисковый запрос..." 
                    value="<?php echo htmlspecialchars($query) ?>"
                    autofocus
                >
                <button type="submit" class="search-btn">Найти</button>
            </form>
        </header>
        
        <!-- Результаты поиска -->
        <main class="search-results">
            <?php if ($query): ?>
                <div class="results-info">
                    <h2>Результаты поиска</h2>
                    <p>
                        <?php if ($totalResults > 0): ?>
                            Найдено <strong><?php echo $totalResults ?></strong> 
                            <?php 
                            if ($totalResults == 1) {
                                echo 'статья';
                            } elseif ($totalResults >= 2 && $totalResults <= 4) {
                                echo 'статьи';
                            } else {
                                echo 'статей';
                            }
                            ?> 
                            по запросу "<strong><?php echo htmlspecialchars($query) ?></strong>"
                        <?php else: ?>
                            По запросу "<strong><?php echo htmlspecialchars($query) ?></strong>" ничего не найдено
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($totalResults > 0): ?>
                    <div class="results-grid">
                        <?php foreach ($searchResults as $article): ?>
                        <article class="result-card">
                            <h3 class="result-title">
                                <a href="article.php?id=<?php echo $article['id'] ?>">
                                    <?php echo htmlspecialchars($article['title']) ?>
                                </a>
                            </h3>
                            
                            <p class="result-excerpt">
                                <?php echo htmlspecialchars($article['excerpt']) ?>
                            </p>
                            
                            <div class="result-meta">
                                <span>👤 <?php echo htmlspecialchars($article['author']['name']) ?></span>
                                <span>📁 <?php echo htmlspecialchars($article['category']) ?></span>
                                <span>📅 <?php echo formatDate($article['date']) ?></span>
                                <span>👁️ <?php echo formatViews($article['views']) ?></span>
                                <span>⏱️ <?php echo $article['reading_time'] ?> мин</span>
                            </div>
                            
                            <div class="result-tags">
                                <?php echo renderTags($article['tags']) ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <h3>😔 К сожалению, ничего не найдено</h3>
                        <p>Попробуйте:</p>
                        <ul>
                            <li>Проверить правильность написания</li>
                            <li>Использовать более общие термины</li>
                            <li>Попробовать другие ключевые слова</li>
                        </ul>
                        
                        <?php if (!empty($allTags)): ?>
                        <h4>💡 Попробуйте поискать по тегам:</h4>
                        <div class="popular-tags" style="margin: 1rem 0;">
                            <?php foreach (array_slice($allTags, 0, 10) as $tag): ?>
                            <a href="?q=<?php echo urlencode($tag) ?>" class="tag"><?php echo htmlspecialchars($tag) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn" style="margin-top: 1rem;">📚 Посмотреть все статьи</a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="search-help">
                    <h2>Как искать статьи?</h2>
                    <p>Введите ключевые слова в поле выше. Поиск выполняется по заголовкам, содержимому и описаниям статей.</p>
                    
                    <?php if (!empty($allTags)): ?>
                    <h3>🏷️ Популярные темы:</h3>
                    <div class="popular-tags">
                        <?php foreach ($allTags as $tag): ?>
                        <a href="?q=<?php echo urlencode($tag) ?>" class="tag"><?php echo htmlspecialchars($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 2rem; padding: 1.5rem; background: #f7fafc; border-radius: 8px;">
                        <h4>💡 Советы по поиску:</h4>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>Используйте ключевые слова из области IT</li>
                            <li>Поиск не чувствителен к регистру</li>
                            <li>Можно искать по именам авторов</li>
                            <li>Поиск работает по всему тексту статей</li>
                        </ul>
                    </div>
                    
                    <!-- Показываем последние статьи как альтернативу -->
                    <?php 
                    $recentArticles = getRecentArticles(3);
                    if (!empty($recentArticles)): 
                    ?>
                    <h3 style="margin-top: 2rem;">📖 Последние статьи:</h3>
                    <div class="articles-grid" style="margin-top: 1rem;">
                        <?php foreach ($recentArticles as $article): ?>
                        <article class="article-card">
                            <h4>
                                <a href="article.php?id=<?php echo $article['id'] ?>">
                                    <?php echo htmlspecialchars($article['title']) ?>
                                </a>
                            </h4>
                            <p><?php echo htmlspecialchars(substr($article['excerpt'], 0, 100)) ?>...</p>
                            <div style="font-size: 0.9rem; color: #718096; margin-top: 0.5rem;">
                                👤 <?php echo htmlspecialchars($article['author']['name']) ?> • 
                                📅 <?php echo formatDate($article['date']) ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>