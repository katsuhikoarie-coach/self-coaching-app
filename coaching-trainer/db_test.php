<?php
// DB接続診断スクリプト（確認後は削除してください）
$dbName = 'asagiri_coach';

$configs = [
    ['user' => 'asagiri',       'pass' => 'teamflow2011'],
    ['user' => 'asagiri_coach', 'pass' => 'teamflow2011'],
    ['user' => 'asagiri',       'pass' => '6BDtZKPKQJJB'],
    ['user' => 'asagiri_coach', 'pass' => '6BDtZKPKQJJB'],
    ['user' => 'asagiri',       'pass' => '6BDtZKPK'],
];

echo '<pre style="font-family:monospace;font-size:13px;line-height:2">';
echo 'PHP ' . PHP_VERSION . "\n";
echo 'PDO drivers: ' . implode(', ', PDO::getAvailableDrivers()) . "\n\n";

foreach ($configs as $c) {
    $dsn = 'mysql:host=localhost;dbname=' . $dbName . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo '[OK] user=' . $c['user'] . '  pass=' . $c['pass'] . "\n";
        $pdo = null;
    } catch (PDOException $e) {
        echo '[NG] user=' . $c['user'] . '  pass=' . $c['pass'] . "\n";
        echo '     ' . $e->getMessage() . "\n";
    }
}
echo '</pre>';
echo '<p style="color:red;font-family:sans-serif"><strong>確認後このファイルを削除してください</strong></p>';
