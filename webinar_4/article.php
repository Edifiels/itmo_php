<?php
require_once 'blog_data.php';
require_once 'blog_functions.php';

// Получаем ID статьи безопасно
$articleId = getParam('id', 0, 'int');

// Получаем статью с валидацией
$article = getArticleSafely($articleId);

// Если статья не найдена
if (!$article) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Статья не найдена</h1>";
    exit;
}

// Увеличиваем счетчик просмотров
$articles[$articleId]['meta']['views']++;

// Получаем похожие статьи (из той же категории)
$relatedArticles = array_filter($articles, function($art) use ($article) {
    return $art['category_id'] === $article['category_id'] && $art['id'] !== $article['id'];
});
$relatedArticles = array_slice($relatedArticles, 0, 3);
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
                
                <?php echo generateTagsHtml($article['tags'], true) ?>
            </header>
            
            <div class="<><">
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
                        👁️ <?php echo formatViews($related['meta']['views']) ?> просмотров • 
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