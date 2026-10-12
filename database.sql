CREATE DATABASE IF NOT EXISTS book_management
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE book_management;

CREATE TABLE IF NOT EXISTS books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  author VARCHAR(150) NOT NULL,
  isbn VARCHAR(30) DEFAULT NULL,
  category VARCHAR(100) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional sample records
INSERT INTO books (title, author, isbn, category, price, quantity) VALUES
('The Alchemist', 'Paulo Coelho', '9780062315007', 'Fiction', 299.00, 8),
('Clean Code', 'Robert C. Martin', '9780132350884', 'Programming', 650.00, 4);
