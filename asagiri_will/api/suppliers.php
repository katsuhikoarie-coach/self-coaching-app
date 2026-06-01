<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

// ────────────────────────────────────────
// GET: 仕入先一覧（is_active=1） / 単件取得
// ────────────────────────────────────────
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); jsonResponse(['error' => '見つかりません']); exit; }
        jsonResponse($row);
    } else {
        $stmt = $db->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_code");
        jsonResponse($stmt->fetchAll());
    }
    exit;
}

// ────────────────────────────────────────
// POST: 新規登録
// ────────────────────────────────────────
if ($method === 'POST') {
    $code = trim($body['supplier_code'] ?? '');
    $name = trim($body['supplier_name'] ?? '');
    if (!$code || !$name) {
        http_response_code(400);
        jsonResponse(['error' => '仕入先コードと仕入先名は必須です']);
        exit;
    }
    try {
        $stmt = $db->prepare("
            INSERT INTO suppliers
              (supplier_code, supplier_name, kana, tel, fax, email, address, contact_person, payment_terms, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $code,
            $name,
            $body['kana']           ?? '',
            $body['tel']            ?? '',
            $body['fax']            ?? '',
            $body['email']          ?? '',
            $body['address']        ?? '',
            $body['contact_person'] ?? '',
            $body['payment_terms']  ?? '',
            $body['notes']          ?? '',
        ]);
        jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(409);
            jsonResponse(['error' => 'この仕入先コードはすでに使用されています']);
        } else {
            http_response_code(500);
            jsonResponse(['error' => '登録エラー', 'detail' => $e->getMessage()]);
        }
    }
    exit;
}

// ────────────────────────────────────────
// PUT: 更新
// ────────────────────────────────────────
if ($method === 'PUT') {
    $id   = $body['id']            ?? null;
    $code = trim($body['supplier_code'] ?? '');
    $name = trim($body['supplier_name'] ?? '');
    if (!$id || !$code || !$name) {
        http_response_code(400);
        jsonResponse(['error' => 'id・仕入先コード・仕入先名は必須です']);
        exit;
    }
    try {
        $stmt = $db->prepare("
            UPDATE suppliers SET
              supplier_code=?, supplier_name=?, kana=?, tel=?, fax=?, email=?,
              address=?, contact_person=?, payment_terms=?, notes=?
            WHERE id=?
        ");
        $stmt->execute([
            $code,
            $name,
            $body['kana']           ?? '',
            $body['tel']            ?? '',
            $body['fax']            ?? '',
            $body['email']          ?? '',
            $body['address']        ?? '',
            $body['contact_person'] ?? '',
            $body['payment_terms']  ?? '',
            $body['notes']          ?? '',
            (int)$id,
        ]);
        jsonResponse(['ok' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(409);
            jsonResponse(['error' => 'この仕入先コードはすでに使用されています']);
        } else {
            http_response_code(500);
            jsonResponse(['error' => '更新エラー', 'detail' => $e->getMessage()]);
        }
    }
    exit;
}

// ────────────────────────────────────────
// DELETE: 論理削除（is_active = 0）
// ────────────────────────────────────────
if ($method === 'DELETE') {
    $id = $body['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        jsonResponse(['error' => 'id は必須です']);
        exit;
    }
    $db->prepare("UPDATE suppliers SET is_active = 0 WHERE id = ?")->execute([(int)$id]);
    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
