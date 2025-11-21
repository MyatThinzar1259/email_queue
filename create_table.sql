CREATE DATABASE IF NOT EXISTS email_queue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE email_queue;

CREATE TABLE email_queue (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_attempts (attempts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;