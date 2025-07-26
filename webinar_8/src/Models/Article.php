<?php
namespace Blog\Models;

class Article {
    private $id;
    private $title;
    private $slug;
    private $content;
    private $excerpt;
    private $authorId;
    private $categoryId;
    private $views;
    private $readingTime;
    private $status;
    private $publishedAt;
    private $createdAt;
    private $updatedAt;
    
    // Связанные данные
    private $author;
    private $category;
    private $tags = [];
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    private function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->excerpt = $data['excerpt'] ?? '';
        $this->authorId = $data['author_id'] ?? null;
        $this->categoryId = $data['category_id'] ?? null;
        $this->views = $data['views'] ?? 0;
        $this->readingTime = $data['reading_time'] ?? 0;
        $this->status = $data['status'] ?? 'published';
        $this->publishedAt = $data['published_at'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        
        // Связанные данные из JOIN
        if (isset($data['author_name'])) {
            $this->author = [
                'name' => $data['author_name'],
                'email' => $data['author_email'] ?? '',
                'bio' => $data['author_bio'] ?? ''
            ];
        }
        
        if (isset($data['category_name'])) {
            $this->category = $data['category_name'];
        }
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getSlug() { return $this->slug; }
    public function getContent() { return $this->content; }
    public function getExcerpt() { return $this->excerpt; }
    public function getAuthorId() { return $this->authorId; }
    public function getCategoryId() { return $this->categoryId; }
    public function getViews() { return $this->views; }
    public function getReadingTime() { return $this->readingTime; }
    public function getStatus() { return $this->status; }
    public function getPublishedAt() { return $this->publishedAt; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getDate() { return $this->publishedAt ?: $this->createdAt; }
    
    public function getAuthor() { return $this->author; }
    public function getCategory() { return $this->category; }
    public function getTags() { return $this->tags; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setTitle($title) { $this->title = $title; }
    public function setContent($content) { $this->content = $content; }
    public function setViews($views) { $this->views = $views; }
    public function setAuthor($author) { $this->author = $author; }
    public function setCategory($category) { $this->category = $category; }
    public function setTags($tags) { $this->tags = $tags; }
    
    // Бизнес-логика
    public function incrementViews() {
        $this->views++;
    }
    
    public function isPublished() {
        return $this->status === 'published';
    }
    
    // Преобразование в массив для совместимости
    public function toArray() {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author_id' => $this->authorId,
            'category_id' => $this->categoryId,
            'views' => $this->views,
            'reading_time' => $this->readingTime,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'author' => $this->author,
            'category' => $this->category,
            'tags' => $this->tags,
            'date' => $this->getDate()
        ];
    }
}
?>