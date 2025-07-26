<?php
// comments_admin.php - Админ-панель для модерации комментариев
require_once 'auth.php';
require_once '../functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Получаем данные администратора
$currentAdmin = getCurrentAdmin();

// Проверка подключения к БД
$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "<!DOCTYPE html><html><head><title>Ошибка подключения к БД</title></head><body>";
    echo "<h1>❌ Ошибка подключения к базе данных</h1>";
    echo "<p><a href='index.php'>← Назад в админку</a></p>";
    echo "</body></html>";
    exit;
}

// Обработка действий с комментариями
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем CSRF токен

        $commentId = (int)($_POST['comment_id'] ?? 0);
        
        if (isset($_POST['approve'])) {
            if (updateCommentStatus($commentId, 'approved')) {
                $message = 'Комментарий одобрен';
            } else {
                $error = 'Ошибка при одобрении комментария';
            }
        } elseif (isset($_POST['reject'])) {
            if (updateCommentStatus($commentId, 'rejected')) {
                $message = 'Комментарий отклонен';
            } else {
                $error = 'Ошибка при отклонении комментария';
            }
        } elseif (isset($_POST['delete'])) {
            if (deleteComment($commentId)) {
                $message = 'Комментарий удален';
            } else {
                $error = 'Ошибка при удалении комментария';
            }
        }
    
}

// Получаем параметры фильтрации
$statusFilter = $_GET['status'] ?? 'pending';
$validStatuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'pending';
}

// Получаем комментарии
if ($statusFilter === 'all') {
    $comments = getAllComments();
} else {
    $comments = getAllComments($statusFilter);
}

// Получаем статистику комментариев
$commentStats = [
    'pending' => count(getAllComments('pending')),
    'approved' => count(getAllComments('approved')),
    'rejected' => count(getAllComments('rejected')),
    'total' => count(getAllComments())
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация комментариев | IT Blog</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
        }
        
        .admin-user a {
            color: #fed7d7;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .admin-user a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .admin-nav {
            background: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 2rem;
        }
        
        .admin-nav a {
            color: #4a5568;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: #667eea;
            color: white;
        }
        
        .comments-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.pending h3 { color: #f6ad55; }
        .stat-card.approved h3 { color: #68d391; }
        .stat-card.rejected h3 { color: #fc8181; }
        .stat-card.total h3 { color: #667eea; }
        
        .filter-tabs {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-tabs ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 1rem;
        }
        
        .filter-tabs a {
            color: #4a5568;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tabs a:hover, .filter-tabs a.active {
            background: #667eea;
            color: white;
        }
        
        .comment-moderation {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .comment-moderation:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .comment-author-info h4 {
            margin: 0 0 0.25rem 0;
            color: #2d3748;
        }
        
        .comment-meta {
            font-size: 0.9rem;
            color: #718096;
        }
        
        .comment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .comment-status.pending {
            background: #fef5e7;
            color: #c05621;
        }
        
        .comment-status.approved {
            background: #f0fff4;
            color: #22543d;
        }
        
        .comment-status.rejected {
            background: #fed7d7;
            color: #c53030;
        }
        
        .comment-content {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .comment-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .btn-approve {
            background: #68d391;
            color: white;
        }
        
        .btn-approve:hover {
            background: #48bb78;
        }
        
        .btn-reject {
            background: #fc8181;
            color: white;
        }
        
        .btn-reject:hover {
            background: #f56565;
        }
        
        .btn-delete {
            background: #e53e3e;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c53030;
        }
        
        .btn-view {
            background: #667eea;
            color: white;
        }
        
        .btn-view:hover {
            background: #5a67d8;
        }
        
        .no-comments {
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .message, .error {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .message {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
        }
    </style>
</head>
<body>
    <!-- Заголовок админки -->
    <div class="admin-header">
        <div class="container">
            <div class="admin-title">
                <h1>💬 Модерация комментариев</h1>
            </div>
            <div class="admin-user">
                <span>Привет, <strong><?php echo htmlspecialchars($currentAdmin['username']) ?>!</strong></span>
                <a href="?logout=1">Выйти</a>
            </div>
        </div>
    </div>
    
    <!-- Навигация админки -->
    <div class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php">📄 Статьи</a></li>
                <li><a href="comments_admin.php" class="active">💬 Комментарии</a></li>
                <li><a href="../index.php" target="_blank">👁️ Просмотр сайта</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <!-- Статистика комментариев -->
        <div class="comments-stats">
            <div class="stat-card pending">
                <h3><?php echo $commentStats['pending'] ?></h3>
                <p>На модерации</p>
            </div>
            <div class="stat-card approved">
                <h3><?php echo $commentStats['approved'] ?></h3>
                <p>Одобрено</p>
            </div>
            <div class="stat-card rejected">
                <h3><?php echo $commentStats['rejected'] ?></h3>
                <p>Отклонено</p>
            </div>
            <div class="stat-card total">
                <h3><?php echo $commentStats['total'] ?></h3>
                <p>Всего</p>
            </div>
        </div>
        
        <!-- Фильтры -->
        <div class="filter-tabs">
            <ul>
                <li>
                    <a href="?status=pending" class="<?php echo $statusFilter === 'pending' ? 'active' : '' ?>">
                        ⏳ На модерации (<?php echo $commentStats['pending'] ?>)
                    </a>
                </li>
                <li>
                    <a href="?status=approved" class="<?php echo $statusFilter === 'approved' ? 'active' : '' ?>">
                        ✅ Одобренные (<?php echo $commentStats['approved'] ?>)
                    </a>
                </li>
                <li>
                    <a href="?status=rejected" class="<?php echo $statusFilter === 'rejected' ? 'active' : '' ?>">
                        ❌ Отклоненные (<?php echo $commentStats['rejected'] ?>)
                    </a>
                </li>
                <li>
                    <a href="?status=all" class="<?php echo $statusFilter === 'all' ? 'active' : '' ?>">
                        📋 Все комментарии (<?php echo $commentStats['total'] ?>)
                    </a>
                </li>
            </ul>
        </div>
        
        <main>
            <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Список комментариев -->
            <?php if (empty($comments)): ?>
            <div class="no-comments">
                <h3>💬 
                    <?php 
                    switch ($statusFilter) {
                        case 'pending': echo 'Нет комментариев на модерации'; break;
                        case 'approved': echo 'Нет одобренных комментариев'; break;
                        case 'rejected': echo 'Нет отклоненных комментариев'; break;
                        default: echo 'Комментариев пока нет';
                    }
                    ?>
                </h3>
                <p>
                    <?php if ($statusFilter === 'pending'): ?>
                    Все комментарии уже обработаны!
                    <?php else: ?>
                    Когда пользователи начнут оставлять комментарии, они появятся здесь.
                    <?php endif; ?>
                </p>
                <div style="margin-top: 1rem;">
                    <a href="admin/index.php" class="btn btn-view">📄 Управление статьями</a>
                    <a href="../index.php" class="btn btn-view" target="_blank">👁️ Просмотр сайта</a>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="comment-moderation">
                <div class="comment-header">
                    <div class="comment-author-info">
                        <h4><?php echo htmlspecialchars($comment['author_name']) ?></h4>
                        <div class="comment-meta">
                            📧 <?php echo htmlspecialchars($comment['author_email']) ?> • 
                            📅 <?php echo formatDateTime($comment['created_at']) ?> • 
                            📄 К статье: <a href="../article.php?id=<?php echo $comment['article_id'] ?>" target="_blank"><?php echo htmlspecialchars($comment['article_title']) ?></a>
                        </div>
                    </div>
                    <div class="comment-status <?php echo $comment['status'] ?>">
                        <?php 
                        switch ($comment['status']) {
                            case 'pending': echo '⏳ На модерации'; break;
                            case 'approved': echo '✅ Одобрен'; break;
                            case 'rejected': echo '❌ Отклонен'; break;
                        }
                        ?>
                    </div>
                </div>
                
                <div class="comment-content">
                    <?php echo nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
                
                <div class="comment-actions">
                    <?php if ($comment['status'] !== 'approved'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id'] ?>">
                        <button type="submit" name="approve" class="btn btn-approve">
                            ✅ Одобрить
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($comment['status'] !== 'rejected'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken ?>">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id'] ?>">
                        <button type="submit" name="reject" class="btn btn-reject">
                            ❌ Отклонить
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: inline;" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить этот комментарий? Это действие нельзя отменить.');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken ?>">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id'] ?>">
                        <button type="submit" name="delete" class="btn btn-delete">
                            🗑️ Удалить
                        </button>
                    </form>
                    
                    <a href="../article.php?id=<?php echo $comment['article_id'] ?>#comments" target="_blank" class="btn btn-view">
                        👁️ Просмотреть
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Автоматическое скрытие сообщений
        setTimeout(() => {
            const messages = document.querySelectorAll('.message, .error');
            messages.forEach(msg => {
                msg.style.opacity = '0.8';
                setTimeout(() => {
                    msg.style.display = 'none';
                }, 3000);
            });
        }, 5000);
        
        // Подтверждение действий
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('button[name="delete"]')) {
                    if (!confirm('Вы уверены, что хотите удалить этот комментарий? Это действие нельзя отменить.')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
        
        // Клавиатурные сокращения
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            // A - одобрить первый комментарий на модерации
            if (e.key === 'a' || e.key === 'A') {
                const approveBtn = document.querySelector('.btn-approve');
                if (approveBtn) {
                    approveBtn.click();
                }
            }
            
            // R - отклонить первый комментарий на модерации
            if (e.key === 'r' || e.key === 'R') {
                const rejectBtn = document.querySelector('.btn-reject');
                if (rejectBtn) {
                    rejectBtn.click();
                }
            }
        });
        
        // Автообновление количества комментариев на модерации
        setInterval(() => {
            fetch('comments_admin.php?ajax=stats')
                .then(response => response.json())
                .then(data => {
                    // Обновляем счетчики (если добавлен AJAX endpoint)
                })
                .catch(err => {
                    // Игнорируем ошибки автообновления
                });
        }, 30000); // каждые 30 секунд
    </script>
</body>
</html>