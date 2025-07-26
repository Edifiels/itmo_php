<?php
// index.php - Главная страница с использованием ООП подхода
require_once 'autoload.php';

use Blog\Controllers\ArticleController;
use Blog\Repositories\ArticleRepository;
use Blog\Repositories\CommentRepository;
use Blog\Repositories\UserRepository;
use Blog\Services\HelperService;

try {
    // Получаем подключение к БД
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Ошибка подключения к базе данных");
    }
    
    // Создаем репозитории
    $articleRepository = new ArticleRepository($pdo);
    $commentRepository = new CommentRepository($pdo);
    $userRepository = new UserRepository($pdo);
    
    // Создаем контроллер
    $articleController = new ArticleController($articleRepository, $commentRepository, $userRepository);
    
    // Получаем номер страницы
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 6;
    
    // Получаем данные через контроллер
    $data = $articleController->index($page, $perPage);
    
    $allArticles = $data['articles'];
    $pagination = $data['pagination'];
    $popularArticles = $data['popularArticles'];
    $stats = $data['stats'];
    
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><title>Ошибка</title></head><body>";
    echo "<h1>❌ Ошибка: " . htmlspecialchars($e->getMessage()) . "</h1>";
    echo "<p><a href='database/migration.php'>Запустить миграцию данных</a></p>";
    echo "</body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Blog - Статьи о веб-разработке<?php echo $page > 1 ? ' | Страница ' . $page : '' ?></title>
    <link rel="stylesheet" href="style.css">
    <meta name="description" content="Современный IT блог с статьями о веб-разработке, PHP, JavaScript, базах данных и других технологиях">
    <meta name="keywords" content="PHP, JavaScript, веб-разработка, программирование, IT, блог">
</head>
<body>
    <header>
        <div class="container">
            <h1>🚀 IT Blog</h1>
            <p>Статьи о современной веб-разработке</p>
        </div>
    </header>

    <main class="container">
        <!-- Статистика -->
        <section class="stats">
            <div class="stat-item">
                <h3><?php echo $stats['articles'] ?></h3>
                <p>Статей</p>
            </div>
            <div class="stat-item">
                <h3><?php echo HelperService::formatViews($stats['views']) ?></h3>
                <p>Просмотров</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $stats['authors'] ?></h3>
                <p>Авторов</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $stats['comments'] ?></h3>
                <p>Комментариев</p>
            </div>
        </section>

        <!-- Поиск -->
        <section class="search-section">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Поиск статей..." class="search-input" 
                       value="<?php echo htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit" class="search-btn">🔍 Найти</button>
            </form>
            <div class="search-links">
                <a href="admin/index.php" class="admin-link">📝 Управление статьями</a>
                <a href="#popular" class="scroll-link">🔥 Популярные</a>
            </div>
        </section>

        <!-- Популярные статьи -->
        <?php if (!empty($popularArticles) && $page == 1): ?>
        <section class="popular-section" id="popular">
            <h2>🔥 Популярные статьи</h2>
            <div class="articles-grid">
                <?php foreach ($popularArticles as $article): ?>
                <article class="article-card popular">
                    <div class="article-header">
                        <h3 class="article-title">
                            <a href="article.php?id=<?php echo $article->getId() ?>">
                                <?php echo htmlspecialchars($article->getTitle()) ?>
                            </a>
                        </h3>
                        <p class="article-excerpt">
                            <?php echo htmlspecialchars($article->getExcerpt()) ?>
                        </p>
                    </div>
                    
                    <div class="article-meta">
                        <span>👤 <?php echo htmlspecialchars($article->getAuthor()['name']) ?></span>
                        <span>📁 <?php echo htmlspecialchars($article->getCategory()) ?></span>
                        <span>📅 <?php echo HelperService::formatDate($article->getDate()) ?></span>
                    </div>
                    
                    <div class="article-tags">
                        <?php echo HelperService::renderTags($article->getTags()) ?>
                    </div>
                    
                    <div class="article-stats">
                        <span>👁️ <?php echo HelperService::formatViews($article->getViews()) ?></span>
                        <span>⏱️ <?php echo $article->getReadingTime() ?> мин</span>
                        <span>💬 <?php echo $commentRepository->countByArticle($article->getId()) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Все статьи -->
        <section class="articles">
            <h2>
                📚 <?php echo $page == 1 ? 'Последние статьи' : "Статьи - страница $page" ?>
                <small>(<?php echo $pagination['total_articles'] ?> всего)</small>
            </h2>
            
            <?php if (empty($allArticles)): ?>
                <div class="no-articles">
                    <h3>📝 Статей пока нет</h3>
                    <p>Создайте первую статью в <a href="admin/index.php">админ-панели</a></p>
                    <div style="margin-top: 2rem;">
                        <a href="database/migration.php" class="btn btn-secondary">🔄 Запустить миграцию данных</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($allArticles as $article): ?>
                    <article class="article-card">
                        <div class="article-header">
                            <h3 class="article-title">
                                <a href="article.php?id=<?php echo $article->getId() ?>">
                                    <?php echo htmlspecialchars($article->getTitle()) ?>
                                </a>
                            </h3>
                            <p class="article-excerpt">
                                <?php echo htmlspecialchars($article->getExcerpt()) ?>
                            </p>
                        </div>
                        
                        <div class="article-meta">
                            <span>👤 <?php echo htmlspecialchars($article->getAuthor()['name']) ?></span>
                            <span>📁 <?php echo htmlspecialchars($article->getCategory()) ?></span>
                            <span>📅 <?php echo HelperService::formatDate($article->getDate()) ?></span>
                        </div>
                        
                        <div class="article-tags">
                            <?php echo HelperService::renderTags($article->getTags()) ?>
                        </div>
                        
                        <div class="article-stats">
                            <span>👁️ <?php echo HelperService::formatViews($article->getViews()) ?></span>
                            <span>⏱️ <?php echo $article->getReadingTime() ?> мин</span>
                            <span>💬 <?php echo $commentRepository->countByArticle($article->getId()) ?></span>
                        </div>
                        
                        <div class="article-actions">
                            <a href="article.php?id=<?php echo $article->getId() ?>" class="btn btn-primary">
                                Читать далее →
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Пагинация -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-wrapper">
                    <?php echo HelperService::renderPagination($pagination, 'index.php'); ?>
                    
                    <!-- Информация о пагинации -->
                    <div class="pagination-info">
                        <?php 
                        $start = ($pagination['current_page'] - 1) * $pagination['per_page'] + 1;
                        $end = min($pagination['current_page'] * $pagination['per_page'], $pagination['total_articles']);
                        ?>
                        Показаны статьи <strong><?php echo "$start-$end" ?></strong> 
                        из <strong><?php echo $pagination['total_articles'] ?></strong>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        
        <!-- Дополнительная информация -->
        <?php if ($page == 1): ?>
        <section class="info-section">
            <div class="info-cards">
                <div class="info-card">
                    <h3>🎯 О проекте</h3>
                    <p>IT Blog - это современная платформа для изучения веб-разработки. Теперь с ООП архитектурой!</p>
                </div>
                <div class="info-card">
                    <h3>✍️ Для авторов</h3>
                    <p>Хотите поделиться своими знаниями? Воспользуйтесь <a href="admin/index.php">админ-панелью</a> для публикации статей.</p>
                </div>
                <div class="info-card">
                    <h3>🔍 Навигация</h3>
                    <p>Используйте <a href="search.php">поиск</a> для быстрого поиска статей по ключевым словам или тегам.</p>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <!-- Кнопка "Наверх" -->
    <button id="scrollToTop" class="scroll-to-top" title="Наверх">↑</button>

    <script>
        // Плавная прокрутка для якорных ссылок
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Кнопка "Наверх"
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Анимация появления карточек при прокрутке
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Применяем анимацию к карточкам статей
        document.querySelectorAll('.article-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
        
        // Предзагрузка следующей страницы (опционально)
        <?php if ($pagination['has_next']): ?>
        const nextPageLink = 'index.php?page=<?php echo $pagination['next_page'] ?>';
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = nextPageLink;
        document.head.appendChild(link);
        <?php endif; ?>
        
        // Клавиатурная навигация по страницам
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            if (e.key === 'ArrowLeft' && <?php echo $pagination['has_prev'] ? 'true' : 'false' ?>) {
                window.location.href = 'index.php?page=<?php echo $pagination['prev_page'] ?? 1 ?>';
            } else if (e.key === 'ArrowRight' && <?php echo $pagination['has_next'] ? 'true' : 'false' ?>) {
                window.location.href = 'index.php?page=<?php echo $pagination['next_page'] ?? 1 ?>';
            }
        });
    </script>
</body>
</html>