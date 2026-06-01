<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

// ────────────────────────────────────────
// GET: 一覧 / 単件（明細付き）
// ────────────────────────────────────────
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $stmt->execute([(int)$id]);
        $po = $stmt->fetch();
        if (!$po) { http_response_code(404); jsonResponse(['error' => '見つかりません']); exit; }
        $iStmt = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ? ORDER BY id");
        $iStmt->execute([(int)$id]);
        $po['items'] = $iStmt->fetchAll();
        jsonResponse($po);
    } else {
        $stmt = $db->query("
            SELECT po.*,
              COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS total_amount
            FROM purchase_orders po
            LEFT JOIN purchase_order_items poi ON poi.po_id = po.id
            GROUP BY po.id
            ORDER BY po.created_at DESC
        ");
        jsonResponse($stmt->fetchAll());
    }
    exit;
}

// ────────────────────────────────────────
// POST: 新規作成 / 発注完了
// ────────────────────────────────────────
if ($method === 'POST') {
    $action = $body['action'] ?? '';

    // action=complete : 発注完了＋在庫入庫
    if ($action === 'complete') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) { http_response_code(400); jsonResponse(['error' => 'id は必須']); exit; }

        $poStmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $poStmt->execute([$id]);
        $po = $poStmt->fetch();
        if (!$po) { http_response_code(404); jsonResponse(['error' => '発注書が見つかりません']); exit; }
        if ($po['status'] !== 'draft') {
            http_response_code(400);
            jsonResponse(['error' => 'すでに発注済みです']);
            exit;
        }

        $iStmt = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll();

        try {
            $db->beginTransaction();

            $db->prepare("UPDATE purchase_orders SET status = 'ordered' WHERE id = ?")->execute([$id]);

            $today           = date('Y-m-d');
            $stockChkStmt    = $db->prepare("SELECT qty, name FROM stock WHERE code = ?");
            $stockUpdStmt    = $db->prepare("UPDATE stock SET qty = qty + ? WHERE code = ?");
            $stockInsStmt    = $db->prepare("INSERT INTO stock (code, name, price, qty) VALUES (?,?,?,?)");
            $inoutStmt       = $db->prepare("
                INSERT INTO stock_inout (io_date, code, name, in_qty, out_qty, stock_after, note)
                VALUES (?,?,?,?,0,?,?)
            ");

            foreach ($items as $item) {
                $code  = $item['product_code'] ?? '';
                $qty   = (int)$item['quantity'];
                $name  = $item['product_name'] ?? '';
                $price = (int)$item['unit_price'];
                if (!$code || $qty <= 0) continue;

                $stockChkStmt->execute([$code]);
                $stockRow = $stockChkStmt->fetch();

                if ($stockRow) {
                    $newQty = (int)$stockRow['qty'] + $qty;
                    $stockUpdStmt->execute([$qty, $code]);
                    $inName = $stockRow['name'] ?: $name;
                } else {
                    $newQty = $qty;
                    $stockInsStmt->execute([$code, $name, $price, $qty]);
                    $inName = $name;
                }
                $inoutStmt->execute([$today, $code, $inName, $qty, $newQty, '発注入庫 ' . $po['po_number']]);
            }

            $db->commit();
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'サーバーエラーが発生しました']);
        }
        exit;
    }

    // 新規作成
    $poNumber     = trim($body['po_number'] ?? '');
    $orderDate    = $body['order_date'] ?? '';
    $delivDate    = $body['delivery_date'] ?: null;
    $supplierId   = !empty($body['supplier_id']) ? (int)$body['supplier_id'] : null;
    $supplierName = $body['supplier_name'] ?? '';
    $notes        = $body['notes'] ?? '';
    $items        = $body['items'] ?? [];

    if (!$poNumber || !$orderDate) {
        http_response_code(400);
        jsonResponse(['error' => '発注番号と発注日は必須']);
        exit;
    }

    try {
        $db->beginTransaction();
        $db->prepare("
            INSERT INTO purchase_orders
              (po_number, order_date, delivery_date, supplier_id, supplier_name, notes, status)
            VALUES (?,?,?,?,?,?,'draft')
        ")->execute([$poNumber, $orderDate, $delivDate, $supplierId, $supplierName, $notes]);

        $poId = (int)$db->lastInsertId();
        $iStmt = $db->prepare("
            INSERT INTO purchase_order_items (po_id, product_code, product_name, quantity, unit_price)
            VALUES (?,?,?,?,?)
        ");
        foreach ($items as $item) {
            $iStmt->execute([
                $poId,
                $item['product_code'] ?? '',
                $item['product_name'] ?? '',
                (int)($item['quantity'] ?? 1),
                (int)($item['unit_price'] ?? 0),
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'id' => $poId, 'po_number' => $poNumber]);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        if ($e->getCode() === '23000') {
            http_response_code(409);
            jsonResponse(['error' => 'この発注番号はすでに使用されています']);
        } else {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'サーバーエラーが発生しました']);
        }
    }
    exit;
}

// ────────────────────────────────────────
// PUT: 更新（下書きのみ）
// ────────────────────────────────────────
if ($method === 'PUT') {
    $id           = (int)($body['id'] ?? 0);
    $poNumber     = trim($body['po_number'] ?? '');
    $orderDate    = $body['order_date'] ?? '';
    $delivDate    = $body['delivery_date'] ?: null;
    $supplierId   = !empty($body['supplier_id']) ? (int)$body['supplier_id'] : null;
    $supplierName = $body['supplier_name'] ?? '';
    $notes        = $body['notes'] ?? '';
    $items        = $body['items'] ?? [];

    if (!$id || !$poNumber || !$orderDate) {
        http_response_code(400);
        jsonResponse(['error' => 'id・発注番号・発注日は必須']);
        exit;
    }

    try {
        $db->beginTransaction();
        $db->prepare("
            UPDATE purchase_orders
            SET po_number=?, order_date=?, delivery_date=?, supplier_id=?, supplier_name=?, notes=?
            WHERE id=? AND status='draft'
        ")->execute([$poNumber, $orderDate, $delivDate, $supplierId, $supplierName, $notes, $id]);

        $db->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$id]);
        $iStmt = $db->prepare("
            INSERT INTO purchase_order_items (po_id, product_code, product_name, quantity, unit_price)
            VALUES (?,?,?,?,?)
        ");
        foreach ($items as $item) {
            $iStmt->execute([
                $id,
                $item['product_code'] ?? '',
                $item['product_name'] ?? '',
                (int)($item['quantity'] ?? 1),
                (int)($item['unit_price'] ?? 0),
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true]);
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ────────────────────────────────────────
// DELETE: 削除（下書きのみ）
// ────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { http_response_code(400); jsonResponse(['error' => 'id は必須']); exit; }

    $chkStmt = $db->prepare("SELECT status FROM purchase_orders WHERE id = ?");
    $chkStmt->execute([$id]);
    $po = $chkStmt->fetch();
    if (!$po) { http_response_code(404); jsonResponse(['error' => '見つかりません']); exit; }
    if ($po['status'] !== 'draft') {
        http_response_code(400);
        jsonResponse(['error' => '下書き以外の発注書は削除できません']);
        exit;
    }
    $db->prepare("DELETE FROM purchase_orders WHERE id = ?")->execute([$id]);
    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
