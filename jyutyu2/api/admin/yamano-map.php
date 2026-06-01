<?php
// ============================================================
// 管理者用 ヤマノ未分類紐づけAPI
// POST { yamano_order_id, fc_user_id }
//   yamano_orders.fc_user_id を更新
//   yamano_shipping_map に配送先氏名を登録（次回から自動マッチング）
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(['error' => 'Method Not Allowed']);
    exit;
}

$db   = getDB();
$body = getRequestBody();

$yamano_order_id = trim($body['yamano_order_id'] ?? '');
$fc_user_id      = (int)($body['fc_user_id'] ?? 0);

// バリデーション
if (!$yamano_order_id || !$fc_user_id) {
    http_response_code(400);
    jsonResponse(['error' => 'yamano_order_id と fc_user_id は必須です']);
    exit;
}

// 注文が存在するか確認 + shipping_name 取得
$stmt = $db->prepare("SELECT shipping_name FROM yamano_orders WHERE yamano_order_id = ?");
$stmt->execute([$yamano_order_id]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    jsonResponse(['error' => '注文が見つかりません']);
    exit;
}

// fc_user が存在するか確認
$stmt = $db->prepare("SELECT id FROM fc_users WHERE id = ? AND active = 1");
$stmt->execute([$fc_user_id]);
if (!$stmt->fetch()) {
    http_response_code(400);
    jsonResponse(['error' => '指定されたセンターが存在しません']);
    exit;
}

$shippingName = $order['shipping_name'];

try {
    $db->beginTransaction();

    // yamano_orders.fc_user_id を更新
    $stmt = $db->prepare("UPDATE yamano_orders SET fc_user_id = ? WHERE yamano_order_id = ?");
    $stmt->execute([$fc_user_id, $yamano_order_id]);

    // yamano_shipping_map に登録（既にあれば更新）
    if ($shippingName !== '') {
        $stmt = $db->prepare("
            INSERT INTO yamano_shipping_map (shipping_name, fc_user_id, note)
            VALUES (?, ?, '管理画面から手動登録')
            ON DUPLICATE KEY UPDATE fc_user_id = VALUES(fc_user_id)
        ");
        $stmt->execute([$shippingName, $fc_user_id]);
    }

    $db->commit();
    jsonResponse(['ok' => true, 'yamano_order_id' => $yamano_order_id, 'fc_user_id' => $fc_user_id]);

} catch (\Throwable $e) {
    $db->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => '更新に失敗しました']);
}
