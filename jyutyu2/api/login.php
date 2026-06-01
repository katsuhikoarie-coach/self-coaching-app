<?php
// ============================================================
// ログインAPI
// POST { email, password } → セッション生成
// ============================================================
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(['error' => 'POSTのみ対応']);
    exit;
}

$body     = getRequestBody();
$email    = trim($body['email']    ?? '');
$password = trim($body['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    jsonResponse(['error' => 'メールアドレスとパスワードを入力してください']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM fc_users WHERE email = ? AND active = 1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    jsonResponse(['error' => 'メールアドレスまたはパスワードが違います']);
    exit;
}

session_regenerate_id(true);
$_SESSION['fc_user_id']   = (int)$user['id'];
$_SESSION['fc_name']      = $user['fc_name'];
$_SESSION['contact_name'] = $user['contact_name'] ?? '';
$_SESSION['email']        = $user['email'];
$_SESSION['center_type']  = $user['center_type']  ?? 'FC';
$_SESSION['address']      = $user['address']      ?? '';
$_SESSION['phone']        = $user['phone']        ?? '';
$_SESSION['role']         = ($user['fc_name'] === '朝霧ヤマノ') ? 'admin' : 'user';
$_SESSION['login_at']     = time();

$db->prepare("UPDATE fc_users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

jsonResponse([
    'ok'           => true,
    'id'           => (int)$user['id'],
    'fc_name'      => $user['fc_name'],
    'contact_name' => $user['contact_name'] ?? '',
    'email'        => $user['email'],
    'center_type'  => $user['center_type']  ?? 'FC',
    'role'         => $_SESSION['role'],
]);
