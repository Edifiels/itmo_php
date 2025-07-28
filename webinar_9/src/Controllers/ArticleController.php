<?php
namespace Blog\Controllers;

use Blog\Repositories\ArticleRepository;
use Blog\Repositories\CommentRepository;
use Blog\Repositories\UserRepository;

class ArticleController {
    private $articleRepository;
    private $commentRepository;
    private $userRepository;
    
    public function __construct(ArticleRepository $articleRepository, CommentRepository $commentRepository, UserRepository $userRepository) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->userRepository = $userRepository;
    }
    
    /**
     * Показать главную страницу со статьями
     */
    public function index($page = 1, $perPage = 6) {
        $result = $this->articleRepository->findWithPagination($page, $perPage);
        $popularArticles = $this->articleRepository->findPopular(3);
        $stats = $this->getBlogStats();
        
        return [
            'articles' => $result['articles'],
            'pagination' => $result['pagination'],
            'popularArticles' => $popularArticles,
            'stats' => $stats
        ];
    }
    
    /**
     * Показать одну статью
     */
    public function show($id) {
        $article = $this->articleRepository->find($id);
        
        if (!$article) {
            return null;
        }
        
        // Увеличиваем просмотры
        $newViews = $this->articleRepository->incrementViews($id);
        $article->setViews($newViews);
        
        // Получаем комментарии
        $comments = $this->commentRepository->findByArticle($id);
        
        // Получаем похожие статьи
        $similarArticles = $this->articleRepository->findSimilar($id, 3);
        
        return [
            'article' => $article,
            'comments' => $comments,
            'similarArticles' => $similarArticles
        ];
    }
    
    /**
     * Поиск статей
     */
    public function search($query) {
        $articles = $this->articleRepository->search($query);
        $allTags = $this->userRepository->getAllTags();
        
        return [
            'articles' => $articles,
            'query' => $query,
            'totalResults' => count($articles),
            'allTags' => $allTags
        ];
    }
    
    /**
     * Получение всех статей для админки
     */
    public function getAllForAdmin() {
        return $this->articleRepository->findAll();
    }
    
    /**
     * Создание статьи
     */
    public function create($data) {
        try {
            $newId = $this->articleRepository->create($data);
            
            if ($newId) {
                return [
                    'success' => true,
                    'message' => "Статья успешно создана с ID: $newId",
                    'id' => $newId
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
    public function update($id, $data) {
        try {
            $result = $this->articleRepository->update($id, $data);
            
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
    public function delete($id) {
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
        $stmt = $this->articleRepository->findAll();
        $totalViews = 0;
        foreach ($stmt as $article) {
            $totalViews += $article->getViews();
        }
        return $totalViews;
    }
}
?>