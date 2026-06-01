<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();
$action = $_GET['action'] ?? $body['action'] ?? '';

// ────────────────────────────────────────
// GET: エステコース一覧
// ────────────────────────────────────────
if ($method === 'GET') {
    $stmt   = $db->query("SELECT * FROM este_menus ORDER BY id");
    $rows   = $stmt->fetchAll();
    $result = array_map(function($m) {
        return [
            'id'     => $m['id'],
            'name'   => $m['name'],
            'price'  => (int)$m['price'],
            'tax'    => (int)$m['tax_rate'],
            'active' => (bool)$m['active'],
        ];
    }, $rows);
    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: コース登録・更新
// ────────────────────────────────────────
if ($method === 'POST') {

    // action=replace_all : 全件一括保存（初期データ投入）
    if ($action === 'replace_all') {
        $menus = $body['data'] ?? [];
        if (empty($menus)) {
            jsonResponse(['ok' => true, 'count' => 0]);
            exit;
        }
        $stmt = $db->prepare("
            INSERT INTO este_menus (id, name, price, tax_rate, active)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name), price=VALUES(price),
              tax_rate=VALUES(tax_rate), active=VALUES(active)
        ");
        $db->beginTransaction();
        foreach ($menus as $m) {
            $mid = $m['id'] ?? '';
            if (!$mid) continue;
            $stmt->execute([
                $mid,
                $m['name'] ?? '',
                (int)($m['price'] ?? 0),
                (int)($m['tax'] ?? $m['tax_rate'] ?? 10),
                isset($m['active']) && $m['active'] === false ? 0 : 1,
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'count' => count($menus)]);
        exit;
    }

    // action=update : 単一コース更新
    if ($action === 'update') {
        $mid  = $body['id'] ?? '';
        $data = $body['data'] ?? [];
        if (!$mid) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        $sets = []; $params = [];
        if (isset($data['name']))     { $sets[] = 'name = ?';     $params[] = $data['name']; }
        if (isset($data['price']))    { $sets[] = 'price = ?';    $params[] = (int)$data['price']; }
        if (isset($data['tax']))      { $sets[] = 'tax_rate = ?'; $params[] = (int)$data['tax']; }
        if (isset($data['active']))   { $sets[] = 'active = ?';   $params[] = $data['active'] ? 1 : 0; }
        if (empty($sets)) {
            jsonResponse(['ok' => true]);
            exit;
        }
        $params[] = $mid;
        $db->prepare("UPDATE este_menus SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        jsonResponse(['ok' => true]);
        exit;
    }

    // action=create : 新規コース追加
    $mid  = $body['id'] ?? '';
    $name = $body['name'] ?? '';
    if (!$mid || !$name) {
        http_response_code(400);
        jsonResponse(['error' => 'id と name は必須']);
        exit;
    }
    $db->prepare("
        INSERT INTO este_menus (id, name, price, tax_rate, active)
        VALUES (?,?,?,?,1)
        ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), tax_rate=VALUES(tax_rate)
    ")->execute([
        $mid, $name,
        (int)($body['price'] ?? 0),
        (int)($body['tax'] ?? 10),
    ]);
    jsonResponse(['ok' => true, 'id' => $mid]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
