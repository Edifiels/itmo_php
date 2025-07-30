<?php
namespace Blog\Services;

class SEOService {
    
    /**
     * Генерация мета-тегов для страницы
     */
    public static function generateMetaTags($title, $description, $keywords = '', $image = '', $url = '') {
        // Очистка и ограничение длины
        $title = self::cleanText($title, 60);
        $description = self::cleanText($description, 160);
        $keywords = self::cleanText($keywords, 255);
        
        // Текущий URL если не передан
        if (empty($url)) {
            $url = self::getCurrentUrl();
        }
        
        $html = '';
        
        // Основные мета-теги
        $html .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
        if (!empty($keywords)) {
            $html .= '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
        }
        $html .= '<meta name="robots" content="index, follow">' . "\n";
        $html .= '<meta name="author" content="IT Blog">' . "\n";
        $html .= '<link rel="canonical" href="' . htmlspecialchars($url) . '">' . "\n";
        
        // Open Graph теги
        $html .= '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
        $html .= '<meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
        $html .= '<meta property="og:type" content="article">' . "\n";
        $html .= '<meta property="og:site_name" content="IT Blog">' . "\n";
        
        if (!empty($image)) {
            $html .= '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
        }
        
        // Twitter Card теги
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
        
        if (!empty($image)) {
            $html .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
        }
        
        return $html;
    }
    
    /**
     * Генерация структурированных данных Schema.org для статьи
     */
    public static function generateArticleSchema($article, $author, $comments = []) {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $article->getTitle(),
            "description" => $article->getExcerpt(),
            "author" => [
                "@type" => "Person",
                "name" => $author['name'],
                "email" => $author['email']
            ],
            "datePublished" => date('c', strtotime($article->getCreatedAt())),
            "dateModified" => date('c', strtotime($article->getUpdatedAt() ?: $article->getCreatedAt())),
            "publisher" => [
                "@type" => "Organization",
                "name" => "IT Blog",
                "url" => self::getBaseUrl()
            ],
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => self::getCurrentUrl()
            ],
            "wordCount" => str_word_count(strip_tags($article->getContent())),
            "timeRequired" => "PT" . $article->getReadingTime() . "M",
            "keywords" => implode(', ', $article->getTags())
        ];
        
        // Добавляем комментарии если есть
        if (!empty($comments)) {
            $schema["comment"] = [];
            foreach ($comments as $comment) {
                $schema["comment"][] = [
                    "@type" => "Comment",
                    "text" => $comment->getContent(),
                    "author" => [
                        "@type" => "Person",
                        "name" => $comment->getAuthorName()
                    ],
                    "dateCreated" => date('c', strtotime($comment->getCreatedAt()))
                ];
            }
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    /**
     * Генерация структурированных данных для хлебных крошек
     */
    public static function generateBreadcrumbSchema($items) {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => []
        ];
        
        foreach ($items as $index => $item) {
            $schema["itemListElement"][] = [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => $item['name'],
                "item" => $item['url']
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>';
    }
    
    /**
     * Генерация sitemap.xml
     */
    public static function generateSitemap($articles, $categories = []) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $baseUrl = self::getBaseUrl();
        
        // Главная страница
        $xml .= self::addSitemapUrl($baseUrl . '/', '1.0', 'daily', date('c'));
        
        // Страница поиска
        $xml .= self::addSitemapUrl($baseUrl . '/search.php', '0.8', 'weekly');
        
        // Статьи
        foreach ($articles as $article) {
            $url = $baseUrl . '/article.php?id=' . $article->getId();
            $lastmod = date('c', strtotime($article->getUpdatedAt() ?: $article->getCreatedAt()));
            $xml .= self::addSitemapUrl($url, '0.9', 'weekly', $lastmod);
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Добавление URL в sitemap
     */
    private static function addSitemapUrl($url, $priority = '0.5', $changefreq = 'monthly', $lastmod = null) {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        if ($lastmod) {
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
        }
        $xml .= "    <changefreq>$changefreq</changefreq>\n";
        $xml .= "    <priority>$priority</priority>\n";
        $xml .= "  </url>\n";
        
        return $xml;
    }
    
    /**
     * Генерация robots.txt
     */
    public static function generateRobotsTxt() {
        $baseUrl = self::getBaseUrl();
        
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /config/\n";
        $content .= "Disallow: /database/\n";
        $content .= "Disallow: /src/\n";
        $content .= "\n";
        $content .= "Sitemap: {$baseUrl}/sitemap.xml\n";
        
        return $content;
    }
    
    /**
     * Очистка текста для SEO
     */
    private static function cleanText($text, $maxLength = 160) {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (mb_strlen($text, 'UTF-8') > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3, 'UTF-8') . '...';
        }
        
        return $text;
    }
    
    /**
     * Получение текущего URL
     */
    private static function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Получение базового URL
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'];
    }
    
    /**
     * Генерация мета-тегов для главной страницы
     */
    public static function getHomePageMeta() {
        return [
            'title' => 'IT Blog - Современные статьи о веб-разработке и программировании',
            'description' => 'Профессиональный IT блог с актуальными статьями о PHP, JavaScript, веб-разработке, базах данных и современных технологиях программирования.',
            'keywords' => 'IT блог, веб-разработка, PHP, JavaScript, программирование, MySQL, фронтенд, бэкенд, технологии'
        ];
    }
    
    /**
     * Генерация хлебных крошек
     */
    public static function generateBreadcrumbs($items) {
        if (empty($items)) return '';
        
        $html = '<nav class="breadcrumbs" aria-label="breadcrumb">';
        $html .= '<ol class="breadcrumb-list">';
        
        foreach ($items as $index => $item) {
            $isLast = $index === count($items) - 1;
            
            $html .= '<li class="breadcrumb-item">';
            if (!$isLast && !empty($item['url'])) {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '">';
                $html .= htmlspecialchars($item['name']);
                $html .= '</a>';
            } else {
                $html .= '<span>' . htmlspecialchars($item['name']) . '</span>';
            }
            $html .= '</li>';
            
            if (!$isLast) {
                $html .= '<li class="breadcrumb-separator">/</li>';
            }
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }
}
?>