<?php
// ============================================================
// FC認証確認API
// GET /api/auth.php → ログイン中FCユーザー情報を返す
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session_check.php';

$fcUser = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['error' => 'Method Not Allowed']);
    exit;
}

jsonResponse([
    'ok'           => true,
    'id'           => (int)$fcUser['id'],
    'email'        => $fcUser['email'],
    'fc_name'      => $fcUser['fc_name'],
    'contact_name' => $fcUser['contact_name'] ?? '',
    'address'      => $fcUser['address'] ?? '',
    'phone'        => $fcUser['phone'] ?? '',
    'center_type'  => $fcUser['center_type'] ?? 'FC',
]);
