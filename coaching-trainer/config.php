<?php
// =====================================================
// コーチングトレーナー 設定ファイル
// デプロイ先に合わせて APP_BASE_PATH を変更してください
// =====================================================

// アプリのベースパス（末尾スラッシュなし。ルートなら '' に）
define('APP_BASE_PATH', '/coaching-trainer');

// Gemini API
define('GEMINI_API_KEY', 'AIzaSyAi3EKYakp2wm5FQBbwmpTNrrqQ2XWPZZo');
define('GEMINI_MODEL',   'gemini-2.5-flash');

// MySQL / MariaDB
define('DB_HOST',    'localhost');
define('DB_NAME',    'asagiri_coach');
define('DB_USER',    'asagiri_coach');
define('DB_PASS',    'teamflow2011');
define('DB_CHARSET', 'utf8mb4');

// 管理者メールアドレス
define('ADMIN_EMAIL', 'katsuhiko.arie@gmail.com');

// PDO 接続（シングルトン）
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'DB接続エラー']);
            exit;
        }
    }
    return $pdo;
}

// 認証チェック（未ログインならログインページへ）
function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_BASE_PATH . '/login.php');
        exit;
    }
}

// 管理者チェック（管理者以外はホームへリダイレクト）
function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: ' . APP_BASE_PATH . '/index.php');
        exit;
    }
}

// 現在のユーザーが管理者か
function isAdmin(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return ($_SESSION['user_role'] ?? '') === 'admin';
}
