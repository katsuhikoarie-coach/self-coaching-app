<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();
$action = $_GET['action'] ?? $body['action'] ?? '';

// ────────────────────────────────────────
// GET: 顧客一覧を返す
// ────────────────────────────────────────
if ($method === 'GET') {
    $sql    = "SELECT * FROM customers ORDER BY CAST(id AS UNSIGNED)";
    $stmt   = $db->query($sql);
    $rows   = $stmt->fetchAll();
    // フロントエンドが期待する形式に変換
    $result = array_map(function($c) {
        return [
            'id'       => $c['id'],
            'no'       => (int)$c['id'],
            'name'     => $c['name'],
            'kana'     => $c['kana'],
            'tel'      => $c['tel'],
            'mobile'   => $c['mobile'],
            'email'    => $c['email'],
            'addr'     => $c['addr'],
            'bday'     => $c['bday'],
            'birth'    => $c['bday'],  // 後方互換
            'rank'     => $c['rank_name'],
            'rank_id'  => $c['rank_id'],
            'staff'    => $c['staff'],
            'note'     => $c['note'],
            'homecare' => (bool)$c['homecare'],
            'keep_item'=> (bool)$c['keep_item'],
            'active'   => $c['active'] ? true : false,
        ];
    }, $rows);
    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: 顧客の作成・更新
// ────────────────────────────────────────
if ($method === 'POST') {

    // action=update : 単一顧客の一部更新（active/退会など）
    if ($action === 'update') {
        $id   = $body['id'] ?? '';
        $data = $body['data'] ?? [];
        if (!$id || empty($data)) {
            http_response_code(400);
            jsonResponse(['error' => 'id と data は必須']);
            exit;
        }
        $sets   = [];
        $params = [];
        $allowed = ['name','kana','zip','addr','tel','mobile','email','bday',
                    'rank_id','rank_name','staff','note','homecare','keep_item','active'];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`$col` = ?";
                $params[] = $data[$col];
            }
        }
        if (empty($sets)) {
            jsonResponse(['ok' => true]);
            exit;
        }
        $params[] = $id;
        $db->prepare("UPDATE customers SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        jsonResponse(['ok' => true]);
        exit;
    }

    // action=replace_all : 全件一括保存（saveCustomers_FB の置き換え）
    if ($action === 'replace_all') {
        $customers = $body['data'] ?? [];
        if (empty($customers)) {
            jsonResponse(['ok' => true, 'count' => 0]);
            exit;
        }
        $stmt = $db->prepare("
            INSERT INTO customers
              (id, name, kana, zip, addr, tel, mobile, email, bday,
               rank_id, rank_name, staff, note, homecare, keep_item, active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name), kana=VALUES(kana), zip=VALUES(zip),
              addr=VALUES(addr), tel=VALUES(tel), mobile=VALUES(mobile),
              email=VALUES(email), bday=VALUES(bday),
              rank_name=VALUES(rank_name), staff=VALUES(staff),
              note=VALUES(note), active=VALUES(active)
        ");
        $db->beginTransaction();
        foreach ($customers as $c) {
            $cid = $c['id'] ?? $c['no'] ?? '';
            if (!$cid) continue;
            $stmt->execute([
                (string)$cid,
                $c['name'] ?? '',
                $c['kana'] ?? '',
                $c['zip'] ?? '',
                $c['addr'] ?? '',
                $c['tel'] ?? '',
                $c['mobile'] ?? '',
                $c['email'] ?? '',
                $c['bday'] ?? $c['birth'] ?? '',
                $c['rank_id'] ?? '',
                $c['rank'] ?? $c['rank_name'] ?? '一般',
                $c['staff'] ?? '',
                $c['note'] ?? '',
                empty($c['homecare']) ? 0 : 1,
                empty($c['keep_item']) ? 0 : 1,
                isset($c['active']) && $c['active'] === false ? 0 : 1,
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'count' => count($customers)]);
        exit;
    }

    // action=create : 新規顧客1件登録
    $cid  = (string)($body['id'] ?? $body['no'] ?? '');
    $name = $body['name'] ?? '';
    if (!$cid || !$name) {
        http_response_code(400);
        jsonResponse(['error' => 'id と name は必須']);
        exit;
    }
    $stmt = $db->prepare("
        INSERT INTO customers
          (id, name, kana, zip, addr, tel, mobile, email, bday,
           rank_id, rank_name, staff, note, homecare, keep_item, active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          name=VALUES(name), kana=VALUES(kana), addr=VALUES(addr),
          tel=VALUES(tel), mobile=VALUES(mobile), email=VALUES(email),
          bday=VALUES(bday), rank_name=VALUES(rank_name), staff=VALUES(staff), note=VALUES(note)
    ");
    $stmt->execute([
        $cid, $name,
        $body['kana'] ?? '',
        $body['zip'] ?? '',
        $body['addr'] ?? '',
        $body['tel'] ?? '',
        $body['mobile'] ?? '',
        $body['email'] ?? '',
        $body['bday'] ?? $body['birth'] ?? '',
        $body['rank_id'] ?? '',
        $body['rank'] ?? $body['rank_name'] ?? '一般',
        $body['staff'] ?? '',
        $body['note'] ?? '',
        empty($body['homecare']) ? 0 : 1,
        empty($body['keep_item']) ? 0 : 1,
        1,
    ]);
    jsonResponse(['ok' => true, 'id' => $cid]);
    exit;
}

// ────────────────────────────────────────
// PUT: 顧客情報の修正
// ────────────────────────────────────────
if ($method === 'PUT') {
    $id   = $body['id'] ?? '';
    $name = $body['name'] ?? '';
    if (!$id || !$name) {
        http_response_code(400);
        jsonResponse(['error' => 'id と name は必須']);
        exit;
    }

    $bday = $body['bday'] ?? '';
    if ($bday === '' || $bday === 'null') $bday = null;

    $db->prepare("
        UPDATE customers SET
          name      = ?,
          kana      = ?,
          tel       = ?,
          mobile    = ?,
          addr      = ?,
          bday      = ?,
          email     = ?,
          rank_name = ?,
          staff     = ?,
          note      = ?
        WHERE id = ?
    ")->execute([
        $name,
        $body['kana']   ?? '',
        $body['tel']    ?? '',
        $body['mobile'] ?? '',
        $body['addr']   ?? '',
        $bday,
        $body['email']  ?? '',
        $body['rank']   ?? '一般',
        $body['staff']  ?? '',
        $body['note']   ?? '',
        $id,
    ]);

    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
