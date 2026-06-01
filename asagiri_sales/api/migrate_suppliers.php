<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db = getDB();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS suppliers (
          id INT AUTO_INCREMENT PRIMARY KEY,
          supplier_code VARCHAR(20) NOT NULL UNIQUE,
          supplier_name VARCHAR(100) NOT NULL,
          kana VARCHAR(100),
          tel VARCHAR(20),
          fax VARCHAR(20),
          email VARCHAR(100),
          address VARCHAR(200),
          contact_person VARCHAR(50),
          payment_terms VARCHAR(100),
          notes TEXT,
          is_active TINYINT(1) DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    jsonResponse(['ok' => true, 'message' => 'suppliers テーブルを作成しました（または既存）']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'サーバーエラーが発生しました']);
}
