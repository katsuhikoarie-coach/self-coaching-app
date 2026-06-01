<?php
// ============================================================
// 管理者用 注文API
// GET  ?period=YYYY-MM → 月度別注文一覧（全センター）
// GET  （periodなし）  → 最新200件
// POST action=update_status → ステータス変更
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: 注文一覧 ───────────────────────────────────────────
if ($method === 'GET') {
    try {
        $period = $_GET['period'] ?? null;

        if ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
            $range = periodToDateRange($period);
            $stmt  = $db->prepare("
                SELECT o.*, u.contact_name
                FROM orders o
                LEFT JOIN fc_users u ON u.id = o.fc_user_id
                WHERE o.order_date BETWEEN ? AND ?
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$range['start'], $range['end']]);
        } else {
            $stmt = $db->prepare("
                SELECT o.*, u.contact_name
                FROM orders o
                LEFT JOIN fc_users u ON u.id = o.fc_user_id
                ORDER BY o.created_at DESC
                LIMIT 200
            ");
            $stmt->execute();
        }
        $orders = $stmt->fetchAll();

        if (empty($orders)) {
            jsonResponse([]);
            exit;
        }

        // 明細を一括取得
        $ids     = array_column($orders, 'id');
        $ph      = implode(',', array_fill(0, count($ids), '?'));
        $items   = $db->prepare("SELECT * FROM order_items WHERE order_id IN ($ph) ORDER BY id");
        $items->execute($ids);
        $allItems = $items->fetchAll();

        $itemsMap = [];
        foreach ($allItems as $it) {
            $itemsMap[$it['order_id']][] = [
                'code'   => $it['product_code'],
                'name'   => $it['product_name'],
                'price'  => (float)$it['price'],
                'qty'    => (int)$it['quantity'],
                'tax'    => (float)$it['tax_rate'],
                'amount' => (float)$it['amount'],
            ];
        }

        $result = array_map(function ($o) use ($itemsMap) {
            return [
                'id'             => $o['id'],
                'order_date'     => $o['order_date'],
                'month_period'   => $o['month_period'] ?? dateToMonthPeriod($o['order_date']),
                'fc_name'        => $o['fc_name'],
                'contact_name'   => $o['contact_name'] ?? '',
                'subtotal'       => (float)$o['subtotal'],
                'tax_total'      => (float)$o['tax_total'],
                'total'          => (float)$o['total'],
                'note'           => $o['note'] ?? '',
                'status'         => $o['status'],
                'billing_status' => $o['billing_status'] ?? 'unbilled',
                'created_at'     => $o['created_at'],
                'items'          => $itemsMap[$o['id']] ?? [],
            ];
        }, $orders);

        jsonResponse($result);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ── POST: ステータス変更 ────────────────────────────────────
if ($method === 'POST') {
    $body   = getRequestBody();
    $action = $body['action'] ?? '';

    if ($action === 'update_status') {
        $id     = $body['id']     ?? '';
        $status = $body['status'] ?? '';
        $allowed = ['received', 'confirmed', 'shipped'];

        if (!$id || !in_array($status, $allowed, true)) {
            http_response_code(400);
            jsonResponse(['error' => '不正なパラメータです']);
            exit;
        }
        try {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            jsonResponse(['ok' => true, 'id' => $id, 'status' => $status]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'ステータス更新に失敗しました']);
        }
        exit;
    }

    if ($action === 'update_billing') {
        // 将来: 請求ステータス変更
        $id             = $body['id']             ?? '';
        $billing_status = $body['billing_status'] ?? '';
        $allowed        = ['unbilled', 'billed'];
        if (!$id || !in_array($billing_status, $allowed, true)) {
            http_response_code(400);
            jsonResponse(['error' => '不正なパラメータです']);
            exit;
        }
        try {
            $stmt = $db->prepare("UPDATE orders SET billing_status = ? WHERE id = ?");
            $stmt->execute([$billing_status, $id]);
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            jsonResponse(['error' => '更新に失敗しました']);
        }
        exit;
    }

    if ($action === 'delete') {
        $order_id = $body['order_id'] ?? '';
        if (!$order_id) {
            http_response_code(400);
            jsonResponse(['error' => '注文IDが必要です']);
            exit;
        }
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $db->commit();
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => '削除に失敗しました']);
        }
        exit;
    }

    http_response_code(400);
    jsonResponse(['error' => '不明なアクションです']);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
