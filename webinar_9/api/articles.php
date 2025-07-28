<?php
/**
 * API эндпоинты для статей
 * api/articles.php
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
$articleRepository = new ArticleRepository($pdo);
$commentRepository = new CommentRepository($pdo);
$userRepository = new UserRepository($pdo);
$articleController = new ArticleController($articleRepository, $commentRepository, $userRepository);

// GET /api/articles - получить все статьи
if ($method === 'GET' && ($subPath === '' || $subPath === '/')) {
    
    // Параметры пагинации
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 10))); // Максимум 50 статей за раз
    
    // Фильтры
    $filters = [];
    $params = [];
    
    if (!empty($_GET['category'])) {
        $filters[] = "c.name = ?";
        $params[] = $_GET['category'];
    }
    
    if (!empty($_GET['author'])) {
        $filters[] = "u.name = ?";
        $params[] = $_GET['author'];
    }
    
    if (!empty($_GET['search'])) {
        $filters[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($_GET['status'])) {
        $filters[] = "a.status = ?";
        $params[] = $_GET['status'];
    }
    
    // Сортировка
    $sortField = $_GET['sort'] ?? 'created_at';
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC');
    
    $allowedSortFields = ['title', 'created_at', 'views', 'reading_time'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'created_at';
    }
    
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    // Получаем статьи через контроллер
    $data = $articleController->index($page, $limit);
    
    // Применяем фильтры если есть
    if (!empty($_GET['search'])) {
        $searchData = $articleController->search($_GET['search']);
        $data['articles'] = $searchData['articles'];
        $data['pagination']['total_articles'] = $searchData['totalResults'];
    }
    
    // Форматируем ответ согласно версии API
    $articles = [];
    foreach ($data['articles'] as $article) {
        if ($apiVersion === 1) {
            // Версия 1: простой формат
            $articles[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'author' => $article->getAuthor()['name'],
                'category' => $article->getCategory(),
                'created' => $article->getCreatedAt(),
                'views' => $article->getViews()
            ];
        } else {
            // Версия 2: расширенный формат
            $articles[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'content' => $article->getContent(),
                'excerpt' => $article->getExcerpt(),
                'reading_time' => $article->getReadingTime(),
                'views' => $article->getViews(),
                'author' => $article->getAuthor(),
                'category' => $article->getCategory(),
                'tags' => $article->getTags(),
                'timestamps' => [
                    'created_at' => $article->getCreatedAt(),
                    'updated_at' => $article->getUpdatedAt()
                ],
                'meta' => [
                    'api_version' => $apiVersion,
                    'comments_count' => $commentRepository->countByArticle($article->getId())
                ]
            ];
        }
    }
    
    sendJsonResponse([
        'articles' => $articles,
        'pagination' => $data['pagination'],
        'filters' => [
            'category' => $_GET['category'] ?? null,
            'author' => $_GET['author'] ?? null,
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? 'published'
        ],
        'sort' => [
            'field' => $sortField,
            'order' => $sortOrder
        ]
    ], 200, "Найдено статей: " . count($articles));
}

// GET /api/articles/{id} - получить статью по ID
elseif ($method === 'GET' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $articleId = (int)$matches[1];
    
    $data = $articleController->show($articleId);
    
    if (!$data) {
        sendJsonResponse(null, 404, 'Статья не найдена');
    }
    
    $article = $data['article'];
    
    // Форматируем ответ согласно версии API
    if ($apiVersion === 1) {
        $response = [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'author' => $article->getAuthor()['name'],
            'category' => $article->getCategory(),
            'created' => $article->getCreatedAt(),
            'views' => $article->getViews()
        ];
    } else {
        $response = [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'slug' => $article->getSlug(),
            'content' => $article->getContent(),
            'excerpt' => $article->getExcerpt(),
            'reading_time' => $article->getReadingTime(),
            'views' => $article->getViews(),
            'author' => $article->getAuthor(),
            'category' => $article->getCategory(),
            'tags' => $article->getTags(),
            'similar_articles' => $data['similarArticles'],
            'timestamps' => [
                'created_at' => $article->getCreatedAt(),
                'updated_at' => $article->getUpdatedAt()
            ],
            'meta' => [
                'api_version' => $apiVersion,
                'comments_count' => count($data['comments'])
            ]
        ];
    }
    
    sendJsonResponse($response, 200, 'Статья получена');
}

// POST /api/articles - создать новую статью
elseif ($method === 'POST' && ($subPath === '' || $subPath === '/')) {
    $data = getJsonInput();
    
    // Валидация обязательных полей
    $requiredFields = ['title', 'content', 'author_id', 'category_id'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[] = "Поле '$field' обязательно";
        }
    }
    
    // Дополнительная валидация
    if (!empty($data['title']) && strlen($data['title']) > 255) {
        $errors[] = 'Заголовок не должен превышать 255 символов';
    }
    
    if (!empty($data['excerpt']) && strlen($data['excerpt']) > 500) {
        $errors[] = 'Описание не должно превышать 500 символов';
    }
    
    if (!empty($errors)) {
        sendJsonResponse(['errors' => $errors], 400, 'Ошибки валидации');
    }
    
    // Проверяем существование автора и категории
    $author = $userRepository->find($data['author_id']);
    if (!$author) {
        sendJsonResponse(null, 404, 'Автор не найден');
    }
    
    $categories = $userRepository->getCategories();
    $categoryExists = false;
    foreach ($categories as $category) {
        if ($category['id'] == $data['category_id']) {
            $categoryExists = true;
            break;
        }
    }
    
    if (!$categoryExists) {
        sendJsonResponse(null, 404, 'Категория не найдена');
    }
    
    // Подготавливаем данные для создания
    $articleData = [
        'title' => $data['title'],
        'content' => $data['content'],
        'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 200) . '...',
        'author_id' => $data['author_id'],
        'category_id' => $data['category_id'],
        'reading_time' => $data['reading_time'] ?? max(1, round(str_word_count(strip_tags($data['content'])) / 200)),
        'tags' => $data['tags'] ?? [],
        'date' => $data['date'] ?? date('Y-m-d')
    ];
    
    // Создаем статью
    $result = $articleController->create($articleData);
    
    if ($result['success']) {
        sendJsonResponse([
            'id' => $result['id'],
            'title' => $articleData['title']
        ], 201, $result['message']);
    } else {
        sendJsonResponse(null, 500, $result['message']);
    }
}

// PUT /api/articles/{id} - обновить статью
elseif ($method === 'PUT' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $articleId = (int)$matches[1];
    $data = getJsonInput();
    
    // Проверяем существование статьи
    $existingData = $articleController->show($articleId);
    if (!$existingData) {
        sendJsonResponse(null, 404, 'Статья не найдена');
    }
    
    // Валидация данных
    $errors = [];
    if (isset($data['title']) && (empty(trim($data['title'])) || strlen($data['title']) > 255)) {
        $errors[] = 'Заголовок должен быть от 1 до 255 символов';
    }
    
    if (isset($data['content']) && empty(trim($data['content']))) {
        $errors[] = 'Содержимое не может быть пустым';
    }
    
    if (!empty($errors)) {
        sendJsonResponse(['errors' => $errors], 400, 'Ошибки валидации');
    }
    
    // Подготавливаем данные для обновления
    $updateData = [];
    if (isset($data['title'])) $updateData['title'] = $data['title'];
    if (isset($data['content'])) {
        $updateData['content'] = $data['content'];
        $updateData['reading_time'] = max(1, round(str_word_count(strip_tags($data['content'])) / 200));
    }
    if (isset($data['excerpt'])) $updateData['excerpt'] = $data['excerpt'];
    if (isset($data['category_id'])) $updateData['category_id'] = $data['category_id'];
    if (isset($data['author_id'])) $updateData['author_id'] = $data['author_id'];
    if (isset($data['tags'])) $updateData['tags'] = $data['tags'];
    if (isset($data['date'])) $updateData['date'] = $data['date'];
    
    // Обновляем статью
    $result = $articleController->update($articleId, $updateData);
    
    if ($result['success']) {
        sendJsonResponse(['id' => $articleId], 200, $result['message']);
    } else {
        sendJsonResponse(null, 500, $result['message']);
    }
}

// DELETE /api/articles/{id} - удалить статью
elseif ($method === 'DELETE' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $articleId = (int)$matches[1];
    
    // Проверяем существование статьи
    $existingData = $articleController->show($articleId);
    if (!$existingData) {
        sendJsonResponse(null, 404, 'Статья не найдена');
    }
    
    // Удаляем статью
    $result = $articleController->delete($articleId);
    
    if ($result['success']) {
        // 204 No Content для успешного удаления
        http_response_code(204);
        exit;
    } else {
        sendJsonResponse(null, 500, $result['message']);
    }
}

// Метод не поддерживается
else {
    sendJsonResponse(null, 405, 'Метод не поддерживается для этого эндпоинта');
}
?>