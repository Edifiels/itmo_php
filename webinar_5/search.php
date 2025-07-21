<?php
require_once 'data.php';
require_once 'functions.php';

// Получаем поисковый запрос
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Выполняем поиск
$searchResults = searchArticles($query);
$totalResults = count($searchResults);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск<?= $query ? ': ' . htmlspecialchars($query) : '' ?> | IT Blog</title>
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
                    value="<?= htmlspecialchars($query) ?>"
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
                            Найдено <strong><?= $totalResults ?></strong> 
                            <?= $totalResults == 1 ? 'статья' : 'статей' ?> 
                            по запросу "<strong><?= htmlspecialchars($query) ?></strong>"
                        <?php else: ?>
                            По запросу "<strong><?= htmlspecialchars($query) ?></strong>" ничего не найдено
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($totalResults > 0): ?>
                    <div class="results-grid">
                        <?php foreach ($searchResults as $article): ?>
                        <article class="result-card">
                            <h3 class="result-title">
                                <a href="article.php?id=<?= $article['id'] ?>">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h3>
                            
                            <p class="result-excerpt">
                                <?= htmlspecialchars($article['excerpt']) ?>
                            </p>
                            
                            <div class="result-meta">
                                <span>👤 <?= htmlspecialchars($article['author']['name']) ?></span>
                                <span>📁 <?= htmlspecialchars($article['category']) ?></span>
                                <span>📅 <?= formatDate($article['date']) ?></span>
                                <span>👁️ <?= formatViews($article['views']) ?></span>
                                <span>⏱️ <?= $article['reading_time'] ?> мин</span>
                            </div>
                            
                            <div class="result-tags">
                                <?= renderTags($article['tags']) ?>
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
                        <a href="index.php" class="btn">Посмотреть все статьи</a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="search-help">
                    <h2>Как искать статьи?</h2>
                    <p>Введите ключевые слова в поле выше. Поиск выполняется по заголовкам, содержимому и описаниям статей.</p>
                    
                    <h3>Популярные темы:</h3>
                    <div class="popular-tags">
                        <a href="?q=PHP" class="tag">PHP</a>
                        <a href="?q=JavaScript" class="tag">JavaScript</a>
                        <a href="?q=MySQL" class="tag">MySQL</a>
                        <a href="?q=API" class="tag">API</a>
                        <a href="?q=Backend" class="tag">Backend</a>
                        <a href="?q=Frontend" class="tag">Frontend</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>