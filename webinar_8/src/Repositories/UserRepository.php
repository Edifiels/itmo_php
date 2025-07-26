<?php
namespace Blog\Repositories;

use Blog\Models\User;
use PDO;

class UserRepository {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Получение всех авторов
     */
    public function findAll() {
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY name");
        $usersData = $stmt->fetchAll();
        
        $users = [];
        foreach ($usersData as $data) {
            $users[] = new User($data);
        }
        
        return $users;
    }
    
    /**
     * Получение пользователя по ID
     */
    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return new User($data);
    }
    
    /**
     * Получение категорий (добавлено для совместимости)
     */
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение всех тегов
     */
    public function getAllTags() {
        $stmt = $this->pdo->query("SELECT name FROM tags ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Подсчет пользователей
     */
    public function getCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }
}
?>