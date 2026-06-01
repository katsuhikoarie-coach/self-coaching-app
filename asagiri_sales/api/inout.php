<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ────────────────────────────────────────
// GET: 入出庫履歴一覧
// ────────────────────────────────────────
if ($method === 'GET') {
    $stmt   = $db->query("SELECT * FROM stock_inout ORDER BY io_date DESC, id DESC LIMIT 1000");
    $rows   = $stmt->fetchAll();
    $result = array_map(function($r) {
        return [
            'date'  => $r['io_date'],
            'code'  => $r['code'],
            'name'  => $r['name'],
            'in'    => (int)$r['in_qty'],
            'out'   => (int)$r['out_qty'],
            'stock' => (int)$r['stock_after'],
            'note'  => $r['note'],
        ];
    }, $rows);
    jsonResponse($result);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
