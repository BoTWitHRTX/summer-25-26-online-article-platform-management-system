-- ================================================================
-- INKWELL - DATABASE
-- Import this file ONCE in phpMyAdmin (Import -> choose file -> Go)
-- ================================================================

CREATE DATABASE IF NOT EXISTS inkwell_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inkwell_db;

-- ----------------------------------------------------------------
-- 1. USERS  (one table holds all 3 roles: admin, author, reader)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    name               VARCHAR(100) NOT NULL,
    email              VARCHAR(120) NOT NULL,
    contact            VARCHAR(30)  NOT NULL,
    username           VARCHAR(50)  NOT NULL UNIQUE,
    password           VARCHAR(255) NOT NULL,             -- stored as a password_hash()
    role               ENUM('admin','author','reader') NOT NULL DEFAULT 'reader',
    status             ENUM('active','suspended') NOT NULL DEFAULT 'active',
    bio                VARCHAR(255) NOT NULL DEFAULT '',  -- author pen-name / bio
    address            VARCHAR(255) NOT NULL DEFAULT '',  -- reader shipping/contact address
    order_limit        DECIMAL(10,2) NOT NULL DEFAULT 100.00, -- admin-controlled: max a reader may spend on one order
    security_question  VARCHAR(150) NOT NULL DEFAULT '',
    security_answer    VARCHAR(255) NOT NULL DEFAULT '',  -- stored as a password_hash()
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 2. ARTICLES  (books/articles written by authors)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    author_id  INT NOT NULL,
    title      VARCHAR(200) NOT NULL,
    category   VARCHAR(60)  NOT NULL,
    content    TEXT NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status     ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 3. LIKES  (reader likes a published article, one like per reader)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    reader_id  INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_like (article_id, reader_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (reader_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 4. COMMENTS  (reader CRUD: create / edit / delete their own comment)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    reader_id  INT NOT NULL,
    comment    VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (reader_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 5. RATINGS  (1-5 stars, one rating per reader per article)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    reader_id  INT NOT NULL,
    rating     TINYINT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_rating (article_id, reader_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (reader_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 6. ORDERS  (reader buys a published article; capped by order_limit)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    reader_id  INT NOT NULL,
    article_id INT NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reader_id)  REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 7. FEEDBACK  (admin -> author, CRUD by admin)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT NOT NULL,
    author_id  INT NOT NULL,
    article_id INT NULL,
    message    VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (author_id)  REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 8. ACTIVITY LOG  (audit trail the admin can read)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    username   VARCHAR(50)  NOT NULL,
    role       VARCHAR(20)  NOT NULL,
    action     VARCHAR(255) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- No seed rows below: config.php creates a working admin account
-- automatically the first time the app runs (admin / admin123).
-- Sign up author and reader accounts from the register page.
