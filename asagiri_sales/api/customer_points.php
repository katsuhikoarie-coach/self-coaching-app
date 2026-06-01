<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

function getBalance($db, $customerId) {
    $stmt = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN point_type='add'      THEN points ELSE 0 END), 0) AS add_total,
            COALESCE(SUM(CASE WHEN point_type='subtract' THEN points ELSE 0 END), 0) AS sub_total
        FROM customer_points WHERE customer_id = ?
    ");
    $stmt->execute([(int)$customerId]);
    $row = $stmt->fetch();
    return (int)$row['add_total'] - (int)$row['sub_total'];
}

// ────────────────────────────────────────
// GET: ポイント履歴一覧 + 残高
// ────────────────────────────────────────
if ($method === 'GET') {
    $customerId = $_GET['customer_id'] ?? null;
    if (!$customerId) {
        http_response_code(400);
        jsonResponse(['error' => 'customer_id は必須です']);
        exit;
    }
    try {
        $stmt = $db->prepare("SELECT * FROM customer_points WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([(int)$customerId]);
        $history = $stmt->fetchAll();
        $balance = getBalance($db, $customerId);
        jsonResponse(['balance' => $balance, 'history' => $history]);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ────────────────────────────────────────
// POST: ポイント追加・削除
// ────────────────────────────────────────
if ($method === 'POST') {
    $body      = getRequestBody() ?? [];
    $custId    = $body['customer_id'] ?? null;
    $ptType    = $body['point_type']  ?? '';
    $points    = (int)($body['points'] ?? 0);
    $reason    = trim($body['reason']     ?? '');
    $createdBy = trim($body['created_by'] ?? '');

    if (!$custId || !in_array($ptType, ['add', 'subtract'], true) || $points <= 0) {
        http_response_code(400);
        jsonResponse(['error' => '必須項目が不足しています（customer_id・point_type・points）']);
        exit;
    }

    if ($ptType === 'subtract') {
        $balance = getBalance($db, $custId);
        if ($balance < $points) {
            http_response_code(400);
            jsonResponse(['error' => '残高不足です（残高：' . $balance . 'pt）']);
            exit;
        }
    }

    try {
        $db->prepare("
            INSERT INTO customer_points (customer_id, point_type, points, reason, created_by)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([(int)$custId, $ptType, $points, $reason, $createdBy]);
        jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId(), 'balance' => getBalance($db, $custId)]);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ────────────────────────────────────────
// DELETE: 履歴レコード削除（残高チェックあり）
// ────────────────────────────────────────
if ($method === 'DELETE') {
    $body = getRequestBody() ?? [];
    $id   = $body['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        jsonResponse(['error' => 'id は必須です']);
        exit;
    }
    try {
        $stmt = $db->prepare("SELECT * FROM customer_points WHERE id = ?");
        $stmt->execute([(int)$id]);
        $record = $stmt->fetch();
        if (!$record) {
            http_response_code(404);
            jsonResponse(['error' => 'レコードが見つかりません']);
            exit;
        }

        // add レコードを削除すると残高が減る → 0未満にならないかチェック
        if ($record['point_type'] === 'add') {
            $balance = getBalance($db, $record['customer_id']);
            if (($balance - (int)$record['points']) < 0) {
                http_response_code(400);
                jsonResponse(['error' => 'このレコードを削除すると残高が0未満になります（残高：' . $balance . 'pt）']);
                exit;
            }
        }

        $db->prepare("DELETE FROM customer_points WHERE id = ?")->execute([(int)$id]);
        jsonResponse(['ok' => true, 'balance' => getBalance($db, $record['customer_id'])]);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
