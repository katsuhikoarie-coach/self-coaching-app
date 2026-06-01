<?php
// ============================================================
// 初期パスワード設定スクリプト
// 使い方: サーバー上でブラウザアクセス or CLI で一度だけ実行
//   php init_passwords.php
//
// ※ 実行後はセキュリティのためこのファイルを削除してください。
// ============================================================

// このファイルと同じディレクトリから実行
$configPath = __DIR__ . '/../config/db.php';
if (!file_exists($configPath)) {
    die("config/db.php が見つかりません\n");
}

// DB接続のみ取得（CORS等のヘッダー出力を回避するためダミー環境変数）
$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once $configPath;

$initialPassword = 'asagiri2026';
$hash = password_hash($initialPassword, PASSWORD_DEFAULT);

$db = getDB();

// 未設定ユーザーのみ更新
$stmt = $db->prepare("UPDATE fc_users SET password_hash = ? WHERE password_hash IS NULL");
$stmt->execute([$hash]);
$updated = $stmt->rowCount();

echo "完了: {$updated} 件のユーザーに仮パスワードを設定しました\n";
echo "仮パスワード: {$initialPassword}\n";
echo "Hash: {$hash}\n\n";

// 確認出力
$rows = $db->query("SELECT id, email, fc_name, CASE WHEN password_hash IS NOT NULL THEN 'SET' ELSE 'NOT SET' END AS pw_status FROM fc_users ORDER BY fc_name")->fetchAll();
foreach ($rows as $r) {
    echo "[{$r['id']}] {$r['fc_name']} / {$r['email']} → {$r['pw_status']}\n";
}
