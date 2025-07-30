<?php
namespace Blog\Repositories;

use Blog\Models\Comment;
use PDO;

class CommentRepository {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Получение комментариев статьи
     */
    public function findByArticle($articleId, $status = 'approved') {
        $stmt = $this->pdo->prepare("
            SELECT * FROM comments 
            WHERE article_id = ? AND status = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$articleId, $status]);
        $commentsData = $stmt->fetchAll();
        
        $comments = [];
        foreach ($commentsData as $data) {
            $comments[] = new Comment($data);
        }
        
        return $comments;
    }
    
    /**
     * Получение всех комментариев для модерации
     */
    public function findAll($status = null) {
        if ($status) {
            $stmt = $this->pdo->prepare("
                SELECT c.*, a.title as article_title 
                FROM comments c
                JOIN articles a ON c.article_id = a.id
                WHERE c.status = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$status]);
        } else {
            $stmt = $this->pdo->query("
                SELECT c.*, a.title as article_title 
                FROM comments c
                JOIN articles a ON c.article_id = a.id
                ORDER BY c.created_at DESC
            ");
        }
        
        $commentsData = $stmt->fetchAll();
        
        $comments = [];
        foreach ($commentsData as $data) {
            $comments[] = new Comment($data);
        }
        
        return $comments;
    }
    
    /**
     * Добавление нового комментария
     */
    public function create($articleId, $authorName, $authorEmail, $content) {
        $stmt = $this->pdo->prepare("
            INSERT INTO comments (article_id, author_name, author_email, content)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$articleId, $authorName, $authorEmail, $content]);
    }
    
    /**
     * Подсчет комментариев статьи
     */
    public function countByArticle($articleId, $status = 'approved') {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM comments 
            WHERE article_id = ? AND status = ?
        ");
        $stmt->execute([$articleId, $status]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Обновление статуса комментария
     */
    public function updateStatus($commentId, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE comments SET status = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $commentId]);
    }
    
    /**
     * Удаление комментария
     */
    public function delete($commentId) {
        $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = ?");
        return $stmt->execute([$commentId]);
    }
    
    /**
     * Подсчет общего количества комментариев
     */
    public function getCount($status = 'approved') {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchColumn();
    }
}
?>