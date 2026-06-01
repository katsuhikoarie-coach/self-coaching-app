<?php
/**
 * fc_users テーブル ALTER & ユーザー追加スクリプト
 * 実行方法: ブラウザで https://fc-order.asagiriyamano.jp/fc_migrate.php?key=run2026 にアクセス
 * ⚠ 実行後は必ずサーバーからこのファイルを削除してください
 */

if (($_GET['key'] ?? '') !== 'run2026') {
    http_response_code(403);
    die('Access denied');
}

set_time_limit(60);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

// DB接続（asagiri_fcorder）
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=asagiri_fcorder;charset=utf8mb4',
        'asagiri_fcorder',
        'JvsHzdyKDNk8',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('DB接続エラー: ' . htmlspecialchars($e->getMessage()));
}

echo "<pre style='font-family:monospace;font-size:14px;padding:20px;line-height:1.8'>\n";
echo "=== fc_users テーブル変更スクリプト (asagiri_fcorder) ===\n\n";

// ─── 現在のカラム構造を確認 ───────────────────────────────────
echo "[1] 現在の fc_users テーブル構造:\n";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM fc_users")->fetchAll();
    foreach ($cols as $c) {
        echo "  {$c['Field']} | {$c['Type']} | " . ($c['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (PDOException $e) {
    echo "  ⚠ fc_users テーブルが見つかりません: " . $e->getMessage() . "\n";
    echo "</pre>\n";
    exit;
}
echo "\n";

$existingCols = array_column($cols, 'Field');

// ─── カラム追加 ───────────────────────────────────────────────
echo "[2] カラム追加:\n";

$newCols = [
    'center_code' => "ALTER TABLE fc_users ADD COLUMN center_code VARCHAR(10) NULL COMMENT 'センターコード' AFTER active",
    'center_type' => "ALTER TABLE fc_users ADD COLUMN center_type ENUM('BC','FC','販社') NULL COMMENT 'センター種別' AFTER center_code",
    'grade'       => "ALTER TABLE fc_users ADD COLUMN grade VARCHAR(20) NULL COMMENT 'グレード' AFTER center_type",
];

foreach ($newCols as $colName => $sql) {
    if (in_array($colName, $existingCols, true)) {
        echo "  SKIP  : {$colName} は既に存在します\n";
    } else {
        try {
            $pdo->exec($sql);
            echo "  ADD   : {$colName} を追加しました ✓\n";
        } catch (PDOException $e) {
            echo "  ERROR : {$colName} の追加に失敗 → " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";

// ─── ユーザー追加 ─────────────────────────────────────────────
echo "[3] ユーザー追加:\n";

// fc_usersのカラムを再取得（追加後）
$currentCols = array_column(
    $pdo->query("SHOW COLUMNS FROM fc_users")->fetchAll(),
    'Field'
);

// 追加するユーザー
// contact_name = 担当者名（fc_usersのカラム名に合わせる）
$newUsers = [
    [
        'contact_name' => '有江啓子',
        'email'        => 'ya.asagiriten@gmail.com',
        'fc_name'      => '朝霧ヤマノ',
        'active'       => 1,
    ],
    [
        'contact_name' => '有江真吾',
        'email'        => 'asagiriyamano@gmail.com',
        'fc_name'      => '朝霧ヤマノ',
        'active'       => 1,
    ],
    [
        'contact_name' => '伊藤成子',
        'email'        => 'seiko.i.3124@gmail.com',
        'fc_name'      => 'セイコ朝霧',
        'active'       => 1,
    ],
];

foreach ($newUsers as $u) {
    // 重複チェック
    $chk = $pdo->prepare("SELECT id FROM fc_users WHERE email = ?");
    $chk->execute([$u['email']]);
    if ($chk->fetch()) {
        echo "  SKIP  : {$u['email']} は既に存在します\n";
        continue;
    }

    // INSERT（必須: email, fc_name, active）
    $insertCols = ['email', 'fc_name', 'active'];
    $insertVals = [$u['email'], $u['fc_name'], $u['active']];

    // contact_name カラムがあれば追加
    if (in_array('contact_name', $currentCols, true)) {
        $insertCols[] = 'contact_name';
        $insertVals[] = $u['contact_name'];
    }

    $colList     = implode(', ', $insertCols);
    $placeholder = implode(', ', array_fill(0, count($insertCols), '?'));
    $stmt = $pdo->prepare("INSERT INTO fc_users ({$colList}) VALUES ({$placeholder})");
    $stmt->execute($insertVals);

    $newId = $pdo->lastInsertId();
    echo "  INSERT: {$u['contact_name']} &lt;{$u['email']}&gt; / {$u['fc_name']} → ID={$newId} ✓\n";
}
echo "\n";

// ─── 最終確認 ─────────────────────────────────────────────────
echo "[4] 変更後の fc_users テーブル構造:\n";
foreach ($pdo->query("SHOW COLUMNS FROM fc_users")->fetchAll() as $c) {
    echo "  {$c['Field']} | {$c['Type']}\n";
}
echo "\n";

echo "[5] 登録済みユーザー一覧:\n";
$users = $pdo->query("SELECT id, contact_name, email, fc_name, active FROM fc_users ORDER BY id")->fetchAll();
foreach ($users as $u) {
    $active = $u['active'] ? '有効' : '無効';
    echo "  ID={$u['id']} | {$u['contact_name']} | {$u['email']} | {$u['fc_name']} | {$active}\n";
}

echo "\n=== 完了 ===\n";
echo "⚠ このファイル (fc_migrate.php) をサーバーから削除してください。\n";
echo "</pre>\n";
