<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

// ────────────────────────────────────────
// GET: 一覧 または 詳細 (?id=XX)
// ────────────────────────────────────────
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM este_sales WHERE id=?");
        $stmt->execute([$id]);
        $sale = $stmt->fetch();
        if (!$sale) { http_response_code(404); jsonResponse(['error' => 'not found']); exit; }

        $istmt = $db->prepare("SELECT * FROM este_sales_items WHERE sale_id=? ORDER BY id");
        $istmt->execute([$id]);
        $items = $istmt->fetchAll();

        jsonResponse([
            'id'       => $sale['id'],
            'date'     => $sale['sale_date'],
            'custNo'   => $sale['cust_no'],
            'custName' => $sale['cust_name'],
            'staff'    => $sale['staff'],
            'subtotal' => (int)$sale['subtotal'],
            'taxAmt'   => (int)$sale['tax_amt'],
            'total'    => (int)$sale['total'],
            'note'     => $sale['note'],
            'items'    => array_map(function($it) {
                return [
                    'menuId'      => $it['menu_id'],
                    'menuName'    => $it['menu_name'],
                    'itemType'    => $it['item_type'],
                    'ticketCount' => (int)$it['ticket_count'],
                    'qty'         => (int)$it['qty'],
                    'unitPrice'   => (int)$it['unit_price'],
                    'subtotal'    => (int)$it['subtotal'],
                ];
            }, $items),
        ]);
        exit;
    }

    // 一覧
    $rows = $db->query("SELECT * FROM este_sales ORDER BY sale_date DESC, id DESC")->fetchAll();
    jsonResponse(array_map(function($s) {
        return [
            'id'       => $s['id'],
            'date'     => $s['sale_date'],
            'custNo'   => $s['cust_no'],
            'custName' => $s['cust_name'],
            'staff'    => $s['staff'],
            'subtotal' => (int)$s['subtotal'],
            'taxAmt'   => (int)$s['tax_amt'],
            'total'    => (int)$s['total'],
            'note'     => $s['note'],
        ];
    }, $rows));
    exit;
}

// ────────────────────────────────────────
// POST: 伝票登録
// ────────────────────────────────────────
if ($method === 'POST') {
    $id       = $body['id']       ?? '';
    $saleDate = $body['date']     ?? '';
    $items    = $body['items']    ?? [];

    if (!$id || !$saleDate) {
        http_response_code(400);
        jsonResponse(['error' => 'id と date は必須']);
        exit;
    }

    $db->beginTransaction();
    try {
        // ヘッダー登録
        $db->prepare("
            INSERT INTO este_sales (id, sale_date, cust_no, cust_name, staff, subtotal, tax_amt, total, note)
            VALUES (?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              sale_date=VALUES(sale_date), cust_name=VALUES(cust_name),
              subtotal=VALUES(subtotal), tax_amt=VALUES(tax_amt), total=VALUES(total)
        ")->execute([
            $id,
            $saleDate,
            (string)($body['custNo'] ?? ''),
            $body['custName'] ?? '',
            $body['staff']    ?? '',
            (int)($body['subtotal'] ?? 0),
            (int)($body['taxAmt']   ?? 0),
            (int)($body['total']    ?? 0),
            $body['note'] ?? '',
        ]);

        // 既存明細を削除して再挿入
        $db->prepare("DELETE FROM este_sales_items WHERE sale_id=?")->execute([$id]);

        $item_stmt = $db->prepare("
            INSERT INTO este_sales_items (sale_id, menu_id, menu_name, item_type, ticket_count, qty, unit_price, subtotal)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $keep_stmt = $db->prepare("
            INSERT INTO keep_items
              (id, item_date, customer_id, customer_name, menu_id, menu_name, qty, count_val, used_count, price, note)
            VALUES (?,?,?,?,?,?,?,?,0,?,?)
        ");

        // 当日のkeep_itemsの件数を取得してID連番の起点にする
        $dateStr = str_replace('-', '', $saleDate);
        $kcount_stmt = $db->prepare("SELECT COUNT(*) FROM keep_items WHERE id LIKE ?");
        $kcount_stmt->execute(["KP-$dateStr-%"]);
        $kbase = (int)$kcount_stmt->fetchColumn();
        $keepSeq = $kbase + 1;

        foreach ($items as $it) {
            $item_stmt->execute([
                $id,
                $it['menuId']      ?? '',
                $it['menuName']    ?? '',
                $it['itemType']    ?? 'single',
                (int)($it['ticketCount'] ?? 1),
                (int)($it['qty']         ?? 1),
                (int)($it['unitPrice']   ?? 0),
                (int)($it['subtotal']    ?? 0),
            ]);

            // 回数券の場合は keep_items に自動登録
            if (($it['itemType'] ?? '') === 'ticket') {
                $keepId = 'KP-' . $dateStr . '-' . str_pad($keepSeq, 3, '0', STR_PAD_LEFT);
                $keepSeq++;
                $keep_stmt->execute([
                    $keepId,
                    $saleDate,
                    (string)($body['custNo'] ?? ''),
                    $body['custName'] ?? '',
                    $it['menuId']   ?? '',
                    $it['menuName'] ?? '',
                    (int)($it['qty']         ?? 1),
                    (int)($it['ticketCount'] ?? 5),
                    (int)($it['unitPrice']   ?? 0),
                    '',
                ]);
            }
        }

        $db->commit();
        jsonResponse(['ok' => true, 'id' => $id]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        jsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

// ────────────────────────────────────────
// PUT: 伝票の修正
// ────────────────────────────────────────
if ($method === 'PUT') {
    $id = $body['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        jsonResponse(['error' => 'id は必須']);
        exit;
    }

    $db->prepare("
        UPDATE este_sales SET
          cust_name = ?,
          staff     = ?,
          total     = ?,
          note      = ?
        WHERE id = ?
    ")->execute([
        $body['custName'] ?? '',
        $body['staff']    ?? '',
        (int)($body['total'] ?? 0),
        $body['note']     ?? '',
        $id,
    ]);

    jsonResponse(['ok' => true]);
    exit;
}

// ────────────────────────────────────────
// DELETE: 伝票の削除（明細も同時削除）
// ────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $body['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        jsonResponse(['error' => 'id は必須']);
        exit;
    }
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM este_sales_items WHERE sale_id=?")->execute([$id]);
        $db->prepare("DELETE FROM este_sales WHERE id=?")->execute([$id]);
        $db->commit();
        jsonResponse(['ok' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        jsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
