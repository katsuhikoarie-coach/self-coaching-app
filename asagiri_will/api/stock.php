<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();
$action = $_GET['action'] ?? $body['action'] ?? '';

// ────────────────────────────────────────
// GET: 在庫一覧
// ────────────────────────────────────────
if ($method === 'GET') {
    $stmt   = $db->query("SELECT * FROM stock ORDER BY code");
    $rows   = $stmt->fetchAll();
    $result = array_map(function($s) {
        return [
            'code'  => $s['code'],
            'name'  => $s['name'],
            'price' => (int)$s['price'],
            'qty'   => (int)$s['qty'],
        ];
    }, $rows);
    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: 在庫更新
// ────────────────────────────────────────
if ($method === 'POST') {

    // action=update_qty : 単一商品の在庫数更新（updateStock_FB の置き換え）
    if ($action === 'update_qty') {
        $code = $body['code'] ?? '';
        $qty  = isset($body['qty']) ? (int)$body['qty'] : null;
        if (!$code || $qty === null) {
            http_response_code(400);
            jsonResponse(['error' => 'code と qty は必須']);
            exit;
        }
        $db->prepare("UPDATE stock SET qty = ? WHERE code = ?")->execute([$qty, $code]);
        // 入出庫履歴（inoutテーブルは入出庫登録APIで管理するためここでは省略）
        jsonResponse(['ok' => true]);
        exit;
    }

    // action=replace_all : 在庫全件一括保存（saveInventory_FB の置き換え）
    if ($action === 'replace_all') {
        $items = $body['data'] ?? [];
        if (empty($items)) {
            jsonResponse(['ok' => true, 'count' => 0]);
            exit;
        }
        $stmt = $db->prepare("
            INSERT INTO stock (code, name, price, qty)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), qty=VALUES(qty)
        ");
        $db->beginTransaction();
        foreach ($items as $s) {
            $code = $s['code'] ?? '';
            if (!$code) continue;
            $stmt->execute([
                $code,
                $s['name'] ?? '',
                (float)($s['price'] ?? 0),
                (int)($s['qty'] ?? 0),
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'count' => count($items)]);
        exit;
    }

    // action=add_item : 新規商品をstockに追加（既存なら名前・価格だけ更新、在庫数は変えない）
    if ($action === 'add_item') {
        $code  = $body['code']  ?? '';
        $name  = $body['name']  ?? '';
        $price = (float)($body['price'] ?? 0);
        $qty   = (int)($body['qty']   ?? 0);
        if (!$code) {
            http_response_code(400);
            jsonResponse(['error' => 'code は必須']);
            exit;
        }
        try {
            $db->prepare("
                INSERT INTO `stock` (`code`, `name`, `price`, `qty`)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `price`=VALUES(`price`)
            ")->execute([$code, $name, $price, $qty]);
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            jsonResponse(['error' => 'stock追加エラー', 'detail' => $e->getMessage()]);
        }
        exit;
    }

    // action=inout : 入出庫登録
    if ($action === 'inout') {
        $code    = $body['code'] ?? '';
        $in_qty  = (int)($body['in'] ?? 0);
        $out_qty = (int)($body['out'] ?? 0);
        $stock   = (int)($body['stock'] ?? 0);
        $name    = $body['name'] ?? '';
        $note    = $body['note'] ?? '';
        $date    = $body['date'] ?? date('Y-m-d');
        if (!$code || ($in_qty === 0 && $out_qty === 0)) {
            http_response_code(400);
            jsonResponse(['error' => '入力不足']);
            exit;
        }
        // 在庫更新
        $db->prepare("UPDATE stock SET qty = ? WHERE code = ?")->execute([$stock, $code]);
        // 履歴登録
        $db->prepare("
            INSERT INTO stock_inout (io_date, code, name, in_qty, out_qty, stock_after, note)
            VALUES (?,?,?,?,?,?,?)
        ")->execute([$date, $code, $name, $in_qty, $out_qty, $stock, $note]);
        jsonResponse(['ok' => true]);
        exit;
    }

    http_response_code(400);
    jsonResponse(['error' => '不明なaction']);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
