<?php
// data.php - Система работы с файлами данных

// Создание директорий если их нет
function createDataDirectories() {
    $dirs = ['data', 'data/articles'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Создание базовых файлов с данными при первом запуске
function initializeData() {
    createDataDirectories();
    
    // Создаем файл авторов если его нет
    if (!file_exists('data/authors.json')) {
        $authors = [
            1 => ['name' => 'Анна Разработчик', 'email' => 'anna@blog.ru', 'bio' => 'Senior PHP разработчик с 8-летним опытом'],
            2 => ['name' => 'Дмитрий Архитектор', 'email' => 'dmitry@blog.ru', 'bio' => 'Технический архитектор и fullstack разработчик'],
            3 => ['name' => 'Мария Фронтенд', 'email' => 'maria@blog.ru', 'bio' => 'Frontend разработчик, UI/UX дизайнер']
        ];
        saveJsonFile('data/authors.json', $authors);
    }
    
    // Создаем файл категорий если его нет
    if (!file_exists('data/categories.json')) {
        $categories = [
            1 => 'PHP и Backend',
            2 => 'Frontend', 
            3 => 'Базы данных',
            4 => 'DevOps'
        ];
        saveJsonFile('data/categories.json', $categories);
    }
    
    // Создаем статьи если их нет
    createDefaultArticles();
}

// Создание статей по умолчанию
function createDefaultArticles() {
    $defaultArticles = [
        1 => [
            'title' => 'Основы PHP 8: Новые возможности',
            'content' => 'PHP 8 принес множество долгожданных изменений. JIT-компилятор значительно ускоряет выполнение кода. Union Types позволяют указывать несколько типов для параметра. Named Arguments делают код более читаемым. Attributes заменяют комментарии-аннотации. Match expression - более мощная альтернатива switch. Nullsafe operator упрощает работу с цепочками вызовов. Все эти нововведения делают PHP более современным и производительным языком программирования.',
            'excerpt' => 'Обзор ключевых нововведений PHP 8: JIT, Union Types, Named Arguments',
            'author_id' => 1,
            'category_id' => 1,
            'date' => '2025-07-15',
            'reading_time' => 8,
            'tags' => ['PHP', 'Backend']
        ],
        2 => [
            'title' => 'Создание REST API на PHP',
            'content' => 'REST API - основа современных веб-приложений. Начнем с базовой структуры: создадим endpoints для GET, POST, PUT, DELETE операций. Важно правильно обрабатывать HTTP заголовки и коды ответов. Аутентификация через JWT токены обеспечит безопасность. Валидация входных данных защитит от ошибок. CORS настройки позволят работать с фронтендом. Документирование API через OpenAPI стандарт поможет другим разработчикам.',
            'excerpt' => 'Пошаговое создание REST API на PHP с примерами кода',
            'author_id' => 1,
            'category_id' => 1,
            'date' => '2025-07-12',
            'reading_time' => 12,
            'tags' => ['PHP', 'API', 'Backend']
        ],
        3 => [
            'title' => 'MySQL оптимизация запросов',
            'content' => 'Производительность БД критически важна. Индексы - первый шаг к оптимизации. EXPLAIN покажет план выполнения запроса. Избегайте SELECT * в продакшене. JOIN операции требуют особого внимания к индексам. Денормализация иногда оправдана. Партицирование таблиц для больших объемов данных. Мониторинг slow query log выявит проблемные места. Кеширование на уровне приложения уменьшит нагрузку на БД.',
            'excerpt' => 'Техники оптимизации MySQL для увеличения производительности',
            'author_id' => 2,
            'category_id' => 3,
            'date' => '2025-07-08',
            'reading_time' => 10,
            'tags' => ['MySQL', 'Оптимизация']
        ],
        4 => [
            'title' => 'JavaScript ES2024: Новинки года',
            'content' => 'JavaScript продолжает развиваться. Array.with() позволяет создавать новые массивы с измененными элементами. Object.groupBy() упрощает группировку данных. Promise.withResolvers() дает больше контроля над промисами. Temporal API наконец заменит Date. Import attributes улучшают работу с модулями. Decorators стандартизируются. Эти нововведения делают JavaScript еще более мощным и удобным для разработки.',
            'excerpt' => 'Обзор новых возможностей JavaScript ES2024',
            'author_id' => 3,
            'category_id' => 2,
            'date' => '2025-07-05',
            'reading_time' => 6,
            'tags' => ['JavaScript', 'Frontend']
        ]
    ];
    
    foreach ($defaultArticles as $id => $article) {
        if (!file_exists("data/articles/{$id}.json")) {
            saveJsonFile("data/articles/{$id}.json", $article);
        }
    }
}

// Сохранение JSON файла
function saveJsonFile($filename, $data) {
    return file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Загрузка JSON файла
function loadJsonFile($filename, $default = []) {
    if (!file_exists($filename)) {
        return $default;
    }
    
    $content = file_get_contents($filename);
    return json_decode($content, true) ?: $default;
}

// Получение всех авторов
function getAuthors() {
    return loadJsonFile('data/authors.json', []);
}

// Получение всех категорий
function getCategories() {
    return loadJsonFile('data/categories.json', []);
}

// Получение списка всех статей (только ID)
function getArticleIds() {
    if (!is_dir('data/articles')) {
        return [];
    }
    
    $files = glob('data/articles/*.json');
    $ids = [];
    
    foreach ($files as $file) {
        $id = (int) basename($file, '.json');
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    
    return $ids;
}

// Загрузка статьи из файла
function loadArticleFromFile($id) {
    $filename = "data/articles/{$id}.json";
    if (!file_exists($filename)) {
        return null;
    }
    
    $article = loadJsonFile($filename);
    if (!$article) {
        return null;
    }
    
    $article['id'] = $id;
    return $article;
}

// Сохранение статьи в файл
function saveArticleToFile($id, $article) {
    $filename = "data/articles/{$id}.json";
    return saveJsonFile($filename, $article);
}

// Создание новой статьи
function createArticle($articleData) {
    // Найти следующий свободный ID
    $ids = getArticleIds();
    $nextId = empty($ids) ? 1 : max($ids) + 1;
    
    // Добавить дату создания если не указана
    if (!isset($articleData['date'])) {
        $articleData['date'] = date('Y-m-d');
    }
    
    // Сохранить статью
    if (saveArticleToFile($nextId, $articleData)) {
        return $nextId;
    }
    
    return false;
}

// Обновление существующей статьи
function updateArticle($id, $articleData) {
    if (!file_exists("data/articles/{$id}.json")) {
        return false;
    }
    
    return saveArticleToFile($id, $articleData);
}

// Удаление статьи
function deleteArticle($id) {
    $filename = "data/articles/{$id}.json";
    if (file_exists($filename)) {
        return unlink($filename);
    }
    return false;
}

// Функции для работы с просмотрами (из предыдущей версии)
function loadViews() {
    return loadJsonFile('data/views.json', []);
}

function saveViews($views) {
    return saveJsonFile('data/views.json', $views);
}

function incrementViews($articleId) {
    $views = loadViews();
    $views[$articleId] = isset($views[$articleId]) ? $views[$articleId] + 1 : 1;
    saveViews($views);
    return $views[$articleId];
}

function getViews($articleId) {
    $views = loadViews();
    return isset($views[$articleId]) ? $views[$articleId] : 0;
}

// Инициализация данных при подключении файла
initializeData();
?>