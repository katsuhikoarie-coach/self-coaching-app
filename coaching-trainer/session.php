<?php
require_once __DIR__ . '/config.php';
requireAuth();

$theme     = htmlspecialchars($_GET['theme'] ?? 'その他', ENT_QUOTES, 'UTF-8');
$mode      = ($_GET['mode'] ?? 'roleplay') === 'scenario' ? 'scenario' : 'roleplay';
$sessionId = uniqid('s_', true);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>セッション — コーチングトレーナー</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@300;400;600&family=Zen+Kaku+Gothic+New:wght@300;400;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --ink:   #1a1a2e;
    --paper: #f5f0e8;
    --sage:  #7a9e7e;
    --rust:  #c4622d;
    --mist:  #e8e0d0;
    --white: #ffffff;
  }
  body {
    font-family: 'Zen Kaku Gothic New', sans-serif;
    background: var(--paper);
    height: 100dvh;
    display: flex; flex-direction: column;
    color: var(--ink);
    overflow: hidden;
  }

  /* ヘッダー */
  header {
    padding: 14px 22px;
    background: var(--white);
    border-bottom: 1px solid var(--mist);
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
  }
  .header-left { display: flex; align-items: center; gap: 10px; }
  .back-btn {
    padding: 5px 13px; border: 1.5px solid var(--mist);
    border-radius: 8px; font-size: 13px; font-family: inherit;
    background: transparent; cursor: pointer; color: #888;
    text-decoration: none; transition: all .2s;
  }
  .back-btn:hover { border-color: #999; color: var(--ink); }
  .badge {
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; letter-spacing: .04em;
  }
  .badge-theme { background: #e8f5e9; color: var(--sage); }
  .badge-mode  { background: #fef3e2; color: var(--rust); }
  .timer { font-size: 13px; color: #999; font-variant-numeric: tabular-nums; min-width: 46px; text-align: right; }

  /* ステップバー */
  .steps-bar {
    background: var(--white); padding: 10px 22px;
    border-bottom: 1px solid var(--mist);
    flex-shrink: 0; overflow-x: auto; scrollbar-width: none;
  }
  .steps-bar::-webkit-scrollbar { display: none; }
  .steps { display: flex; gap: 6px; min-width: max-content; }
  .step {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px;
    font-size: 11px; transition: all .3s;
    white-space: nowrap; color: #ccc;
  }
  .step.active { background: var(--sage); color: #fff; font-weight: 700; }
  .step.done   { color: var(--sage); }
  .step-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

  /* チャットエリア */
  .chat-area {
    flex: 1; overflow-y: auto; padding: 20px 22px;
    display: flex; flex-direction: column; gap: 14px;
  }
  .bubble-wrap {
    display: flex; gap: 9px; align-items: flex-end;
    animation: fadeIn .3s ease;
  }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
  .bubble-wrap.client { flex-direction: row; }
  .bubble-wrap.coach  { flex-direction: row-reverse; }
  .avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
  }
  .avatar.client-av { background: #e8e0d0; }
  .avatar.coach-av  { background: var(--sage); }
  .bubble {
    max-width: 72%; padding: 13px 17px;
    border-radius: 16px; font-size: 14px; line-height: 1.85;
  }
  .bubble.client {
    background: var(--white);
    border-radius: 16px 16px 16px 4px;
    box-shadow: 0 2px 8px rgba(26,26,46,.06);
  }
  .bubble.coach {
    background: var(--sage); color: #fff;
    border-radius: 16px 16px 4px 16px;
  }
  .bubble-name { font-size: 11px; color: #aaa; margin-bottom: 3px; letter-spacing: .04em; }
  .bubble-wrap.coach .bubble-name { text-align: right; color: rgba(255,255,255,.65); }

  /* ヒントカード */
  .hint-card {
    background: #fffbf0; border: 1px solid #f0d8a0;
    border-radius: 12px; padding: 13px 17px;
    font-size: 13px; color: #8a6820; line-height: 1.75;
    margin: 4px 0; animation: fadeIn .3s ease;
  }
  .hint-label { font-size: 11px; font-weight: 700; letter-spacing: .08em; color: #b8941c; margin-bottom: 5px; }

  /* タイピングインジケーター */
  .typing { display: flex; gap: 4px; padding: 8px 0; }
  .typing span {
    width: 7px; height: 7px; background: #ccc;
    border-radius: 50%; animation: bounce 1.2s infinite;
  }
  .typing span:nth-child(2) { animation-delay: .2s; }
  .typing span:nth-child(3) { animation-delay: .4s; }
  @keyframes bounce { 0%,60%,100%{transform:none} 30%{transform:translateY(-6px)} }

  /* シナリオモード */
  .scenario-box {
    background: var(--white); border-radius: 14px; padding: 24px 22px;
    box-shadow: 0 2px 12px rgba(26,26,46,.06); animation: fadeIn .3s ease;
  }
  .scenario-progress {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 18px;
  }
  .progress-bar-wrap {
    flex: 1; height: 5px; background: var(--mist);
    border-radius: 3px; overflow: hidden;
  }
  .progress-bar { height: 100%; background: var(--sage); border-radius: 3px; transition: width .4s ease; }
  .progress-text { font-size: 12px; color: #999; white-space: nowrap; }
  .scenario-label { font-size: 11px; color: #999; letter-spacing: .08em; margin-bottom: 10px; }
  .scenario-situation { font-size: 12px; color: #888; line-height: 1.75; margin-bottom: 14px; padding: 10px 14px; background: var(--paper); border-radius: 8px; }
  .scenario-client { font-size: 15px; line-height: 1.95; color: var(--ink); margin-bottom: 18px; }
  .scenario-choices { display: flex; flex-direction: column; gap: 9px; }
  .choice-btn {
    padding: 13px 16px; border: 1.5px solid var(--mist);
    border-radius: 10px; background: transparent;
    font-size: 13px; font-family: inherit; text-align: left;
    cursor: pointer; transition: all .2s; line-height: 1.7; color: var(--ink);
  }
  .choice-btn:hover:not(:disabled) { border-color: var(--sage); background: #f8faf8; }
  .choice-btn.correct  { border-color: var(--sage); background: #f0f8f0; color: #2e7d32; }
  .choice-btn.incorrect { border-color: #e57373; background: #fff5f5; color: #c62828; }
  .choice-result {
    display: none; margin-top: 16px;
    padding: 14px 16px; border-radius: 10px;
    font-size: 13px; line-height: 1.85;
  }
  .result-header { font-weight: 700; margin-bottom: 8px; }
  .result-model  { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,.08); color: #555; }
  .next-sc-btn {
    margin-top: 14px; padding: 10px 22px;
    background: var(--sage); color: #fff; border: none;
    border-radius: 8px; font-family: inherit;
    font-size: 14px; font-weight: 700; cursor: pointer;
    transition: background .2s;
  }
  .next-sc-btn:hover { background: #6a8e6e; }

  /* 入力エリア */
  .input-area {
    background: var(--white); border-top: 1px solid var(--mist);
    padding: 14px 22px; flex-shrink: 0;
  }
  .step-hint { font-size: 12px; color: var(--sage); margin-bottom: 8px; letter-spacing: .03em; }
  .input-row { display: flex; gap: 9px; align-items: flex-end; }
  textarea {
    flex: 1; padding: 11px 15px;
    border: 1.5px solid var(--mist); border-radius: 11px;
    font-size: 14px; font-family: inherit; resize: none;
    min-height: 48px; max-height: 130px; outline: none;
    transition: border-color .2s; line-height: 1.65;
    color: var(--ink); background: var(--paper);
  }
  textarea:focus { border-color: var(--sage); }
  .send-btn {
    width: 48px; height: 48px; background: var(--sage);
    border: none; border-radius: 11px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s; flex-shrink: 0;
  }
  .send-btn:hover { background: #6a8e6e; }
  .send-btn:disabled { background: #ccc; cursor: not-allowed; }
  .send-btn svg { fill: #fff; width: 20px; height: 20px; }
  .end-btn {
    width: 100%; padding: 13px; margin-top: 10px;
    background: transparent; border: 1.5px solid var(--rust);
    border-radius: 10px; color: var(--rust);
    font-size: 13px; font-family: inherit; font-weight: 700;
    cursor: pointer; transition: all .2s; letter-spacing: .04em;
  }
  .end-btn:hover { background: var(--rust); color: #fff; }
  .end-btn:disabled { opacity: .5; pointer-events: none; }

  @media (max-width: 600px) {
    .bubble { max-width: 86%; }
    .chat-area { padding: 14px 16px; }
    .input-area { padding: 11px 16px; }
    header { padding: 12px 16px; }
  }
</style>
</head>
<body>

<header>
  <div class="header-left">
    <a href="<?= APP_BASE_PATH ?>/index.php" class="back-btn">← 戻る</a>
    <span class="badge badge-theme"><?= $theme ?></span>
    <span class="badge badge-mode"><?= $mode === 'roleplay' ? '🎭 ロールプレイ' : '📖 シナリオ' ?></span>
  </div>
  <div class="timer" id="timer">0:00</div>
</header>

<div class="steps-bar" id="stepsBarWrap">
  <div class="steps" id="stepsBar">
    <div class="step active" data-step="0"><span class="step-dot"></span>導入・関係構築</div>
    <div class="step"        data-step="1"><span class="step-dot"></span>テーマ設定</div>
    <div class="step"        data-step="2"><span class="step-dot"></span>深掘り・探求</div>
    <div class="step"        data-step="3"><span class="step-dot"></span>ビジョン・価値観</div>
    <div class="step"        data-step="4"><span class="step-dot"></span>行動決定</div>
  </div>
</div>

<div class="chat-area" id="chatArea"></div>

<div class="input-area">
  <div class="step-hint" id="stepHint">まずクライアントに挨拶し、自己紹介してください</div>
  <?php if ($mode === 'roleplay'): ?>
  <div class="input-row">
    <textarea id="msgInput" placeholder="コーチとして返答を入力..." rows="1"
              onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
    <button class="send-btn" id="sendBtn" onclick="sendMessage()">
      <svg viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
    </button>
  </div>
  <?php endif; ?>
  <button class="end-btn" id="endBtn" onclick="endSession()">セッションを終了してフィードバックを受ける</button>
</div>

<script>
const THEME      = <?= json_encode($theme) ?>;
const MODE       = <?= json_encode($mode) ?>;
const SESSION_ID = <?= json_encode($sessionId) ?>;
const BASE_PATH  = <?= json_encode(APP_BASE_PATH) ?>;
const START_TIME = Date.now();

let messages     = [];
let currentStep  = 0;
let timerHandle;

const STEPS = [
  { name: '導入・関係構築', hint: 'まずクライアントに挨拶し、自己紹介してください' },
  { name: 'テーマ設定',     hint: 'クライアントが話したいテーマを引き出してください' },
  { name: '深掘り・探求',   hint: 'シンプルな質問でクライアントの体験を深掘りしてください' },
  { name: 'ビジョン・価値観', hint: 'クライアントが本当に望む姿・価値観を引き出してください' },
  { name: '行動決定',       hint: 'ベビーステップを一緒に決め、報告の約束をしてください' },
];

// ====================================================
//  システムプロンプト
// ====================================================
function getClientSystemPrompt() {
  return `あなたはコーチングセッションに来たクライアント役を演じてください。

【テーマ】${THEME}

【クライアント像】
- 30〜40代の一般的な社会人
- コーチングはほぼ初めて。少し緊張している
- 悩みはあるが、自分でもまだ整理できていない
- 話しやすい雰囲気には素直に応じ、徐々に本音を開示する
- コーチが評価・判断・アドバイスをすると少し壁を作る

【セッションのフェーズ】${STEPS[currentStep]?.name ?? ''}

【厳守ルール】
- 必ずクライアントとして返答する。コーチングのフィードバックや分析は一切しない
- 一度に話す量は2〜4文程度
- 「私はAIです」などとは絶対に言わない
- 日本語で自然に話す。感情表現（躊躇、安堵、気づき、抵抗など）を豊かに`;
}

function getHintSystemPrompt() {
  return `あなたはアドラー心理学に精通したコーチングスーパーバイザーです。
以下の会話を見て、練習中のコーチへの短いアドバイスを1〜2文で日本語でください。
アドラー的視点（勇気づけ・横の関係・主体性の尊重・共感）から具体的に。
返答は「💡 ヒント：」で始め、それ以外のテキストは不要です。`;
}

// ====================================================
//  タイマー
// ====================================================
function startTimer() {
  timerHandle = setInterval(() => {
    const s = Math.floor((Date.now() - START_TIME) / 1000);
    document.getElementById('timer').textContent =
      Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  }, 1000);
}

// ====================================================
//  ステップ更新
// ====================================================
function updateStep(n) {
  currentStep = Math.min(n, STEPS.length - 1);
  document.querySelectorAll('.step').forEach((el, i) => {
    el.classList.remove('active', 'done');
    if (i === currentStep)     el.classList.add('active');
    else if (i < currentStep)  el.classList.add('done');
  });
  document.getElementById('stepHint').textContent = STEPS[currentStep].hint;
}

// ====================================================
//  メッセージ描画
// ====================================================
function addBubble(role, content) {
  const area = document.getElementById('chatArea');
  const wrap = document.createElement('div');
  wrap.className = 'bubble-wrap ' + role;

  const av = document.createElement('div');
  av.className = 'avatar ' + (role === 'client' ? 'client-av' : 'coach-av');
  av.textContent = role === 'client' ? '👤' : '🎤';

  const inner = document.createElement('div');
  const nameEl = document.createElement('div');
  nameEl.className = 'bubble-name';
  nameEl.textContent = role === 'client' ? 'クライアント' : 'あなた（コーチ）';

  const bub = document.createElement('div');
  bub.className = 'bubble ' + role;
  bub.textContent = content;

  inner.appendChild(nameEl);
  inner.appendChild(bub);
  wrap.appendChild(av);
  wrap.appendChild(inner);
  area.appendChild(wrap);
  area.scrollTop = area.scrollHeight;
}

function addHint(text) {
  const area = document.getElementById('chatArea');
  const card = document.createElement('div');
  card.className = 'hint-card';
  const label = document.createElement('div');
  label.className = 'hint-label';
  label.textContent = 'スーパーバイザーからのヒント';
  const body = document.createElement('div');
  body.textContent = text;
  card.appendChild(label);
  card.appendChild(body);
  area.appendChild(card);
  area.scrollTop = area.scrollHeight;
}

function showTyping() {
  const area = document.getElementById('chatArea');
  const wrap = document.createElement('div');
  wrap.className = 'bubble-wrap client';
  wrap.id = 'typing-indicator';
  wrap.innerHTML = '<div class="avatar client-av">👤</div>'
    + '<div class="bubble client"><div class="typing"><span></span><span></span><span></span></div></div>';
  area.appendChild(wrap);
  area.scrollTop = area.scrollHeight;
}
function removeTyping() {
  document.getElementById('typing-indicator')?.remove();
}

// ====================================================
//  Gemini API 呼び出し
// ====================================================
async function callProxy(system, msgs) {
  let res, data;
  try {
    res  = await fetch(BASE_PATH + '/api/proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ system, messages: msgs }),
    });
    data = await res.json();
  } catch (fetchErr) {
    console.error('[session] fetch error:', fetchErr);
    throw fetchErr;
  }
  if (data.error) {
    console.error('[session] proxy error:', data);
    throw new Error(data.error + (data.debug ? ' / debug: ' + data.debug : ''));
  }
  return data.content || '';
}

// ====================================================
//  ロールプレイ: クライアント返答を取得
// ====================================================
async function getClientResponse(coachMsg) {
  showTyping();
  setSendDisabled(true);

  const msgCount = messages.length;
  if      (msgCount >= 14) updateStep(4);
  else if (msgCount >= 10) updateStep(3);
  else if (msgCount >= 6)  updateStep(2);
  else if (msgCount >= 3)  updateStep(1);

  try {
    const reply = await callProxy(getClientSystemPrompt(), messages);
    removeTyping();
    messages.push({ role: 'model', content: reply });
    addBubble('client', reply);

    // 6メッセージごとにヒント表示
    if (messages.length % 6 === 0) {
      const hintMsgs = [{ role: 'user', content:
        `コーチの発言：「${coachMsg}」\nクライアントの返答：「${reply}」\nこのコーチへのひと言アドバイス：` }];
      try {
        const hint = await callProxy(getHintSystemPrompt(), hintMsgs);
        if (hint) addHint(hint);
      } catch (_) {}
    }
  } catch (e) {
    removeTyping();
    addBubble('client', '（通信エラーが発生しました。しばらくしてから再試行してください）');
  }
  setSendDisabled(false);
  document.getElementById('msgInput')?.focus();
}

function setSendDisabled(v) {
  const btn = document.getElementById('sendBtn');
  if (btn) btn.disabled = v;
}

// ====================================================
//  ロールプレイ: メッセージ送信
// ====================================================
async function sendMessage() {
  const input = document.getElementById('msgInput');
  const text  = input?.value.trim();
  if (!text) return;
  input.value = '';
  autoResize(input);
  messages.push({ role: 'user', content: text });
  addBubble('coach', text);
  await getClientResponse(text);
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}
function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 130) + 'px';
}

// ====================================================
//  セッション終了
// ====================================================
async function endSession() {
  if (MODE === 'roleplay' && messages.length < 4) {
    alert('もう少しセッションを続けてからフィードバックを受けてください');
    return;
  }
  if (MODE === 'scenario' && answeredCount < scenarioList.length) {
    alert(`まだ ${scenarioList.length - answeredCount} 問残っています。全問答えてからフィードバックを受けてください`);
    return;
  }
  if (!confirm('セッションを終了してフィードバックを受けますか？')) return;

  clearInterval(timerHandle);
  setSendDisabled(true);
  const endBtn = document.getElementById('endBtn');
  if (endBtn) { endBtn.disabled = true; endBtn.textContent = 'フィードバックを準備中...'; }

  const sessionData = {
    session_id:       SESSION_ID,
    theme:            THEME,
    mode:             MODE,
    messages:         messages,
    duration:         Math.floor((Date.now() - START_TIME) / 1000),
    scenario_score:   MODE === 'scenario' ? calcScenarioScore() : null,
    scenario_correct: MODE === 'scenario' ? correctCount : null,
    scenario_total:   MODE === 'scenario' ? answeredCount : null,
  };
  sessionStorage.setItem('sessionData', JSON.stringify(sessionData));
  location.href = BASE_PATH + '/feedback.php';
}

// ====================================================
//  シナリオモード
// ====================================================
const SCENARIOS = {
  '職場の悩み': [
    {
      situation: '営業チームの定例会議でのシーン。クライアントは3ヶ月連続で上司に提案を否定されている。',
      client: '上司に毎回会議で否定されるんです。先日も提案したら「それは違う」ってバッサリで…。もう何も言いたくなくなってきました。',
      choices: [
        { text: '上司の方針に問題があると思いますね。でもあなたの提案は正しかったと思いますよ。', correct: false,
          reason: '評価・判断をしており、横の関係から離れています。コーチはどちらが正しいかを判断する立場ではありません。' },
        { text: 'そうでしたか…。バッサリ言われたとき、どんな気持ちでしたか？', correct: true,
          reason: '「そうでしたか」と受容し、感情に寄り添うオープンクエスチョンで深掘りしています。正解です。' },
        { text: 'それは辛いですね。もっと強く自己主張する練習をしましょう。', correct: false,
          reason: 'アドバイスが早すぎます。まずクライアントの感情を十分に受け止めることが先決です。' },
        { text: '他にも同じような経験はありますか？', correct: false,
          reason: '情報収集は大切ですが、今の局面ではまず「もう何も言いたくない」という感情への共感が優先です。' },
      ]
    },
    {
      situation: '2年勤めた職場での対話。以前は仕事が好きだったが最近変化を感じている。',
      client: '最近、仕事にやる気が出なくて。仕事自体は嫌いじゃないんですが、なんか空回りしているというか…前はもっと楽しかったと思うんですが。',
      choices: [
        { text: '休暇を取ってリフレッシュすることをお勧めします。', correct: false,
          reason: 'アドバイスが早すぎます。クライアントの気持ちを十分に聞く前に解決策を提示するのは時期尚早です。' },
        { text: '前は楽しかったんですね。今、何かが変わった感じがしているんですか？', correct: true,
          reason: 'クライアントの言葉を反映し（前は楽しかった）、今の体験に焦点を当てた問いかけです。受容と共感が感じられます。' },
        { text: 'なぜやる気がなくなったと思いますか？', correct: false,
          reason: '「なぜ」は原因探しに走りやすく、責められているように感じさせることがあります。まず感情に寄り添いましょう。' },
        { text: '仕事量が多すぎるのではないですか？', correct: false,
          reason: 'コーチが原因を決めつけており、クライアントの主体性を損ないます。' },
      ]
    },
    {
      situation: '後輩指導担当になって2ヶ月。初めてのマネジメント経験。',
      client: '後輩の指導担当になったんですが、うまくいかなくて。言ってもなかなかやってくれないし、コミュニケーションも難しくて…正直しんどいです。',
      choices: [
        { text: 'コミュニケーション研修に参加してみると良いかもしれません。', correct: false,
          reason: 'アドバイスが早すぎます。クライアントが「しんどい」と言っているのに、すぐ解決策を提示するのは共感の欠如です。' },
        { text: 'しんどいですね…。うまくいかないとき、どんな気持ちになりますか？', correct: true,
          reason: '「しんどい」を受容し、そのときの感情を深掘りする問いかけです。クライアントのペースに合わせた関わりができています。' },
        { text: 'その後輩に問題があるんじゃないですか？', correct: false,
          reason: '第三者への判断・評価です。コーチはクライアント自身の体験に焦点を当てるべきで、他者の評価は行いません。' },
        { text: 'どんな指示を出しているんですか？', correct: false,
          reason: '具体的な情報収集に走っており、「しんどい」という感情を受け止めることが先です。' },
      ]
    },
    {
      situation: '10年勤めた会社で、初めてキャリアを見直そうとしている。',
      client: '転職しようか迷っているんです。今の会社に大きな不満があるわけじゃないんですが、このままでいいのかなと思って。でも踏み出す怖さもあって…',
      choices: [
        { text: '転職市場は今好調ですから、いいチャンスかもしれませんよ。', correct: false,
          reason: 'コーチの価値観・情報でクライアントの決断を誘導しています。クライアントの主体性を尊重することが重要です。' },
        { text: '踏み出す怖さがあるんですね。その怖さ、もう少し聞かせてもらえますか？', correct: true,
          reason: 'クライアントが言った「怖さ」に寄り添い、深掘りするオープンクエスチョンです。感情の探索を促しています。' },
        { text: '転職か残留か、メリット・デメリットを一緒に整理しましょう。', correct: false,
          reason: '分析・解決策提示に走っており、感情を十分に受け止める前に論理的整理を促すのは時期尚早です。' },
        { text: '今の会社の具体的な不満は何ですか？', correct: false,
          reason: 'クライアントは「大きな不満があるわけじゃない」と言っています。不満探しはクライアントの主訴と合っていません。' },
      ]
    },
    {
      situation: 'チームプロジェクトでリーダーを任されて3ヶ月。初めての管理職経験。',
      client: 'チームで決めたことを私がひっくり返してしまって、メンバーからひんしゅくを買ってしまったんです。リーダーとしてどうすればいいのか、自信がなくなってきて…',
      choices: [
        { text: 'リーダーシップ研修を受けてみると、ヒントが見つかるかもしれませんよ。', correct: false,
          reason: 'アドバイスが早すぎます。「自信がなくなってきた」という感情を受け止める前に解決策を提示しています。' },
        { text: 'ひんしゅくを買ってしまったとき、どんな気持ちでしたか？', correct: true,
          reason: 'クライアントの体験に寄り添い、そのときの感情を深掘りする問いかけです。評価せず、クライアントのペースに合わせています。' },
        { text: 'なぜチームの決定をひっくり返したんですか？', correct: false,
          reason: '「なぜ」という問いはクライアントを防衛的にさせやすく、責められているように感じさせることがあります。' },
        { text: 'メンバーに素直に謝るのが一番だと思いますよ。', correct: false,
          reason: 'コーチが行動を指示しています。クライアントが自分で答えを見つけるプロセスを支援することが大切です。' },
      ]
    },
  ],
  '人間関係': [
    {
      situation: '数年来の友人に相談したところ、予想外の返答があった翌日。',
      client: '友達に相談したら「あなたが悪い」って言われて…。私のことをわかってくれる人が誰もいない気がします。',
      choices: [
        { text: 'それは辛かったですね。「わかってくれる人がいない」って感じているんですね。', correct: true,
          reason: 'クライアントの言葉をそのまま受け止め、感情を反映しています。受容・共感の基本的な関わりができています。' },
        { text: 'お友達もあなたのことを心配して言ったのかもしれませんよ。', correct: false,
          reason: '友人を弁護することで、クライアントの気持ちが否定される可能性があります。まず「辛い」という気持ちを受け取りましょう。' },
        { text: 'そんなお友達とは距離を置いた方がいいかもしれませんね。', correct: false,
          reason: 'コーチの価値観でアドバイスをしています。人間関係への介入はクライアントが自分で決めることです。' },
        { text: 'そういうとき、どう対処してきましたか？', correct: false,
          reason: '行動や対処法の話に移るのは早すぎます。まずクライアントの孤独感・辛さを十分に受け止めましょう。' },
      ]
    },
    {
      situation: '半年前から新しいチームに異動。仕事はできるが人間関係に悩む。',
      client: '職場でなんか浮いている気がして。みんながランチに行くとき声をかけてもらえないし、会話の輪に入れないことも多くて。',
      choices: [
        { text: '積極的に話しかけてみましょう。勇気を出すことが大切です。', correct: false,
          reason: 'アドバイスが早すぎます。「浮いている気がする」という感情を受け止めることなくアドバイスしています。' },
        { text: '輪に入れないとき、どんな気持ちになりますか？', correct: true,
          reason: 'クライアントの体験に寄り添い、感情を深める問いかけです。安心して話せる場を作っています。' },
        { text: '声をかけてもらえないのは、あなたの何かが影響しているのかもしれません。', correct: false,
          reason: 'クライアントに問題があるような言い方で、評価・判断になっています。' },
        { text: 'みんなはどんな話をしているんですか？', correct: false,
          reason: '情報収集に向かっており、クライアント自身の感情への寄り添いが欠けています。' },
      ]
    },
    {
      situation: '5年間のパートナーシップ。最近すれ違いが続いている。',
      client: 'パートナーと最近うまくいってなくて。話し合いをしようとすると、どちらかが感情的になってしまって、いつも途中で終わっちゃうんです。',
      choices: [
        { text: 'お互いに冷静になる時間を作ってみては？感情が落ち着いてから話しましょう。', correct: false,
          reason: 'アドバイスが早すぎます。「うまくいってない」という気持ちを受け止める前に解決策を提示しています。' },
        { text: '感情的になってしまうとき、あなた自身はどんな気持ちになっていますか？', correct: true,
          reason: 'クライアント自身の感情体験に焦点を当てた問いかけです。「あなた自身は」という言葉で主体性を引き出しています。' },
        { text: 'パートナーが感情的になるのはなぜだと思いますか？', correct: false,
          reason: '第三者（パートナー）の分析に向かっており、クライアント自身の体験から離れています。' },
        { text: '話し合いのやり方を変えてみましょう。メモを取りながら話すといいですよ。', correct: false,
          reason: '具体的なアドバイスをしており、クライアントが自分で答えを見つける前にコーチが答えを出しています。' },
      ]
    },
    {
      situation: '独立して2年。仕事は安定しているが、心の中に引っかかりがある。',
      client: '親のことが頭から離れなくて。ちゃんと連絡はしているんですが、なんか罪悪感があるんです。「もっとしてあげないといけない」って。',
      choices: [
        { text: '十分頑張っていますよ。自分を責めすぎないでください。', correct: false,
          reason: '根拠のない励まし。罪悪感という感情を「いい話」に変えようとしており、まず受け止めることが大切です。' },
        { text: 'その罪悪感、もう少し聞かせてもらえますか？「もっとしてあげないといけない」というのは、どんなふうに感じていますか？', correct: true,
          reason: '罪悪感を受け止め、丁寧に深掘りしています。クライアントが感情を言語化できるよう寄り添っています。' },
        { text: '親御さんも喜んでいると思いますよ。', correct: false,
          reason: '憶測で第三者の気持ちを語っています。根拠のない評価はクライアントの感情を否定することになります。' },
        { text: '親御さんはどんな人ですか？', correct: false,
          reason: '情報収集に走っており、今の「罪悪感」という感情への共感が薄くなります。' },
      ]
    },
    {
      situation: '友人関係が変わってしまったことを感じ始めている30代。',
      client: '仲の良かった友達と、最近なんかぎこちなくて。お互い忙しくなったからかもしれないんですが…昔みたいに戻れるかな、って不安で。',
      choices: [
        { text: '連絡をこまめにするようにしてみてはいかがですか？', correct: false,
          reason: 'アドバイスが早すぎます。「ぎこちない・不安」という感情をまず受け止めることが先決です。' },
        { text: '「昔みたいに戻れるかな」って不安なんですね。その不安、もう少し話してもらえますか？', correct: true,
          reason: 'クライアントの言葉を使いながら感情を受け止め、安心して話せる問いかけをしています。受容と共感の基本です。' },
        { text: '相手もあなたのことを大切に思っていると思いますよ。', correct: false,
          reason: '憶測で第三者の気持ちを語っており、クライアントの不安感を置き去りにしています。' },
        { text: '友達関係はみんな変わっていくものですよ。', correct: false,
          reason: '一般化によってクライアントの個別の体験を軽視しています。感情への共感が欠けています。' },
      ]
    },
  ],
  '自己実現': [
    {
      situation: '副業で音楽活動を始めようとしているが、過去に何度か挫折している。',
      client: 'やりたいことがあるんですが、いつも途中で諦めてしまうんです。今度こそ変わりたいと思って来ました。',
      choices: [
        { text: 'すばらしいですね！じゃあ今日から毎日30分やってみましょう！', correct: false,
          reason: '行動を急ぎすぎています。「変わりたい」という勇気を受け取る前に、コーチが答えを出してしまっています。' },
        { text: 'そのやりたいことって何ですか？', correct: false,
          reason: '内容の把握より先に、今日ここに来るという決断をしたことへの勇気づけが大切です。' },
        { text: '今日こうして来てくださったこと、それ自体がすでに一歩だと思います。どんな気持ちで来ましたか？', correct: true,
          reason: '来たこと自体を勇気づけ（アドラー的）、感情に焦点を当てています。クライアントの主体性を尊重しています。' },
        { text: '今まで何回諦めましたか？', correct: false,
          reason: 'マイナスの過去に焦点を当てており、クライアントの「変わりたい」という意欲をそぎます。' },
      ]
    },
    {
      situation: 'プロジェクトへの挑戦を繰り返し尻込みしてきた経験がある。',
      client: '何かをやろうとするたびに、「どうせ自分には無理だ」という気持ちが出てきて動けなくなるんです。',
      choices: [
        { text: 'そんなことはありません！あなたには可能性がありますよ。', correct: false,
          reason: '根拠のない励まし。クライアントの感情を否定することになり、かえって信頼関係を損なうことがあります。' },
        { text: '「どうせ無理だ」という気持ちが出てくるとき、どんな感じがしますか？', correct: true,
          reason: 'クライアントの言葉を使いながら、その体験を深掘りしています。評価せずに感情の探索を促しています。' },
        { text: '失敗への恐れがあるんですね。それを克服する方法を考えましょう。', correct: false,
          reason: '分析と解決策提示が早すぎます。感情を十分に受け止める前に「克服」という方向に向かっています。' },
        { text: '動けなくなったとき、いつもどうしていますか？', correct: false,
          reason: '対処行動に焦点が移っており、今この瞬間のクライアントの感情への共感が薄くなります。' },
      ]
    },
    {
      situation: '転職か現職継続か、半年悩んでいる。期限が迫ってきた。',
      client: 'ある大事な選択をしなきゃいけないんですが、なかなか決められなくて。どちらを選んでも後悔しそうで怖いんです。',
      choices: [
        { text: 'どちらの選択肢が良いか、メリット・デメリットを一緒に整理しましょう。', correct: false,
          reason: '論理的分析に移るのは早すぎます。「後悔が怖い」という感情を受け止めることなく問題解決に向かっています。' },
        { text: '後悔が怖いんですね。その「後悔が怖い」という感じ、もう少し話してもらえますか？', correct: true,
          reason: '感情（後悔への恐れ）を受容し、クライアントが自分の気持ちをさらに探索できるよう丁寧に深掘りしています。' },
        { text: 'どんな選択肢があるんですか？', correct: false,
          reason: '内容の把握に向かっており、クライアントの感情への共感が欠けています。' },
        { text: '直感を信じて決めてみましょう。後悔しても学びがありますよ。', correct: false,
          reason: 'アドバイスと価値観の押しつけです。コーチはクライアントが自分で決めるプロセスを支援する立場です。' },
      ]
    },
    {
      situation: '現状に息苦しさを感じているが、具体的な一歩が踏み出せない。',
      client: '変わりたいとは思うんですが、変わった後の自分がイメージできなくて。今のままは嫌だけど、変わることも怖いというか…',
      choices: [
        { text: '変化は怖いですが、それが成長ですよ。一歩踏み出してみましょう！', correct: false,
          reason: 'アドバイスとコーチの価値観の押しつけです。「変わることが怖い」という感情をまず受け止めましょう。' },
        { text: '今その言葉を言ってみて、どんな感じがしていますか？', correct: true,
          reason: '今この瞬間のクライアントの体感・感情を確認する問いかけです。プレゼンス（今ここ）への注目ができています。' },
        { text: '今のままが嫌なんですね。何が一番嫌ですか？', correct: false,
          reason: 'ネガティブな側面の深掘りに向かっており、クライアントの気持ちを否定的な方向に固定してしまいます。' },
        { text: '「怖い」という感情は自然なことです。気にしなくて大丈夫ですよ。', correct: false,
          reason: '感情を「気にしなくていい」と評価しており、感情を否定することになります。感情はまず受け止めることが大切です。' },
      ]
    },
    {
      situation: '長年の夢を少しずつ追いかけているが、家族の反応に揺れている。',
      client: 'ずっとやりたいことがあって少しずつ動いているんですが、親から「安定した仕事をしなさい」って言われると、自分が間違っているのかなって思えてきて…',
      choices: [
        { text: 'ご両親の心配はわかりますが、あなたの夢も大切ですよ。バランスを取りましょう。', correct: false,
          reason: 'コーチの価値観でどちらも正しいとまとめており、クライアントの感情の葛藤を十分に受け止めていません。' },
        { text: '「間違っているのかな」と思えてくるとき、どんな気持ちがしていますか？', correct: true,
          reason: 'クライアントの言葉にある揺らぎに寄り添い、感情を深掘りしています。評価せず主体性を尊重した問いかけです。' },
        { text: 'ご両親にあなたの気持ちをきちんと説明してみましたか？', correct: false,
          reason: 'アドバイスに走っており、クライアントの「間違っているのかも」という揺らぎへの共感が抜けています。' },
        { text: 'ご両親の言葉は気にしないで、自分の道を信じてください。', correct: false,
          reason: '根拠のない励ましとアドバイスです。クライアントが自分で答えを見つけるプロセスを先取りしています。' },
      ]
    },
  ],
  'その他': [
    {
      situation: '特に大きな出来事はないが、日常に重さを感じている。',
      client: '最近なんか気力がわかなくて…。別に大きな悩みがあるわけじゃないんですけど、なんとなく毎日がしんどいんです。',
      choices: [
        { text: 'なんとなくしんどい…。そのしんどさ、もう少し聞かせてもらえますか？', correct: true,
          reason: 'クライアントのペースに合わせ、「なんとなく」という言葉を使いながら安全に話を広げています。受容の姿勢が感じられます。' },
        { text: '睡眠や食事はちゃんと取れていますか？', correct: false,
          reason: '原因探しに走っています。まず「しんどい」という感情を受け止めてから、必要であれば情報を集めましょう。' },
        { text: '運動すると気分が変わりますよ。試してみてください。', correct: false,
          reason: 'アドバイスです。感情を受け止める前に解決策を提示しています。' },
        { text: 'それはバーンアウトかもしれませんね。', correct: false,
          reason: '診断・評価はコーチの役割ではありません。クライアントにレッテルを貼ることは慎むべきです。' },
      ]
    },
    {
      situation: '30代半ば。特に問題はないが、将来への漠然とした不安がある。',
      client: '将来のことが不安で。具体的に何かがあるわけじゃないんですが、このままでいいのかなって、漠然と不安なんです。',
      choices: [
        { text: '具体的に何が不安なのかを整理しましょう。', correct: false,
          reason: '整理・分析に走りすぎており、まず「漠然と不安」という感情を受け止めることが先です。' },
        { text: '漠然と不安…。どんなときに一番その不安を感じますか？', correct: true,
          reason: 'クライアントの言葉を受け止めながら、感情がどんな場面・タイミングで現れるかを探っています。体験に根ざした丁寧な関わりです。' },
        { text: 'みんな同じように不安を感じています。大丈夫ですよ。', correct: false,
          reason: '一般化・評価で、クライアントの個別の不安体験を軽視することになります。' },
        { text: '将来の計画を立てると不安が和らぐかもしれませんね。', correct: false,
          reason: 'アドバイスです。不安という感情を受け止める前に解決策を提示しています。' },
      ]
    },
    {
      situation: 'バリバリ働いているが、ふとした瞬間に空虚さを感じることがある。',
      client: '毎日やるべきことはこなしているんですが、充実感がなくて。忙しいはずなのに「何してるんだろう」って思う瞬間があるんです。',
      choices: [
        { text: 'やりがいを感じる仕事や趣味を見つけてみましょう。', correct: false,
          reason: 'アドバイスが早すぎます。充実感がない・「何してるんだろう」という感情を受け止めることが先決です。' },
        { text: '「何してるんだろう」と思う瞬間があるんですね。そのとき、どんな気持ちがしていますか？', correct: true,
          reason: 'クライアントの言葉を反映し、その瞬間の感情を深掘りしています。評価せず、クライアントの体験に寄り添っています。' },
        { text: '本当はどんな毎日を過ごしたいですか？', correct: false,
          reason: 'ビジョンを聞く質問は大切ですが、感情を十分に受け止める前に望む未来を聞くのは早すぎます。' },
        { text: '頑張っているのに充実感がないのは、目標がないからかもしれません。', correct: false,
          reason: 'コーチが原因を決めつけており、クライアントの主体性を損ないます。' },
      ]
    },
    {
      situation: 'やりたいことはある。でもそれを表に出すことが怖い。',
      client: 'やりたいことはあるんですが、「人にどう思われるか」が気になって行動できないんです。情けないとは思うんですが…',
      choices: [
        { text: '人の目を気にしすぎるのは良くないですよ。自分らしく生きましょう。', correct: false,
          reason: 'コーチの価値観を押しつけています。「気にするのは良くない」という評価でクライアントを傷つける可能性があります。' },
        { text: '「情けない」って言いましたが…そう感じているんですね。そのとき、どんな気持ちがしていますか？', correct: true,
          reason: '自己批判の言葉（情けない）に気づき、否定も肯定もせず、そのときの感情を探索する問いかけです。繊細で丁寧な関わりです。' },
        { text: 'どんなことをやりたいんですか？', correct: false,
          reason: 'やりたいこと（内容）への質問で、今の感情への共感が欠けています。' },
        { text: '人の目って、具体的にどんな「目」を想像していますか？', correct: false,
          reason: '悪くない質問ですが、まず「情けない」という自己批判の言葉を受け止めることが優先です。' },
      ]
    },
    {
      situation: '仕事も生活も特に問題はないが、なぜか自信が持てない日々が続いている。',
      client: '仕事も人間関係も特に問題はないんですが、なんか自信が持てなくて。ちゃんとできているはずなのに、どこか「自分はダメだ」って感じるんです。',
      choices: [
        { text: '実績を振り返るワークをしてみましょう。自分の強みが見えてきますよ。', correct: false,
          reason: 'アドバイスが早すぎます。「自分はダメだ」という感覚を受け止める前に解決策を提示しています。' },
        { text: '「自分はダメだ」という感じ…それはどんなときに強く出てきますか？', correct: true,
          reason: 'クライアントの言葉を受け止め、感情体験がどんな場面で現れるかを探る問いかけです。評価せず、体験に根ざした関わりができています。' },
        { text: '自信がないのは、他の人と比べすぎているからかもしれませんね。', correct: false,
          reason: 'コーチが原因を決めつけており、クライアントの主体性を損ないます。' },
        { text: 'なぜそう感じるんだと思いますか？', correct: false,
          reason: '「なぜ」という問いはクライアントを防衛的にさせやすく、責められているように感じさせることがあります。' },
      ]
    },
  ],
};

// シナリオモード状態
let scenarioIndex  = 0;
let scenarioList   = [];
let correctCount   = 0;
let answeredCount  = 0;

function calcScenarioScore() {
  return answeredCount > 0 ? Math.round((correctCount / answeredCount) * 100) : null;
}

function initScenarioMode() {
  document.getElementById('stepsBarWrap').style.display = 'none';
  const allScenarios = SCENARIOS[THEME] || SCENARIOS['その他'];
  const shuffled = [...allScenarios].sort(() => Math.random() - 0.5);
  scenarioList = shuffled.slice(0, 4);
  showScenario(0);
}

function showScenario(idx) {
  const area = document.getElementById('chatArea');
  area.innerHTML = '';

  if (idx >= scenarioList.length) {
    const score = calcScenarioScore();
    const box = document.createElement('div');
    box.className = 'scenario-box';
    box.innerHTML = `
      <div class="scenario-label">COMPLETE</div>
      <p style="font-size:16px;line-height:1.9;margin-bottom:16px">
        全 ${scenarioList.length} 問が完了しました！<br>
        正解数：<strong>${correctCount} / ${answeredCount}</strong>（${score}点）<br><br>
        「セッションを終了してフィードバックを受ける」を押してください。
      </p>`;
    area.appendChild(box);
    return;
  }

  const sc = scenarioList[idx];
  messages.push({ role: 'model', content: sc.client });

  const box = document.createElement('div');
  box.className = 'scenario-box';

  // プログレス
  const pct = Math.round((idx / scenarioList.length) * 100);
  box.innerHTML = `
    <div class="scenario-progress">
      <div class="progress-bar-wrap">
        <div class="progress-bar" style="width:${pct}%"></div>
      </div>
      <span class="progress-text">${idx + 1} / ${scenarioList.length}</span>
    </div>
    <div class="scenario-label">シナリオ ${idx + 1} &nbsp; クライアントの状況</div>
    <div class="scenario-situation">${esc(sc.situation)}</div>
    <div class="scenario-label" style="margin-top:14px">クライアントの発言</div>
    <div class="scenario-client">${esc(sc.client)}</div>
    <p style="font-size:11px;color:#999;margin-bottom:10px;letter-spacing:.04em">コーチとして最適な返答を選んでください</p>
    <div class="scenario-choices">
      ${sc.choices.map((c, i) =>
        `<button class="choice-btn" data-idx="${i}" onclick="selectChoice(this)">${esc(c.text)}</button>`
      ).join('')}
    </div>
    <div class="choice-result" id="choiceResult"></div>`;
  area.appendChild(box);
}

function selectChoice(btn) {
  const idx = parseInt(btn.dataset.idx);
  const sc  = scenarioList[scenarioIndex];
  const ch  = sc.choices[idx];

  document.querySelectorAll('.choice-btn').forEach(b => b.disabled = true);
  answeredCount++;

  const resultEl = document.getElementById('choiceResult');
  resultEl.style.display = 'block';

  if (ch.correct) {
    btn.classList.add('correct');
    correctCount++;
    resultEl.style.background = '#f0f8f0';
    resultEl.style.border     = '1px solid #a5d6a7';
    resultEl.innerHTML = `
      <div class="result-header" style="color:#2e7d32">✓ 正解！</div>
      <div>${esc(ch.reason)}</div>
      <button class="next-sc-btn" onclick="goNextScenario()">次のシナリオへ →</button>`;
  } else {
    btn.classList.add('incorrect');
    const correctIdx = sc.choices.findIndex(c => c.correct);
    document.querySelectorAll('.choice-btn')[correctIdx]?.classList.add('correct');
    resultEl.style.background = '#fff5f5';
    resultEl.style.border     = '1px solid #ef9a9a';
    resultEl.innerHTML = `
      <div class="result-header" style="color:#c62828">✗ 不正解</div>
      <div>${esc(ch.reason)}</div>
      <div class="result-model">模範解答：「${esc(sc.choices[correctIdx].text)}」</div>
      <button class="next-sc-btn" onclick="goNextScenario()">次のシナリオへ →</button>`;
  }
  messages.push({ role: 'user', content: btn.textContent.trim() });
  document.getElementById('chatArea').scrollTop = 9999;
}

function goNextScenario() {
  scenarioIndex++;
  showScenario(scenarioIndex);
}

function esc(str) {
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ====================================================
//  初期化
// ====================================================
window.addEventListener('load', () => {
  startTimer();

  if (MODE === 'roleplay') {
    setTimeout(() => {
      showTyping();
      setTimeout(() => {
        removeTyping();
        const greetings = [
          'はじめまして。今日はよろしくお願いします。',
          'こんにちは。こういうのは初めてなので少し緊張しているんですが…よろしくお願いします。',
          'よろしくお願いします。えっと…どんなふうに始めたらいいんでしょう？',
        ];
        const g = greetings[Math.floor(Math.random() * greetings.length)];
        messages.push({ role: 'model', content: g });
        addBubble('client', g);
      }, 1200);
    }, 600);
  } else {
    initScenarioMode();
  }
});
</script>
</body>
</html>
