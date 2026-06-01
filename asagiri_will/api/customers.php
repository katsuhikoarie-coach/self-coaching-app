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
            'code'     => $c['code'] ?? '',
            'name'     => $c['name'],
            'kana'     => $c['kana'] ?? $c['name_kana'] ?? '',
            'tel'      => $c['tel'] ?? $c['phone'] ?? '',
            'mobile'   => $c['mobile'] ?? '',
            'email'    => $c['email'] ?? '',
            'addr'     => $c['addr'] ?? '',
            'bday'     => $c['bday'] ?? $c['birthday'] ?? '',
            'birth'    => $c['bday'] ?? $c['birthday'] ?? '',
            'rank'     => $c['rank_name'] ?? '',
            'rank_id'  => $c['rank_id'] ?? '',
            'staff'    => $c['staff'] ?? '',
            'note'     => $c['note'] ?? $c['memo'] ?? '',
            'homecare' => !empty($c['homecare']),
            'keep_item'=> !empty($c['keep_item']),
            'active'   => isset($c['active']) ? (bool)$c['active'] : true,
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
        $allowed = ['name', 'name_kana', 'phone', 'email', 'birthday', 'memo', 'code'];
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
            INSERT INTO customers (code, name, name_kana, phone, email, birthday, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name), name_kana=VALUES(name_kana),
              phone=VALUES(phone), email=VALUES(email),
              birthday=VALUES(birthday), memo=VALUES(memo)
        ");
        $db->beginTransaction();
        try {
            foreach ($customers as $c) {
                $code = (string)($c['code'] ?? $c['no'] ?? $c['id'] ?? '');
                if (!$code) continue;
                $birthday = $c['bday'] ?? $c['birth'] ?? $c['birthday'] ?? '';
                if ($birthday === '' || $birthday === 'null') $birthday = null;
                $stmt->execute([
                    $code,
                    $c['name'] ?? '',
                    $c['kana'] ?? $c['name_kana'] ?? '',
                    $c['tel'] ?? $c['phone'] ?? '',
                    $c['email'] ?? '',
                    $birthday ?: null,
                    $c['note'] ?? $c['memo'] ?? '',
                ]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            jsonResponse(['error' => 'DB保存エラー', 'detail' => $e->getMessage()]);
            exit;
        }
        jsonResponse(['ok' => true, 'count' => count($customers)]);
        exit;
    }

    // action=create : 新規顧客1件登録
    $code = (string)($body['code'] ?? $body['no'] ?? $body['id'] ?? '');
    $name = $body['name'] ?? '';
    if (!$code || !$name) {
        http_response_code(400);
        jsonResponse(['error' => 'code と name は必須']);
        exit;
    }
    $birthday = $body['bday'] ?? $body['birth'] ?? $body['birthday'] ?? '';
    if ($birthday === '' || $birthday === 'null') $birthday = null;
    try {
        $db->prepare("
            INSERT INTO customers (code, name, name_kana, phone, email, birthday, memo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name), name_kana=VALUES(name_kana),
              phone=VALUES(phone), email=VALUES(email),
              birthday=VALUES(birthday), memo=VALUES(memo)
        ")->execute([
            $code, $name,
            $body['kana'] ?? $body['name_kana'] ?? '',
            $body['tel'] ?? $body['phone'] ?? '',
            $body['email'] ?? '',
            $birthday ?: null,
            $body['note'] ?? $body['memo'] ?? '',
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        jsonResponse(['error' => 'DB保存エラー', 'detail' => $e->getMessage()]);
        exit;
    }
    jsonResponse(['ok' => true, 'id' => $code]);
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

    $birthday = $body['bday'] ?? $body['birth'] ?? $body['birthday'] ?? '';
    if ($birthday === '' || $birthday === 'null') $birthday = null;

    $db->prepare("
        UPDATE customers SET
          name      = ?,
          name_kana = ?,
          phone     = ?,
          email     = ?,
          birthday  = ?,
          memo      = ?
        WHERE id = ?
    ")->execute([
        $name,
        $body['kana']   ?? $body['name_kana'] ?? '',
        $body['tel']    ?? $body['phone']     ?? '',
        $body['email']  ?? '',
        $birthday ?: null,
        $body['note']   ?? $body['memo']      ?? '',
        $id,
    ]);

    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
