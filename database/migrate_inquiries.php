<?php
/**
 * Migration: Create Inquiries Table for Contact / Lead Management
 */
require_once __DIR__ . '/../config/config.php';

$db = get_db();

$sql = "
CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(25) NULL,
    contact_raw VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied', 'archived') NOT NULL DEFAULT 'unread',
    admin_notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inquiries_status (status),
    INDEX idx_inquiries_created (created_at),
    INDEX idx_inquiries_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$db->exec($sql);
echo "Inquiries table created successfully!\n";

