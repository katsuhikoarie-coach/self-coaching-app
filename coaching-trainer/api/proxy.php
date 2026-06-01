<?php
// PHPエラーをJSONで返す（500の空レスポンス対策）
set_error_handler(function($errno, $errstr) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['error' => 'PHPエラー', 'debug' => $errno . ': ' . $errstr]);
    exit;
});

require_once dirname(__DIR__) . '/config.php';
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

$messages      = $input['messages']      ?? [];
$systemPrompt  = $input['system']        ?? '';
$feedbackMode  = !empty($input['feedback_mode']);

// Gemini API フォーマットに変換
// system_instruction は v1 で対応が限定的なため、最初の user/model ターンとして埋め込む
$geminiContents = [];

if (!empty($systemPrompt)) {
    $geminiContents[] = [
        'role'  => 'user',
        'parts' => [['text' => $systemPrompt]],
    ];
    $geminiContents[] = [
        'role'  => 'model',
        'parts' => [['text' => 'わかりました。指示に従います。']],
    ];
}

foreach ($messages as $msg) {
    $role = ($msg['role'] === 'model') ? 'model' : 'user';
    $geminiContents[] = [
        'role'  => $role,
        'parts' => [['text' => (string)($msg['content'] ?? '')]],
    ];
}

// generationConfig: フィードバック時は JSON 出力 + トークン数増
$generationConfig = [
    'temperature'     => 0.85,
    'maxOutputTokens' => $feedbackMode ? 2048 : 1024,
    'thinkingConfig'  => ['thinkingBudget' => 0],
];
if ($feedbackMode) {
    $generationConfig['responseMimeType'] = 'application/json';
}

$requestBody = [
    'contents'         => $geminiContents,
    'generationConfig' => $generationConfig,
];

$url      = sprintf(
    'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
    GEMINI_MODEL,
    GEMINI_API_KEY
);
$bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE);

// --------------------------------------------------------------------------
// HTTP リクエスト実行（curl SSL有り → SSL無し → file_get_contents）
// --------------------------------------------------------------------------
$response = false;
$method   = '';
$debugErr = '';
$httpCode = 0;
$curlErr  = '';

// --- 方式1: curl（SSL検証あり）------------------------------------------
if (function_exists('curl_init')) {
    $response = proxyCurl($url, $bodyJson, true, $httpCode, $curlErr);
    if ($response !== false && $httpCode === 200) {
        $method = 'curl';
    } else {
        $debugErr = 'curl(ssl=on) HTTP=' . $httpCode . ' err=' . $curlErr
                  . ' body=' . (string)$response;
        $response = false;
    }
}

// --- 方式2: curl（SSL検証なし フォールバック）-----------------------------
if ($response === false && function_exists('curl_init')) {
    $response = proxyCurl($url, $bodyJson, false, $httpCode, $curlErr);
    if ($response !== false && $httpCode === 200) {
        $method = 'curl(no-ssl)';
    } else {
        $debugErr .= ' / curl(ssl=off) HTTP=' . $httpCode . ' err=' . $curlErr
                   . ' body=' . (string)$response;
        $response = false;
    }
}

// --- 方式3: file_get_contents --------------------------------------------
if ($response === false) {
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => $bodyJson,
            'timeout'       => 60,
            'ignore_errors' => true,
        ],
        'ssl'  => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);
    $fgcResult = @file_get_contents($url, false, $context);
    $fgcErr    = error_get_last();
    $fgcCode   = 0;
    // $http_response_header はグローバルスコープに自動生成される
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $hLine) {
            if (preg_match('#HTTP/\S+\s+(\d+)#', $hLine, $m)) {
                $fgcCode = (int)$m[1];
                break;
            }
        }
    }
    if ($fgcResult !== false && $fgcCode === 200) {
        $response = $fgcResult;
        $method   = 'file_get_contents';
    } else {
        $debugErr .= ' / fgc HTTP=' . $fgcCode
                   . ' err=' . ($fgcErr['message'] ?? 'unknown')
                   . ' body=' . (string)$fgcResult;
    }
}

// --- 全方式失敗 -----------------------------------------------------------
if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Gemini APIへの通信に失敗しました',
        'debug' => $debugErr,
    ]);
    exit;
}

// --------------------------------------------------------------------------
// レスポンス解析
// --------------------------------------------------------------------------
$data = json_decode($response, true);

if (isset($data['error'])) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Gemini API エラー: ' . ($data['error']['message'] ?? 'unknown'),
        'debug' => 'method=' . $method . ' gemini_error=' . json_encode($data['error']),
    ]);
    exit;
}

$text  = '';
$parts = $data['candidates'][0]['content']['parts'] ?? [];
foreach ($parts as $part) {
    if (isset($part['text'])) {
        $text .= $part['text'];
    }
}

if ($text === '') {
    $finishReason = $data['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    http_response_code(502);
    echo json_encode([
        'error' => 'Gemini APIからの応答が空です (finishReason: ' . $finishReason . ')',
        'debug' => 'method=' . $method . ' raw=' . substr($response, 0, 400),
    ]);
    exit;
}

echo json_encode(['content' => $text]);

// --------------------------------------------------------------------------
// curl ヘルパー関数（PHP 7.4 対応: union return type を使わない）
// --------------------------------------------------------------------------
function proxyCurl($url, $body, $sslVerify, &$httpCode, &$err) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Expect:'],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => $sslVerify,
        CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    if ($err || $response === false) {
        return false;
    }
    return $response;
}
