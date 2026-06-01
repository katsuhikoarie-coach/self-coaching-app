<?php
/**
 * 管理画面用 DBマイグレーション
 * 実行: https://fc-order.asagiriyamano.jp/admin-migrate.php?key=migrate2026
 * 実行後は必ず削除してください
 */
if (($_GET['key'] ?? '') !== 'migrate2026') { http_response_code(403); die('Access denied'); }
header('Content-Type: text/html; charset=UTF-8');

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=asagiri_fcorder;charset=utf8mb4',
        'asagiri_fcorder', 'JvsHzdyKDNk8',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB接続エラー: ' . htmlspecialchars($e->getMessage()));
}

echo "<pre style='font-family:monospace;font-size:14px;padding:20px;line-height:1.8'>\n";
echo "=== 管理画面用 DBマイグレーション ===\n\n";

$cols = array_column($pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_ASSOC), 'Field');

// billing_status カラム追加
if (in_array('billing_status', $cols)) {
    echo "SKIP: billing_status は既に存在\n";
} else {
    $pdo->exec("ALTER TABLE orders ADD COLUMN billing_status ENUM('unbilled','billed') NOT NULL DEFAULT 'unbilled' COMMENT '請求ステータス' AFTER status");
    echo "ADD:  billing_status を追加 ✓\n";
}

// month_period カラム追加
if (in_array('month_period', $cols)) {
    echo "SKIP: month_period は既に存在\n";
} else {
    $pdo->exec("ALTER TABLE orders ADD COLUMN month_period VARCHAR(7) NULL COMMENT '月度 例:2026-05' AFTER billing_status");
    echo "ADD:  month_period を追加 ✓\n";
}

// 既存注文の month_period を計算して設定
// 21日以降 → 翌月度 / 20日以前 → 当月度
$updated = $pdo->exec("
    UPDATE orders
    SET month_period = CASE
        WHEN DAY(order_date) >= 21 THEN DATE_FORMAT(order_date + INTERVAL 1 MONTH, '%Y-%m')
        ELSE DATE_FORMAT(order_date, '%Y-%m')
    END
    WHERE month_period IS NULL
");
echo "UPDATE: month_period を {$updated} 件設定 ✓\n\n";

// インデックス追加（月度検索の高速化）
try {
    $pdo->exec("ALTER TABLE orders ADD INDEX idx_month_period (month_period)");
    echo "INDEX: idx_month_period を追加 ✓\n";
} catch (PDOException $e) {
    echo "SKIP:  idx_month_period は既に存在（または不要）\n";
}

// 結果確認
echo "\n=== 現在の orders テーブル構造 ===\n";
foreach ($pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo "  {$c['Field']} | {$c['Type']}\n";
}

echo "\n=== 完了 ===\n";
echo "⚠ このファイル (admin-migrate.php) をサーバーから削除してください。\n";
echo "</pre>\n";
