<?php
namespace Blog\Services;

class HelperService {
    
    /**
     * Форматирование даты
     */
    public static function formatDate($date) {
        return date('d.m.Y', strtotime($date));
    }
    
    /**
     * Форматирование даты и времени
     */
    public static function formatDateTime($datetime) {
        return date('d.m.Y H:i', strtotime($datetime));
    }
    
    /**
     * Форматирование просмотров
     */
    public static function formatViews($views) {
        if ($views < 1000) return $views;
        if ($views < 1000000) return round($views / 1000, 1) . 'K';
        return round($views / 1000000, 1) . 'M';
    }
    
    /**
     * Безопасное экранирование HTML
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Безопасная очистка HTML с разрешенными тегами
     */
    public static function sanitizeHTML($value) {
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h3><h4><blockquote>';
        return strip_tags($value, $allowedTags);
    }
    
    /**
     * Генерация тегов HTML
     */
    public static function renderTags($tags) {
        if (empty($tags)) {
            return '';
        }
        
        $html = '<div class="article-tags">';
        foreach ($tags as $tag) {
            $html .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
        }
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Генерация HTML для пагинации
     */
    public static function renderPagination($pagination, $baseUrl = 'index.php', $queryParams = []) {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }
        
        $html = '<div class="pagination">';
        
        // Формируем базовый URL с параметрами
        $buildUrl = function($page) use ($baseUrl, $queryParams) {
            $params = array_merge($queryParams, ['page' => $page]);
            return $baseUrl . '?' . http_build_query($params);
        };
        
        // Кнопка "Предыдущая"
        if ($pagination['has_prev']) {
            $html .= '<a href="' . $buildUrl($pagination['prev_page']) . '" class="pagination-btn">← Предыдущая</a>';
        }
        
        // Номера страниц (показываем до 7 страниц)
        $current = $pagination['current_page'];
        $total = $pagination['total_pages'];
        
        $start = max(1, $current - 3);
        $end = min($total, $current + 3);
        
        // Показываем первую страницу если она не входит в диапазон
        if ($start > 1) {
            $html .= '<a href="' . $buildUrl(1) . '" class="pagination-btn">1</a>';
            if ($start > 2) {
                $html .= '<span class="pagination-dots">...</span>';
            }
        }
        
        // Показываем страницы в диапазоне
        for ($i = $start; $i <= $end; $i++) {
            $isActive = ($i === $current);
            $class = $isActive ? 'pagination-btn active' : 'pagination-btn';
            $html .= '<a href="' . $buildUrl($i) . '" class="' . $class . '">' . $i . '</a>';
        }
        
        // Показываем последнюю страницу если она не входит в диапазон
        if ($end < $total) {
            if ($end < $total - 1) {
                $html .= '<span class="pagination-dots">...</span>';
            }
            $html .= '<a href="' . $buildUrl($total) . '" class="pagination-btn">' . $total . '</a>';
        }
        
        // Кнопка "Следующая"
        if ($pagination['has_next']) {
            $html .= '<a href="' . $buildUrl($pagination['next_page']) . '" class="pagination-btn">Следующая →</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Валидация данных статьи
     */
    public static function validateArticleData($data) {
        $errors = [];
        
        if (empty(trim($data['title']))) {
            $errors[] = 'Заголовок обязателен';
        }
        
        if (strlen(trim($data['title'])) > 255) {
            $errors[] = 'Заголовок слишком длинный (максимум 255 символов)';
        }
        
        if (empty(trim($data['content']))) {
            $errors[] = 'Содержимое обязательно';
        }
        
        if (empty(trim($data['excerpt']))) {
            $errors[] = 'Описание обязательно';
        }
        
        if (!isset($data['author_id']) || !is_numeric($data['author_id'])) {
            $errors[] = 'Неверный автор';
        }
        
        if (!isset($data['category_id']) || !is_numeric($data['category_id'])) {
            $errors[] = 'Неверная категория';
        }
        
        return $errors;
    }
}
?>