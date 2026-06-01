<?php
/**
 * 注文DB診断スクリプト
 * アクセス: https://fc-order.asagiriyamano.jp/check_orders.php?key=chk2026
 * 確認後は必ず削除してください
 */
if (($_GET['key'] ?? '') !== 'chk2026') { http_response_code(403); die('Access denied'); }
header('Content-Type: text/html; charset=UTF-8');

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=asagiri_fcorder;charset=utf8mb4',
        'asagiri_fcorder', 'JvsHzdyKDNk8',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('DB接続エラー: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">
<title>注文DB診断</title>
<style>
body { font-family: sans-serif; padding: 16px; font-size: 13px; }
table { border-collapse: collapse; width: 100%; margin-bottom: 24px; }
th { background: #334; color: #fff; padding: 6px 10px; text-align: left; white-space: nowrap; }
td { border-bottom: 1px solid #ddd; padding: 6px 10px; vertical-align: top; }
tr:hover td { background: #f5f5f5; }
h2 { margin: 24px 0 8px; color: #334; }
.ok { color: green; font-weight: bold; }
.ng { color: red; font-weight: bold; }
</style>
</head><body>

<h1>注文DB診断 (asagiri_fcorder)</h1>

<?php
// ── 1. fc_users 一覧 ──────────────────────────────────────────
echo '<h2>① fc_users テーブル</h2>';
$users = $pdo->query("SELECT id, contact_name, email, fc_name, active FROM fc_users ORDER BY id")->fetchAll();
echo '<table><tr><th>ID</th><th>担当者名</th><th>メール</th><th>FC名</th><th>有効</th></tr>';
foreach ($users as $u) {
    $a = $u['active'] ? '<span class="ok">有効</span>' : '<span class="ng">無効</span>';
    echo "<tr><td>{$u['id']}</td><td>" . h($u['contact_name']) . "</td><td>" . h($u['email']) . "</td><td>" . h($u['fc_name']) . "</td><td>{$a}</td></tr>";
}
echo '</table>';

// 朝霧ヤマノユーザーのID一覧
$asagiriUsers = array_filter($users, fn($u) => $u['fc_name'] === '朝霧ヤマノ');
$asagiriIds   = array_column(array_values($asagiriUsers), 'id');
echo '<p>朝霧ヤマノ fc_user IDs: <strong>' . (empty($asagiriIds) ? 'なし' : implode(', ', $asagiriIds)) . '</strong></p>';

// ── 2. orders テーブル 全件 ───────────────────────────────────
echo '<h2>② orders テーブル 全件</h2>';
$orders = $pdo->query("SELECT id, fc_user_id, fc_name, fc_email, order_date, total, status, created_at FROM orders ORDER BY created_at DESC")->fetchAll();
$total = count($orders);
echo "<p>合計: <strong>{$total} 件</strong></p>";

if ($total === 0) {
    echo '<p class="ng">注文が1件もありません</p>';
} else {
    echo '<table><tr><th>受注番号</th><th>fc_user_id</th><th>FC名</th><th>メール</th><th>注文日</th><th>合計</th><th>status</th><th>作成日時</th></tr>';
    foreach ($orders as $o) {
        $isTarget = ($o['id'] === '2026-05-16-0004') ? ' style="background:#ffe;"' : '';
        echo "<tr{$isTarget}><td><strong>" . h($o['id']) . "</strong></td><td>{$o['fc_user_id']}</td><td>" . h($o['fc_name']) . "</td><td>" . h($o['fc_email']) . "</td><td>{$o['order_date']}</td><td>¥" . number_format($o['total']) . "</td><td>{$o['status']}</td><td>{$o['created_at']}</td></tr>";
    }
    echo '</table>';
}

// ── 3. 対象注文 2026-05-16-0004 の確認 ─────────────────────
echo '<h2>③ 受注番号 2026-05-16-0004 の確認</h2>';
$target = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$target->execute(['2026-05-16-0004']);
$row = $target->fetch();
if ($row) {
    echo '<p class="ok">✅ DBに存在します</p>';
    echo '<table><tr><th>カラム</th><th>値</th></tr>';
    foreach ($row as $k => $v) {
        echo '<tr><td>' . h($k) . '</td><td>' . h((string)$v) . '</td></tr>';
    }
    echo '</table>';

    // 明細
    $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items->execute(['2026-05-16-0004']);
    $itemRows = $items->fetchAll();
    echo '<p>明細: ' . count($itemRows) . '件</p>';
    if ($itemRows) {
        echo '<table><tr><th>商品コード</th><th>商品名</th><th>数量</th><th>単価</th></tr>';
        foreach ($itemRows as $it) {
            echo '<tr><td>' . h($it['product_code']) . '</td><td>' . h($it['product_name']) . '</td><td>' . $it['quantity'] . '</td><td>¥' . number_format($it['price']) . '</td></tr>';
        }
        echo '</table>';
    }
} else {
    echo '<p class="ng">❌ DBに存在しません（注文が保存されていない可能性）</p>';
}

// ── 4. 朝霧ヤマノAPIと同じSQLで全件取得 ─────────────────────
echo '<h2>④ 朝霧ヤマノ用SQL（全件取得）の実行結果</h2>';
$all = $pdo->query("SELECT id, fc_user_id, fc_name, order_date, total FROM orders ORDER BY created_at DESC LIMIT 500")->fetchAll();
echo '<p>取得件数: <strong>' . count($all) . ' 件</strong></p>';
echo '<table><tr><th>受注番号</th><th>fc_user_id</th><th>FC名</th><th>注文日</th><th>合計</th></tr>';
foreach ($all as $o) {
    $hi = ($o['id'] === '2026-05-16-0004') ? ' style="background:#ffe;"' : '';
    echo "<tr{$hi}><td>" . h($o['id']) . "</td><td>{$o['fc_user_id']}</td><td>" . h($o['fc_name']) . "</td><td>{$o['order_date']}</td><td>¥" . number_format($o['total']) . "</td></tr>";
}
echo '</table>';

// ── 5. fc_name の値チェック ──────────────────────────────────
echo '<h2>⑤ api/orders.php の fc_name 判定チェック</h2>';
$arieUser = $pdo->prepare("SELECT id, contact_name, fc_name FROM fc_users WHERE email = ?");
$arieUser->execute(['katsuhiko.arie@gmail.com']); // 有江健彦
$arie = $arieUser->fetch();
if ($arie) {
    $match = ($arie['fc_name'] === '朝霧ヤマノ');
    echo '<p>有江健彦の fc_name: <strong>' . h($arie['fc_name']) . '</strong> → 全件取得フラグ: ' . ($match ? '<span class="ok">true（全件取得）</span>' : '<span class="ng">false（自分だけ）← 要確認</span>') . '</p>';
} else {
    echo '<p class="ng">有江健彦のレコードが見つかりません（メールアドレス確認が必要）</p>';
}

$keiko = $pdo->prepare("SELECT id, contact_name, fc_name FROM fc_users WHERE email = ?");
$keiko->execute(['ya.asagiriten@gmail.com']); // 有江啓子
$k = $keiko->fetch();
if ($k) {
    echo '<p>有江啓子の fc_name: <strong>' . h($k['fc_name']) . '</strong> / fc_user_id: <strong>' . $k['id'] . '</strong></p>';
}

echo '<p style="color:red;margin-top:32px">⚠ 確認後、このファイル (check_orders.php) をサーバーから削除してください。</p>';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
</body></html>
