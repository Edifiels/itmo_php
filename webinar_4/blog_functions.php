<?php
/**
 * Библиотека функций для блога
 * Содержит все переиспользуемые функции
 */

// ============================================================================
// ФУНКЦИИ ДЛЯ ФОРМАТИРОВАНИЯ
// ============================================================================

/**
 * Форматирует дату в читаемый вид
 */
function formatDate($dateString, $format = 'd F Y в H:i') {
    $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
    ];
    
    $timestamp = strtotime($dateString);
    $formatted = date($format, $timestamp);
    
    // Заменяем английские месяцы на русские
    foreach ($months as $num => $month) {
        $englishMonth = date('F', mktime(0, 0, 0, $num, 1));
        $formatted = str_replace($englishMonth, $month, $formatted);
    }
    
    return $formatted;
}

/**
 * Форматирует число просмотров
 */
function formatViews($views) {
    if ($views < 1000) {
        return number_format($views);
    } elseif ($views < 1000000) {
        return number_format($views / 1000, 1) . 'K';
    } else {
        return number_format($views / 1000000, 1) . 'M';
    }
}

/**
 * Подсчитывает слова в тексте
 */
function countWords($text) {
    return str_word_count(strip_tags($text));
}

// ============================================================================
// ФУНКЦИИ ДЛЯ РАБОТЫ С ДАННЫМИ
// ============================================================================

/**
 * Безопасно получает параметр из $_GET
 */
function getParam($name, $default = '', $type = 'string') {
    if (!isset($_GET[$name])) {
        return $default;
    }
    
    $value = $_GET[$name];
    
    switch ($type) {
        case 'int':
            return (int)$value;
        case 'float':
            return (float)$value;
        case 'bool':
            return (bool)$value;
        case 'string':
        default:
            return trim(htmlspecialchars($value));
    }
}

/**
 * Проверяет, является ли ID валидным
 */
function isValidId($id) {
    return is_numeric($id) && $id > 0;
}

/**
 * Безопасно получает статью по ID
 */
function getArticleSafely($articleId) {
    if (!isValidId($articleId)) {
        return null;
    }
    
    return getArticleWithRelations($articleId);
}

/**
 * Выполняет поиск статей
 */
function searchArticles($query, $categoryId = 0, $tagSlug = '', $authorId = 0) {
    global $articles;
    
    $results = [];
    
    foreach ($articles as $article) {
        $fullArticle = getArticleWithRelations($article['id']);
        $matches = true;
        
        // Поиск по тексту
        if ($query && $matches) {
            $searchText = $fullArticle['title'] . ' ' . $fullArticle['content'] . ' ' . $fullArticle['excerpt'];
            $matches = stripos($searchText, $query) !== false;
        }
        
        // Фильтр по категории
        if ($categoryId && $matches) {
            $matches = $fullArticle['category_id'] === $categoryId;
        }
        
        // Фильтр по тегу
        if ($tagSlug && $matches) {
            $tagSlugs = array_column($fullArticle['tags'], 'slug');
            $matches = in_array($tagSlug, $tagSlugs);
        }
        
        // Фильтр по автору
        if ($authorId && $matches) {
            $matches = $fullArticle['author_id'] === $authorId;
        }
        
        if ($matches) {
            $results[] = $fullArticle;
        }
    }
    
    return $results;
}

/**
 * Сортирует статьи по релевантности (просмотрам)
 */
function sortArticlesByRelevance($articles) {
    usort($articles, function($a, $b) {
        return $b['meta']['views'] - $a['meta']['views'];
    });
    return $articles;
}

/**
 * Сортирует статьи по дате публикации
 */
function sortArticlesByDate($articles, $order = 'desc') {
    usort($articles, function($a, $b) use ($order) {
        $result = strtotime($a['dates']['published']) - strtotime($b['dates']['published']);
        return $order === 'desc' ? -$result : $result;
    });
    return $articles;
}

/**
 * Вычисляет общую статистику блога
 */
function calculateBlogStats($articles) {
    $totalViews = 0;
    foreach ($articles as $article) {
        $totalViews += $article['meta']['views'];
    }
    
    return [
        'total_articles' => count($articles),
        'total_views' => $totalViews,
        'total_authors' => count($GLOBALS['authors']),
        'total_categories' => count($GLOBALS['categories'])
    ];
}

// ============================================================================
// ФУНКЦИИ ДЛЯ HTML ГЕНЕРАЦИИ
// ============================================================================

/**
 * Генерирует мета-информацию статьи
 */
function generateArticleMeta($article, $showCategory = true) {
    $html = '<div class="article-meta">';
    $html .= '<span>👤 ' . htmlspecialchars($article['author']['name']) . '</span>';
    
    if ($showCategory) {
        $html .= '<span class="category-badge">' . htmlspecialchars($article['category']['name']) . '</span>';
    } else {
        $html .= '<span>📅 ' . date('d.m.Y', strtotime($article['dates']['published'])) . '</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Генерирует HTML для тегов
 */
function generateTagsHtml($tags, $linkable = false) {
    $html = '<div class="article-tags">';
    
    foreach ($tags as $tag) {
        if ($linkable) {
            $html .= '<a href="search.php?tag=' . urlencode($tag['slug']) . '" class="tag">';
            $html .= '#' . htmlspecialchars($tag['name']);
            $html .= '</a>';
        } else {
            $html .= '<span class="tag">#' . htmlspecialchars($tag['name']) . '</span>';
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Генерирует статистику статьи
 */
function generateStatsHtml($meta) {
    $html = '<div class="article-stats">';
    $html .= '<span>👁️ ' . formatViews($meta['views']) . '</span>';
    $html .= '<span>❤️ ' . $meta['likes'] . '</span>';
    
    if (isset($meta['comments_count'])) {
        $html .= '<span>💬 ' . $meta['comments_count'] . '</span>';
    }
    
    $html .= '<span>⏱️ ' . $meta['reading_time'] . ' мин</span>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Генерирует карточку статьи для главной страницы
 */
function generateArticleCard($article, $featured = false) {
    ob_start();
?>
<article class="article-card">
    <div class="article-image">
        <?php echo $featured ? '📖 ' . htmlspecialchars($article['title']) : '📄 ' . htmlspecialchars($article['category']['name']) ?>
    </div>
    <div class="article-content">
        <h3 class="article-title"><?php echo htmlspecialchars($article['title']) ?></h3>
        <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt']) ?></p>
        
        <?php echo generateArticleMeta($article, !$featured) ?>
        <?php echo generateTagsHtml($article['tags']) ?>
        <?php echo generateStatsHtml($article['meta']) ?>
        
        <a href="article.php?id=<?php echo $article['id'] ?>" class="read-more">Читать далее →</a>
    </div>
</article>
<?php
    return ob_get_clean();
}

?>