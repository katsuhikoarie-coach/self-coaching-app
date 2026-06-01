<?php
set_time_limit(120);
$pdo = new PDO(
    'mysql:host=localhost;dbname=asagiri_sales;charset=utf8mb4',
    'asagiri_sales',
    'asa123giri',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

header('Content-Type: text/plain; charset=utf-8');

$colList = '`code`,`name`,`price`,`genre`,`supplier`,`discontinued`,`created_at`,`updated_at`';

echo "-- products export from asagiri_sales\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
echo "SET NAMES utf8mb4;\n";
echo "SET foreign_key_checks=0;\n";
echo "TRUNCATE TABLE `products`;\n\n";

$stmt = $pdo->query("SELECT $colList FROM products ORDER BY code");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $vals = [];
    foreach ($row as $v) {
        $vals[] = ($v === null) ? 'NULL' : $pdo->quote((string)$v);
    }
    echo "INSERT INTO `products` ($colList) VALUES (" . implode(',', $vals) . ");\n";
    $count++;
}

echo "\n-- Total: $count rows\n";
