<?php
// sitemap.php - Генератор sitemap.xml для SEO
require_once 'autoload.php';

use Blog\Repositories\ArticleRepository;
use Blog\Repositories\UserRepository;
use Blog\Services\SEOService;

header('Content-Type: application/xml; charset=utf-8');

try {
    // Получаем подключение к БД
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Ошибка подключения к базе данных");
    }
    
    // Создаем репозитории
    $articleRepository = new ArticleRepository($pdo);
    $userRepository = new UserRepository($pdo);
    
    // Получаем все статьи
    $articles = $articleRepository->findAll();
    $categories = $userRepository->getCategories();
    
    // Генерируем sitemap
    echo SEOService::generateSitemap($articles, $categories);
    
} catch (Exception $e) {
    // В случае ошибки возвращаем базовый sitemap
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo '  <url>' . "\n";
    echo '    <loc>http://' . $_SERVER['HTTP_HOST'] . '/' . '</loc>' . "\n";
    echo '    <changefreq>daily</changefreq>' . "\n";
    echo '    <priority>1.0</priority>' . "\n";
    echo '  </url>' . "\n";
    echo '</urlset>' . "\n";
}
?>