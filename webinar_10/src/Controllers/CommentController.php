<?php
namespace Blog\Controllers;

use Blog\Repositories\CommentRepository;
use Blog\Services\SecurityService;

class CommentController {
    private $commentRepository;
    private $pdo;
    
    public function __construct(CommentRepository $commentRepository, $pdo = null) {
        $this->commentRepository = $commentRepository;
        $this->pdo = $pdo;
    }
    
    /**
     * Добавление нового комментария с защитой от спама
     */
    public function add($articleId, $authorName, $authorEmail, $content, $csrfToken = '', $honeypotData = []) {
        // Проверка CSRF токена
        if (!SecurityService::validateCSRFToken($csrfToken)) {
            return [
                'success' => false,
                'message' => 'Ошибка безопасности. Обновите страницу и попробуйте снова.'
            ];
        }
        
        // Проверка honeypot поля
        if (!empty($honeypotData['name']) && !SecurityService::checkHoneypot($honeypotData['name'])) {
            // Молча отклоняем спам
            return [
                'success' => true,
                'message' => 'Спасибо! Ваш комментарий отправлен на модерацию.'
            ];
        }
        
        // Проверка rate limiting для комментариев
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!SecurityService::checkRateLimit('comment', $userIP, 3, 600)) { // 3 комментария за 10 минут
            return [
                'success' => false,
                'message' => 'Слишком много комментариев. Подождите немного перед отправкой следующего.'
            ];
        }
        
        // Валидация данных
        $errors = $this->validateCommentData([
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'content' => $content
        ]);
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors)
            ];
        }
        
        // Проверка на спам
        if (SecurityService::checkSpam($content, $authorEmail)) {
            // Увеличиваем лимит попыток для спамеров
            SecurityService::incrementRateLimit('comment', $userIP);
            
            return [
                'success' => false,
                'message' => 'Комментарий содержит недопустимый контент.'
            ];
        }
        
        // Проверка на дублирующиеся комментарии
        if ($this->pdo && SecurityService::checkDuplicateComment($this->pdo, $articleId, $content, $authorEmail)) {
            return [
                'success' => false,
                'message' => 'Вы уже оставляли похожий комментарий недавно.'
            ];
        }
        
        try {
            $result = $this->commentRepository->create($articleId, $authorName, $authorEmail, $content);
            
            if ($result) {
                // Увеличиваем счетчик использования (для честных пользователей)
                SecurityService::incrementRateLimit('comment', $userIP);
                
                return [
                    'success' => true,
                    'message' => 'Спасибо! Ваш комментарий отправлен на модерацию.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при добавлении комментария. Попробуйте еще раз.'
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
     * Получение комментариев для модерации
     */
    public function getForModeration($status = null) {
        $comments = $this->commentRepository->findAll($status);
        $stats = $this->getCommentStats();
        
        return [
            'comments' => $comments,
            'stats' => $stats
        ];
    }
    
    /**
     * Одобрение комментария
     */
    public function approve($commentId) {
        try {
            $result = $this->commentRepository->updateStatus($commentId, 'approved');
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Комментарий одобрен'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при одобрении комментария'
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
     * Отклонение комментария
     */
    public function reject($commentId) {
        try {
            $result = $this->commentRepository->updateStatus($commentId, 'rejected');
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Комментарий отклонен'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при отклонении комментария'
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
     * Удаление комментария
     */
    public function delete($commentId) {
        try {
            $result = $this->commentRepository->delete($commentId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Комментарий удален'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Ошибка при удалении комментария'
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
     * Получение статистики комментариев
     */
    public function getCommentStats() {
        return [
            'pending' => $this->commentRepository->getCount('pending'),
            'approved' => $this->commentRepository->getCount('approved'),
            'rejected' => $this->commentRepository->getCount('rejected'),
            'total' => $this->commentRepository->getCount('pending') + 
                      $this->commentRepository->getCount('approved') + 
                      $this->commentRepository->getCount('rejected')
        ];
    }
    
    /**
     * Валидация данных комментария
     */
    private function validateCommentData($data) {
        $errors = [];
        
        if (empty(trim($data['author_name']))) {
            $errors[] = 'Имя обязательно';
        }
        
        if (strlen(trim($data['author_name'])) > 100) {
            $errors[] = 'Имя слишком длинное (максимум 100 символов)';
        }
        
        // Проверка на подозрительные символы в имени
        if (preg_match('/[<>"\']/', $data['author_name'])) {
            $errors[] = 'Имя содержит недопустимые символы';
        }
        
        if (empty(trim($data['author_email']))) {
            $errors[] = 'Email обязателен';
        } elseif (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email адрес';
        }
        
        if (empty(trim($data['content']))) {
            $errors[] = 'Комментарий не может быть пустым';
        }
        
        if (strlen(trim($data['content'])) < 10) {
            $errors[] = 'Комментарий должен содержать минимум 10 символов';
        }
        
        if (strlen(trim($data['content'])) > 1000) {
            $errors[] = 'Комментарий слишком длинный (максимум 1000 символов)';
        }
        
        // Дополнительные проверки на спам
        $content = strtolower($data['content']);
        
        // Проверка на слишком много заглавных букв
        $uppercaseRatio = (strlen($data['content']) - strlen(strtolower($data['content']))) / strlen($data['content']);
        if ($uppercaseRatio > 0.6) {
            $errors[] = 'Слишком много заглавных букв';
        }
        
        // Проверка на подозрительные паттерны
        if (preg_match('/(.)\1{5,}/', $data['content'])) {
            $errors[] = 'Обнаружены повторяющиеся символы';
        }
        
        return $errors;
    }
}
?>