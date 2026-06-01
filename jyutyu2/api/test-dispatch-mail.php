<?php
// ============================================================
// 発注完了メール 単体テストスクリプト
// ブラウザで直接アクセスして動作確認する。
// 確認後はサーバーから必ず削除すること。
// ============================================================
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>発注完了メール テスト</title>
<style>
  body { font-family: sans-serif; max-width: 700px; margin: 40px auto; padding: 0 20px; }
  h1   { font-size: 1.2rem; border-bottom: 2px solid #333; padding-bottom: 8px; }
  pre  { background: #f5f5f5; padding: 16px; border-radius: 6px; white-space: pre-wrap; word-break: break-all; }
  .ok  { color: #1a7a3c; font-weight: bold; }
  .err { color: #c0392b; font-weight: bold; }
  .label { color: #555; font-size: 0.85rem; margin-top: 16px; margin-bottom: 4px; }
</style>
</head>
<body>
<h1>発注完了メール 単体テスト</h1>
<?php

$db = getDB();

// 最新の注文を1件取得
$stmt = $db->prepare("
    SELECT o.*, u.contact_name
    FROM orders o
    LEFT JOIN fc_users u ON u.id = o.fc_user_id
    ORDER BY o.created_at DESC
    LIMIT 1
");
$stmt->execute();
$order = $stmt->fetch();

if (!$order) {
    echo '<p class="err">注文データが1件も見つかりませんでした。</p>';
    echo '</body></html>';
    exit;
}

// 明細取得
$itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
$itemStmt->execute([$order['id']]);
$items = $itemStmt->fetchAll();

$to          = $order['fc_email']     ?? '';
$fcName      = $order['fc_name']      ?? '';
$contactName = $order['contact_name'] ?? '';
$orderDate   = $order['order_date']   ?? '';
$subtotal    = (float)$order['subtotal'];
$taxTotal    = (float)$order['tax_total'];
$total       = (float)$order['total'];
$note        = $order['note']         ?? '';
$orderId     = $order['id'];

// ── 使用する注文情報を表示 ────────────────────────────────
echo '<p class="label">テスト対象の注文</p>';
echo '<pre>';
echo "受注番号 : {$orderId}\n";
echo "注文日   : {$orderDate}\n";
echo "センター : {$fcName}\n";
echo "担当者   : {$contactName}\n";
echo "送信先   : {$to}\n";
echo "合計     : ¥" . number_format($total) . "\n";
echo "明細件数 : " . count($items) . "件\n";
echo '</pre>';

if (empty($to)) {
    echo '<p class="err">送信先メールアドレスが空のため送信できません（orders.fc_email が未設定）。</p>';
    echo '</body></html>';
    exit;
}

// ── メール本文生成 ────────────────────────────────────────
$subject = "【発注完了】ご注文の商品を発注しました";

$itemLines = '';
foreach ($items as $it) {
    $name   = $it['product_name'] ?? '';
    $code   = $it['product_code'] ?? '';
    $price  = number_format((float)($it['price']  ?? 0));
    $qty    = (int)($it['quantity'] ?? 1);
    $amount = number_format((float)($it['amount'] ?? 0));
    $itemLines .= "  [{$code}] {$name}\n";
    $itemLines .= "        ¥{$price} × {$qty}個 = ¥{$amount}\n";
}

$subtotal_fmt = number_format($subtotal);
$taxTotal_fmt = number_format($taxTotal);
$total_fmt    = number_format($total);

$mail_body  = "{$contactName} 様\n\n";
$mail_body .= "ご注文いただいた商品をヤマノへ発注いたしました。\n\n";
$mail_body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$mail_body .= "【発注情報】\n";
$mail_body .= "受注番号：{$orderId}\n";
$mail_body .= "注文日　：{$orderDate}\n";
$mail_body .= "センター：{$fcName}\n\n";
$mail_body .= "【発注商品】\n";
$mail_body .= $itemLines;
$mail_body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$mail_body .= "小計（税抜）：¥{$subtotal_fmt}\n";
$mail_body .= "消費税　　　：¥{$taxTotal_fmt}\n";
$mail_body .= "合計（税込）：¥{$total_fmt}\n";
$mail_body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($note !== '') {
    $mail_body .= "\n【備考】\n{$note}\n";
}

$mail_body .= "\nご不明な点は朝霧ヤマノまでご連絡ください。\n\n";
$mail_body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$mail_body .= "朝霧ヤマノ\n";
$mail_body .= "fc-order@asagiriyamano.jp\n";
$mail_body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
$mail_body .= "※ このメールはセンター受注システムから自動送信されています。\n";

// ── 送信内容プレビュー ────────────────────────────────────
echo '<p class="label">送信するメール本文</p>';
echo '<pre>' . htmlspecialchars($mail_body) . '</pre>';

// ── 実際に送信 ───────────────────────────────────────────
mb_language('japanese');
mb_internal_encoding('UTF-8');
$result = mb_send_mail($to, $subject, $mail_body, 'From: fc-order@asagiriyamano.jp');

echo '<p class="label">送信結果</p>';
if ($result) {
    echo "<p class=\"ok\">✅ 送信成功　宛先: {$to}</p>";
} else {
    echo "<p class=\"err\">❌ 送信失敗　mb_send_mail() が false を返しました。</p>";
}

echo '<hr style="margin-top:40px">';
echo '<p style="color:#888;font-size:0.8rem">⚠️ このファイルは確認後にサーバーから削除してください。</p>';
?>
</body>
</html>
