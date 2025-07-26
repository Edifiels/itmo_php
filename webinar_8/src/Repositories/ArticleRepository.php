<?php
namespace Blog\Repositories;

use Blog\Models\Article;
use PDO;

class ArticleRepository {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Получение статьи по ID
     */
    public function find($id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                u.bio as author_bio,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.id = ? AND a.status = 'published'
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        $article = new Article($data);
        $article->setTags($this->getArticleTags($id));
        
        return $article;
    }
    
    /**
     * Получение всех статей
     */
    public function findAll($limit = null, $offset = 0) {
        $sql = "
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.published_at DESC, a.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $this->pdo->query($sql);
        $articlesData = $stmt->fetchAll();
        
        $articles = [];
        foreach ($articlesData as $data) {
            $article = new Article($data);
            $article->setTags($this->getArticleTags($article->getId()));
            $articles[] = $article;
        }
        
        return $articles;
    }
    
    /**
     * Получение статей с пагинацией
     */
    public function findWithPagination($page = 1, $perPage = 5) {
        // Подсчитываем общее количество статей
        $countStmt = $this->pdo->query("
            SELECT COUNT(*) FROM articles 
            WHERE status = 'published'
        ");
        $totalArticles = $countStmt->fetchColumn();
        
        // Вычисляем OFFSET
        $offset = ($page - 1) * $perPage;
        
        // Получаем статьи для текущей страницы
        $articles = $this->findAll($perPage, $offset);
        
        return [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_articles' => $totalArticles,
                'total_pages' => ceil($totalArticles / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($totalArticles / $perPage),
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < ceil($totalArticles / $perPage) ? $page + 1 : null
            ]
        ];
    }
    
    /**
     * Поиск статей
     */
    public function search($query) {
        if (empty(trim($query))) {
            return $this->findAll();
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
            ORDER BY a.published_at DESC, a.created_at DESC
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $articlesData = $stmt->fetchAll();
        
        $articles = [];
        foreach ($articlesData as $data) {
            $article = new Article($data);
            $article->setTags($this->getArticleTags($article->getId()));
            $articles[] = $article;
        }
        
        return $articles;
    }
    
    /**
     * Получение похожих статей
     */
    public function findSimilar($articleId, $limit = 3) {
        // Получаем категорию текущей статьи
        $stmt = $this->pdo->prepare("SELECT category_id FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $currentCategoryId = $stmt->fetchColumn();
        
        if (!$currentCategoryId) {
            return [];
        }
        
        // Получаем статьи из той же категории
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.category_id = ? 
            AND a.id != ? 
            AND a.status = 'published'
            ORDER BY a.views DESC, a.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$currentCategoryId, $articleId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Получение популярных статей
     */
    public function findPopular($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*,
                u.name as author_name,
                u.email as author_email,
                c.name as category_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'published'
            ORDER BY a.views DESC, a.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $articlesData = $stmt->fetchAll();
        
        $articles = [];
        foreach ($articlesData as $data) {
            $article = new Article($data);
            $article->setTags($this->getArticleTags($article->getId()));
            $articles[] = $article;
        }
        
        return $articles;
    }
    
    /**
     * Увеличение просмотров статьи
     */
    public function incrementViews($id) {
        $stmt = $this->pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Возвращаем новое количество просмотров
        $stmt = $this->pdo->prepare("SELECT views FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Подсчет общего количества статей
     */
    public function getCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        return $stmt->fetchColumn();
    }
    
    /**
     * Создание новой статьи
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO articles (title, slug, content, excerpt, author_id, category_id, reading_time, published_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $slug = $this->generateSlug($data['title']);
        $publishedAt = $data['date'] ?? date('Y-m-d');
        $readingTime = $data['reading_time'] ?? $this->calculateReadingTime($data['content']);
        
        $result = $stmt->execute([
            $data['title'],
            $slug,
            $data['content'],
            $data['excerpt'],
            $data['author_id'],
            $data['category_id'],
            $readingTime,
            $publishedAt
        ]);
        
        if ($result) {
            $articleId = $this->pdo->lastInsertId();
            
            // Сохраняем теги если есть
            if (!empty($data['tags'])) {
                $this->saveArticleTags($articleId, $data['tags']);
            }
            
            return $articleId;
        }
        
        return false;
    }
    
    /**
     * Обновление статьи
     */
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE articles 
            SET title = ?, slug = ?, content = ?, excerpt = ?, author_id = ?, 
                category_id = ?, reading_time = ?, published_at = ?
            WHERE id = ?
        ");
        
        $slug = $this->generateSlug($data['title']);
        $publishedAt = $data['date'] ?? date('Y-m-d');
        $readingTime = $data['reading_time'] ?? $this->calculateReadingTime($data['content']);
        
        $result = $stmt->execute([
            $data['title'],
            $slug,
            $data['content'],
            $data['excerpt'],
            $data['author_id'],
            $data['category_id'],
            $readingTime,
            $publishedAt,
            $id
        ]);
        
        if ($result) {
            // Обновляем теги
            $tags = $data['tags'] ?? [];
            $this->saveArticleTags($id, $tags);
        }
        
        return $result;
    }
    
    /**
     * Удаление статьи
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Получение тегов статьи
     */
    private function getArticleTags($articleId) {
        $stmt = $this->pdo->prepare("
            SELECT t.name 
            FROM tags t 
            JOIN article_tags at ON t.id = at.tag_id 
            WHERE at.article_id = ?
            ORDER BY t.name
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Сохранение тегов для статьи
     */
    private function saveArticleTags($articleId, $tags) {
        // Удаляем существующие теги статьи
        $stmt = $this->pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
        $stmt->execute([$articleId]);
        
        if (empty($tags)) {
            return true;
        }
        
        // Добавляем новые теги
        $stmtTag = $this->pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
        $stmtGetTag = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmtArticleTag = $this->pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
        
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;
            
            // Добавляем тег если его нет
            $stmtTag->execute([$tagName, $this->generateSlug($tagName)]);
            
            // Получаем ID тега
            $stmtGetTag->execute([$tagName]);
            $tagId = $stmtGetTag->fetchColumn();
            
            // Связываем статью с тегом
            if ($tagId) {
                $stmtArticleTag->execute([$articleId, $tagId]);
            }
        }
    }
    
    /**
     * Генерация slug
     */
    private function generateSlug($title) {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = preg_replace('/[^а-яёa-z0-9\s-]/ui', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
    
    /**
     * Расчет времени чтения
     */
    private function calculateReadingTime($content) {
        $words = str_word_count(strip_tags($content));
        return max(1, round($words / 200)); // 200 слов в минуту
    }
}
?>