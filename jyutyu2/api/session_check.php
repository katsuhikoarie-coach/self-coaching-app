<?php
// ============================================================
// セッション認証ヘルパー
// セッション名: asagiri_fcorder  /  有効期限: 24時間
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name('asagiri_fcorder');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    if (!isset($_SESSION['fc_user_id'])) return false;
    if (isset($_SESSION['login_at']) && time() - (int)$_SESSION['login_at'] > 86400) {
        session_unset();
        session_destroy();
        return false;
    }
    return true;
}

function requireLogin(): array {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => '認証が必要です'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return [
        'id'           => (int)$_SESSION['fc_user_id'],
        'fc_name'      => $_SESSION['fc_name']      ?? '',
        'contact_name' => $_SESSION['contact_name'] ?? '',
        'email'        => $_SESSION['email']        ?? '',
        'center_type'  => $_SESSION['center_type']  ?? 'FC',
        'address'      => $_SESSION['address']      ?? '',
        'phone'        => $_SESSION['phone']        ?? '',
        'role'         => $_SESSION['role']         ?? 'user',
    ];
}

function getCenterType(): string {
    return $_SESSION['center_type'] ?? 'FC';
}

function getFcUserId(): int {
    return (int)($_SESSION['fc_user_id'] ?? 0);
}
