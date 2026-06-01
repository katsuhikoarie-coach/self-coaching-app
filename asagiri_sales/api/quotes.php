<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

// ────────────────────────────────────────
// GET: 見積一覧 or 1件取得
// ────────────────────────────────────────
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id) {
        // 1件取得
        $stmt = $db->prepare("SELECT * FROM quotes WHERE id = ?");
        $stmt->execute([$id]);
        $quote = $stmt->fetch();
        if (!$quote) {
            http_response_code(404);
            jsonResponse(['error' => '見積が見つかりません']);
            exit;
        }
        $iStmt = $db->prepare("SELECT * FROM quotes_items WHERE quote_id = ? ORDER BY sort_order, id");
        $iStmt->execute([$id]);
        $quote['items'] = $iStmt->fetchAll();
        jsonResponse($quote);
        exit;
    }

    // 一覧取得
    $stmt   = $db->query("SELECT * FROM quotes ORDER BY quote_date DESC, id DESC");
    $quotes = $stmt->fetchAll();

    // 明細を一括取得して紐付け
    $iStmt   = $db->query("SELECT * FROM quotes_items ORDER BY quote_id, sort_order, id");
    $allItems = $iStmt->fetchAll();
    $itemsMap = [];
    foreach ($allItems as $it) {
        $itemsMap[$it['quote_id']][] = $it;
    }

    $result = array_map(function($q) use ($itemsMap) {
        $q['items'] = $itemsMap[$q['id']] ?? [];
        return $q;
    }, $quotes);

    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: action=create / update / delete
// ────────────────────────────────────────
if ($method === 'POST') {
    $action = $body['action'] ?? '';

    // ── CREATE ──────────────────────────
    if ($action === 'create') {
        $quoteDate  = $body['quote_date']  ?? date('Y-m-d');
        $custNo     = $body['cust_no']     ?? '';
        $custName   = $body['cust_name']   ?? '';
        $rate       = (int)($body['rate']  ?? 100);
        $note       = $body['note']        ?? '';
        $totalTax10 = (int)($body['total_tax10'] ?? 0);
        $totalTax8  = (int)($body['total_tax8']  ?? 0);
        $totalAmount = (int)($body['total_amount'] ?? 0);
        $items      = $body['items']       ?? [];

        // quote_no 自動採番: Q{YYYYMMDD}-{連番4桁}
        $datePart = str_replace('-', '', $quoteDate);
        $cntStmt  = $db->prepare("SELECT COUNT(*) FROM quotes WHERE quote_no LIKE ?");
        $cntStmt->execute(["Q{$datePart}-%"]);
        $seq     = (int)$cntStmt->fetchColumn() + 1;
        $quoteNo = sprintf("Q%s-%04d", $datePart, $seq);

        $db->beginTransaction();

        $db->prepare("
            INSERT INTO quotes
              (quote_no, cust_no, cust_name, quote_date, rate, note,
               total_tax10, total_tax8, total_amount)
            VALUES (?,?,?,?,?,?,?,?,?)
        ")->execute([$quoteNo, $custNo, $custName, $quoteDate, $rate, $note,
                     $totalTax10, $totalTax8, $totalAmount]);
        $quoteId = (int)$db->lastInsertId();

        insertItems($db, $quoteId, $items);

        $db->commit();
        jsonResponse(['ok' => true, 'id' => $quoteId, 'quote_no' => $quoteNo]);
        exit;
    }

    // ── UPDATE ──────────────────────────
    if ($action === 'update') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        $quoteDate   = $body['quote_date']   ?? date('Y-m-d');
        $custNo      = $body['cust_no']      ?? '';
        $custName    = $body['cust_name']    ?? '';
        $rate        = (int)($body['rate']   ?? 100);
        $note        = $body['note']         ?? '';
        $totalTax10  = (int)($body['total_tax10']  ?? 0);
        $totalTax8   = (int)($body['total_tax8']   ?? 0);
        $totalAmount = (int)($body['total_amount'] ?? 0);
        $items       = $body['items']        ?? [];

        $db->beginTransaction();

        $db->prepare("
            UPDATE quotes SET
              cust_no=?, cust_name=?, quote_date=?, rate=?, note=?,
              total_tax10=?, total_tax8=?, total_amount=?
            WHERE id=?
        ")->execute([$custNo, $custName, $quoteDate, $rate, $note,
                     $totalTax10, $totalTax8, $totalAmount, $id]);

        $db->prepare("DELETE FROM quotes_items WHERE quote_id = ?")->execute([$id]);
        insertItems($db, $id, $items);

        $db->commit();
        jsonResponse(['ok' => true, 'id' => $id]);
        exit;
    }

    // ── DELETE ──────────────────────────
    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        // CASCADE により quotes_items も自動削除
        $db->prepare("DELETE FROM quotes WHERE id = ?")->execute([$id]);
        jsonResponse(['ok' => true]);
        exit;
    }

    http_response_code(400);
    jsonResponse(['error' => '不明な action: ' . $action]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);

// ────────────────────────────────────────
// 明細INSERT ヘルパー
// ────────────────────────────────────────
function insertItems(PDO $db, int $quoteId, array $items): void {
    $stmt = $db->prepare("
        INSERT INTO quotes_items
          (quote_id, sort_order, prod_code, prod_name, price, qty, rate, tax_rate)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    foreach ($items as $i => $it) {
        $stmt->execute([
            $quoteId,
            (int)($it['sort_order'] ?? $i),
            $it['prod_code'] ?? '',
            $it['prod_name'] ?? '',
            (int)($it['price']    ?? 0),
            (int)($it['qty']      ?? 1),
            (int)($it['rate']     ?? 100),
            (int)($it['tax_rate'] ?? 10),
        ]);
    }
}
