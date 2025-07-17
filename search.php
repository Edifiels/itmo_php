<?php
require_once 'blog_data.php';

// Получаем параметры поиска
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$tagFilter = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$authorFilter = isset($_GET['author']) ? (int)$_GET['author'] : 0;

// Функция поиска статей
function searchArticles($query, $categoryId = 0, $tagSlug = '', $authorId = 0) {
    global $articles;
    
    $results = [];
    
    foreach ($articles as $article) {
        $fullArticle = getArticleWithRelations($article['id']);
        $matches = true;
        
        // Поиск по тексту
        if ($query && $matches) {
            $searchText = $fullArticle['title'] . ' ' . $fullArticle['content'] . ' ' . $fullArticle['excerpt'];
            $matches = stripos($searchText, $query) !== false;
        }
        
        // Фильтр по категории
        if ($categoryId && $matches) {
            $matches = $fullArticle['category_id'] === $categoryId;
        }
        
        // Фильтр по тегу
        if ($tagSlug && $matches) {
            $tagSlugs = array_column($fullArticle['tags'], 'slug');
            $matches = in_array($tagSlug, $tagSlugs);
        }
        
        // Фильтр по автору
        if ($authorId && $matches) {
            $matches = $fullArticle['author_id'] === $authorId;
        }
        
        if ($matches) {
            $results[] = $fullArticle;
        }
    }
    
    return $results;
}

// Выполняем поиск
$searchResults = searchArticles($searchQuery, $categoryFilter, $tagFilter, $authorFilter);

// Сортируем результаты по релевантности (по количеству просмотров)
usort($searchResults, function($a, $b) {
    return $b['meta']['views'] - $a['meta']['views'];
});

// Статистика поиска
$totalResults = count($searchResults);
$totalViews = array_sum(array_column($searchResults, 'meta.views'));
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
                    value="<?= htmlspecialchars($searchQuery) ?>"
                >
                <button type="submit" class="search-btn">Найти</button>
            </form>
            
            <div class="filters">
                <div class="filter-group">
                    <label>Категория:</label>
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $categoryFilter === $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Автор:</label>
                    <select name="author" class="filter-select" onchange="this.form.submit()">
                        <option value="0">Все авторы</option>
                        <?php foreach ($authors as $author): ?>
                        <option value="<?= $author['id'] ?>" <?= $authorFilter === $author['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($author['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Скрытые поля для сохранения других параметров -->
                <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                <input type="hidden" name="tag" value="<?= htmlspecialchars($tagFilter) ?>">
            </div>
        </div>
        
        <div class="search-results">
            <?php if ($totalResults > 0): ?>
            <div class="results-header">
                <div class="results-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= $totalResults ?></div>
                        <div>Найдено статей</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($totalViews) ?></div>
                        <div>Общие просмотры</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= round($totalViews / $totalResults) ?></div>
                        <div>Среднее просмотров</div>
                    </div>
                </div>
            </div>
            
            <?php foreach ($searchResults as $article): ?>
            <div class="result-item">
                <h2 class="result-title">
                    <a href="article.php?id=<?= $article['id'] ?>">
                        <?= htmlspecialchars($article['title']) ?>
                    </a>
                </h2>
                
                <p class="result-excerpt">
                    <?= htmlspecialchars($article['excerpt']) ?>
                </p>
                
                <div class="result-meta">
                    <span>👤 <?= htmlspecialchars($article['author']['name']) ?></span>
                    <span>📁 <?= htmlspecialchars($article['category']['name']) ?></span>
                    <span>📅 <?= date('d.m.Y', strtotime($article['dates']['published'])) ?></span>
                    <span>👁️ <?= number_format($article['meta']['views']) ?> просмотров</span>
                    <span>⏱️ <?= $article['meta']['reading_time'] ?> мин</span>
                </div>
                
                <div class="result-tags">
                    <?php foreach ($article['tags'] as $tag): ?>
                        <a href="search.php?tag=<?= urlencode($tag['slug']) ?>" class="tag">
                            #<?= htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
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