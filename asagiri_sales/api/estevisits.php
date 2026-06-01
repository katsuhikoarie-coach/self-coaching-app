<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

// ────────────────────────────────────────
// GET: 来店履歴一覧 (/?cust_no=XX で顧客絞り込み)
// ────────────────────────────────────────
if ($method === 'GET') {
    $custNo = $_GET['cust_no'] ?? '';
    if ($custNo) {
        $stmt = $db->prepare("SELECT * FROM este_visits WHERE customer_id=? ORDER BY visit_date DESC, id DESC");
        $stmt->execute([$custNo]);
    } else {
        $stmt = $db->query("SELECT * FROM este_visits ORDER BY visit_date DESC, id DESC");
    }
    $rows = $stmt->fetchAll();

    $result = array_map(function($v) {
        return [
            'id'          => $v['id'],
            'date'        => $v['visit_date'],
            'custNo'      => (string)$v['customer_id'],
            'custName'    => $v['customer_name'],
            'menuId'      => $v['menu_id'],
            'menuName'    => $v['menu_name'],
            'staff'       => $v['staff'],
            'price'       => (int)$v['price'],
            'taxRate'     => (int)$v['tax_rate'],
            'tax'         => (int)$v['tax'],
            'total'       => (int)$v['total'],
            'nextVisit'   => $v['next_visit'],
            'note'        => $v['note'],
            'keepItemId'  => $v['keep_item_id'] ?? null,
            'usedTicket'  => (int)($v['used_ticket'] ?? 0),
        ];
    }, $rows);

    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: 来店登録（回数券使用時はトランザクション）
// ────────────────────────────────────────
if ($method === 'POST') {
    $vid       = $body['id'] ?? '';
    $visitDate = $body['date'] ?? $body['visit_date'] ?? '';
    if (!$vid || !$visitDate) {
        http_response_code(400);
        jsonResponse(['error' => 'id と date は必須']);
        exit;
    }

    $nextVisit  = $body['nextVisit'] ?? $body['next_visit'] ?? null;
    if ($nextVisit === '' || $nextVisit === 'null') $nextVisit = null;

    $keepItemId = $body['keepItemId'] ?? null;
    if ($keepItemId === '' || $keepItemId === 'null') $keepItemId = null;
    $usedTicket = $keepItemId ? 1 : 0;

    $db->beginTransaction();
    try {
        // 来店登録
        $db->prepare("
            INSERT INTO este_visits
              (id, visit_date, customer_id, customer_name,
               menu_id, menu_name, staff, price, tax_rate, tax, total,
               next_visit, note, keep_item_id, used_ticket)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              visit_date=VALUES(visit_date), customer_name=VALUES(customer_name),
              menu_name=VALUES(menu_name), total=VALUES(total),
              keep_item_id=VALUES(keep_item_id), used_ticket=VALUES(used_ticket)
        ")->execute([
            $vid,
            $visitDate,
            (string)($body['custNo'] ?? $body['customer_id'] ?? ''),
            $body['custName'] ?? $body['customer_name'] ?? '',
            $body['menuId']   ?? $body['menu_id']        ?? '',
            $body['menuName'] ?? $body['menu_name']       ?? '',
            $body['staff']    ?? '',
            (int)($body['price']   ?? 0),
            (int)($body['taxRate'] ?? $body['tax_rate'] ?? 10),
            (int)($body['tax']     ?? 0),
            (int)($body['total']   ?? 0),
            $nextVisit,
            $body['note']       ?? '',
            $keepItemId,
            $usedTicket,
        ]);

        // 回数券消化：keep_items.used_count を +1
        if ($keepItemId) {
            $upd = $db->prepare("
                UPDATE keep_items SET used_count = used_count + 1
                WHERE id=? AND used_count < count_val
            ");
            $upd->execute([$keepItemId]);
            if ($upd->rowCount() === 0) {
                throw new Exception('回数券の残回数が0のため使用できません');
            }
        }

        $db->commit();
        jsonResponse(['ok' => true, 'id' => $vid]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ────────────────────────────────────────
// PUT: 来店記録の修正
// ────────────────────────────────────────
if ($method === 'PUT') {
    $id = $body['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        jsonResponse(['error' => 'id は必須']);
        exit;
    }
    $nextVisit = $body['nextVisit'] ?? null;
    if ($nextVisit === '' || $nextVisit === 'null') $nextVisit = null;

    $db->prepare("
        UPDATE este_visits SET
          customer_name = ?,
          menu_name     = ?,
          staff         = ?,
          total         = ?,
          note          = ?,
          next_visit    = ?
        WHERE id = ?
    ")->execute([
        $body['custName'] ?? '',
        $body['menuName'] ?? '',
        $body['staff']    ?? '',
        (int)($body['total'] ?? 0),
        $body['note']     ?? '',
        $nextVisit,
        $id,
    ]);

    jsonResponse(['ok' => true]);
    exit;
}

// ────────────────────────────────────────
// DELETE: 来店記録の削除
// ────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $body['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        jsonResponse(['error' => 'id は必須']);
        exit;
    }
    $db->prepare("DELETE FROM este_visits WHERE id=?")->execute([$id]);
    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
