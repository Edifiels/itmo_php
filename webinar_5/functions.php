<?php
// functions.php - Упрощенные функции блога

// Форматирование даты
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

// Форматирование просмотров
function formatViews($views) {
    if ($views < 1000) return $views;
    if ($views < 1000000) return round($views / 1000, 1) . 'K';
    return round($views / 1000000, 1) . 'M';
}

// Получение статьи по ID с полными данными
function getArticle($id) {
    // Загружаем статью из файла
    $article = loadArticleFromFile($id);
    if (!$article) {
        return null;
    }
    
    // Добавляем связанные данные
    $authors = getAuthors();
    $categories = getCategories();
    
    $article['author'] = isset($authors[$article['author_id']]) 
        ? $authors[$article['author_id']] 
        : ['name' => 'Неизвестный автор', 'email' => ''];
    
    $article['category'] = isset($categories[$article['category_id']]) 
        ? $categories[$article['category_id']] 
        : 'Без категории';
    
    // Добавляем просмотры
    $article['views'] = getViews($id);
    
    return $article;
}

// Получение всех статей
function getAllArticles() {
    $articleIds = getArticleIds();
    $articles = [];
    
    foreach ($articleIds as $id) {
        $article = getArticle($id);
        if ($article) {
            $articles[] = $article;
        }
    }
    
    // Сортировка по дате (новые сначала)
    usort($articles, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $articles;
}

// Поиск статей
function searchArticles($query) {
    $allArticles = getAllArticles();
    
    if (empty($query)) {
        return $allArticles;
    }
    
    return array_filter($allArticles, function($article) use ($query) {
        $searchIn = $article['title'] . ' ' . $article['content'] . ' ' . $article['excerpt'];
        return stripos($searchIn, $query) !== false;
    });
}

// Получение похожих статей (из той же категории)
function getSimilarArticles($articleId, $limit = 3) {
    $currentArticle = getArticle($articleId);
    if (!$currentArticle) {
        return [];
    }
    
    $allArticles = getAllArticles();
    $similar = [];
    
    foreach ($allArticles as $article) {
        if ($article['id'] != $articleId && 
            $article['category_id'] == $currentArticle['category_id']) {
            $similar[] = $article;
        }
    }
    
    return array_slice($similar, 0, $limit);
}

// Генерация тегов
function renderTags($tags) {
    if (empty($tags)) {
        return '';
    }
    
    $html = '<div class="article-tags">';
    foreach ($tags as $tag) {
        $html .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
    }
    $html .= '</div>';
    
    return $html;
}

// Статистика блога  
function getBlogStats() {
    $articleIds = getArticleIds();
    $authors = getAuthors();
    $categories = getCategories();
    
    $totalViews = 0;
    foreach ($articleIds as $id) {
        $totalViews += getViews($id);
    }
    
    return [
        'articles' => count($articleIds),
        'views' => $totalViews,
        'authors' => count($authors),
        'categories' => count($categories)
    ];
}

// Получение статей определенного автора
function getArticlesByAuthor($authorId) {
    $allArticles = getAllArticles();
    
    return array_filter($allArticles, function($article) use ($authorId) {
        return $article['author_id'] == $authorId;
    });
}

// Получение статей определенной категории
function getArticlesByCategory($categoryId) {
    $allArticles = getAllArticles();
    
    return array_filter($allArticles, function($article) use ($categoryId) {
        return $article['category_id'] == $categoryId;
    });
}

// Получение популярных статей (по просмотрам)
function getPopularArticles($limit = 5) {
    $allArticles = getAllArticles();
    
    // Сортируем по просмотрам
    usort($allArticles, function($a, $b) {
        return $b['views'] - $a['views'];
    });
    
    return array_slice($allArticles, 0, $limit);
}

// Получение последних статей
function getRecentArticles($limit = 5) {
    $allArticles = getAllArticles();
    
    // Уже отсортированы по дате в getAllArticles()
    return array_slice($allArticles, 0, $limit);
}

// Валидация данных статьи
function validateArticleData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Заголовок обязателен';
    }
    
    if (empty($data['content'])) {
        $errors[] = 'Содержимое обязательно';
    }
    
    if (empty($data['excerpt'])) {
        $errors[] = 'Описание обязательно';
    }
    
    if (!isset($data['author_id']) || !is_numeric($data['author_id'])) {
        $errors[] = 'Неверный автор';
    }
    
    if (!isset($data['category_id']) || !is_numeric($data['category_id'])) {
        $errors[] = 'Неверная категория';
    }
    
    return $errors;
}

// Подсчет слов в тексте
function countWords($text) {
    return str_word_count(strip_tags($text));
}

// Автоматический расчет времени чтения
function calculateReadingTime($content) {
    $words = countWords($content);
    return max(1, round($words / 200)); // 200 слов в минуту
}

// Генерация slug из заголовка
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9а-я ]/ui', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    return trim($slug, '-');
}

// Получение списка всех тегов
function getAllTags() {
    $allArticles = getAllArticles();
    $tags = [];
    
    foreach ($allArticles as $article) {
        if (!empty($article['tags'])) {
            $tags = array_merge($tags, $article['tags']);
        }
    }
    
    return array_unique($tags);
}
?>