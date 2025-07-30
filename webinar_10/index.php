<?php
// index.php - Главная страница с SEO оптимизацией и безопасностью
require_once 'autoload.php';

use Blog\Controllers\ArticleController;
use Blog\Repositories\ArticleRepository;
use Blog\Repositories\CommentRepository;
use Blog\Repositories\UserRepository;
use Blog\Services\HelperService;
use Blog\Services\SecurityService;
use Blog\Services\SEOService;

// Инициализация безопасной сессии
SecurityService::initSecureSession();

// Очистка старых данных rate limiting
SecurityService::cleanupRateLimit();

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
    
    // Подготавливаем SEO данные
    $seoData = SEOService::getHomePageMeta();
    if ($page > 1) {
        $seoData['title'] = 'IT Blog - Страница ' . $page . ' | Статьи о веб-разработке';
        $seoData['description'] = 'IT блог - страница ' . $page . '. Статьи о современной веб-разработке, программировании и технологиях.';
    }
    
    // Подготавливаем хлебные крошки
    $breadcrumbs = [
        ['name' => 'Главная', 'url' => '/']
    ];
    
    if ($page > 1) {
        $breadcrumbs[] = ['name' => 'Страница ' . $page, 'url' => ''];
    }
    
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
    <title><?php echo htmlspecialchars($seoData['title']) ?></title>
    
    <!-- SEO мета-теги -->
    <?php echo SEOService::generateMetaTags(
        $seoData['title'],
        $seoData['description'],
        $seoData['keywords']
    ) ?>
    
    <!-- Стили -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Структурированные данные для хлебных крошек -->
    <?php echo SEOService::generateBreadcrumbSchema($breadcrumbs) ?>
    
    <!-- Дополнительные SEO теги -->
    <link rel="alternate" type="application/rss+xml" title="IT Blog RSS" href="/rss.xml">
    <meta name="theme-color" content="#667eea">
    <meta name="msapplication-TileColor" content="#667eea">
</head>
<body>
    <header>
        <div class="container">
            <h1>🚀 IT Blog</h1>
            <p>Статьи о современной веб-разработке</p>
        </div>
    </header>

    <main class="container">
        <!-- Хлебные крошки -->
        <?php if ($page > 1): ?>
        <div class="breadcrumbs-wrapper">
            <?php echo SEOService::generateBreadcrumbs($breadcrumbs) ?>
        </div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <section class="stats" aria-label="Статистика блога">
            <div class="stat-item">
                <h2><?php echo $stats['articles'] ?></h2>
                <p>Статей</p>
            </div>
            <div class="stat-item">
                <h2><?php echo HelperService::formatViews($stats['views']) ?></h2>
                <p>Просмотров</p>
            </div>
            <div class="stat-item">
                <h2><?php echo $stats['authors'] ?></h2>
                <p>Авторов</p>
            </div>
            <div class="stat-item">
                <h2><?php echo $stats['comments'] ?></h2>
                <p>Комментариев</p>
            </div>
        </section>

        <!-- Поиск -->
        <section class="search-section" role="search">
            <form action="search.php" method="GET" class="search-form">
                <label for="search-input" class="visually-hidden">Поиск статей</label>
                <input type="text" name="q" id="search-input" placeholder="Поиск статей..." 
                       class="search-input" value="<?php echo htmlspecialchars($_GET['q'] ?? '') ?>"
                       aria-label="Поиск по статьям блога">
                <button type="submit" class="search-btn" aria-label="Выполнить поиск">🔍 Найти</button>
            </form>
            <nav class="search-links" aria-label="Дополнительные ссылки">
                <a href="admin/index.php" class="admin-link">📝 Управление статьями</a>
                <a href="#popular" class="scroll-link">🔥 Популярные</a>
            </nav>
        </section>

        <!-- Популярные статьи -->
        <?php if (!empty($popularArticles) && $page == 1): ?>
        <section class="popular-section" id="popular" aria-labelledby="popular-heading">
            <h2 id="popular-heading">🔥 Популярные статьи</h2>
            <div class="articles-grid">
                <?php foreach ($popularArticles as $article): ?>
                <article class="article-card popular" itemscope itemtype="https://schema.org/Article">
                    <header class="article-header">
                        <h3 class="article-title" itemprop="headline">
                            <a href="article.php?id=<?php echo $article->getId() ?>" itemprop="url">
                                <?php echo htmlspecialchars($article->getTitle()) ?>
                            </a>
                        </h3>
                        <p class="article-excerpt" itemprop="description">
                            <?php echo htmlspecialchars($article->getExcerpt()) ?>
                        </p>
                    </header>
                    
                    <div class="article-meta">
                        <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                            👤 <span itemprop="name"><?php echo htmlspecialchars($article->getAuthor()['name']) ?></span>
                        </span>
                        <span>📁 <?php echo htmlspecialchars($article->getCategory()) ?></span>
                        <time datetime="<?php echo date('c', strtotime($article->getDate())) ?>" itemprop="datePublished">
                            📅 <?php echo HelperService::formatDate($article->getDate()) ?>
                        </time>
                    </div>
                    
                    <div class="article-tags">
                        <?php echo HelperService::renderTags($article->getTags()) ?>
                    </div>
                    
                    <div class="article-stats">
                        <span>👁️ <?php echo HelperService::formatViews($article->getViews()) ?></span>
                        <span itemprop="timeRequired" content="PT<?php echo $article->getReadingTime() ?>M">
                            ⏱️ <?php echo $article->getReadingTime() ?> мин
                        </span>
                        <span>💬 <?php echo $commentRepository->countByArticle($article->getId()) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Все статьи -->
        <section class="articles" aria-labelledby="articles-heading">
            <h2 id="articles-heading">
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
                    <?php foreach ($allArticles as $index => $article): ?>
                    <article class="article-card" itemscope itemtype="https://schema.org/Article">
                        <header class="article-header">
                            <h3 class="article-title" itemprop="headline">
                                <a href="article.php?id=<?php echo $article->getId() ?>" itemprop="url">
                                    <?php echo htmlspecialchars($article->getTitle()) ?>
                                </a>
                            </h3>
                            <p class="article-excerpt" itemprop="description">
                                <?php echo htmlspecialchars($article->getExcerpt()) ?>
                            </p>
                        </header>
                        
                        <div class="article-meta">
                            <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                                👤 <span itemprop="name"><?php echo htmlspecialchars($article->getAuthor()['name']) ?></span>
                            </span>
                            <span itemprop="articleSection"><?php echo htmlspecialchars($article->getCategory()) ?></span>
                            <time datetime="<?php echo date('c', strtotime($article->getDate())) ?>" itemprop="datePublished">
                                📅 <?php echo HelperService::formatDate($article->getDate()) ?>
                            </time>
                        </div>
                        
                        <div class="article-tags" itemprop="keywords">
                            <?php echo HelperService::renderTags($article->getTags()) ?>
                        </div>
                        
                        <div class="article-stats">
                            <span>👁️ <?php echo HelperService::formatViews($article->getViews()) ?></span>
                            <span itemprop="timeRequired" content="PT<?php echo $article->getReadingTime() ?>M">
                                ⏱️ <?php echo $article->getReadingTime() ?> мин
                            </span>
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
                <nav class="pagination-wrapper" aria-label="Навигация по страницам">
                    <?php echo HelperService::renderPagination($pagination, 'index.php'); ?>
                    
                    <!-- Информация о пагинации -->
                    <div class="pagination-info" role="status" aria-live="polite">
                        <?php 
                        $start = ($pagination['current_page'] - 1) * $pagination['per_page'] + 1;
                        $end = min($pagination['current_page'] * $pagination['per_page'], $pagination['total_articles']);
                        ?>
                        Показаны статьи <strong><?php echo "$start-$end" ?></strong> 
                        из <strong><?php echo $pagination['total_articles'] ?></strong>
                    </div>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        
        <!-- Дополнительная информация -->
        <?php if ($page == 1): ?>
        <section class="info-section" aria-labelledby="info-heading">
            <h2 id="info-heading" class="visually-hidden">Дополнительная информация</h2>
            <div class="info-cards">
                <article class="info-card">
                    <h3>🎯 О проекте</h3>
                    <p>IT Blog - это современная платформа для изучения веб-разработки с улучшенной безопасностью и SEO!</p>
                </article>
                <article class="info-card">
                    <h3>✍️ Для авторов</h3>
                    <p>Хотите поделиться своими знаниями? Воспользуйтесь <a href="admin/index.php">админ-панелью</a> для публикации статей.</p>
                </article>
                <article class="info-card">
                    <h3>🔍 Навигация</h3>
                    <p>Используйте <a href="search.php">поиск</a> для быстрого поиска статей по ключевым словам или тегам.</p>
                </article>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <!-- Кнопка "Наверх" -->
    <button id="scrollToTop" class="scroll-to-top" title="Наверх" aria-label="Прокрутить наверх">↑</button>

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
        
        // Улучшенная доступность для screen readers
        document.addEventListener('DOMContentLoaded', function() {
            // Объявляем количество найденных статей для screen readers
            const articlesCount = <?php echo count($allArticles) ?>;
            if (articlesCount > 0) {
                const announcement = document.createElement('div');
                announcement.setAttribute('aria-live', 'polite');
                announcement.setAttribute('aria-atomic', 'true');
                announcement.className = 'visually-hidden';
                announcement.textContent = `Загружено ${articlesCount} статей на странице ${<?php echo $page ?>}`;
                document.body.appendChild(announcement);
            }
        });
    </script>
</body>
</html>