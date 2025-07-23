<?php
// functions.php - Расширенные функции для работы с MySQL базой данных (Занятие 7)

require_once 'config/database.php';

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

/**
 * Форматирование даты
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Форматирование даты и времени
 */
function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

/**
 * Форматирование просмотров
 */
function formatViews($views) {
    if ($views < 1000) return $views;
    if ($views < 1000000) return round($views / 1000, 1) . 'K';
    return round($views / 1000000, 1) . 'M';
}

/**
 * Создание slug из строки
 */
function generateSlug($title) {
    $slug = mb_strtolower($title, 'UTF-8');
    $slug = preg_replace('/[^а-яёa-z0-9\s-]/ui', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Подсчет слов в тексте
 */
function countWords($text) {
    return str_word_count(strip_tags($text));
}

/**
 * Автоматический расчет времени чтения
 */
function calculateReadingTime($content) {
    $words = countWords($content);
    return max(1, round($words / 200)); // 200 слов в минуту
}

/**
 * Безопасное экранирование HTML
 */
function sanitizeString($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Безопасная очистка HTML с разрешенными тегами
 */
function sanitizeHTML($value) {
    $allowedTags = '<p><br><strong><em><u><ol><ul><li><h3><h4><blockquote>';
    return strip_tags($value, $allowedTags);
}

/**
 * Валидация email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Генерация превью текста
 */
function generateExcerpt($content, $length = 200) {
    $content = strip_tags($content);
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . '...';
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С АВТОРАМИ ==========

/**
 * Получение всех авторов
 */
function getAuthors() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT * FROM users ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting authors: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение автора по ID
 */
function getAuthor($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting author: " . $e->getMessage());
        return null;
    }
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С КАТЕГОРИЯМИ ==========

/**
 * Получение всех категорий
 */
function getCategories() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение категории по ID
 */
function getCategory($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting category: " . $e->getMessage());
        return null;
    }
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С ТЕГАМИ ==========

/**
 * Получение тегов статьи
 */
function getArticleTags($articleId) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT t.name 
            FROM tags t 
            JOIN article_tags at ON t.id = at.tag_id 
            WHERE at.article_id = ?
            ORDER BY t.name
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error getting article tags: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение всех тегов
 */
function getAllTags() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT name FROM tags ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error getting all tags: " . $e->getMessage());
        return [];
    }
}

/**
 * Сохранение тегов для статьи
 */
function saveArticleTags($articleId, $tags) {
    try {
        $pdo = getDatabaseConnection();
        
        // Удаляем существующие теги статьи
        $stmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
        $stmt->execute([$articleId]);
        
        if (empty($tags)) {
            return true;
        }
        
        // Добавляем новые теги
        $stmtTag = $pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
        $stmtGetTag = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmtArticleTag = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
        
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;
            
            // Добавляем тег если его нет
            $stmtTag->execute([$tagName, generateSlug($tagName)]);
            
            // Получаем ID тега
            $stmtGetTag->execute([$tagName]);
            $tagId = $stmtGetTag->fetchColumn();
            
            // Связываем статью с тегом
            if ($tagId) {
                $stmtArticleTag->execute([$articleId, $tagId]);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error saving article tags: " . $e->getMessage());
        return false;
    }
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ СО СТАТЬЯМИ ==========

/**
 * Получение статьи по ID с полными данными
 */
function getArticle($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                u.bio as author_bio,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.id = ? AND a.status = 'published'
        ");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            return null;
        }
        
        // Формируем структуру как в старом коде
        $article['author'] = [
            'name' => $article['author_name'],
            'email' => $article['author_email'],
            'bio' => $article['author_bio']
        ];
        $article['category'] = $article['category_name'];
        $article['date'] = $article['published_at'] ?: $article['created_at'];
        
        // Получаем теги
        $article['tags'] = getArticleTags($id);
        
        return $article;
    } catch (PDOException $e) {
        error_log("Error getting article: " . $e->getMessage());
        return null;
    }
}

/**
 * Получение всех опубликованных статей
 */
function getAllArticles($limit = null, $offset = 0) {
    try {
        $pdo = getDatabaseConnection();
        
        $sql = "
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.published_at DESC, a.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->query($sql);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные для каждой статьи
        foreach ($articles as &$article) {
            $article['author'] = [
                'name' => $article['author_name'],
                'email' => $article['author_email']
            ];
            $article['category'] = $article['category_name'];
            $article['date'] = $article['published_at'] ?: $article['created_at'];
            $article['tags'] = getArticleTags($article['id']);
        }
        
        return $articles;
    } catch (PDOException $e) {
        error_log("Error getting all articles: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение статей с пагинацией (НОВАЯ ФУНКЦИЯ)
 */
function getArticlesWithPagination($page = 1, $perPage = 5) {
    try {
        $pdo = getDatabaseConnection();
        
        // Подсчитываем общее количество статей
        $countStmt = $pdo->query("
            SELECT COUNT(*) FROM articles 
            WHERE status = 'published'
        ");
        $totalArticles = $countStmt->fetchColumn();
        
        // Вычисляем OFFSET
        $offset = ($page - 1) * $perPage;
        
        // Получаем статьи для текущей страницы
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.published_at DESC, a.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные
        foreach ($articles as &$article) {
            $article['author'] = [
                'name' => $article['author_name'],
                'email' => $article['author_email']
            ];
            $article['category'] = $article['category_name'];
            $article['date'] = $article['published_at'] ?: $article['created_at'];
            $article['tags'] = getArticleTags($article['id']);
        }
        
        return [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_articles' => $totalArticles,
                'total_pages' => ceil($totalArticles / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($totalArticles / $perPage),
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < ceil($totalArticles / $perPage) ? $page + 1 : null
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting paginated articles: " . $e->getMessage());
        return [
            'articles' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total_articles' => 0,
                'total_pages' => 0,
                'has_prev' => false,
                'has_next' => false,
                'prev_page' => null,
                'next_page' => null
            ]
        ];
    }
}

/**
 * Генерация HTML для пагинации (НОВАЯ ФУНКЦИЯ)
 */
function renderPagination($pagination, $baseUrl = 'index.php', $queryParams = []) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Формируем базовый URL с параметрами
    $buildUrl = function($page) use ($baseUrl, $queryParams) {
        $params = array_merge($queryParams, ['page' => $page]);
        return $baseUrl . '?' . http_build_query($params);
    };
    
    // Кнопка "Предыдущая"
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $buildUrl($pagination['prev_page']) . '" class="pagination-btn">← Предыдущая</a>';
    }
    
    // Номера страниц (показываем до 7 страниц)
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    
    $start = max(1, $current - 3);
    $end = min($total, $current + 3);
    
    // Показываем первую страницу если она не входит в диапазон
    if ($start > 1) {
        $html .= '<a href="' . $buildUrl(1) . '" class="pagination-btn">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-dots">...</span>';
        }
    }
    
    // Показываем страницы в диапазоне
    for ($i = $start; $i <= $end; $i++) {
        $isActive = ($i === $current);
        $class = $isActive ? 'pagination-btn active' : 'pagination-btn';
        $html .= '<a href="' . $buildUrl($i) . '" class="' . $class . '">' . $i . '</a>';
    }
    
    // Показываем последнюю страницу если она не входит в диапазон
    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<span class="pagination-dots">...</span>';
        }
        $html .= '<a href="' . $buildUrl($total) . '" class="pagination-btn">' . $total . '</a>';
    }
    
    // Кнопка "Следующая"
    if ($pagination['has_next']) {
        $html .= '<a href="' . $buildUrl($pagination['next_page']) . '" class="pagination-btn">Следующая →</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Поиск статей
 */
function searchArticles($query) {
    if (empty(trim($query))) {
        return getAllArticles();
    }
    
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
            ORDER BY a.published_at DESC, a.created_at DESC
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные
        foreach ($articles as &$article) {
            $article['author'] = [
                'name' => $article['author_name'],
                'email' => $article['author_email']
            ];
            $article['category'] = $article['category_name'];
            $article['date'] = $article['published_at'] ?: $article['created_at'];
            $article['tags'] = getArticleTags($article['id']);
        }
        
        return $articles;
    } catch (PDOException $e) {
        error_log("Error searching articles: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение похожих статей (из той же категории)
 */
function getSimilarArticles($articleId, $limit = 3) {
    try {
        $pdo = getDatabaseConnection();
        
        // Сначала получаем категорию текущей статьи
        $stmt = $pdo->prepare("SELECT category_id FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $currentCategoryId = $stmt->fetchColumn();
        
        if (!$currentCategoryId) {
            return [];
        }
        
        // Получаем статьи из той же категории
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.category_id = ? 
            AND a.id != ? 
            AND a.status = 'published'
            ORDER BY a.views DESC, a.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$currentCategoryId, $articleId, $limit]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting similar articles: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение популярных статей (по просмотрам)
 */
function getPopularArticles($limit = 5) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.views DESC, a.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $articles = $stmt->fetchAll();
        
        // Дополняем данные
        foreach ($articles as &$article) {
            $article['author'] = [
                'name' => $article['author_name'],
                'email' => $article['author_email']
            ];
            $article['category'] = $article['category_name'];
            $article['date'] = $article['published_at'] ?: $article['created_at'];
            $article['tags'] = getArticleTags($article['id']);
        }
        
        return $articles;
    } catch (PDOException $e) {
        error_log("Error getting popular articles: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение последних статей
 */
function getRecentArticles($limit = 5) {
    return getAllArticles($limit);
}

// ========== CRUD ОПЕРАЦИИ ДЛЯ СТАТЕЙ ==========

/**
 * Создание новой статьи
 */
function createArticle($articleData) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO articles (title, slug, content, excerpt, author_id, category_id, reading_time, published_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $slug = generateSlug($articleData['title']);
        $publishedAt = $articleData['date'] ?? date('Y-m-d');
        $readingTime = $articleData['reading_time'] ?? calculateReadingTime($articleData['content']);
        
        $result = $stmt->execute([
            $articleData['title'],
            $slug,
            $articleData['content'],
            $articleData['excerpt'],
            $articleData['author_id'],
            $articleData['category_id'],
            $readingTime,
            $publishedAt
        ]);
        
        if ($result) {
            $articleId = $pdo->lastInsertId();
            
            // Сохраняем теги если есть
            if (!empty($articleData['tags'])) {
                saveArticleTags($articleId, $articleData['tags']);
            }
            
            return $articleId;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error creating article: " . $e->getMessage());
        return false;
    }
}

/**
 * Обновление статьи
 */
function updateArticle($id, $articleData) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            UPDATE articles 
            SET title = ?, slug = ?, content = ?, excerpt = ?, author_id = ?, 
                category_id = ?, reading_time = ?, published_at = ?
            WHERE id = ?
        ");
        
        $slug = generateSlug($articleData['title']);
        $publishedAt = $articleData['date'] ?? date('Y-m-d');
        $readingTime = $articleData['reading_time'] ?? calculateReadingTime($articleData['content']);
        
        $result = $stmt->execute([
            $articleData['title'],
            $slug,
            $articleData['content'],
            $articleData['excerpt'],
            $articleData['author_id'],
            $articleData['category_id'],
            $readingTime,
            $publishedAt,
            $id
        ]);
        
        if ($result) {
            // Обновляем теги
            $tags = $articleData['tags'] ?? [];
            saveArticleTags($id, $tags);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating article: " . $e->getMessage());
        return false;
    }
}

/**
 * Удаление статьи
 */
function deleteArticle($id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting article: " . $e->getMessage());
        return false;
    }
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С ПРОСМОТРАМИ ==========

/**
 * Увеличение просмотров статьи
 */
function incrementViews($articleId) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $stmt->execute([$articleId]);
        
        // Возвращаем новое количество просмотров
        $stmt = $pdo->prepare("SELECT views FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error incrementing views: " . $e->getMessage());
        return 0;
    }
}

// ========== ФУНКЦИИ ДЛЯ РАБОТЫ С КОММЕНТАРИЯМИ (НОВЫЕ) ==========

/**
 * Получение комментариев статьи
 */
function getArticleComments($articleId, $status = 'approved') {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM comments 
            WHERE article_id = ? AND status = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$articleId, $status]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting comments: " . $e->getMessage());
        return [];
    }
}

/**
 * Добавление нового комментария
 */
function addComment($articleId, $authorName, $authorEmail, $content) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            INSERT INTO comments (article_id, author_name, author_email, content)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$articleId, $authorName, $authorEmail, $content]);
    } catch (PDOException $e) {
        error_log("Error adding comment: " . $e->getMessage());
        return false;
    }
}

/**
 * Подсчет комментариев статьи
 */
function getCommentsCount($articleId, $status = 'approved') {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM comments 
            WHERE article_id = ? AND status = ?
        ");
        $stmt->execute([$articleId, $status]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error counting comments: " . $e->getMessage());
        return 0;
    }
}

/**
 * Модерация комментариев (для админки)
 */
function updateCommentStatus($commentId, $status) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            UPDATE comments SET status = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $commentId]);
    } catch (PDOException $e) {
        error_log("Error updating comment status: " . $e->getMessage());
        return false;
    }
}

/**
 * Получение всех комментариев для модерации
 */
function getAllComments($status = null) {
    try {
        $pdo = getDatabaseConnection();
        
        if ($status) {
            $stmt = $pdo->prepare("
                SELECT c.*, a.title as article_title 
                FROM comments c
                JOIN articles a ON c.article_id = a.id
                WHERE c.status = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("
                SELECT c.*, a.title as article_title 
                FROM comments c
                JOIN articles a ON c.article_id = a.id
                ORDER BY c.created_at DESC
            ");
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting all comments: " . $e->getMessage());
        return [];
    }
}

/**
 * Удаление комментария
 */
function deleteComment($commentId) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        return $stmt->execute([$commentId]);
    } catch (PDOException $e) {
        error_log("Error deleting comment: " . $e->getMessage());
        return false;
    }
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

/**
 * Генерация тегов HTML
 */
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

/**
 * Статистика блога
 */
function getBlogStats() {
    try {
        $pdo = getDatabaseConnection();
        
        $stats = [];
        
        // Количество статей
        $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        $stats['articles'] = $stmt->fetchColumn();
        
        // Общие просмотры
        $stmt = $pdo->query("SELECT SUM(views) FROM articles WHERE status = 'published'");
        $stats['views'] = $stmt->fetchColumn() ?: 0;
        
        // Количество авторов
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $stats['authors'] = $stmt->fetchColumn();
        
        // Количество категорий
        $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
        $stats['categories'] = $stmt->fetchColumn();
        
        // Количество комментариев
        $stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'");
        $stats['comments'] = $stmt->fetchColumn();
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting blog stats: " . $e->getMessage());
        return ['articles' => 0, 'views' => 0, 'authors' => 0, 'categories' => 0, 'comments' => 0];
    }
}

/**
 * Валидация данных статьи
 */
function validateArticleData($data) {
    $errors = [];
    
    if (empty(trim($data['title']))) {
        $errors[] = 'Заголовок обязателен';
    }
    
    if (strlen(trim($data['title'])) > 255) {
        $errors[] = 'Заголовок слишком длинный (максимум 255 символов)';
    }
    
    if (empty(trim($data['content']))) {
        $errors[] = 'Содержимое обязательно';
    }
    
    if (empty(trim($data['excerpt']))) {
        $errors[] = 'Описание обязательно';
    }
    
    if (!isset($data['author_id']) || !is_numeric($data['author_id'])) {
        $errors[] = 'Неверный автор';
    } else {
        // Проверяем существование автора
        $author = getAuthor($data['author_id']);
        if (!$author) {
            $errors[] = 'Автор не найден';
        }
    }
    
    if (!isset($data['category_id']) || !is_numeric($data['category_id'])) {
        $errors[] = 'Неверная категория';
    } else {
        // Проверяем существование категории
        $category = getCategory($data['category_id']);
        if (!$category) {
            $errors[] = 'Категория не найдена';
        }
    }
    
    return $errors;
}

/**
 * Валидация данных комментария
 */
function validateCommentData($data) {
    $errors = [];
    
    if (empty(trim($data['author_name']))) {
        $errors[] = 'Имя обязательно';
    }
    
    if (strlen(trim($data['author_name'])) > 100) {
        $errors[] = 'Имя слишком длинное (максимум 100 символов)';
    }
    
    if (empty(trim($data['author_email']))) {
        $errors[] = 'Email обязателен';
    } elseif (!validateEmail($data['author_email'])) {
        $errors[] = 'Некорректный email адрес';
    }
    
    if (empty(trim($data['content']))) {
        $errors[] = 'Комментарий не может быть пустым';
    }
    
    if (strlen(trim($data['content'])) < 10) {
        $errors[] = 'Комментарий должен содержать минимум 10 символов';
    }
    
    if (strlen(trim($data['content'])) > 1000) {
        $errors[] = 'Комментарий слишком длинный (максимум 1000 символов)';
    }
    
    return $errors;
}
?>