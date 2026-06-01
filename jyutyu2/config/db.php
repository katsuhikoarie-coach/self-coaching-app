<?php
// ============================================================
// DB接続設定 ── 朝霧ヤマノ FC受注システム
// ファイル設置先: public_html/fc-order.asagiriyamano.jp/config/db.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'asagiri_fcorder');   // CORESERVERで作成したDB名
define('DB_USER', 'asagiri_fcorder');   // DBユーザー名（DB名と同一）
define('DB_PASS', 'JvsHzdyKDNk8');

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
// ユーティリティ
// ────────────────────────────────────────
function jsonResponse($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function getRequestBody() {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

// 受注番号生成: YYYY-MM-DD-NNNN
function generateOrderId($db, $date) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE order_date = ?");
    $stmt->execute([$date]);
    $count = (int)$stmt->fetchColumn();
    return $date . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// ── CORS・セキュリティヘッダー ────────────────────────────────
header('Access-Control-Allow-Origin: https://fc-order.asagiriyamano.jp');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
