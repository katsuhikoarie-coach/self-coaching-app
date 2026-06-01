<?php
require 'config.php';

try {
    $pdo = getDB();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code     VARCHAR(50)  NOT NULL,
            name     VARCHAR(200) NOT NULL,
            price    INT UNSIGNED NOT NULL DEFAULT 0,
            tax_rate TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT '消費税率（8または10）',
            volume   VARCHAR(100) NOT NULL DEFAULT '' COMMENT '内容量',
            UNIQUE KEY uk_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "テーブル作成完了\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
