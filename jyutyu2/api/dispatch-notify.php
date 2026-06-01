<?php
// ============================================================
// 発注完了通知API
// POST /api/dispatch-notify.php
//   body: { orderId, secret }
// ヤマノ発注自動化サーバーから呼ばれ、FCへ発注完了メールを送信する
// ※ Firebaseトークン不要。共有シークレットで認証する
// ============================================================
require_once __DIR__ . '/../config/db.php';

// automation/server.mjs の DISPATCH_SECRET と一致させること
define('DISPATCH_SECRET', 'YAMANO_DISPATCH_2026');

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$body    = getRequestBody();
$orderId = trim($body['orderId'] ?? '');
$secret  = $body['secret']  ?? '';

if ($secret !== DISPATCH_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => '認証エラー'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'orderId は必須'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDB();

// 注文情報取得（contact_name は fc_users から）
$stmt = $db->prepare("
    SELECT o.*, u.contact_name, u.email AS fc_email
    FROM orders o
    LEFT JOIN fc_users u ON u.id = o.fc_user_id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => '注文が見つかりません'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 明細取得
$itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

// 送信先はセンターのメールアドレス
$to = $order['fc_email'] ?? '';
if (empty($to)) {
    http_response_code(400);
    echo json_encode(['error' => '送信先メールアドレスが取得できません'], JSON_UNESCAPED_UNICODE);
    exit;
}

$fcName      = $order['fc_name']      ?? '';
$contactName = $order['contact_name'] ?? '';
$orderDate   = $order['order_date']   ?? '';
$subtotal    = (float)$order['subtotal'];
$taxTotal    = (float)$order['tax_total'];
$total       = (float)$order['total'];
$note        = $order['note']         ?? '';

$subject = "【発注完了】ご注文の商品を発注しました";

$itemLines = '';
foreach ($items as $it) {
    $name   = $it['product_name'] ?? '';
    $code   = $it['product_code'] ?? '';
    $price  = number_format((float)($it['price']    ?? 0));
    $qty    = (int)($it['quantity'] ?? 1);
    $amount = number_format((float)($it['amount']   ?? 0));
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

mb_language('japanese');
mb_internal_encoding('UTF-8');
$result = mb_send_mail($to, $subject, $mail_body, 'From: fc-order@asagiriyamano.jp');

if ($result) {
    jsonResponse(['ok' => true, 'to' => $to]);
} else {
    http_response_code(500);
    jsonResponse(['error' => 'メール送信に失敗しました']);
}
