<?php
// ============================================================
// DB接続設定
// ============================================================
// ※ このファイルは public_html/sales/config/db.php に設置します
// ※ DBパスワードは在りさんから受け取った値を設定してください

define('DB_HOST', 'localhost');
define('DB_NAME', 'asagiri_will');       // CORESERVERで作成したDB名
define('DB_USER', 'asagiri_will');       // DB/ユーザー名（DB名と同一）
define('DB_PASS', 'YOt0rUBDmYtX8M');    // DBパスワード

// Firebase認証用（Google IDトークン検証に使用）
define('FIREBASE_API_KEY', 'AIzaSyDZtuZa4RvofqIB375zbmPb9X-UwOUTMC0');

// アクセス許可メールアドレス
define('ALLOWED_EMAILS', [
    'katsuhiko.arie@gmail.com',
    'ya.asagiriten@gmail.com',
]);

// ────────────────────────────────────────
// DB接続（シングルトン）
// ────────────────────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'DB接続エラー'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $pdo;
}

// ────────────────────────────────────────
// Firebase IDトークン検証
// ────────────────────────────────────────
function verifyFirebaseToken($idToken) {
    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . FIREBASE_API_KEY;
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode(['idToken' => $idToken]),
            'timeout' => 10,
        ],
    ]);
    $result = @file_get_contents($url, false, $ctx);
    if (!$result) return null;
    $data = json_decode($result, true);
    if (empty($data['users'][0])) return null;
    $user = $data['users'][0];
    if (!in_array($user['email'], ALLOWED_EMAILS, true)) return null;
    return $user;
}

// ────────────────────────────────────────
// 認証必須（各APIの先頭で呼ぶ）
// ────────────────────────────────────────
function requireAuth() {
    // Authorization: Bearer <idToken> ヘッダーを取得
    $token = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'authorization') {
                $token = str_replace('Bearer ', '', $v);
                break;
            }
        }
    }
    // Apache環境でgetallheaders()が使えない場合のフォールバック
    if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    if (!$token && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => '認証が必要です'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $user = verifyFirebaseToken($token);
    if (!$user) {
        http_response_code(403);
        echo json_encode(['error' => 'アクセス権限がありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $user;
}

// ────────────────────────────────────────
// ユーティリティ
// ────────────────────────────────────────
function jsonResponse($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function getRequestBody() {
    $raw = file_get_contents('php://input');
    return $raw ? json_decode($raw, true) : [];
}

// CORS設定（同一ドメインのため最小限）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
