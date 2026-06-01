<?php
// curl確認用ファイル（確認後は削除してください）
echo "curl: " . (function_exists('curl_init') ? '✅ 使える' : '❌ 使えない') . "\n";
echo "PHPバージョン: " . phpversion() . "\n";

// Gemini APIに簡単なテストリクエスト
$key = 'AIzaSyAi3EKYakp2wm5FQBbwmpTNrrqQ2XWPZZo'; // ここもAPIキーに書き換えてください

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $key;
$body = json_encode(['contents' => [['role' => 'user', 'parts' => [['text' => 'こんにちは']]]]]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTPステータス: " . $code . "\n";
if ($err) echo "curlエラー: " . $err . "\n";
if ($res) {
    $data = json_decode($res, true);
    echo "API応答: " . ($data['candidates'][0]['content']['parts'][0]['text'] ?? '応答なし') . "\n";
}
