<?php
/**
 * API эндпоинты для комментариев
 * api/comments.php
 */

use Blog\Controllers\CommentController;
use Blog\Repositories\CommentRepository;

// Проверяем подключение к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    sendJsonResponse(null, 500, 'Ошибка подключения к базе данных');
}

// Создаем репозиторий и контроллер
$commentRepository = new CommentRepository($pdo);
$commentController = new CommentController($commentRepository);

// GET /api/comments - получить все комментарии или комментарии статьи
if ($method === 'GET' && ($subPath === '' || $subPath === '/')) {
    
    // Параметры
    $articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
    $status = $_GET['status'] ?? 'approved';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    
    if ($articleId) {
        // Получаем комментарии конкретной статьи
        $comments = $commentRepository->findByArticle($articleId, $status);
        
        $formattedComments = [];
        foreach ($comments as $comment) {
            if ($apiVersion === 1) {
                $formattedComments[] = [
                    'id' => $comment->getId(),
                    'author' => $comment->getAuthorName(),
                    'content' => $comment->getContent(),
                    'created' => $comment->getCreatedAt()
                ];
            } else {
                $formattedComments[] = [
                    'id' => $comment->getId(),
                    'article_id' => $comment->getArticleId(),
                    'author' => [
                        'name' => $comment->getAuthorName(),
                        'email' => $comment->getAuthorEmail()
                    ],
                    'content' => $comment->getContent(),
                    'status' => $comment->getStatus(),
                    'timestamps' => [
                        'created_at' => $comment->getCreatedAt(),
                        'updated_at' => $comment->getUpdatedAt()
                    ],
                    'meta' => [
                        'api_version' => $apiVersion
                    ]
                ];
            }
        }
        
        sendJsonResponse([
            'comments' => $formattedComments,
            'article_id' => $articleId,
            'status_filter' => $status,
            'total' => count($formattedComments)
        ], 200, "Найдено комментариев: " . count($formattedComments));
        
    } else {
        // Получаем все комментарии (для модерации)
        $data = $commentController->getForModeration($status);
        $comments = $data['comments'];
        $stats = $data['stats'];
        
        $formattedComments = [];
        foreach ($comments as $comment) {
            if ($apiVersion === 1) {
                $formattedComments[] = [
                    'id' => $comment->getId(),
                    'article_id' => $comment->getArticleId(),
                    'author' => $comment->getAuthorName(),
                    'content' => $comment->getContent(),
                    'status' => $comment->getStatus(),
                    'created' => $comment->getCreatedAt()
                ];
            } else {
                $formattedComments[] = [
                    'id' => $comment->getId(),
                    'article_id' => $comment->getArticleId(),
                    'article_title' => $comment->getArticleTitle(),
                    'author' => [
                        'name' => $comment->getAuthorName(),
                        'email' => $comment->getAuthorEmail()
                    ],
                    'content' => $comment->getContent(),
                    'status' => $comment->getStatus(),
                    'timestamps' => [
                        'created_at' => $comment->getCreatedAt(),
                        'updated_at' => $comment->getUpdatedAt()
                    ],
                    'meta' => [
                        'api_version' => $apiVersion
                    ]
                ];
            }
        }
        
        sendJsonResponse([
            'comments' => $formattedComments,
            'statistics' => $stats,
            'status_filter' => $status,
            'total' => count($formattedComments)
        ], 200, "Комментарии получены");
    }
}

// GET /api/comments/{id} - получить комментарий по ID
elseif ($method === 'GET' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $commentId = (int)$matches[1];
    
    // Получаем все комментарии и ищем нужный
    $allComments = $commentRepository->findAll();
    $comment = null;
    
    foreach ($allComments as $c) {
        if ($c->getId() == $commentId) {
            $comment = $c;
            break;
        }
    }
    
    if (!$comment) {
        sendJsonResponse(null, 404, 'Комментарий не найден');
    }
    
    if ($apiVersion === 1) {
        $response = [
            'id' => $comment->getId(),
            'article_id' => $comment->getArticleId(),
            'author' => $comment->getAuthorName(),
            'content' => $comment->getContent(),
            'status' => $comment->getStatus(),
            'created' => $comment->getCreatedAt()
        ];
    } else {
        $response = [
            'id' => $comment->getId(),
            'article_id' => $comment->getArticleId(),
            'article_title' => $comment->getArticleTitle(),
            'author' => [
                'name' => $comment->getAuthorName(),
                'email' => $comment->getAuthorEmail()
            ],
            'content' => $comment->getContent(),
            'status' => $comment->getStatus(),
            'timestamps' => [
                'created_at' => $comment->getCreatedAt(),
                'updated_at' => $comment->getUpdatedAt()
            ],
            'meta' => [
                'api_version' => $apiVersion
            ]
        ];
    }
    
    sendJsonResponse($response, 200, 'Комментарий получен');
}

// POST /api/comments - создать новый комментарий
elseif ($method === 'POST' && ($subPath === '' || $subPath === '/')) {
    $data = getJsonInput();
    
    // Валидация обязательных полей
    $requiredFields = ['article_id', 'author_name', 'author_email', 'content'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[] = "Поле '$field' обязательно";
        }
    }
    
    // Дополнительная валидация
    if (!empty($data['author_email']) && !filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email адрес';
    }
    
    if (!empty($data['content']) && strlen($data['content']) < 10) {
        $errors[] = 'Комментарий должен содержать минимум 10 символов';
    }
    
    if (!empty($data['content']) && strlen($data['content']) > 1000) {
        $errors[] = 'Комментарий не должен превышать 1000 символов';
    }
    
    if (!empty($errors)) {
        sendJsonResponse(['errors' => $errors], 400, 'Ошибки валидации');
    }
    
    // Добавляем комментарий
    $result = $commentController->add(
        $data['article_id'],
        $data['author_name'],
        $data['author_email'],
        $data['content']
    );
    
    if ($result['success']) {
        sendJsonResponse([
            'article_id' => $data['article_id'],
            'author_name' => $data['author_name'],
            'status' => 'pending'
        ], 201, $result['message']);
    } else {
        sendJsonResponse(null, 400, $result['message']);
    }
}

// PUT /api/comments/{id} - обновить статус комментария
elseif ($method === 'PUT' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $commentId = (int)$matches[1];
    $data = getJsonInput();
    
    // Проверяем существование комментария
    $allComments = $commentRepository->findAll();
    $commentExists = false;
    
    foreach ($allComments as $c) {
        if ($c->getId() == $commentId) {
            $commentExists = true;
            break;
        }
    }
    
    if (!$commentExists) {
        sendJsonResponse(null, 404, 'Комментарий не найден');
    }
    
    // Валидация статуса
    if (!isset($data['status']) || !in_array($data['status'], ['pending', 'approved', 'rejected'])) {
        sendJsonResponse(['errors' => ['Статус должен быть: pending, approved или rejected']], 400, 'Ошибка валидации');
    }
    
    // Обновляем статус
    $result = null;
    if ($data['status'] === 'approved') {
        $result = $commentController->approve($commentId);
    } elseif ($data['status'] === 'rejected') {
        $result = $commentController->reject($commentId);
    } else {
        // Возвращаем в pending статус
        $commentRepository->updateStatus($commentId, 'pending');
        $result = ['success' => true, 'message' => 'Комментарий возвращен на модерацию'];
    }
    
    if ($result['success']) {
        sendJsonResponse(['id' => $commentId, 'status' => $data['status']], 200, $result['message']);
    } else {
        sendJsonResponse(null, 500, $result['message']);
    }
}

// DELETE /api/comments/{id} - удалить комментарий
elseif ($method === 'DELETE' && preg_match('#^/(\d+)$#', $subPath, $matches)) {
    $commentId = (int)$matches[1];
    
    // Проверяем существование комментария
    $allComments = $commentRepository->findAll();
    $commentExists = false;
    
    foreach ($allComments as $c) {
        if ($c->getId() == $commentId) {
            $commentExists = true;
            break;
        }
    }
    
    if (!$commentExists) {
        sendJsonResponse(null, 404, 'Комментарий не найден');
    }
    
    // Удаляем комментарий
    $result = $commentController->delete($commentId);
    
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