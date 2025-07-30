<?php
namespace Blog\Controllers;

use Blog\Repositories\ArticleRepository;
use Blog\Repositories\CommentRepository;
use Blog\Repositories\UserRepository;
use Blog\Services\HelperService;

class AdminController {
    private $articleRepository;
    private $commentRepository;
    private $userRepository;
    
    public function __construct(ArticleRepository $articleRepository, CommentRepository $commentRepository, UserRepository $userRepository) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->userRepository = $userRepository;
    }
    
    /**
     * Главная страница админки
     */
    public function index() {
        $allArticles = $this->articleRepository->findAll();
        $authors = $this->userRepository->findAll();
        $categories = $this->userRepository->getCategories();
        $stats = $this->getBlogStats();
        $pendingComments = $this->commentRepository->findAll('pending');
        
        return [
            'articles' => $allArticles,
            'authors' => $authors,
            'categories' => $categories,
            'stats' => $stats,
            'pendingComments' => $pendingComments
        ];
    }
    
    /**
     * Создание статьи
     */
    public function createArticle($data) {
        // Подготовка данных
        $articleData = [
            'title' => HelperService::sanitizeString($data['title']),
            'content' => HelperService::sanitizeHTML($data['content']),
            'excerpt' => HelperService::sanitizeString($data['excerpt']),
            'author_id' => (int)$data['author_id'],
            'category_id' => (int)$data['category_id'],
            'reading_time' => (int)$data['reading_time'],
            'tags' => array_filter(array_map('trim', explode(',', $data['tags']))),
            'date' => $data['date'] ?? date('Y-m-d')
        ];
        
        // Валидация
        $errors = HelperService::validateArticleData($articleData);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors)
            ];
        }
        
        // Создание
        try {
            $newId = $this->articleRepository->create($articleData);
            if ($newId) {
                return [
                    'success' => true,
                    'message' => "Статья успешно создана с ID: $newId"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при создании статьи'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Обновление статьи
     */
    public function updateArticle($id, $data) {
        // Подготовка данных
        $articleData = [
            'title' => HelperService::sanitizeString($data['title']),
            'content' => HelperService::sanitizeHTML($data['content']),
            'excerpt' => HelperService::sanitizeString($data['excerpt']),
            'author_id' => (int)$data['author_id'],
            'category_id' => (int)$data['category_id'],
            'reading_time' => (int)$data['reading_time'],
            'tags' => array_filter(array_map('trim', explode(',', $data['tags']))),
            'date' => $data['date'] ?? date('Y-m-d')
        ];
        
        // Валидация
        $errors = HelperService::validateArticleData($articleData);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors)
            ];
        }
        
        // Обновление
        try {
            $result = $this->articleRepository->update($id, $articleData);
            if ($result) {
                return [
                    'success' => true,
                    'message' => "Статья ID $id успешно обновлена"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при обновлении статьи'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Удаление статьи
     */
    public function deleteArticle($id) {
        try {
            $result = $this->articleRepository->delete($id);
            if ($result) {
                return [
                    'success' => true,
                    'message' => "Статья ID $id успешно удалена"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при удалении статьи'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получение статьи для редактирования
     */
    public function getArticleForEdit($id) {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return null;
        }
        
        // Преобразуем в массив для совместимости с формой
        $articleArray = $article->toArray();
        $articleArray['tags'] = implode(', ', $article->getTags());
        
        return $articleArray;
    }
    
    /**
     * Получение статистики блога
     */
    public function getBlogStats() {
        return [
            'articles' => $this->articleRepository->getCount(),
            'views' => $this->getTotalViews(),
            'authors' => $this->userRepository->getCount(),
            'comments' => $this->commentRepository->getCount()
        ];
    }
    
    /**
     * Получение общих просмотров
     */
    private function getTotalViews() {
        $articles = $this->articleRepository->findAll();
        $totalViews = 0;
        foreach ($articles as $article) {
            $totalViews += $article->getViews();
        }
        return $totalViews;
    }
}
?>