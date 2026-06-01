<?php
// ============================================================
// セッション情報取得API
// GET → 現在のセッション情報をJSON返却。未ログインは401。
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['error' => 'GETのみ対応']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    jsonResponse(['error' => '未ログイン']);
    exit;
}

jsonResponse([
    'ok'           => true,
    'id'           => (int)$_SESSION['fc_user_id'],
    'fc_name'      => $_SESSION['fc_name']      ?? '',
    'contact_name' => $_SESSION['contact_name'] ?? '',
    'email'        => $_SESSION['email']        ?? '',
    'center_type'  => $_SESSION['center_type']  ?? 'FC',
    'role'         => $_SESSION['role']         ?? 'user',
]);
