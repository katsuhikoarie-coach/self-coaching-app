<?php
// ============================================================
// 掛率管理API
// GET  ?fc_user_id=N          → 現在有効な掛率
// GET  ?fc_user_id=N&history=1 → 全履歴（新しい順）
// POST {fc_user_id, rate_system, rate_general, valid_from} → 登録・更新
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $fc_user_id = (int)($_GET['fc_user_id'] ?? 0);
    if ($fc_user_id <= 0) {
        http_response_code(400);
        jsonResponse(['error' => 'fc_user_id は必須です']);
        exit;
    }

    if (($_GET['history'] ?? '') === '1') {
        $stmt = $db->prepare("SELECT * FROM center_rates WHERE fc_user_id = ? ORDER BY valid_from DESC");
        $stmt->execute([$fc_user_id]);
        jsonResponse($stmt->fetchAll());
    } else {
        $stmt = $db->prepare("SELECT * FROM center_rates WHERE fc_user_id = ? AND valid_to IS NULL ORDER BY valid_from DESC LIMIT 1");
        $stmt->execute([$fc_user_id]);
        $rate = $stmt->fetch();
        jsonResponse($rate ?: null);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body        = getRequestBody();
    $fc_user_id  = (int)($body['fc_user_id'] ?? 0);
    $rate_system  = isset($body['rate_system'])  ? $body['rate_system']  : null;
    $rate_general = isset($body['rate_general']) ? $body['rate_general'] : null;
    $valid_from  = trim($body['valid_from'] ?? '');

    if ($fc_user_id <= 0 || $rate_system === null || $rate_general === null || !$valid_from) {
        http_response_code(400);
        jsonResponse(['error' => 'fc_user_id, rate_system, rate_general, valid_from は必須です']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_from)) {
        http_response_code(400);
        jsonResponse(['error' => 'valid_from の形式が不正です (YYYY-MM-DD)']);
        exit;
    }

    $rate_system  = round((float)$rate_system,  2);
    $rate_general = round((float)$rate_general, 2);

    // 現在有効レコードの valid_to を valid_from の前日にセット
    $prevDay = date('Y-m-d', strtotime($valid_from . ' -1 day'));
    $stmt = $db->prepare("UPDATE center_rates SET valid_to = ? WHERE fc_user_id = ? AND valid_to IS NULL");
    $stmt->execute([$prevDay, $fc_user_id]);

    // 新レコード INSERT
    $stmt = $db->prepare("INSERT INTO center_rates (fc_user_id, rate_system, rate_general, valid_from) VALUES (?, ?, ?, ?)");
    $stmt->execute([$fc_user_id, $rate_system, $rate_general, $valid_from]);

    jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
