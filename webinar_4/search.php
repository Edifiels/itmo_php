<?php
require_once 'blog_data.php';
require_once 'blog_functions.php';

// Получаем параметры поиска безопасно
$searchQuery = getParam('q');
$categoryFilter = getParam('category', 0, 'int');
$tagFilter = getParam('tag');
$authorFilter = getParam('author', 0, 'int');

// Выполняем поиск
$searchResults = searchArticles($searchQuery, $categoryFilter, $tagFilter, $authorFilter);

// Сортируем результаты по релевантности
$searchResults = sortArticlesByRelevance($searchResults);

// Статистика поиска
$totalResults = count($searchResults);
$totalViews = 0;
foreach ($searchResults as $article) {
    $totalViews += $article['meta']['views'];
}
$averageViews = $totalResults > 0 ? round($totalViews / $totalResults) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск по блогу | IT Blog</title>
    <link rel="stylesheet" href="styles/search.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Вернуться на главную</a>
        
        <div class="search-header">
            <h1>🔍 Поиск по блогу</h1>
            
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Введите поисковый запрос..." 
                    value="<?php echo htmlspecialchars($searchQuery) ?>"
                >
                <button type="submit" class="search-btn">Найти</button>
            </form>
            
            <div class="filters">
                <div class="filter-group">
                    <label>Категория:</label>
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id'] ?>" <?php echo $categoryFilter === $category['id'] ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Автор:</label>
                    <select name="author" class="filter-select" onchange="this.form.submit()">
                        <option value="0">Все авторы</option>
                        <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id'] ?>" <?php echo $authorFilter === $author['id'] ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($author['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Скрытые поля для сохранения других параметров -->
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery) ?>">
                <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tagFilter) ?>">
            </div>
        </div>
        
        <div class="search-results">
            <?php if ($totalResults > 0): ?>
            <div class="results-header">
                <div class="results-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $totalResults ?></div>
                        <div>Найдено статей</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo formatViews($totalViews) ?></div>
                        <div>Общие просмотры</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $averageViews ?></div>
                        <div>Среднее просмотров</div>
                    </div>
                </div>
            </div>
            
            <?php foreach ($searchResults as $article): ?>
            <div class="result-item">
                <h2 class="result-title">
                    <a href="article.php?id=<?php echo $article['id'] ?>">
                        <?php echo htmlspecialchars($article['title']) ?>
                    </a>
                </h2>
                
                <p class="result-excerpt">
                    <?php echo htmlspecialchars($article['excerpt']) ?>
                </p>
                
                <div class="result-meta">
                    <span>👤 <?php echo htmlspecialchars($article['author']['name']) ?></span>
                    <span>📁 <?php echo htmlspecialchars($article['category']['name']) ?></span>
                    <span>📅 <?php echo date('d.m.Y', strtotime($article['dates']['published'])) ?></span>
                    <span>👁️ <?php echo formatViews($article['meta']['views']) ?> просмотров</span>
                    <span>⏱️ <?php echo $article['meta']['reading_time'] ?> мин</span>
                </div>
                
                <?php echo generateTagsHtml($article['tags'], true) ?>
            </div>
            <?php endforeach; ?>
            
            <?php else: ?>
            <div class="no-results">
                <h2>😔 Статьи не найдены</h2>
                <p>Попробуйте изменить параметры поиска или вернитесь к <a href="index.php">списку всех статей</a>.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>