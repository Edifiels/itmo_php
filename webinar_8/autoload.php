<?php
/**
 * autoload.php - Простая автозагрузка классов для блога
 */

spl_autoload_register(function ($className) {
    // Преобразуем namespace в путь к файлу
    $className = str_replace('\\', '/', $className);
    
    // Убираем префикс Blog\ если есть
    if (strpos($className, 'Blog/') === 0) {
        $className = substr($className, 5);
    }
    
    $file = __DIR__ . '/src/' . $className . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Подключаем конфигурацию
require_once __DIR__ . '/config/database.php';
?>