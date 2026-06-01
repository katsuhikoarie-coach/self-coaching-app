<?php
// ========================================
// 朝霧ヤマノ チャットボット API
// ========================================

// ★ここにGemini APIキーを入力してください★
define('GEMINI_API_KEY', 'AIzaSyAi3EKYakp2wm5FQBbwmpTNrrqQ2XWPZZo');

// CORS設定（自分のサイトからのみ受け付ける）
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://asagiriyamano.jp');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$question = isset($input['question']) ? trim($input['question']) : '';

if (empty($question)) {
    echo json_encode(['answer' => 'ご質問を入力してください。']);
    exit;
}

// ========================================
// FAQ：ここに答えられる質問を登録
// ========================================
$faqs = [
    ['keywords' => ['営業時間', '何時', '時間', '開店', '閉店'],
     'answer' => "営業時間は以下の通りです。\n火・水・木・土曜日：10:00〜16:30\n日曜日：10:00〜17:00\n定休日：月曜日・金曜日\n完全予約制ですので、お電話またはLINEでご予約ください。"],

    ['keywords' => ['休み', '定休日', '休業'],
     'answer' => "定休日は毎週月曜日と金曜日です。\nそれ以外の火〜木・土・日曜日に営業しております。"],

    ['keywords' => ['予約', '申し込み', '申込', 'ライン', 'LINE'],
     'answer' => "ご予約はお電話またはLINEで承っております。\n📞 電話：090-1677-3387\n💚 LINE：https://lin.ee/Zldv5ze\n完全予約制ですので、お気軽にご連絡ください。"],

    ['keywords' => ['電話', '番号', '連絡'],
     'answer' => "お電話番号は 090-1677-3387 です。\n営業時間内にお気軽にお電話ください。"],

    ['keywords' => ['場所', '住所', 'アクセス', '行き方', '駐車', '駐車場'],
     'answer' => "【住所】〒673-0866 兵庫県明石市朝霧町3-15-15 黒田ビル2階\n\n【アクセス】\n・朝霧丘バス停から徒歩1分\n・大蔵谷コープから徒歩1分\n・メガネの愛眼朝霧店の向かい\n・1階にパン屋「アン・プルミエ」があるビルの2階\n・近隣にコインパーキングあり"],

    ['keywords' => ['料金', '値段', '価格', 'いくら', 'メニュー', 'コース'],
     'answer' => "主なエステメニューの料金です。\n\n🌿 どろんこフェイシャル（60分）：¥8,800〜\n✨ 琥珀エイジングケア（90分）：¥13,200〜\n💆 筋膜エステ（60分）：¥11,000〜\n🌸 免疫力強化コース（60分）：¥9,900〜\n☀️ 美白・収れんコース（60分）：¥8,800〜\n\n詳細はお気軽にお問い合わせください。\n📞 090-1677-3387"],

    ['keywords' => ['どろんこ', 'どろんこ美容', 'クレイ', '泥'],
     'answer' => "どろんこ美容は、山野愛子先生が世界中を旅して辿り着いた美容法です。\n天然クレイの吸着力で毛穴の奥の汚れを深層から取り除き、血行を促進。\n肌本来のターンオーバーを整えます。\n「取り去る美容法」として、琥珀美容と組み合わせることで相乗効果が生まれます。"],

    ['keywords' => ['琥珀', 'コハク', 'エイジング'],
     'answer' => "琥珀美容は、特許取得成分「琥珀エキス」を使った「与える美容法」です。\nハリ・弾力・潤いを集中的にケアし、エイジングケアに優れた効果を発揮します。\nどろんこ美容で汚れを取り除いた後に琥珀成分を浸透させることで、素肌本来の美しさを引き出します。"],

    ['keywords' => ['教室', 'スクール', '生徒', '習う', '学ぶ', '資格'],
     'answer' => "エステ教室の生徒を随時募集しています！\n\n山野愛子先生の美道哲学から最新の筋膜エステまで学べます。\n卒業と同時に自宅サロンを開業できます。\n未経験の方も大歓迎です。\n\n詳しくはLINEまたはお電話でお問い合わせください。\n💚 LINE：https://lin.ee/Zldv5ze\n📞 090-1677-3387"],

    ['keywords' => ['代理店', 'ビジネス', '販売', '副業'],
     'answer' => "ヤマノ化粧品の販売代理店パートナーを募集しています。\nモンドセレクション最高金賞受賞のヤマノ化粧品を販売するお仕事です。\n\n詳しくはLINEまたはお電話でお問い合わせください。\n💚 LINE：https://lin.ee/Zldv5ze\n📞 090-1677-3387"],

    ['keywords' => ['化粧品', '化粧水', 'スキンケア', 'コスメ', 'ヤマノ', '乳液', 'クリーム', '美容液', 'ローション', 'クレンジング', 'フェイシャル', 'フェイシャルクリーム', 'ミルク', 'ミルクローション', 'ミルクL', 'ミルクl', '洗顔', '石鹸', 'CRY'],
     'answer' => "ヤマノ化粧品はモンドセレクション最高金賞を受賞した高品質なスキンケアです。\nどろんこ・琥珀の成分を活かした独自ラインが揃っています。\n\n商品についてのご質問はお気軽にお問い合わせください。\n📞 090-1677-3387"],

    ['keywords' => ['初めて', 'はじめて', '初回', '体験'],
     'answer' => "はじめての方も大歓迎です！\nお試しメニューもご用意していますので、まずはお気軽にご相談ください。\n\n📞 電話：090-1677-3387\n💚 LINE：https://lin.ee/Zldv5ze\n\nスタッフ一同、心よりお待ちしております。"],

    ['keywords' => ['クレジット', 'カード', '支払い', '支払'],
     'answer' => "クレジットカードがご利用いただけます。\n対応ブランド：VISA・MASTER・JCB"],
];

// FAQで回答できるか確認
foreach ($faqs as $faq) {
    foreach ($faq['keywords'] as $keyword) {
        if (mb_strpos($question, $keyword) !== false) {
            echo json_encode(['answer' => $faq['answer'], 'source' => 'faq']);
            exit;
        }
    }
}

// ========================================
// FAQ で答えられない場合 → Gemini API へ
// ========================================
$system_prompt = "あなたは「山野愛子どろんこ美容朝霧店」のAIアシスタントです。
以下の情報をもとに、お客様の質問に丁寧で親しみやすい日本語でお答えください。

【店舗情報】
店名：山野愛子どろんこ美容朝霧店
住所：兵庫県明石市朝霧町3-15-15 黒田ビル2階
電話：090-1677-3387
LINE：https://lin.ee/Zldv5ze
営業時間：火〜木・土 10:00〜16:30、日 10:00〜17:00
定休日：月曜・金曜
完全予約制、クレジットカード可

【エステメニュー】
どろんこフェイシャル（60分）¥8,800〜
琥珀エイジングケア（90分）¥13,200〜
筋膜エステ（60分）¥11,000〜
免疫力強化コース（60分）¥9,900〜
美白・収れんコース（60分）¥8,800〜
エステ教室（生徒募集中）

【特徴】
1986年創業、38年の歴史。山野愛子先生の美道哲学に基づく「どろんこ美容（取り去る）」と「琥珀美容（与える）」の組み合わせ。40代からのエイジングケアに特化。

わからないことは「お電話（090-1677-3387）またはLINEでお問い合わせください」と案内してください。
回答は200文字以内にまとめてください。";

$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

$request_body = json_encode([
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => $system_prompt . "\n\nお客様の質問：" . $question]]]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 300,
        'temperature' => 0.3,
    ]
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    echo json_encode(['answer' => "申し訳ございません。ただいまシステムに問題が発生しています。\nお電話（090-1677-3387）またはLINEでお問い合わせください。"]);
    exit;
}

$result = json_decode($response, true);
$answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'お答えできませんでした。お電話（090-1677-3387）にてお問い合わせください。';

echo json_encode(['answer' => $answer, 'source' => 'gemini']);
