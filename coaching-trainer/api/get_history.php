<?php
require_once __DIR__ . '/../config.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo    = getDB();
    $userId = (int)$_SESSION['user_id'];
    $admin  = isAdmin();

    // 管理者は全ユーザーの履歴＋ユーザー名を取得
    // 一般ユーザーは自分の履歴のみ
    if ($admin) {
        $stmt = $pdo->prepare(
            'SELECT s.session_id, s.user_id, u.name AS user_name,
                    s.theme, s.mode, s.score, s.duration_seconds, s.created_at,
                    f.overall_score, f.used_skills, f.missed_skills, f.summary
             FROM sessions s
             LEFT JOIN users u            ON u.id          = s.user_id
             LEFT JOIN feedback_details f ON f.session_id  = s.session_id
             ORDER BY s.created_at DESC
             LIMIT 200'
        );
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare(
            'SELECT s.session_id, s.user_id, NULL AS user_name,
                    s.theme, s.mode, s.score, s.duration_seconds, s.created_at,
                    f.overall_score, f.used_skills, f.missed_skills, f.summary
             FROM sessions s
             LEFT JOIN feedback_details f ON f.session_id = s.session_id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC
             LIMIT 100'
        );
        $stmt->execute([$userId]);
    }

    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['used_skills']   = json_decode($row['used_skills']   ?? '[]', true) ?: [];
        $row['missed_skills'] = json_decode($row['missed_skills'] ?? '[]', true) ?: [];
    }

    echo json_encode(['sessions' => $rows, 'is_admin' => $admin]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '取得エラー']);
}
