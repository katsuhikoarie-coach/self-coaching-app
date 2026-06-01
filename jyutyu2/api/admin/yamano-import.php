<?php
// ============================================================
// ヤマノ注文履歴 インポートAPI
// 認証: X-Api-Key ヘッダー（run-scraper.bat の API_KEY と一致させる）
//
// GET  → 既存の注文番号一覧を返す（差分チェック用）
// POST { orders: [...] } → 受け取ったデータをDBに保存
// ============================================================

// ── APIキー定義（run-scraper.bat の API_KEY= と同じ値にする） ──
define('IMPORT_API_KEY', 'YAMANO_IMPORT_2026');

require_once __DIR__ . '/auth.php'; // getDB(), jsonResponse(), getRequestBody()

// APIキー検証（Firebase認証は使わない）
$apiKey = '';
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower($k) === 'x-api-key') { $apiKey = trim($v); break; }
    }
}
if (!$apiKey && isset($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
}
if (!$apiKey || $apiKey !== IMPORT_API_KEY) {
    http_response_code(401);
    jsonResponse(['error' => 'APIキーが無効です']);
    exit;
}

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: 既存注文番号一覧 ────────────────────────────────────
// 明細(yamano_order_items)が1件以上あるものだけ「取込済み」とみなす
// 明細0件のレコードは失敗した取込とみなし、再取込させる
if ($method === 'GET') {
    try {
        $stmt = $db->query("
            SELECT DISTINCT yo.yamano_order_id
              FROM yamano_orders yo
             INNER JOIN yamano_order_items yoi ON yoi.yamano_order_id = yo.yamano_order_id
        ");
        $ids  = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        jsonResponse(['existing_ids' => $ids]);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラー']);
    }
    exit;
}

// ── POST: 注文データを受け取ってDBに保存 ─────────────────────
if ($method === 'POST') {
    $body   = getRequestBody();
    $orders = $body['orders'] ?? [];

    if (!is_array($orders) || count($orders) === 0) {
        http_response_code(400);
        jsonResponse(['error' => 'ordersが空またはJSON形式が不正です']);
        exit;
    }

    // shipping_map取得（氏名 → fc_user_id）
    $mapStmt    = $db->query("SELECT shipping_name, fc_user_id FROM yamano_shipping_map");
    $shippingMap = [];
    foreach ($mapStmt->fetchAll() as $r) {
        $shippingMap[$r['shipping_name']] = (int)$r['fc_user_id'];
    }

    $saved   = 0;
    $skipped = 0;
    $errors  = [];

    foreach ($orders as $order) {
        $orderId = trim($order['yamano_order_id'] ?? '');
        if (!$orderId) {
            $errors[] = '注文番号(yamano_order_id)なし';
            continue;
        }

        // shipping_map照合
        $shippingName = $order['shipping_name'] ?? '';
        $fcUserId     = isset($shippingMap[$shippingName]) ? $shippingMap[$shippingName] : null;

        // month_period計算
        $orderDateStr = $order['order_date'] ?? date('Y-m-d');
        $monthPeriod  = dateToMonthPeriod($orderDateStr);

        try {
            $db->beginTransaction();

            // INSERT … ON DUPLICATE KEY UPDATE
            // 既存レコードが空データ（前回の失敗分）の場合も正しく上書きする
            $stmt = $db->prepare("
                INSERT INTO yamano_orders
                  (yamano_order_id, order_date, total_pretax, shipping_name, shipping_address,
                   shipping_tel, status, fc_user_id, month_period)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  order_date       = VALUES(order_date),
                  total_pretax     = VALUES(total_pretax),
                  shipping_name    = VALUES(shipping_name),
                  shipping_address = VALUES(shipping_address),
                  shipping_tel     = VALUES(shipping_tel),
                  status           = VALUES(status),
                  fc_user_id       = COALESCE(fc_user_id, VALUES(fc_user_id)),
                  month_period     = VALUES(month_period)
            ");
            $stmt->execute([
                $orderId,
                $orderDateStr,
                (int)($order['total_pretax']     ?? 0),
                $shippingName,
                $order['shipping_address'] ?? '',
                $order['shipping_tel']     ?? '',
                $order['status']           ?? '',
                $fcUserId,
                $monthPeriod,
            ]);

            // 既存の明細を一旦削除してから挿入（再取込時の上書き対応）
            $db->prepare("DELETE FROM yamano_order_items WHERE yamano_order_id = ?")
               ->execute([$orderId]);

            $itemStmt = $db->prepare("
                INSERT INTO yamano_order_items
                  (yamano_order_id, product_code, product_name, unit_price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($order['items'] ?? [] as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_code']  ?? '',
                    $item['product_name']  ?? '',
                    (int)($item['unit_price'] ?? 0),
                    (int)($item['quantity']   ?? 1),
                    (int)($item['subtotal']   ?? 0),
                ]);
            }

            $db->commit();
            $saved++;

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log("yamano-import {$orderId}: " . $e->getMessage());
            $errors[] = "{$orderId}: " . $e->getMessage();
        }
    }

    jsonResponse([
        'saved'   => $saved,
        'skipped' => $skipped,
        'errors'  => $errors,
    ]);
    exit;
}

// ── DELETE: 明細0件の不完全レコードを削除（再取込用） ────────
if ($method === 'DELETE') {
    try {
        $db->beginTransaction();
        // yamano_order_itemsに1件もない注文を抽出
        $stmt    = $db->query("
            SELECT yo.yamano_order_id
              FROM yamano_orders yo
              LEFT JOIN yamano_order_items yoi ON yoi.yamano_order_id = yo.yamano_order_id
             WHERE yoi.id IS NULL
        ");
        $targets = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        if (count($targets) > 0) {
            $ph = implode(',', array_fill(0, count($targets), '?'));
            $db->prepare("DELETE FROM yamano_orders WHERE yamano_order_id IN ($ph)")->execute($targets);
        }
        $db->commit();
        jsonResponse(['deleted' => count($targets), 'ids' => $targets]);
    } catch (\Throwable $e) {
        $db->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => '削除失敗: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
