<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody() ?? [];

// ────────────────────────────────────────
// GET: 売上伝票一覧（明細付き）
// ────────────────────────────────────────
if ($method === 'GET') {
    try {
        // 伝票一覧取得
        $stmt  = $db->query("SELECT * FROM sales ORDER BY sale_date DESC, id DESC");
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
                'sub10'      => (float)$s['sub10'],
                'sub8'       => (float)$s['sub8'],
                'tax10'      => (float)$s['tax10'],
                'tax8'       => (float)$s['tax8'],
                'total'      => (float)$s['total'],
            ];
        }, $sales);

        jsonResponse($result);
    } catch (\Throwable $e) {
        http_response_code(500);
        jsonResponse(['error' => 'DB取得エラー', 'detail' => $e->getMessage()]);
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
            http_response_code(500);
            jsonResponse(['error' => '削除エラー', 'detail' => $e->getMessage()]);
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

        // 明細（既存削除→再挿入）
        $db->prepare("DELETE FROM sales_items WHERE sale_id = ?")->execute([$saleKey]);
        $itemStmt = $db->prepare("
            INSERT INTO sales_items
              (sale_id, product_code, product_name, qty, kake, tax_rate, amount)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($details as $d) {
            $code = $d['code'] ?? '';
            if ($code === '') continue; // 商品コード未入力行はスキップ
            $qty     = (int)($d['qty']    ?? 1);
            $kake    = (float)($d['kake'] ?? 1.0);
            $taxRate = (float)($d['tax']  ?? 0.10);
            $amount  = (float)($d['amount'] ?? 0);
            $pname   = $d['product_name'] ?? '';
            $itemStmt->execute([$saleKey, $code, $pname, $qty, $kake, $taxRate, $amount]);
        }

        $db->commit();
        jsonResponse(['ok' => true, 'id' => $saleKey]);
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        jsonResponse(['error' => 'DB保存エラー', 'detail' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
