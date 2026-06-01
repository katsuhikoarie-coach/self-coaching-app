<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

// ────────────────────────────────────────
// GET: 回数券残高一覧（最終来店日JOIN）
// ────────────────────────────────────────
if ($method === 'GET') {
    $custNo = $_GET['cust_no'] ?? '';

    $sql = "
        SELECT k.*,
               MAX(ev.visit_date) AS last_visit_date
        FROM keep_items k
        LEFT JOIN este_visits ev
          ON ev.customer_id = k.customer_id
          AND ev.keep_item_id = k.id
    ";
    if ($custNo) {
        $sql .= " WHERE k.customer_id = ?";
        $stmt = $db->prepare($sql . " GROUP BY k.id ORDER BY k.item_date DESC, k.id DESC");
        $stmt->execute([$custNo]);
        $rows = $stmt->fetchAll();
    } else {
        $rows = $db->query($sql . " GROUP BY k.id ORDER BY k.item_date DESC, k.id DESC")->fetchAll();
    }
    $result = array_map(function($k) {
        return [
            'id'         => $k['id'],
            'date'       => $k['item_date'],
            'custNo'     => (string)$k['customer_id'],
            'custName'   => $k['customer_name'],
            'menuId'     => $k['menu_id'] ?? '',
            'menuName'   => $k['menu_name'],
            'qty'        => (int)$k['qty'],
            'totalCount' => (int)$k['count_val'],
            'usedCount'  => (int)($k['used_count'] ?? 0),
            'remaining'  => (int)$k['count_val'] - (int)($k['used_count'] ?? 0),
            'price'      => (int)$k['price'],
            'note'       => $k['note'],
            'lastVisit'  => $k['last_visit_date'],
        ];
    }, $rows);

    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: 回数券使用（action=use_ticket）
// ────────────────────────────────────────
if ($method === 'POST') {
    $action = $body['action'] ?? '';

    if ($action === 'use_ticket') {
        $kid = $body['id'] ?? '';
        if (!$kid) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        $upd = $db->prepare("
            UPDATE keep_items SET used_count = used_count + 1
            WHERE id=? AND used_count < count_val
        ");
        $upd->execute([$kid]);
        if ($upd->rowCount() === 0) {
            http_response_code(409);
            jsonResponse(['error' => '残回数が0のため使用できません']);
            exit;
        }
        jsonResponse(['ok' => true]);
        exit;
    }

    // 後方互換：旧登録形式（使用しないが残す）
    $kid      = $body['id']   ?? '';
    $itemDate = $body['date'] ?? $body['item_date'] ?? '';
    if (!$kid || !$itemDate) {
        http_response_code(400);
        jsonResponse(['error' => 'id と date は必須']);
        exit;
    }
    $db->prepare("
        INSERT INTO keep_items
          (id, item_date, customer_id, customer_name, menu_id, menu_name, qty, count_val, used_count, price, note)
        VALUES (?,?,?,?,?,?,?,?,0,?,?)
        ON DUPLICATE KEY UPDATE
          menu_name=VALUES(menu_name), qty=VALUES(qty), price=VALUES(price)
    ")->execute([
        $kid,
        $itemDate,
        (string)($body['custNo'] ?? $body['customer_id'] ?? ''),
        $body['custName'] ?? $body['customer_name'] ?? '',
        $body['menuId']   ?? '',
        $body['menuName'] ?? $body['menu_name'] ?? '',
        (int)($body['qty']   ?? 1),
        (int)($body['count'] ?? $body['count_val'] ?? 1),
        (int)($body['price'] ?? 0),
        $body['note'] ?? '',
    ]);
    jsonResponse(['ok' => true, 'id' => $kid]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
