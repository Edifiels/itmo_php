<?php
/**
 * Документация API
 * api/docs.php
 */

$docs = [
    'info' => [
        'title' => 'IT Blog API',
        'version' => '2.0.0',
        'description' => 'RESTful API для блога о веб-разработке',
        'contact' => [
            'email' => 'api@blog.ru'
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT'
        ]
    ],
    'servers' => [
        [
            'url' => 'http://localhost/webinar_9/api',
            'description' => 'Development server'
        ]
    ],
    'endpoints' => [
        'articles' => [
            'GET /articles' => [
                'description' => 'Получить список всех статей с поддержкой пагинации, поиска и фильтрации',
                'parameters' => [
                    'page' => 'Номер страницы (по умолчанию: 1)',
                    'limit' => 'Количество статей на странице (по умолчанию: 10, максимум: 50)',
                    'search' => 'Поисковый запрос по заголовку, содержимому и описанию',
                    'category' => 'Фильтр по названию категории',
                    'author' => 'Фильтр по имени автора',
                    'sort' => 'Поле для сортировки (title, created_at, views, reading_time)',
                    'order' => 'Порядок сортировки (ASC, DESC)'
                ],
                'example_request' => 'GET /articles?page=1&limit=5&search=php&sort=views&order=desc',
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Найдено статей: 5',
                    'data' => [
                        'articles' => [
                            [
                                'id' => 1,
                                'title' => 'Основы PHP',
                                'slug' => 'osnovy-php',
                                'content' => 'Полное содержимое статьи...',
                                'excerpt' => 'Введение в PHP программирование',
                                'reading_time' => 8,
                                'views' => 157,
                                'author' => [
                                    'name' => 'Анна Разработчик',
                                    'email' => 'anna@blog.ru'
                                ],
                                'category' => 'PHP и Backend',
                                'tags' => ['PHP', 'Backend'],
                                'timestamps' => [
                                    'created_at' => '2025-07-24 12:00:00',
                                    'updated_at' => '2025-07-24 12:00:00'
                                ]
                            ]
                        ],
                        'pagination' => [
                            'current_page' => 1,
                            'per_page' => 5,
                            'total_articles' => 25,
                            'total_pages' => 5,
                            'has_prev' => false,
                            'has_next' => true
                        ]
                    ]
                ]
            ],
            'GET /articles/{id}' => [
                'description' => 'Получить статью по ID с увеличением счетчика просмотров',
                'parameters' => [
                    'id' => 'ID статьи (обязательный)'
                ],
                'example_request' => 'GET /articles/1',
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Статья получена',
                    'data' => [
                        'id' => 1,
                        'title' => 'Основы PHP',
                        'content' => 'Полное содержимое статьи...',
                        'author' => [
                            'name' => 'Анна Разработчик',
                            'email' => 'anna@blog.ru'
                        ],
                        'similar_articles' => [],
                        'meta' => [
                            'comments_count' => 3
                        ]
                    ]
                ]
            ],
            'POST /articles' => [
                'description' => 'Создать новую статью',
                'request_body' => [
                    'title' => 'Заголовок статьи (обязательный, максимум 255 символов)',
                    'content' => 'Содержимое статьи (обязательное)',
                    'excerpt' => 'Краткое описание (опционально, максимум 500 символов)',
                    'author_id' => 'ID автора (обязательный)',
                    'category_id' => 'ID категории (обязательный)',
                    'tags' => 'Массив тегов (опционально)',
                    'reading_time' => 'Время чтения в минутах (опционально, автоматически рассчитывается)',
                    'date' => 'Дата публикации в формате Y-m-d (опционально, по умолчанию сегодня)'
                ],
                'example_request' => [
                    'title' => 'Новая статья о REST API',
                    'content' => 'Содержимое статьи о создании REST API на PHP...',
                    'excerpt' => 'Изучаем создание REST API',
                    'author_id' => 1,
                    'category_id' => 1,
                    'tags' => ['API', 'REST', 'PHP']
                ],
                'example_response' => [
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Статья успешно создана с ID: 6',
                    'data' => [
                        'id' => 6,
                        'title' => 'Новая статья о REST API'
                    ]
                ]
            ],
            'PUT /articles/{id}' => [
                'description' => 'Обновить существующую статью',
                'parameters' => [
                    'id' => 'ID статьи (обязательный)'
                ],
                'request_body' => [
                    'title' => 'Новый заголовок (опционально)',
                    'content' => 'Новое содержимое (опционально)',
                    'excerpt' => 'Новое описание (опционально)',
                    'category_id' => 'Новая категория (опционально)',
                    'author_id' => 'Новый автор (опционально)',
                    'tags' => 'Новые теги (опционально)',
                    'date' => 'Новая дата публикации (опционально)'
                ],
                'example_request' => [
                    'title' => 'Обновленный заголовок',
                    'excerpt' => 'Обновленное описание статьи'
                ],
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Статья ID 1 успешно обновлена',
                    'data' => ['id' => 1]
                ]
            ],
            'DELETE /articles/{id}' => [
                'description' => 'Удалить статью',
                'parameters' => [
                    'id' => 'ID статьи (обязательный)'
                ],
                'example_request' => 'DELETE /articles/1',
                'example_response' => 'HTTP 204 No Content (пустое тело ответа)'
            ]
        ],
        'comments' => [
            'GET /comments' => [
                'description' => 'Получить комментарии с фильтрацией по статье и статусу',
                'parameters' => [
                    'article_id' => 'ID статьи для фильтрации комментариев (опционально)',
                    'status' => 'Статус комментариев: pending, approved, rejected (по умолчанию: approved)',
                    'page' => 'Номер страницы (по умолчанию: 1)',
                    'limit' => 'Количество комментариев на странице (по умолчанию: 20, максимум: 50)'
                ],
                'example_request' => 'GET /comments?article_id=1&status=approved',
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'data' => [
                        'comments' => [
                            [
                                'id' => 1,
                                'article_id' => 1,
                                'author' => [
                                    'name' => 'Петр Программист',
                                    'email' => 'petr@example.com'
                                ],
                                'content' => 'Отличная статья!',
                                'status' => 'approved',
                                'timestamps' => [
                                    'created_at' => '2025-07-24 15:30:00',
                                    'updated_at' => '2025-07-24 15:30:00'
                                ]
                            ]
                        ],
                        'total' => 1
                    ]
                ]
            ],
            'POST /comments' => [
                'description' => 'Добавить новый комментарий (отправляется на модерацию)',
                'request_body' => [
                    'article_id' => 'ID статьи (обязательный)',
                    'author_name' => 'Имя автора (обязательное, максимум 100 символов)',
                    'author_email' => 'Email автора (обязательный, корректный email)',
                    'content' => 'Текст комментария (обязательный, от 10 до 1000 символов)'
                ],
                'example_request' => [
                    'article_id' => 1,
                    'author_name' => 'Иван Иванов',
                    'author_email' => 'ivan@example.com',
                    'content' => 'Спасибо за подробное объяснение! Очень полезная статья.'
                ],
                'example_response' => [
                    'status' => 'success',
                    'code' => 201,
                    'message' => 'Спасибо! Ваш комментарий отправлен на модерацию.',
                    'data' => [
                        'article_id' => 1,
                        'author_name' => 'Иван Иванов',
                        'status' => 'pending'
                    ]
                ]
            ],
            'PUT /comments/{id}' => [
                'description' => 'Обновить статус комментария (модерация)',
                'parameters' => [
                    'id' => 'ID комментария (обязательный)'
                ],
                'request_body' => [
                    'status' => 'Новый статус: pending, approved, rejected (обязательный)'
                ],
                'example_request' => [
                    'status' => 'approved'
                ],
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Комментарий одобрен',
                    'data' => [
                        'id' => 1,
                        'status' => 'approved'
                    ]
                ]
            ]
        ],
        'categories' => [
            'GET /categories' => [
                'description' => 'Получить список всех категорий со статистикой',
                'example_request' => 'GET /categories',
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'data' => [
                        'categories' => [
                            [
                                'id' => 1,
                                'name' => 'PHP и Backend',
                                'slug' => 'php-backend',
                                'description' => 'Статьи о серверной разработке на PHP',
                                'statistics' => [
                                    'articles_count' => 15,
                                    'total_views' => 1250
                                ]
                            ]
                        ],
                        'total' => 4
                    ]
                ]
            ],
            'GET /categories/{id}/articles' => [
                'description' => 'Получить статьи определенной категории с пагинацией',
                'parameters' => [
                    'id' => 'ID категории (обязательный)',
                    'page' => 'Номер страницы (по умолчанию: 1)',
                    'limit' => 'Количество статей на странице (по умолчанию: 10, максимум: 50)',
                    'sort' => 'Поле для сортировки (title, published_at, views, reading_time)',
                    'order' => 'Порядок сортировки (ASC, DESC)'
                ],
                'example_request' => 'GET /categories/1/articles?page=1&limit=5&sort=views&order=desc',
                'example_response' => [
                    'status' => 'success',
                    'code' => 200,
                    'data' => [
                        'category' => [
                            'id' => 1,
                            'name' => 'PHP и Backend'
                        ],
                        'articles' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'per_page' => 5,
                            'total_articles' => 15,
                            'total_pages' => 3
                        ]
                    ]
                ]
            ]
        ]
    ],
    'versioning' => [
        'description' => 'API поддерживает версионирование для обратной совместимости',
        'methods' => [
            'header' => 'Accept: application/vnd.blog.v1+json',
            'url' => '/api/v1/articles',
            'query' => '/api/articles?version=1'
        ],
        'versions' => [
            'v1' => 'Упрощенный формат ответов, базовая функциональность',
            'v2' => 'Расширенный формат с дополнительными полями и метаданными (текущая)'
        ]
    ],
    'error_codes' => [
        200 => 'OK - Запрос выполнен успешно',
        201 => 'Created - Ресурс создан',
        204 => 'No Content - Успешно, но нет содержимого для возврата',
        400 => 'Bad Request - Ошибка в запросе клиента (валидация, некорректный JSON)',
        404 => 'Not Found - Ресурс не найден',
        405 => 'Method Not Allowed - HTTP метод не поддерживается для данного эндпоинта',
        422 => 'Unprocessable Entity - Ошибки бизнес-логики',
        429 => 'Too Many Requests - Превышен лимит запросов',
        500 => 'Internal Server Error - Внутренняя ошибка сервера'
    ],
    'examples' => [
        'curl_examples' => [
            'get_articles' => 'curl -X GET "http://localhost/webinar_9/api/articles?limit=5"',
            'get_article' => 'curl -X GET "http://localhost/webinar_9/api/articles/1"',
            'create_article' => 'curl -X POST "http://localhost/webinar_9/api/articles" -H "Content-Type: application/json" -d \'{"title":"Test Article","content":"Content here","author_id":1,"category_id":1}\'',
            'update_article' => 'curl -X PUT "http://localhost/webinar_9/api/articles/1" -H "Content-Type: application/json" -d \'{"title":"Updated Title"}\'',
            'delete_article' => 'curl -X DELETE "http://localhost/webinar_9/api/articles/1"',
            'add_comment' => 'curl -X POST "http://localhost/webinar_9/api/comments" -H "Content-Type: application/json" -d \'{"article_id":1,"author_name":"John","author_email":"john@example.com","content":"Great article!"}\'',
            'get_categories' => 'curl -X GET "http://localhost/webinar_9/api/categories"'
        ]
    ]
];

// Проверяем, запрошен ли JSON формат
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if (strpos($acceptHeader, 'application/json') !== false || isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Иначе отображаем HTML документацию
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $docs['info']['title'] ?> v<?= $docs['info']['version'] ?> - Документация</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; }
        .header h1 { margin: 0 0 0.5rem 0; font-size: 2.5rem; }
        .header p { margin: 0; opacity: 0.9; font-size: 1.1rem; }
        .version-badge { background: rgba(255,255,255,0.2); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; margin-top: 1rem; display: inline-block; }
        .endpoint { background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin: 1rem 0; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .method { display: inline-block; padding: 4px 12px; color: white; border-radius: 4px; font-weight: bold; margin-right: 1rem; font-size: 0.9rem; }
        .get { background: #28a745; }
        .post { background: #007bff; }
        .put { background: #ffc107; color: black; }
        .delete { background: #dc3545; }
        .code { background: #f8f9fa; padding: 1rem; border-radius: 4px; border-left: 4px solid #007bff; overflow-x: auto; margin: 1rem 0; }
        pre { margin: 0; font-size: 0.9rem; }
        .params { background: #e8f4f8; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
        .error-codes { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; }
        .toc { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .toc ul { margin: 0; padding-left: 1.5rem; }
        .toc a { text-decoration: none; color: #007bff; }
        .toc a:hover { text-decoration: underline; }
        .section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
        .feature-card { background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #667eea; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .version-info { background: linear-gradient(135deg, #e6fffa 0%, #f0fff4 100%); padding: 1.5rem; border-radius: 8px; border: 1px solid #b2f5ea; margin: 1rem 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📚 <?= $docs['info']['title'] ?></h1>
        <p><?= $docs['info']['description'] ?></p>
        <span class="version-badge">Версия <?= $docs['info']['version'] ?></span>
        <div style="margin-top: 1rem;">
            <strong>Base URL:</strong> <code style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 4px;"><?= $docs['servers'][0]['url'] ?></code>
        </div>
    </div>

    <div class="toc">
        <h2>📋 Содержание</h2>
        <ul>
            <li><a href="#overview">Обзор API</a></li>
            <li><a href="#versioning">Версионирование</a></li>
            <li><a href="#endpoints">Эндпоинты</a>
                <ul>
                    <li><a href="#articles-api">Статьи</a></li>
                    <li><a href="#comments-api">Комментарии</a></li>
                    <li><a href="#categories-api">Категории</a></li>
                </ul>
            </li>
            <li><a href="#errors">Коды ошибок</a></li>
            <li><a href="#examples">Примеры с cURL</a></li>
        </ul>
    </div>

    <section class="section" id="overview">
        <h2>🚀 Обзор API</h2>
        <p>IT Blog API предоставляет RESTful интерфейс для управления статьями блога, комментариями и категориями. API поддерживает стандартные HTTP методы и возвращает данные в формате JSON.</p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h3>📄 CRUD для статей</h3>
                <p>Полный набор операций создания, чтения, обновления и удаления статей с поддержкой тегов, категорий и авторов.</p>
            </div>
            <div class="feature-card">
                <h3>💬 Система комментариев</h3>
                <p>Добавление комментариев с модерацией, изменение статусов и получение комментариев по статьям.</p>
            </div>
            <div class="feature-card">
                <h3>🔍 Поиск и фильтрация</h3>
                <p>Мощные возможности поиска по содержимому, фильтрации по категориям, авторам и сортировки результатов.</p>
            </div>
            <div class="feature-card">
                <h3>📊 Пагинация</h3>
                <p>Все списки поддерживают постраничный вывод с настраиваемым количеством элементов на странице.</p>
            </div>
        </div>

        <h3>Формат ответов:</h3>
        <div class="code">
            <pre>{
  "status": "success|error",
  "code": 200,
  "message": "Описание результата",
  "data": { ... },
  "timestamp": "2025-07-28T12:00:00+00:00"
}</pre>
        </div>
    </section>

    <section class="section" id="versioning">
        <h2>🔄 Версионирование</h2>
        <div class="version-info">
            <p><strong>Текущая версия:</strong> v<?= $docs['info']['version'] ?></p>
            <p>API поддерживает версионирование для обеспечения обратной совместимости. Вы можете указать версию несколькими способами:</p>
        </div>
        
        <h3>Способы указания версии:</h3>
        <div class="code">
            <pre># Через заголовок Accept (рекомендуется)
curl -H "Accept: application/vnd.blog.v1+json" /api/articles

# Через параметр запроса
curl /api/articles?version=1

# Через URL (если поддерживается)
curl /api/v1/articles</pre>
        </div>

        <h3>Различия версий:</h3>
        <ul>
            <li><strong>v1:</strong> Упрощенный формат ответов, базовая функциональность</li>
            <li><strong>v2:</strong> Расширенный формат с дополнительными полями и метаданными (текущая)</li>
        </ul>
    </section>

    <section class="section" id="endpoints">
        <h2>🔗 Эндпоинты</h2>

        <h3 id="articles-api">📚 Статьи</h3>
        
        <?php foreach ($docs['endpoints']['articles'] as $endpoint => $info): ?>
        <?php 
        $method = explode(' ', $endpoint)[0];
        $path = explode(' ', $endpoint)[1];
        ?>
        
        <div class="endpoint">
            <h4>
                <span class="method <?= strtolower($method) ?>"><?= $method ?></span>
                <code><?= $path ?></code>
            </h4>
            
            <p><?= $info['description'] ?></p>

            <?php if (isset($info['parameters'])): ?>
            <h5>Параметры запроса:</h5>
            <div class="params">
                <?php foreach ($info['parameters'] as $param => $description): ?>
                <p><code><?= $param ?></code> - <?= $description ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($info['request_body'])): ?>
            <h5>Тело запроса (JSON):</h5>
            <div class="params">
                <?php foreach ($info['request_body'] as $field => $description): ?>
                <p><code><?= $field ?></code> - <?= $description ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h5>Пример запроса:</h5>
            <div class="code">
                <pre><?= is_array($info['example_request']) ? json_encode($info['example_request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $info['example_request'] ?></pre>
            </div>

            <h5>Пример ответа:</h5>
            <div class="code">
                <pre><?= is_array($info['example_response']) ? json_encode($info['example_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $info['example_response'] ?></pre>
            </div>
        </div>
        <?php endforeach; ?>

        <h3 id="comments-api">💬 Комментарии</h3>
        
        <?php foreach ($docs['endpoints']['comments'] as $endpoint => $info): ?>
        <?php 
        $method = explode(' ', $endpoint)[0];
        $path = explode(' ', $endpoint)[1];
        ?>
        
        <div class="endpoint">
            <h4>
                <span class="method <?= strtolower($method) ?>"><?= $method ?></span>
                <code><?= $path ?></code>
            </h4>
            
            <p><?= $info['description'] ?></p>

            <?php if (isset($info['parameters'])): ?>
            <h5>Параметры запроса:</h5>
            <div class="params">
                <?php foreach ($info['parameters'] as $param => $description): ?>
                <p><code><?= $param ?></code> - <?= $description ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($info['request_body'])): ?>
            <h5>Тело запроса (JSON):</h5>
            <div class="params">
                <?php foreach ($info['request_body'] as $field => $description): ?>
                <p><code><?= $field ?></code> - <?= $description ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h5>Пример запроса:</h5>
            <div class="code">
                <pre><?= is_array($info['example_request']) ? json_encode($info['example_request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $info['example_request'] ?></pre>
            </div>

            <h5>Пример ответа:</h5>
            <div class="code">
                <pre><?= is_array($info['example_response']) ? json_encode($info['example_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $info['example_response'] ?></pre>
            </div>
        </div>
        <?php endforeach; ?>

        <h3 id="categories-api">📁 Категории</h3>
        
        <?php foreach ($docs['endpoints']['categories'] as $endpoint => $info): ?>
        <?php 
        $method = explode(' ', $endpoint)[0];
        $path = explode(' ', $endpoint)[1];
        ?>
        
        <div class="endpoint">
            <h4>
                <span class="method <?= strtolower($method) ?>"><?= $method ?></span>
                <code><?= $path ?></code>
            </h4>
            
            <p><?= $info['description'] ?></p>

            <?php if (isset($info['parameters'])): ?>
            <h5>Параметры запроса:</h5>
            <div class="params">
                <?php foreach ($info['parameters'] as $param => $description): ?>
                <p><code><?= $param ?></code> - <?= $description ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h5>Пример запроса:</h5>
            <div class="code">
                <pre><?= is_array($info['example_request']) ? json_encode($info['example_request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $info['example_request'] ?></pre>
            </div>

            <h5>Пример ответа:</h5>
            <div class="code">
                <pre><?= is_array($info['example_response']) ? json_encode($info['example_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $info['example_response'] ?></pre>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <section class="section" id="errors">
        <h2>⚠️ Коды ошибок</h2>
        <div class="error-codes">
            <?php foreach ($docs['error_codes'] as $code => $description): ?>
            <div><strong><?= $code ?></strong></div>
            <div><?= $description ?></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section" id="examples">
        <h2>💻 Примеры с cURL</h2>
        
        <h3>Работа со статьями:</h3>
        <div class="code">
            <pre># Получение списка статей
<?= $docs['examples']['curl_examples']['get_articles'] ?>

# Получение статьи по ID
<?= $docs['examples']['curl_examples']['get_article'] ?>

# Создание новой статьи
<?= $docs['examples']['curl_examples']['create_article'] ?>

# Обновление статьи
<?= $docs['examples']['curl_examples']['update_article'] ?>

# Удаление статьи
<?= $docs['examples']['curl_examples']['delete_article'] ?></pre>
        </div>

        <h3>Работа с комментариями:</h3>
        <div class="code">
            <pre># Добавление комментария
<?= $docs['examples']['curl_examples']['add_comment'] ?></pre>
        </div>

        <h3>Работа с категориями:</h3>
        <div class="code">
            <pre># Получение категорий
<?= $docs['examples']['curl_examples']['get_categories'] ?></pre>
        </div>

        <h3>🧪 Интерактивное тестирование:</h3>
        <p>Для интерактивного тестирования API откройте файл <code><a href="../api_test.html" target="_blank">api_test.html</a></code> в браузере.</p>
    </section>

    <footer style="margin-top: 3rem; padding: 2rem 0; border-top: 1px solid #e2e8f0; text-align: center; color: #718096;">
        <p>📧 По вопросам API обращайтесь: <?= $docs['info']['contact']['email'] ?></p>
        <p>🔗 <a href="<?= $docs['servers'][0]['url'] ?>">API Endpoint</a> | 
           <a href="<?= $docs['servers'][0]['url'] ?>/docs?format=json">JSON Documentation</a> |
           <a href="../api_test.html">Тестирование API</a></p>
        <p style="margin-top: 1rem; font-size: 0.9rem;">
            Создано для обучения веб-разработке • Лицензия: <?= $docs['info']['license']['name'] ?>
        </p>
    </footer>
</body>
</html>