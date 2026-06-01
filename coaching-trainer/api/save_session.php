<?php
require_once __DIR__ . '/../config.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => '不正なリクエスト']);
    exit;
}

$sessionId  = $input['session_id'] ?? uniqid('s_', true);
$userId     = (int)$_SESSION['user_id'];
$theme      = $input['theme']      ?? '';
$mode       = in_array($input['mode'] ?? '', ['roleplay', 'scenario']) ? $input['mode'] : 'roleplay';
$clientType = $input['client_type'] ?? '';
$messages   = json_encode($input['messages'] ?? [], JSON_UNESCAPED_UNICODE);
$feedback   = isset($input['feedback']) ? json_encode($input['feedback'], JSON_UNESCAPED_UNICODE) : null;
$score      = isset($input['score'])    ? (int)$input['score'] : null;
$duration   = isset($input['duration']) ? (int)$input['duration'] : null;

try {
    $pdo = getDB();

    // sessions: UNIQUE KEY(session_id) による INSERT ... ON DUPLICATE KEY UPDATE
    $sql = 'INSERT INTO sessions
              (session_id, user_id, theme, mode, client_type, messages, feedback, score, duration_seconds)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              messages         = VALUES(messages),
              feedback         = VALUES(feedback),
              score            = VALUES(score),
              duration_seconds = VALUES(duration_seconds)';
    $pdo->prepare($sql)->execute([
        $sessionId, $userId, $theme, $mode, $clientType, $messages, $feedback, $score, $duration
    ]);

    // feedback_details: UNIQUE KEY(session_id) による UPSERT
    if (!empty($input['feedback'])) {
        $fb = $input['feedback'];
        $sql2 = 'INSERT INTO feedback_details
                   (session_id, overall_score, used_skills, missed_skills, model_response, summary)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   overall_score  = VALUES(overall_score),
                   used_skills    = VALUES(used_skills),
                   missed_skills  = VALUES(missed_skills),
                   model_response = VALUES(model_response),
                   summary        = VALUES(summary)';
        $pdo->prepare($sql2)->execute([
            $sessionId,
            isset($fb['score']) ? (int)$fb['score'] : null,
            json_encode($fb['used_skills']   ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($fb['missed_skills'] ?? [], JSON_UNESCAPED_UNICODE),
            $fb['model_response'] ?? '',
            $fb['summary']        ?? '',
        ]);
    }

    echo json_encode(['success' => true, 'session_id' => $sessionId]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '保存エラー']);
}
