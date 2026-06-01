<?php
// ============================================================
// 管理者用 ユーザー管理API
// GET                     → fc_users 全件
// POST action=create      → 新規ユーザー追加
// POST action=toggle      → 有効/無効切り替え
// POST action=update      → 情報更新
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: ユーザー一覧 ───────────────────────────────────────
if ($method === 'GET') {
    try {
        $users = $db->query("
            SELECT id, email, fc_name, contact_name, address, phone,
                   active, center_code, center_type, grade, created_at
            FROM fc_users
            ORDER BY fc_name, contact_name
        ")->fetchAll();
        jsonResponse($users);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ── POST: 各種操作 ──────────────────────────────────────────
if ($method === 'POST') {
    $body   = getRequestBody();
    $action = $body['action'] ?? '';

    // 新規ユーザー追加
    if ($action === 'create') {
        $email        = trim($body['email']        ?? '');
        $fc_name      = trim($body['fc_name']      ?? '');
        $contact_name = trim($body['contact_name'] ?? '');
        $active       = isset($body['active']) ? (int)$body['active'] : 1;

        if (!$email || !$fc_name) {
            http_response_code(400);
            jsonResponse(['error' => 'メールアドレスとFC名は必須です']);
            exit;
        }
        // メール重複チェック
        $chk = $db->prepare("SELECT id FROM fc_users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            http_response_code(409);
            jsonResponse(['error' => 'このメールアドレスは既に登録されています']);
            exit;
        }
        try {
            $stmt = $db->prepare("
                INSERT INTO fc_users (email, fc_name, contact_name, active)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$email, $fc_name, $contact_name, $active]);
            jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'ユーザー追加に失敗しました']);
        }
        exit;
    }

    // パスワード設定
    if ($action === 'set_password') {
        $id       = (int)($body['id']       ?? 0);
        $password = trim($body['password']  ?? '');
        if (!$id || strlen($password) < 6) {
            http_response_code(400);
            jsonResponse(['error' => 'IDとパスワード（6文字以上）は必須です']);
            exit;
        }
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE fc_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'パスワード更新に失敗しました']);
        }
        exit;
    }

    // 有効/無効切り替え
    if ($action === 'toggle') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            jsonResponse(['error' => 'IDが必要です']);
            exit;
        }
        try {
            $stmt = $db->prepare("UPDATE fc_users SET active = 1 - active WHERE id = ?");
            $stmt->execute([$id]);
            $row = $db->prepare("SELECT id, active FROM fc_users WHERE id = ?");
            $row->execute([$id]);
            $updated = $row->fetch();
            jsonResponse(['ok' => true, 'id' => $id, 'active' => (int)$updated['active']]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => '更新に失敗しました']);
        }
        exit;
    }

    http_response_code(400);
    jsonResponse(['error' => '不明なアクションです']);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
