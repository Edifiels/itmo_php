-- database/structure.sql
-- Создание структуры базы данных для IT блога

-- Создание базы данных (если не существует)
CREATE DATABASE IF NOT EXISTS it_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_blog;

-- 1. Таблица авторов (users)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Таблица категорий
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Таблица тегов
DROP TABLE IF EXISTS tags;
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Главная таблица статей
DROP TABLE IF EXISTS articles;
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    content TEXT NOT NULL,
    excerpt TEXT,
    author_id INT NOT NULL,
    category_id INT NOT NULL,
    reading_time INT DEFAULT 5,
    views INT DEFAULT 0,
    status ENUM('draft', 'published') DEFAULT 'published',
    published_at DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    
    -- Индексы для оптимизации запросов
    INDEX idx_author (author_id),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_published (published_at),
    INDEX idx_views (views),
    
    -- Полнотекстовый индекс для поиска
    FULLTEXT KEY ft_search (title, content, excerpt)
);

-- 5. Связующая таблица статьи-теги (многие ко многим)
DROP TABLE IF EXISTS article_tags;
CREATE TABLE article_tags (
    article_id INT NOT NULL,
    tag_id INT NOT NULL,
    
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Вставка тестовых данных

-- Авторы
INSERT INTO users (id, name, email, bio) VALUES
(1, 'Анна Разработчик', 'anna@blog.ru', 'Senior PHP разработчик с 8-летним опытом'),
(2, 'Дмитрий Архитектор', 'dmitry@blog.ru', 'Технический архитектор и fullstack разработчик'),
(3, 'Мария Фронтенд', 'maria@blog.ru', 'Frontend разработчик, UI/UX дизайнер');

-- Категории
INSERT INTO categories (id, name, slug, description) VALUES
(1, 'PHP и Backend', 'php-backend', 'Статьи о серверной разработке на PHP'),
(2, 'Frontend', 'frontend', 'Клиентская разработка: HTML, CSS, JavaScript'),
(3, 'Базы данных', 'databases', 'Работа с MySQL, PostgreSQL и другими БД'),
(4, 'DevOps', 'devops', 'Развертывание, CI/CD, серверное администрирование');

-- Теги
INSERT INTO tags (name, slug) VALUES
('PHP', 'php'),
('Backend', 'backend'),
('API', 'api'),
('MySQL', 'mysql'),
('Оптимизация', 'optimization'),
('JavaScript', 'javascript'),
('Frontend', 'frontend');

-- Статьи
INSERT INTO articles (id, title, slug, content, excerpt, author_id, category_id, reading_time, published_at, views) VALUES
(1, 'Основы PHP 8: Новые возможности', 'osnovy-php-8-novye-vozmozhnosti', 
'PHP 8 принес множество долгожданных изменений. JIT-компилятор значительно ускоряет выполнение кода. Union Types позволяют указывать несколько типов для параметра. Named Arguments делают код более читаемым. Attributes заменяют комментарии-аннотации. Match expression - более мощная альтернатива switch. Nullsafe operator упрощает работу с цепочками вызовов. Все эти нововведения делают PHP более современным и производительным языком программирования.',
'Обзор ключевых нововведений PHP 8: JIT, Union Types, Named Arguments',
1, 1, 8, '2025-07-15', 5),

(2, 'Создание REST API на PHP', 'sozdanie-rest-api-na-php',
'REST API - основа современных веб-приложений. Начнем с базовой структуры: создадим endpoints для GET, POST, PUT, DELETE операций. Важно правильно обрабатывать HTTP заголовки и коды ответов. Аутентификация через JWT токены обеспечит безопасность. Валидация входных данных защитит от ошибок. CORS настройки позволят работать с фронтендом. Документирование API через OpenAPI стандарт поможет другим разработчикам.',
'Пошаговое создание REST API на PHP с примерами кода',
1, 1, 12, '2025-07-12', 0),

(3, 'MySQL оптимизация запросов', 'mysql-optimizaciya-zaprosov',
'Производительность БД критически важна. Индексы - первый шаг к оптимизации. EXPLAIN покажет план выполнения запроса. Избегайте SELECT * в продакшене. JOIN операции требуют особого внимания к индексам. Денормализация иногда оправдана. Партицирование таблиц для больших объемов данных. Мониторинг slow query log выявит проблемные места. Кеширование на уровне приложения уменьшит нагрузку на БД.',
'Техники оптимизации MySQL для увеличения производительности',
2, 3, 10, '2025-07-08', 0),

(4, 'JavaScript ES2024: Новинки года', 'javascript-es2024-novinki-goda',
'JavaScript продолжает развиваться. Array.with() позволяет создавать новые массивы с измененными элементами. Object.groupBy() упрощает группировку данных. Promise.withResolvers() дает больше контроля над промисами. Temporal API наконец заменит Date. Import attributes улучшают работу с модулями. Decorators стандартизируются. Эти нововведения делают JavaScript еще более мощным и удобным для разработки.',
'Обзор новых возможностей JavaScript ES2024',
3, 2, 6, '2025-07-05', 0);

-- Связи статьи-теги
INSERT INTO article_tags (article_id, tag_id) VALUES
-- Статья 1: PHP 8
(1, 1), -- PHP
(1, 2), -- Backend

-- Статья 2: REST API
(2, 1), -- PHP
(2, 3), -- API
(2, 2), -- Backend

-- Статья 3: MySQL
(3, 4), -- MySQL
(3, 5), -- Оптимизация

-- Статья 4: JavaScript ES2024
(4, 6), -- JavaScript
(4, 7); -- Frontend