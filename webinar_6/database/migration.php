<?php
// database/migration.php - Миграция данных из файлов в БД

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем файлы
require_once '../config/database.php';

// Функция для создания slug из строки
function createSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $string = preg_replace('/[^а-яёa-z0-9\s-]/ui', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

echo "<h2>🔄 Миграция данных из файлов в MySQL</h2>";

try {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Не удалось подключиться к базе данных");
    }
    
    echo "<p>✅ Подключение к БД установлено</p>";
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // 1. Очищаем существующие данные
    echo "<p>🗑️ Очистка существующих данных...</p>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE article_tags");
    $pdo->exec("TRUNCATE TABLE articles");
    $pdo->exec("TRUNCATE TABLE tags");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 2. Загружаем данные из файлов (если они есть)
    echo "<p>📂 Загрузка данных из файлов...</p>";
    
    // Проверяем существование файлов
    $dataPath = '../data/';
    $authorsFile = $dataPath . 'authors.json';
    $categoriesFile = $dataPath . 'categories.json';
    
    if (file_exists($authorsFile) && file_exists($categoriesFile)) {
        echo "<p>📁 Найдены файлы с данными, выполняем миграцию...</p>";
        
        // Загружаем авторов
        $authorsData = json_decode(file_get_contents($authorsFile), true);
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, bio) VALUES (?, ?, ?, ?)");
        
        foreach ($authorsData as $id => $author) {
            $stmt->execute([
                $id,
                $author['name'],
                $author['email'],
                $author['bio'] ?? ''
            ]);
        }
        echo "<p>👥 Перенесено авторов: " . count($authorsData) . "</p>";
        
        // Загружаем категории
        $categoriesData = json_decode(file_get_contents($categoriesFile), true);
        $stmt = $pdo->prepare("INSERT INTO categories (id, name, slug) VALUES (?, ?, ?)");
        
        foreach ($categoriesData as $id => $categoryName) {
            $stmt->execute([
                $id,
                $categoryName,
                createSlug($categoryName)
            ]);
        }
        echo "<p>📁 Перенесено категорий: " . count($categoriesData) . "</p>";
        
        // Загружаем статьи из папки articles
        $articlesPath = $dataPath . 'articles/';
        if (is_dir($articlesPath)) {
            $files = glob($articlesPath . '*.json');
            $stmtArticle = $pdo->prepare("
                INSERT INTO articles (id, title, slug, content, excerpt, author_id, category_id, reading_time, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Подготовка для тегов
            $stmtTag = $pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
            $stmtGetTag = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmtArticleTag = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
            
            foreach ($files as $file) {
                $articleId = (int) basename($file, '.json');
                $articleData = json_decode(file_get_contents($file), true);
                
                if ($articleData) {
                    // Вставляем статью
                    $stmtArticle->execute([
                        $articleId,
                        $articleData['title'],
                        createSlug($articleData['title']),
                        $articleData['content'],
                        $articleData['excerpt'],
                        $articleData['author_id'],
                        $articleData['category_id'],
                        $articleData['reading_time'],
                        $articleData['date']
                    ]);
                    
                    // Обрабатываем теги
                    if (!empty($articleData['tags'])) {
                        foreach ($articleData['tags'] as $tagName) {
                            // Добавляем тег если его нет
                            $stmtTag->execute([$tagName, createSlug($tagName)]);
                            
                            // Получаем ID тега
                            $stmtGetTag->execute([$tagName]);
                            $tagId = $stmtGetTag->fetchColumn();
                            
                            // Связываем статью с тегом
                            if ($tagId) {
                                $stmtArticleTag->execute([$articleId, $tagId]);
                            }
                        }
                    }
                }
            }
            echo "<p>📚 Перенесено статей: " . count($files) . "</p>";
        }
        
        // Загружаем просмотры
        $viewsFile = $dataPath . 'views.json';
        if (file_exists($viewsFile)) {
            $viewsData = json_decode(file_get_contents($viewsFile), true);
            $stmt = $pdo->prepare("UPDATE articles SET views = ? WHERE id = ?");
            
            foreach ($viewsData as $articleId => $views) {
                $stmt->execute([$views, $articleId]);
            }
            echo "<p>👁️ Обновлены просмотры для статей</p>";
        }
    } else {
        // Если файлов нет, вставляем тестовые данные
        echo "<p>⚠️ Файлы с данными не найдены, вставляем тестовые данные...</p>";
        
        // Вставляем тестовые данные из structure.sql
        $testData = "
        INSERT INTO users (id, name, email, bio) VALUES
        (1, 'Анна Разработчик', 'anna@blog.ru', 'Senior PHP разработчик с 8-летним опытом'),
        (2, 'Дмитрий Архитектор', 'dmitry@blog.ru', 'Технический архитектор и fullstack разработчик'),
        (3, 'Мария Фронтенд', 'maria@blog.ru', 'Frontend разработчик, UI/UX дизайнер');
        
        INSERT INTO categories (id, name, slug) VALUES
        (1, 'PHP и Backend', 'php-backend'),
        (2, 'Frontend', 'frontend'),
        (3, 'Базы данных', 'databases'),
        (4, 'DevOps', 'devops');
        
        INSERT INTO tags (name, slug) VALUES
        ('PHP', 'php'),
        ('Backend', 'backend'),
        ('API', 'api'),
        ('MySQL', 'mysql'),
        ('Оптимизация', 'optimization'),
        ('JavaScript', 'javascript'),
        ('Frontend', 'frontend');
        ";
        
        $pdo->exec($testData);
        echo "<p>✅ Вставлены тестовые данные</p>";
    }
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    // Показываем статистику
    echo "<h3>📊 Результаты миграции:</h3>";
    echo "<ul>";
    
    $stats = [
        'users' => 'Авторов',
        'categories' => 'Категорий',
        'tags' => 'Тегов',
        'articles' => 'Статей',
        'article_tags' => 'Связей статья-тег'
    ];
    
    foreach ($stats as $table => $label) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<li>$label: <strong>$count</strong></li>";
    }
    echo "</ul>";
    
    echo "<p>✅ Миграция успешно завершена!</p>";
    echo "<p>🎉 Теперь ваш блог работает с MySQL базой данных.</p>";
    echo "<p><a href='../index.php'>← Перейти к блогу</a></p>";
    
} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo "<p style='color: red;'>❌ Ошибка миграции: " . $e->getMessage() . "</p>";
    echo "<p>Проверьте:</p>";
    echo "<ul>";
    echo "<li>Подключение к базе данных в config/database.php</li>";
    echo "<li>Существование базы данных 'it_blog'</li>";
    echo "<li>Права доступа к папке с данными</li>";
    echo "</ul>";
}
?>