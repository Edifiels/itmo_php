<?php
/**
 * Простой тест API для проверки работоспособности
 * test_api.php
 */

echo "<h1>🧪 Тест REST API</h1>";

// Получаем текущий URL для определения базового пути
$currentUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$apiUrl = $currentUrl . '/api';

echo "<p><strong>API URL:</strong> <code>$apiUrl</code></p>";

// Функция для выполнения запроса
function testApiEndpoint($url, $method = 'GET', $data = null) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/vnd.blog.v2+json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    return [
        'status_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Функция для отображения результата
function displayResult($title, $result) {
    $status = $result['status_code'];
    $statusClass = ($status >= 200 && $status < 300) ? 'success' : 'error';
    $statusColor = ($status >= 200 && $status < 300) ? '#28a745' : '#dc3545';
    
    echo "<div style='margin: 20px 0; padding: 15px; border-left: 4px solid $statusColor; background: #f8f9fa;'>";
    echo "<h3>$title</h3>";
    echo "<p><strong>Статус:</strong> <span style='color: $statusColor;'>$status</span></p>";
    
    if ($result['error']) {
        echo "<p><strong>Ошибка cURL:</strong> <span style='color: #dc3545;'>{$result['error']}</span></p>";
    }
    
    if ($result['response']) {
        $formattedResponse = json_decode($result['response'], true);
        if ($formattedResponse) {
            echo "<details><summary><strong>Ответ (JSON):</strong></summary>";
            echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
            echo htmlspecialchars(json_encode($formattedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "</pre></details>";
        } else {
            echo "<details><summary><strong>Ответ (Raw):</strong></summary>";
            echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
            echo htmlspecialchars($result['response']);
            echo "</pre></details>";
        }
    }
    
    echo "</div>";
}

echo "<h2>🚀 Выполнение тестов...</h2>";

// Тест 1: Главная страница API
$result1 = testApiEndpoint($apiUrl);
displayResult("1. GET / - Информация об API", $result1);

// Тест 2: Получение статей
$result2 = testApiEndpoint($apiUrl . '/articles');
displayResult("2. GET /articles - Список статей", $result2);

// Тест 3: Получение статьи по ID
$result3 = testApiEndpoint($apiUrl . '/articles/1');
displayResult("3. GET /articles/1 - Статья по ID", $result3);

// Тест 4: Получение комментариев
$result4 = testApiEndpoint($apiUrl . '/comments');
displayResult("4. GET /comments - Список комментариев", $result4);

// Тест 5: Получение категорий
$result5 = testApiEndpoint($apiUrl . '/categories');
displayResult("5. GET /categories - Список категорий", $result5);

// Тест 6: Создание комментария
$commentData = [
    'article_id' => 1,
    'author_name' => 'Тестировщик API',
    'author_email' => 'test@example.com',
    'content' => 'Тестовый комментарий, созданный ' . date('Y-m-d H:i:s')
];
$result6 = testApiEndpoint($apiUrl . '/comments', 'POST', $commentData);
displayResult("6. POST /comments - Создание комментария", $result6);

// Тест 7: 404 ошибка
$result7 = testApiEndpoint($apiUrl . '/nonexistent');
displayResult("7. GET /nonexistent - Тест 404 ошибки", $result7);

echo "<h2>📋 Сводка результатов</h2>";

$allResults = [$result1, $result2, $result3, $result4, $result5, $result6, $result7];
$successCount = 0;
$errorCount = 0;

foreach ($allResults as $result) {
    if ($result['status_code'] >= 200 && $result['status_code'] < 300) {
        $successCount++;
    } else {
        $errorCount++;
    }
}

echo "<div style='padding: 20px; background: #e6f3ff; border: 1px solid #0066cc; border-radius: 8px;'>";
echo "<p><strong>✅ Успешных тестов:</strong> $successCount</p>";
echo "<p><strong>❌ Неудачных тестов:</strong> $errorCount</p>";
echo "<p><strong>📊 Процент успеха:</strong> " . round($successCount / count($allResults) * 100, 1) . "%</p>";
echo "</div>";

if ($errorCount > 0) {
    echo "<h2>🔧 Рекомендации по устранению проблем</h2>";
    echo "<div style='padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;'>";
    echo "<ol>";
    echo "<li>Убедитесь, что все файлы API созданы в папке <code>api/</code></li>";
    echo "<li>Проверьте файл <code>api/.htaccess</code> для маршрутизации</li>";
    echo "<li>Убедитесь, что подключение к базе данных работает</li>";
    echo "<li>Проверьте права доступа к файлам</li>";
    echo "<li>Убедитесь, что autoload.php подключен правильно</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='api_test.html'>🧪 Интерактивный тестер API</a> | <a href='api/docs'>📚 Документация API</a></p>";
?>

<style>
    body { font-family: -apple-system, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
    h1, h2, h3 { color: #2d3748; }
    code { background: #e2e8f0; padding: 2px 6px; border-radius: 3px; }
    details summary { cursor: pointer; font-weight: bold; margin: 10px 0; }
    details[open] summary { margin-bottom: 10px; }
</style>