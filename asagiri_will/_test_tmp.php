<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "PHP OK: " . phpversion() . "\n";

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=asagiri_sales;charset=utf8mb4',
        'asagiri_sales',
        'asa123giri',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $cnt = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "DB接続OK: products = $cnt 件\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
