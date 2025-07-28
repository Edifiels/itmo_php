<?php
/**
 * API эндпоинты для категорий
 * api/categories.php
 */

use Blog\Controllers\ArticleController;
use Blog\Repositories\ArticleRepository;
use Blog\Repositories\CommentRepository;
use Blog\Repositories\UserRepository;

// Проверяем подключение к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    sendJsonResponse(null, 500, 'Ошибка подключения к базе данных');
}

// Создаем репозитории и контроллеры
$userRepository = new UserRepository($pdo);
$articleRepository = new ArticleRepository($pdo);
$commentRepository = new CommentRepository($pdo);
$articleController = new ArticleController($articleRepository, $commentRepository, $userRepository);

// GET /api/categories - получить все категории
if ($method === 'GET' && ($subPath === '' || $subPath === '/')) {
    
    $categories = $userRepository->getCategories();
    
    // Получаем статистику по каждой категории
    $formattedCategories = [];
    foreach ($categories as $category) {
        // Подсчитываем статьи в категории
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, SUM(views) as total_views 
            FROM articles 
            WHERE category_id = ? AND status = 'published'
        ");
        $stmt->execute([$category['id']]);
        $stats = $stmt->fetch();
        
        if ($apiVersion === 1) {
            $formattedCategories[] = [
                'id' => (int)$category['id'],
                'name' => $category['name'],
                'description' => $category['description'] ?? '',
                'articles_count' => (int)$stats['count']
            ];
        } else {
            $formattedCategories[] = [
                'id' => (int)$category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'] ?? '',
                'statistics' => [
                    'articles_count' => (int)$stats['count'],
                    'total_views' => (int)($stats['total_views'] ?? 0)
                ],
                'created_at' => $category['created_at'] ?? null,
                'meta' => [
                    'api_version' => $apiVersion
                ]
            ];
        }
    }
    
    sendJsonResponse([
        'categories' => $formattedCategories,
        'total' => count($formattedCategories)
    ], 200, "Найдено категорий: " . count($formattedCategories));
}

// GET /api/categories/{id} - получить категорию по ID
elseif ($method === 'GET' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $categoryId = (int)$matches[1];
    
    $categories = $userRepository->getCategories();
    $category = null;
    
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $category = $cat;
            break;
        }
    }
    
    if (!$category) {
        sendJsonResponse(null, 404, 'Категория не найдена');
    }
    
    // Получаем статистику категории
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, SUM(views) as total_views 
        FROM articles 
        WHERE category_id = ? AND status = 'published'
    ");
    $stmt->execute([$categoryId]);
    $stats = $stmt->fetch();
    
    // Получаем последние статьи категории
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as author_name 
        FROM articles a
        JOIN users u ON a.author_id = u.id
        WHERE a.category_id = ? AND a.status = 'published'
        ORDER BY a.published_at DESC
        LIMIT 5
    ");
    $stmt->execute([$categoryId]);
    $recentArticles = $stmt->fetchAll();
    
    if ($apiVersion === 1) {
        $response = [
            'id' => (int)$category['id'],
            'name' => $category['name'],
            'description' => $category['description'] ?? '',
            'articles_count' => (int)$stats['count'],
            'recent_articles' => array_map(function($article) {
                return [
                    'id' => (int)$article['id'],
                    'title' => $article['title'],
                    'author' => $article['author_name'],
                    'views' => (int)$article['views'],
                    'created' => $article['created_at']
                ];
            }, $recentArticles)
        ];
    } else {
        $response = [
            'id' => (int)$category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
            'statistics' => [
                'articles_count' => (int)$stats['count'],
                'total_views' => (int)($stats['total_views'] ?? 0)
            ],
            'recent_articles' => array_map(function($article) {
                return [
                    'id' => (int)$article['id'],
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'author' => $article['author_name'],
                    'views' => (int)$article['views'],
                    'reading_time' => (int)$article['reading_time'],
                    'published_at' => $article['published_at'],
                    'created_at' => $article['created_at']
                ];
            }, $recentArticles),
            'created_at' => $category['created_at'] ?? null,
            'meta' => [
                'api_version' => $apiVersion
            ]
        ];
    }
    
    sendJsonResponse($response, 200, 'Категория получена');
}

// GET /api/categories/{id}/articles - получить статьи категории
elseif ($method === 'GET' && preg_match('#^/(\d+)/articles$#', $subPath, $matches)) {
    $categoryId = (int)$matches[1];
    
    // Проверяем существование категории
    $categories = $userRepository->getCategories();
    $categoryExists = false;
    $categoryName = '';
    
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $categoryExists = true;
            $categoryName = $cat['name'];
            break;
        }
    }
    
    if (!$categoryExists) {
        sendJsonResponse(null, 404, 'Категория не найдена');
    }
    
    // Параметры пагинации
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    // Сортировка
    $sortField = $_GET['sort'] ?? 'published_at';
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC');
    
    $allowedSortFields = ['title', 'published_at', 'views', 'reading_time'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'published_at';
    }
    
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    // Получаем общее количество статей
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM articles 
        WHERE category_id = ? AND status = 'published'
    ");
    $countStmt->execute([$categoryId]);
    $totalArticles = $countStmt->fetchColumn();
    
    // Получаем статьи
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.name as author_name,
            u.email as author_email,
            c.name as category_name
        FROM articles a
        JOIN users u ON a.author_id = u.id
        JOIN categories c ON a.category_id = c.id
        WHERE a.category_id = ? AND a.status = 'published'
        ORDER BY a.$sortField $sortOrder
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute([$categoryId]);
    $articlesData = $stmt->fetchAll();
    
    $articles = [];
    foreach ($articlesData as $articleData) {
        // Получаем теги для каждой статьи
        $tagsStmt = $pdo->prepare("
            SELECT t.name 
            FROM tags t 
            JOIN article_tags at ON t.id = at.tag_id 
            WHERE at.article_id = ?
        ");
        $tagsStmt->execute([$articleData['id']]);
        $tags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($apiVersion === 1) {
            $articles[] = [
                'id' => (int)$articleData['id'],
                'title' => $articleData['title'],
                'content' => $articleData['content'],
                'author' => $articleData['author_name'],
                'views' => (int)$articleData['views'],
                'created' => $articleData['created_at']
            ];
        } else {
            $articles[] = [
                'id' => (int)$articleData['id'],
                'title' => $articleData['title'],
                'slug' => $articleData['slug'],
                'content' => $articleData['content'],
                'excerpt' => $articleData['excerpt'],
                'reading_time' => (int)$articleData['reading_time'],
                'views' => (int)$articleData['views'],
                'author' => [
                    'name' => $articleData['author_name'],
                    'email' => $articleData['author_email']
                ],
                'tags' => $tags,
                'timestamps' => [
                    'published_at' => $articleData['published_at'],
                    'created_at' => $articleData['created_at'],
                    'updated_at' => $articleData['updated_at']
                ],
                'meta' => [
                    'api_version' => $apiVersion,
                    'comments_count' => $commentRepository->countByArticle($articleData['id'])
                ]
            ];
        }
    }
    
    $pagination = [
        'current_page' => $page,
        'per_page' => $limit,
        'total_articles' => (int)$totalArticles,
        'total_pages' => ceil($totalArticles / $limit),
        'has_prev' => $page > 1,
        'has_next' => $page < ceil($totalArticles / $limit)
    ];
    
    sendJsonResponse([
        'category' => [
            'id' => $categoryId,
            'name' => $categoryName
        ],
        'articles' => $articles,
        'pagination' => $pagination,
        'sort' => [
            'field' => $sortField,
            'order' => $sortOrder
        ]
    ], 200, "Найдено статей в категории: " . count($articles));
}

// Метод не поддерживается
else {
    sendJsonResponse(null, 405, 'Метод не поддерживается для этого эндпоинта');
}
?>