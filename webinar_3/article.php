<?php
require_once 'blog_data.php';

// Получаем ID статьи из URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем статью с полной информацией
$article = getArticleWithRelations($articleId);

// Если статья не найдена
if (!$article) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Статья не найдена</h1>";
    exit;
}

// Увеличиваем счетчик просмотров (в реальном приложении это делалось бы в БД)
$articles[$articleId]['meta']['views']++;

// Получаем похожие статьи (из той же категории)
$relatedArticles = array_filter($articles, function($art) use ($article) {
    return $art['category_id'] === $article['category_id'] && $art['id'] !== $article['id'];
});
$relatedArticles = array_slice($relatedArticles, 0, 3);

// Функция для форматирования даты
function formatDate($dateString) {
    return date('d F Y в H:i', strtotime($dateString));
}

// Функция для подсчета слов в тексте
function countWords($text) {
    return str_word_count(strip_tags($text));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']) ?> | IT Blog</title>
    <link rel="stylesheet" href="styles/article.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Вернуться к списку статей</a>
        
        <article>
            <header class="article-header">
                <h1 class="article-title"><?php echo htmlspecialchars($article['title']) ?></h1>
                <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt']) ?></p>
                
                <div class="article-meta-header">
                    <div class="meta-card">
                        <div class="meta-label">Автор</div>
                        <div class="meta-value"><?php echo htmlspecialchars($article['author']['name']) ?></div>
                    </div>
                    <div class="meta-card">
                        <div class="meta-label">Категория</div>
                        <div class="meta-value"><?php echo htmlspecialchars($article['category']['name']) ?></div>
                    </div>
                    <div class="meta-card">
                        <div class="meta-label">Дата публикации</div>
                        <div class="meta-value"><?php echo formatDate($article['dates']['published']) ?></div>
                    </div>
                    <div class="meta-card">
                        <div class="meta-label">Время чтения</div>
                        <div class="meta-value"><?php echo $article['meta']['reading_time'] ?> минут</div>
                    </div>
                </div>
                
                <div class="article-tags">
                    <?php foreach ($article['tags'] as $tag): ?>
                        <a href="search.php?tag=<?php echo urlencode($tag['slug']) ?>" class="tag">
                            #<?php echo htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </header>
            
            <div class="article-content">
                <div class="article-text">
                    <?php echo nl2br(htmlspecialchars($article['content'])) ?>
                </div>
            </div>
        </article>
        
        <!-- Информация об авторе -->
        <div class="author-card">
            <div class="author-info">
                <div class="author-avatar">
                    <?php echo strtoupper(substr($article['author']['name'], 0, 1)) ?>
                </div>
                <div class="author-details">
                    <h3><?php echo htmlspecialchars($article['author']['name']) ?></h3>
                    <div><?php echo htmlspecialchars($article['author']['email']) ?></div>
                </div>
            </div>
            <div class="author-bio">
                <?php echo htmlspecialchars($article['author']['bio']) ?>
            </div>
        </div>
        
        <!-- Похожие статьи -->
        <?php if (!empty($relatedArticles)): ?>
        <div class="related-articles">
            <h3 class="related-title">📖 Похожие статьи</h3>
            <div class="related-list">
                <?php foreach ($relatedArticles as $related): ?>
                <a href="article.php?id=<?php echo $related['id'] ?>" class="related-item">
                    <h4><?php echo htmlspecialchars($related['title']) ?></h4>
                    <div class="related-meta">
                        👁️ <?php echo number_format($related['meta']['views']) ?> просмотров • 
                        ⏱️ <?php echo $related['meta']['reading_time'] ?> мин чтения
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>