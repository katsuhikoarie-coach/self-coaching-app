<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM orderer WHERE id = ?");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); jsonResponse(['error' => '見つかりません']); exit; }
        jsonResponse($row);
    } else {
        jsonResponse($db->query("SELECT * FROM orderer ORDER BY id")->fetchAll());
    }
    exit;
}

if ($method === 'POST') {
    $name = trim($body['name'] ?? '');
    if (!$name) { http_response_code(400); jsonResponse(['error' => '名前は必須']); exit; }
    $stmt = $db->prepare("INSERT INTO orderer (name, postal_code, address, tel, fax) VALUES (?,?,?,?,?)");
    $stmt->execute([$name, $body['postal_code'] ?? '', $body['address'] ?? '', $body['tel'] ?? '', $body['fax'] ?? '']);
    jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
    exit;
}

if ($method === 'PUT') {
    $id   = $body['id'] ?? null;
    $name = trim($body['name'] ?? '');
    if (!$id || !$name) { http_response_code(400); jsonResponse(['error' => 'id と name は必須']); exit; }
    $db->prepare("UPDATE orderer SET name=?, postal_code=?, address=?, tel=?, fax=? WHERE id=?")
       ->execute([$name, $body['postal_code'] ?? '', $body['address'] ?? '', $body['tel'] ?? '', $body['fax'] ?? '', (int)$id]);
    jsonResponse(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = $body['id'] ?? null;
    if (!$id) { http_response_code(400); jsonResponse(['error' => 'id は必須']); exit; }
    $db->prepare("DELETE FROM orderer WHERE id = ?")->execute([(int)$id]);
    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
