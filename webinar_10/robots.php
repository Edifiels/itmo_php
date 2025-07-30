<?php
// robots.php - Генератор robots.txt для SEO
require_once 'autoload.php';

use Blog\Services\SEOService;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo SEOService::generateRobotsTxt();
} catch (Exception $e) {
    // В случае ошибки возвращаем базовый robots.txt
    $baseUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
    
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /config/\n";
    echo "Disallow: /database/\n";
    echo "Disallow: /src/\n";
    echo "\n";
    echo "Sitemap: {$baseUrl}/sitemap.xml\n";
}
?>