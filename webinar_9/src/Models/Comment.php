<?php
namespace Blog\Models;

class Comment {
    private $id;
    private $articleId;
    private $authorName;
    private $authorEmail;
    private $content;
    private $status;
    private $createdAt;
    private $updatedAt;
    
    // Связанные данные
    private $articleTitle;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    private function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->articleId = $data['article_id'] ?? null;
        $this->authorName = $data['author_name'] ?? '';
        $this->authorEmail = $data['author_email'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        
        // Связанные данные из JOIN
        if (isset($data['article_title'])) {
            $this->articleTitle = $data['article_title'];
        }
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getArticleId() { return $this->articleId; }
    public function getAuthorName() { return $this->authorName; }
    public function getAuthorEmail() { return $this->authorEmail; }
    public function getContent() { return $this->content; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getArticleTitle() { return $this->articleTitle; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setStatus($status) { $this->status = $status; }
    
    // Бизнес-логика
    public function approve() {
        $this->status = 'approved';
    }
    
    public function reject() {
        $this->status = 'rejected';
    }
    
    public function isApproved() {
        return $this->status === 'approved';
    }
    
    public function isPending() {
        return $this->status === 'pending';
    }
    
    // Преобразование в массив для совместимости
    public function toArray() {
        return [
            'id' => $this->id,
            'article_id' => $this->articleId,
            'author_name' => $this->authorName,
            'author_email' => $this->authorEmail,
            'content' => $this->content,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'article_title' => $this->articleTitle
        ];
    }
}
?>