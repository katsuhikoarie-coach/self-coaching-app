<?php
require_once __DIR__ . '/../config/db.php';
$authUser = requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody() ?? [];

// ────────────────────────────────────────
// GET: 売上伝票一覧（明細付き）
// ────────────────────────────────────────
if ($method === 'GET') {
    try {
        // 伝票一覧取得（上代実績を合算）
        $stmt  = $db->query("
            SELECT s.*, COALESCE(SUM(p.price * si.qty), 0) AS list_price_total
            FROM sales s
            LEFT JOIN sales_items si ON s.id = si.sale_id
            LEFT JOIN products p ON si.product_code = p.code
            GROUP BY s.id
            ORDER BY s.sale_date DESC, s.id DESC
        ");
        $sales = $stmt->fetchAll();

        // 明細を一括取得して紐付け
        $itemsStmt = $db->query("SELECT * FROM sales_items ORDER BY id");
        $allItems  = $itemsStmt->fetchAll();
        $itemsMap  = [];
        foreach ($allItems as $it) {
            $itemsMap[$it['sale_id']][] = $it;
        }

        $result = array_map(function($s) use ($itemsMap) {
            $items = $itemsMap[$s['id']] ?? [];
            $details = array_map(function($it) {
                return [
                    'code' => $it['product_code'],
                    'qty'  => (int)$it['qty'],
                    'kake' => (float)$it['kake'],
                    'tax'  => (float)$it['tax_rate'],
                ];
            }, $items);

            return [
                'no'         => $s['id'],
                'id'         => $s['id'],
                'orderDate'  => $s['sale_date'],
                'delivDate'  => $s['deliver_date'] ?? $s['sale_date'],
                'custNo'     => (int)$s['customer_id'],
                'cid'        => $s['customer_id'],
                'cn'         => $s['customer_name'],
                'd'          => $s['sale_date'],
                'ym'         => $s['year_month'],
                'details'    => $details,
                'sub10'          => (float)$s['sub10'],
                'sub8'           => (float)$s['sub8'],
                'tax10'          => (float)$s['tax10'],
                'tax8'           => (float)$s['tax8'],
                'total'          => (float)$s['total'],
                'listPriceTotal' => (float)$s['list_price_total'],
            ];
        }, $sales);

        jsonResponse($result);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ────────────────────────────────────────
// POST: 売上伝票登録・修正・削除
// ────────────────────────────────────────
if ($method === 'POST') {
    $action = $body['action'] ?? '';

    // action=delete : 伝票削除
    if ($action === 'delete') {
        $id = $body['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM `sales_items` WHERE `sale_id` = ?")->execute([$id]);
            $db->prepare("DELETE FROM `sales` WHERE `id` = ?")->execute([$id]);
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

    $saleId      = $body['no'] ?? $body['id'] ?? '';
    $orderDate   = $body['orderDate'] ?? $body['d'] ?? '';
    $delivDate   = $body['delivDate'] ?? $orderDate;
    $custNo      = (string)($body['custNo'] ?? $body['cid'] ?? '');
    $custName    = $body['cn'] ?? $body['cust_name'] ?? '';
    $custKana    = $body['cust_kana'] ?? '';
    $details     = $body['details'] ?? [];
    $sub10       = (float)($body['sub10'] ?? 0);
    $sub8        = (float)($body['sub8'] ?? 0);
    $tax10       = (float)($body['tax10'] ?? 0);
    $tax8        = (float)($body['tax8'] ?? 0);
    $total       = (float)($body['total'] ?? ($sub10 + $tax10 + $sub8 + $tax8));

    if (!$saleId || !$orderDate) {
        http_response_code(400);
        jsonResponse(['error' => '伝票番号と売上日は必須']);
        exit;
    }

    $yearMonth = substr($orderDate, 0, 7);
    // 伝票番号のキーとして使えない文字を _ に置換
    $saleKey = preg_replace('/[.#$\[\]\/]/', '_', $saleId);

    try {
        // 編集時の二重減算防止：既存明細の在庫数を先に取得
        $oldItemStmt = $db->prepare("SELECT product_code, qty FROM sales_items WHERE sale_id = ?");
        $oldItemStmt->execute([$saleKey]);
        $oldItems = $oldItemStmt->fetchAll();

        $db->beginTransaction();

        $db->prepare("
            INSERT INTO `sales`
              (`id`, `sale_date`, `year_month`, `deliver_date`,
               `customer_id`, `customer_name`, `customer_kana`,
               `sub10`, `sub8`, `tax10`, `tax8`, `total`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              `sale_date`=VALUES(`sale_date`), `year_month`=VALUES(`year_month`),
              `deliver_date`=VALUES(`deliver_date`),
              `customer_id`=VALUES(`customer_id`), `customer_name`=VALUES(`customer_name`),
              `sub10`=VALUES(`sub10`), `sub8`=VALUES(`sub8`),
              `tax10`=VALUES(`tax10`), `tax8`=VALUES(`tax8`), `total`=VALUES(`total`)
        ")->execute([
            $saleKey, $orderDate, $yearMonth, $delivDate ?: $orderDate,
            $custNo, $custName, $custKana,
            $sub10, $sub8, $tax10, $tax8, $total,
        ]);

        // 編集時：旧明細分の在庫を戻す
        if (!empty($oldItems)) {
            $restoreStmt = $db->prepare("UPDATE stock SET qty = qty + ? WHERE code = ?");
            foreach ($oldItems as $old) {
                $restoreStmt->execute([(int)$old['qty'], $old['product_code']]);
            }
        }

        // 明細（既存削除→再挿入）
        $db->prepare("DELETE FROM sales_items WHERE sale_id = ?")->execute([$saleKey]);
        $itemStmt = $db->prepare("
            INSERT INTO sales_items
              (sale_id, product_code, product_name, qty, kake, tax_rate, amount)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($details as $d) {
            $code    = $d['code'] ?? '';
            $qty     = (int)($d['qty'] ?? 1);
            $kake    = (float)($d['kake'] ?? 1.0);
            $taxRate = (float)($d['tax'] ?? 0.10);
            $amount  = (float)($d['amount'] ?? 0);
            $pname   = $d['product_name'] ?? '';
            $itemStmt->execute([$saleKey, $code, $pname, $qty, $kake, $taxRate, $amount]);
        }

        // 在庫減算（stockに登録済み商品のみ対象）
        $warnings        = [];
        $stockCheckStmt  = $db->prepare("SELECT name, qty FROM stock WHERE code = ?");
        $stockDeductStmt = $db->prepare("UPDATE stock SET qty = qty - ? WHERE code = ?");
        foreach ($details as $d) {
            $code = $d['code'] ?? '';
            $qty  = (int)($d['qty'] ?? 1);
            if (!$code || $qty <= 0) continue;

            $stockCheckStmt->execute([$code]);
            $stockRow = $stockCheckStmt->fetch();
            if (!$stockRow) continue; // stock未登録はスキップ

            $stockDeductStmt->execute([$qty, $code]);

            if (($stockRow['qty'] - $qty) < 0) {
                $pname = !empty($d['product_name']) ? $d['product_name'] : $code;
                $warnings[] = $pname . 'が在庫不足です';
            }
        }

        $db->commit();

        // 自動ポイント付与（total × 1%、エラーは売上登録に影響させない）
        try {
            $paStmt = $db->prepare("SELECT total, customer_id FROM sales WHERE id = ?");
            $paStmt->execute([$saleKey]);
            $saleRow = $paStmt->fetch();
            if ($saleRow && (float)$saleRow['total'] > 0) {
                $pts = (int)floor((float)$saleRow['total'] * 0.01);
                if ($pts >= 1) {
                    $db->prepare("DELETE FROM customer_points WHERE sale_id = ? AND reason = '売上自動付与'")->execute([$saleKey]);
                    $db->prepare("
                        INSERT INTO customer_points (customer_id, point_type, points, reason, sale_id, created_by)
                        VALUES (?, 'add', ?, '売上自動付与', ?, ?)
                    ")->execute([(int)$saleRow['customer_id'], $pts, $saleKey, $authUser['email'] ?? '']);
                }
            }
        } catch (\Throwable $e) {
            error_log('Point grant error: ' . $e->getMessage());
        }

        $response = ['ok' => true, 'id' => $saleKey];
        if (!empty($warnings)) $response['warning'] = $warnings;
        jsonResponse($response);
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
