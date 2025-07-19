<?php
// blog_data.php - основные данные блога

// Массив авторов
$authors = [
    1 => [
        'id' => 1,
        'name' => 'Анна Разработчик',
        'email' => 'anna@blog.ru',
        'bio' => 'Senior PHP разработчик с 8-летним опытом. Специализируется на создании высоконагруженных веб-приложений.',
        'avatar' => 'authors/anna.jpg',
        'social' => [
            'github' => 'https://github.com/anna-dev',
            'telegram' => '@anna_developer'
        ]
    ],
    2 => [
        'id' => 2,
        'name' => 'Дмитрий Архитектор',
        'email' => 'dmitry@blog.ru',
        'bio' => 'Технический архитектор и fullstack разработчик. Эксперт по микросервисной архитектуре.',
        'avatar' => 'authors/dmitry.jpg',
        'social' => [
            'github' => 'https://github.com/dmitry-arch',
            'linkedin' => 'https://linkedin.com/in/dmitry-architect'
        ]
    ],
    3 => [
        'id' => 3,
        'name' => 'Мария Фронтенд',
        'email' => 'maria@blog.ru',
        'bio' => 'Frontend разработчик, UI/UX дизайнер. Создает красивые и функциональные интерфейсы.',
        'avatar' => 'authors/maria.jpg',
        'social' => [
            'behance' => 'https://behance.net/maria-frontend'
        ]
    ]
];

// Массив категорий
$categories = [
    1 => ['id' => 1, 'name' => 'PHP и Backend', 'slug' => 'php-backend'],
    2 => ['id' => 2, 'name' => 'Frontend', 'slug' => 'frontend'],
    3 => ['id' => 3, 'name' => 'Базы данных', 'slug' => 'databases'],
    4 => ['id' => 4, 'name' => 'DevOps', 'slug' => 'devops'],
    5 => ['id' => 5, 'name' => 'Карьера в IT', 'slug' => 'career']
];

// Массив тегов
$tags = [
    1 => ['id' => 1, 'name' => 'PHP', 'slug' => 'php'],
    2 => ['id' => 2, 'name' => 'MySQL', 'slug' => 'mysql'],
    3 => ['id' => 3, 'name' => 'JavaScript', 'slug' => 'javascript'],
    4 => ['id' => 4, 'name' => 'Laravel', 'slug' => 'laravel'],
    5 => ['id' => 5, 'name' => 'Vue.js', 'slug' => 'vuejs'],
    6 => ['id' => 6, 'name' => 'Docker', 'slug' => 'docker'],
    7 => ['id' => 7, 'name' => 'API', 'slug' => 'api'],
    8 => ['id' => 8, 'name' => 'Безопасность', 'slug' => 'security']
];

// Массив статей (продолжение blog_data.php)
$articles = [
    1 => [
        'id' => 1,
        'title' => 'Основы PHP 8: Новые возможности и улучшения',
        'slug' => 'osnovy-php-8-novye-vozmozhnosti',
        'content' => 'PHP 8 принес множество долгожданных изменений и новых возможностей. В этой статье мы разберем ключевые нововведения: JIT-компилятор, Union Types, Named Arguments, Attributes и многое другое. JIT-компилятор значительно ускоряет выполнение математических операций и циклов. Union Types позволяют указывать несколько типов для одного параметра или возвращаемого значения.',
        'excerpt' => 'Обзор ключевых нововведений PHP 8: JIT-компилятор, Union Types, Named Arguments и другие важные улучшения.',
        'author_id' => 1,
        'category_id' => 1,
        'tag_ids' => [1, 7],
        'meta' => [
            'views' => 1247,
            'likes' => 89,
            'comments_count' => 23,
            'reading_time' => 8
        ],
        'dates' => [
            'created' => '2025-07-10 09:30:00',
            'updated' => '2025-07-11 14:15:00',
            'published' => '2025-07-10 12:00:00'
        ],
        'status' => 'published',
        'featured' => true,
        'featured_image' => 'articles/php8-features.jpg'
    ],
    
    2 => [
        'id' => 2,
        'title' => 'Создание REST API на PHP: Полное руководство',
        'slug' => 'sozdanie-rest-api-na-php',
        'content' => 'В современной веб-разработке API играют ключевую роль. В этом подробном руководстве мы создадим полноценное REST API на чистом PHP. Рассмотрим принципы REST архитектуры, правильную структуру URL, обработку HTTP методов, аутентификацию через JWT токены и обработку ошибок.',
        'excerpt' => 'Пошаговое создание REST API на PHP с примерами кода, аутентификацией и лучшими практиками.',
        'author_id' => 1,
        'category_id' => 1,
        'tag_ids' => [1, 7, 8],
        'meta' => [
            'views' => 892,
            'likes' => 67,
            'comments_count' => 15,
            'reading_time' => 12
        ],
        'dates' => [
            'created' => '2025-07-12 16:20:00',
            'updated' => '2025-07-13 10:30:00',
            'published' => '2025-07-12 18:00:00'
        ],
        'status' => 'published',
        'featured' => false,
        'featured_image' => 'articles/rest-api-php.jpg'
    ],
    
    3 => [
        'id' => 3,
        'title' => 'MySQL оптимизация: Как ускорить запросы в 10 раз',
        'slug' => 'mysql-optimizaciya-uskorenie-zaprosov',
        'content' => 'Производительность базы данных - критически важный аспект любого веб-приложения. В этой статье мы разберем продвинутые техники оптимизации MySQL: создание правильных индексов, анализ планов выполнения запросов с помощью EXPLAIN, оптимизация JOIN операций.',
        'excerpt' => 'Практические техники оптимизации MySQL для значительного увеличения производительности приложений.',
        'author_id' => 2,
        'category_id' => 3,
        'tag_ids' => [2],
        'meta' => [
            'views' => 734,
            'likes' => 56,
            'comments_count' => 19,
            'reading_time' => 10
        ],
        'dates' => [
            'created' => '2025-07-08 11:45:00',
            'updated' => '2025-07-09 09:20:00',
            'published' => '2025-07-08 15:30:00'
        ],
        'status' => 'published',
        'featured' => true,
        'featured_image' => 'articles/mysql-optimization.jpg'
    ]
];


function getAuthorById($id) {
    global $authors;
    return $authors[$id] ?? null;
}

// Получение категории по ID
function getCategoryById($id) {
    global $categories;
    return $categories[$id] ?? null;
}

// Получение тегов по массиву ID
function getTagsByIds($ids) {
    global $tags;
    return array_filter($tags, function($tag) use ($ids) {
        return in_array($tag['id'], $ids);
    });
}

// Получение статьи со всеми связанными данными
function getArticleWithRelations($articleId) {
    global $articles;
    
    if (!isset($articles[$articleId])) {
        return null;
    }
    
    $article = $articles[$articleId];
    
    // Добавляем связанные данные
    $article['author'] = getAuthorById($article['author_id']);
    $article['category'] = getCategoryById($article['category_id']);
    $article['tags'] = getTagsByIds($article['tag_ids']);
    
    return $article;
}

?>