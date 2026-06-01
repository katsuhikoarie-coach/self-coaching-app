<?php
set_time_limit(30);
$pdo = new PDO(
    'mysql:host=localhost;dbname=asagiri_sales;charset=utf8mb4',
    'asagiri_sales',
    'asa123giri',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
header('Content-Type: text/plain; charset=utf-8');
foreach (['este_sales', 'este_sales_items'] as $table) {
    echo "-- ========== $table ==========\n";
    try {
        $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        echo $row[1] . ";\n\n";
    } catch (Exception $e) {
        echo "-- テーブルなし: " . $e->getMessage() . "\n\n";
    }
}
