<?php
// GETパラメータ: center_type（BC / FC / 販社）
// レスポンス: JSON配列
// active=1 の商品のみ返す

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── DB設定（環境に合わせて変更） ──────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'asagiri_fcorder');
define('DB_USER', 'asagiri_fcorder');
define('DB_PASS', 'JvsHzdyKDNk8');
define('DB_CHARSET', 'utf8mb4');

// ── center_type → 価格カラム対応 ────────────────────────────
$center_type = isset($_GET['center_type']) ? trim($_GET['center_type']) : 'FC';

$price_column_map = [
    'BC'   => 'price_bc',
    'FC'   => 'price_fc',
    '販社' => 'price_hansha',
];

if (!array_key_exists($center_type, $price_column_map)) {
    http_response_code(400);
    echo json_encode(['error' => 'center_type は BC / FC / 販社 のいずれかを指定してください'], JSON_UNESCAPED_UNICODE);
    exit;
}

$price_col = $price_column_map[$center_type];

// ── DB接続 ──────────────────────────────────────────────────
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB接続エラー'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── クエリ ──────────────────────────────────────────────────
$sql = "
    SELECT
        code,
        name,
        category,
        price_fc,
        price_bc,
        tax_rate
    FROM products
    WHERE active = 1
    ORDER BY id ASC
";

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'クエリエラー'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 数値型に変換
foreach ($rows as &$row) {
    $row['price_fc'] = (int)   $row['price_fc'];
    $row['price_bc'] = (int)   $row['price_bc'];
    $row['tax_rate'] = (float) $row['tax_rate'];
}
unset($row);

echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
