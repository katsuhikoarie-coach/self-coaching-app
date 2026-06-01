<?php
// ============================================================
// ログアウトAPI
// POST → セッション破棄
// ============================================================
require_once __DIR__ . '/session_check.php';

session_unset();
session_destroy();

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
