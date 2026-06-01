<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db = getDB();

// ────────────────────────────────────────
// GET: 月次レポートデータ
// ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = $_GET['year'] ?? date('Y');
    $year = preg_replace('/[^0-9]/', '', $year);

    // 月別商品売上
    $stmt = $db->prepare("
        SELECT year_month, SUM(total) as total_amount, COUNT(*) as slip_count
        FROM sales
        WHERE year_month LIKE ?
        GROUP BY year_month
        ORDER BY year_month
    ");
    $stmt->execute([$year . '-%']);
    $salesRows = $stmt->fetchAll();

    $prodAmt = array_fill(0, 12, 0);
    $salesCnt = 0;
    foreach ($salesRows as $r) {
        $mi = (int)substr($r['year_month'], 5, 2) - 1;
        if ($mi >= 0 && $mi < 12) {
            $prodAmt[$mi] = (float)$r['total_amount'];
            $salesCnt += (int)$r['slip_count'];
        }
    }

    // 月別エステ売上
    $stmt2 = $db->prepare("
        SELECT DATE_FORMAT(sale_date, '%Y-%m') as ym,
               SUM(total) as total_amount, COUNT(*) as sale_count
        FROM este_sales
        WHERE sale_date LIKE ?
        GROUP BY ym
        ORDER BY ym
    ");
    $stmt2->execute([$year . '-%']);
    $esteRows = $stmt2->fetchAll();

    $esteAmt = array_fill(0, 12, 0);
    $esteCnt = array_fill(0, 12, 0);
    foreach ($esteRows as $r) {
        $mi = (int)substr($r['ym'], 5, 2) - 1;
        if ($mi >= 0 && $mi < 12) {
            $esteAmt[$mi] = (float)$r['total_amount'];
            $esteCnt[$mi] = (int)$r['sale_count'];
        }
    }

    jsonResponse([
        'year'      => $year,
        'prod_amt'  => $prodAmt,
        'este_amt'  => $esteAmt,
        'este_cnt'  => $esteCnt,
        'sales_cnt' => $salesCnt,
    ]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
