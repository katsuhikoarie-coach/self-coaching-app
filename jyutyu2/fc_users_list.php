<?php
/**
 * fc_users 一覧表示スクリプト（読み取り専用）
 * 実行後は必ずサーバーから削除してください
 */
if (($_GET['key'] ?? '') !== 'list2026') {
    http_response_code(403); die('Access denied');
}
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

$users = $pdo->query("SELECT * FROM fc_users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">
<title>fc_users 一覧</title>
<style>
  body { font-family: sans-serif; padding: 20px; }
  table { border-collapse: collapse; width: 100%; font-size: 13px; }
  th { background: #334; color: #fff; padding: 8px 12px; text-align: left; }
  td { border-bottom: 1px solid #ddd; padding: 8px 12px; }
  tr:hover td { background: #f5f5f5; }
  .active1 { color: green; font-weight: bold; }
  .active0 { color: red; }
</style>
</head><body>
<h2>fc_users テーブル（asagiri_fcorder）</h2>
<p>全 <?= count($users) ?> 件</p>
<table>
<tr>
  <th>ID</th><th>担当者名</th><th>メール</th><th>FC名</th>
  <th>住所</th><th>電話</th><th>有効</th>
  <th>center_code</th><th>center_type</th><th>grade</th>
  <th>登録日</th>
</tr>
<?php foreach ($users as $u): ?>
<tr>
  <td><?= $u['id'] ?></td>
  <td><?= htmlspecialchars($u['contact_name'] ?? '') ?></td>
  <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
  <td><?= htmlspecialchars($u['fc_name'] ?? '') ?></td>
  <td><?= htmlspecialchars($u['address'] ?? '') ?></td>
  <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
  <td class="active<?= $u['active'] ?>"><?= $u['active'] ? '有効' : '無効' ?></td>
  <td><?= htmlspecialchars($u['center_code'] ?? '') ?></td>
  <td><?= htmlspecialchars($u['center_type'] ?? '') ?></td>
  <td><?= htmlspecialchars($u['grade'] ?? '') ?></td>
  <td><?= substr($u['created_at'] ?? '', 0, 10) ?></td>
</tr>
<?php endforeach; ?>
</table>
<p style="color:red;margin-top:20px">⚠ 確認後、このファイル (fc_users_list.php) をサーバーから削除してください。</p>
</body></html>
