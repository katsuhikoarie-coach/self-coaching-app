<?php
require_once __DIR__ . '/config.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>フィードバック — コーチングトレーナー</title>
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
    --gold:  #c4962d;
  }
  body { font-family: 'Zen Kaku Gothic New', sans-serif; background: var(--paper); min-height: 100vh; color: var(--ink); }
  header {
    padding: 18px 36px; background: #fff;
    border-bottom: 1px solid var(--mist);
    display: flex; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 10;
  }
  .back-link {
    padding: 5px 13px; border: 1.5px solid var(--mist);
    border-radius: 8px; font-size: 13px; text-decoration: none; color: #888;
    transition: all .2s;
  }
  .back-link:hover { color: var(--ink); border-color: #999; }
  h1 { font-family: 'Noto Serif JP', serif; font-weight: 300; font-size: 19px; }

  .container { max-width: 780px; margin: 0 auto; padding: 44px 22px 80px; }

  /* ローディング */
  .loading { text-align: center; padding: 80px 24px; }
  .loading-circle {
    width: 54px; height: 54px;
    border: 3px solid var(--mist); border-top-color: var(--sage);
    border-radius: 50%; animation: spin 1s linear infinite;
    margin: 0 auto 22px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .loading p { color: #888; font-size: 14px; line-height: 2; }
  .error-msg { text-align: center; padding: 60px 24px; }
  .error-msg p { color: var(--rust); font-size: 15px; line-height: 2; }
  .error-msg a { color: var(--sage); }

  /* スコアカード */
  .score-card {
    background: #fff; border-radius: 18px; padding: 36px 28px;
    text-align: center; margin-bottom: 20px;
    box-shadow: 0 4px 24px rgba(26,26,46,.06);
  }
  .score-label { font-size: 11px; letter-spacing: .12em; color: #999; margin-bottom: 14px; }
  .score-number { font-family: 'Noto Serif JP', serif; font-size: 76px; font-weight: 300; line-height: 1; margin-bottom: 6px; }
  .score-number.high { color: var(--sage); }
  .score-number.mid  { color: var(--gold); }
  .score-number.low  { color: var(--rust); }
  .score-sub { font-size: 13px; color: #aaa; margin-bottom: 8px; }
  .score-correct { font-size: 15px; font-weight: 700; color: #555; margin-bottom: 16px; }
  .score-bar-wrap { height: 8px; background: var(--mist); border-radius: 4px; overflow: hidden; max-width: 380px; margin: 0 auto; }
  .score-bar { height: 100%; border-radius: 4px; transition: width 1.1s ease; }
  .score-bar.high { background: var(--sage); }
  .score-bar.mid  { background: var(--gold); }
  .score-bar.low  { background: var(--rust); }

  /* セクション */
  .fb-section {
    background: #fff; border-radius: 14px; padding: 24px 22px;
    margin-bottom: 14px; box-shadow: 0 2px 10px rgba(26,26,46,.04);
  }
  .fb-section-title {
    font-size: 11px; letter-spacing: .1em; color: #999;
    margin-bottom: 14px; padding-bottom: 10px;
    border-bottom: 1px solid var(--mist);
  }
  .fb-body { font-size: 14px; line-height: 1.95; color: #444; }
  .skill-list { list-style: none; display: flex; flex-direction: column; gap: 9px; }
  .skill-item { display: flex; align-items: flex-start; gap: 9px; font-size: 13px; line-height: 1.75; color: #444; }
  .skill-dot {
    width: 19px; height: 19px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; flex-shrink: 0; margin-top: 2px;
  }
  .skill-dot.good    { background: #e8f5e9; color: var(--sage); }
  .skill-dot.improve { background: #fef3e2; color: var(--gold); }
  .model-box {
    background: #f8faf8; border-left: 3px solid var(--sage);
    border-radius: 0 10px 10px 0; padding: 14px 18px;
    font-size: 13px; line-height: 1.9; color: #444;
  }
  .next-box {
    background: #fffbf0; border: 1px solid #f0d8a0;
    border-radius: 10px; padding: 14px 18px;
    font-size: 13px; line-height: 1.9; color: #8a6820;
  }

  /* アクション */
  .actions { display: flex; gap: 12px; margin-top: 28px; }
  .btn {
    flex: 1; padding: 15px; border-radius: 11px;
    font-size: 14px; font-family: inherit; font-weight: 700;
    cursor: pointer; transition: all .2s; letter-spacing: .04em;
    text-align: center; text-decoration: none; display: block;
  }
  .btn-primary { background: var(--sage); color: #fff; border: none; }
  .btn-primary:hover { background: #6a8e6e; }
  .btn-secondary { background: transparent; color: var(--ink); border: 1.5px solid var(--mist); }
  .btn-secondary:hover { border-color: #999; }

  @media (max-width: 600px) {
    header { padding: 14px 18px; }
    .container { padding: 28px 16px 56px; }
    .score-card { padding: 28px 18px; }
    .score-number { font-size: 60px; }
    .actions { flex-direction: column; }
  }
</style>
</head>
<body>
<header>
  <a href="<?= APP_BASE_PATH ?>/index.php" class="back-link">← ホーム</a>
  <h1>セッションフィードバック</h1>
</header>

<div class="container">
  <div class="loading" id="loadingArea">
    <div class="loading-circle"></div>
    <p>セッションを分析しています...<br>少しお待ちください（10〜20秒程度）</p>
  </div>

  <div id="feedbackArea" style="display:none">
    <div class="score-card">
      <div class="score-label">COACHING SCORE</div>
      <div class="score-number" id="scoreNum">--</div>
      <div class="score-sub">/ 100点</div>
      <div class="score-correct" id="scoreCorrect" style="display:none"></div>
      <div class="score-bar-wrap"><div class="score-bar" id="scoreBar" style="width:0%"></div></div>
    </div>
    <div class="fb-section">
      <div class="fb-section-title">OVERALL ASSESSMENT &nbsp; 全体評価</div>
      <div class="fb-body" id="overallText"></div>
    </div>
    <div class="fb-section">
      <div class="fb-section-title">STRENGTHS &nbsp; 使えていたスキル</div>
      <ul class="skill-list" id="usedSkills"></ul>
    </div>
    <div class="fb-section">
      <div class="fb-section-title">GROWTH AREAS &nbsp; さらに伸ばせるポイント</div>
      <ul class="skill-list" id="missedSkills"></ul>
    </div>
    <div class="fb-section">
      <div class="fb-section-title">MODEL RESPONSE &nbsp; 模範的な関わり方</div>
      <div class="model-box" id="modelResponse"></div>
    </div>
    <div class="fb-section">
      <div class="fb-section-title">SESSION SUMMARY &nbsp; 振り返りサマリー</div>
      <div class="fb-body" id="summaryText"></div>
    </div>
    <div class="fb-section">
      <div class="fb-section-title">NEXT FOCUS &nbsp; 次のセッションで意識すること</div>
      <div class="next-box" id="nextFocus"></div>
    </div>
    <div class="actions">
      <a href="<?= APP_BASE_PATH ?>/index.php"   class="btn btn-primary">新しいセッションを始める</a>
      <a href="<?= APP_BASE_PATH ?>/history.php" class="btn btn-secondary">学習履歴を見る</a>
    </div>
  </div>

  <div class="error-msg" id="errorArea" style="display:none">
    <p id="errorText">フィードバックの生成に失敗しました。</p>
    <p><a href="<?= APP_BASE_PATH ?>/index.php">ホームに戻る</a></p>
  </div>
</div>

<script>
const BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;

const FEEDBACK_SYSTEM = `あなたはアドラー心理学に精通した、経験豊富なメンタルコーチングスーパーバイザーです。
以下のコーチングセッションログを分析し、必ず有効なJSONのみを返してください。

【評価基準】
- 関係構築（挨拶・自己紹介・守秘義務の説明・安心感の醸成）
- 傾聴の質（受容・共感・自己一致 ／ カール・ロジャーズの3条件）
- 質問の質（シンプル・深掘り・オープンクエスチョン）
- 主体性の尊重（クライアントに答えを出させているか）
- 勇気づけ（アドラー的な関わり方）
- 横の関係（評価・判断をしていないか）
- マッチング（クライアントのペースに合わせているか）
- 行動決定（ベビーステップ・報告の約束）

【返すJSONの形式（厳守）】
{
  "score": 整数(0-100),
  "overall": "全体的な評価（2-3文）",
  "used_skills": ["スキル1", "スキル2", "スキル3"],
  "missed_skills": ["改善点1", "改善点2"],
  "model_response": "このセッションでの模範的な関わり方（具体的な場面を挙げて）",
  "summary": "セッション全体の振り返り（3-5文）",
  "next_focus": "次のセッションで意識するとよいこと（1-2文）"
}

JSONのみ返してください。マークダウンコードブロックも含めず、他の文章は一切不要です。`;

async function generateFeedback(sessionData) {
  const log = (sessionData.messages || []).map(m => {
    const role = m.role === 'user' ? 'コーチ' : 'クライアント';
    return `${role}：${m.content}`;
  }).join('\n');

  const modeLabel = sessionData.mode === 'scenario' ? 'シナリオ学習モード' : 'ロールプレイモード';
  const isScenario  = sessionData.mode === 'scenario' && sessionData.scenario_total > 0;
  const isAllCorrect = isScenario && sessionData.scenario_correct === sessionData.scenario_total;
  const scoreNote = isScenario
    ? `\nシナリオ正答率：${sessionData.scenario_correct}/${sessionData.scenario_total}問正解（${sessionData.scenario_score}点）`
      + (isAllCorrect ? '\n※全問正解です。フィードバックに勇気づけのメッセージを含めてください。' : '')
    : '';

  const prompt = `テーマ：${sessionData.theme}\nモード：${modeLabel}${scoreNote}\n\n【セッションログ】\n${log}\n\n上記のセッションを評価してください。`;

  let res, data;
  try {
    res = await fetch(BASE_PATH + '/api/proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        system:        FEEDBACK_SYSTEM,
        messages:      [{ role: 'user', content: prompt }],
        feedback_mode: true,
      }),
    });
    data = await res.json();
  } catch (fetchErr) {
    console.error('[feedback] fetch error:', fetchErr);
    throw fetchErr;
  }

  if (!res.ok || data.error) {
    console.error('[feedback] proxy error:', data);
    throw new Error(data.error || 'API error ' + res.status);
  }

  // JSON 抽出（responseMimeType=application/json でもコードブロックが混入する場合に対応）
  let raw = data.content || '{}';
  console.log('[feedback] raw response:', raw.slice(0, 200));
  const jsonMatch = raw.match(/\{[\s\S]*\}/);
  if (jsonMatch) raw = jsonMatch[0];
  try {
    return JSON.parse(raw);
  } catch (parseErr) {
    console.error('[feedback] JSON parse error:', parseErr, 'raw:', raw);
    throw parseErr;
  }
}

function showFeedback(fb, sessionData) {
  document.getElementById('loadingArea').style.display  = 'none';
  document.getElementById('feedbackArea').style.display = 'block';

  const score = Math.max(0, Math.min(100, parseInt(fb.score) || 0));
  const cls   = score >= 70 ? 'high' : score >= 50 ? 'mid' : 'low';

  const numEl = document.getElementById('scoreNum');
  numEl.textContent = score;
  numEl.className   = 'score-number ' + cls;

  const bar = document.getElementById('scoreBar');
  bar.className = 'score-bar ' + cls;
  setTimeout(() => { bar.style.width = score + '%'; }, 80);

  // シナリオモード: 正解数を表示
  if (sessionData && sessionData.mode === 'scenario' && sessionData.scenario_total > 0) {
    const el = document.getElementById('scoreCorrect');
    el.textContent = `${sessionData.scenario_correct} / ${sessionData.scenario_total} 問正解`;
    el.style.display = 'block';
  }

  document.getElementById('overallText').textContent   = fb.overall        || '';
  document.getElementById('summaryText').textContent   = fb.summary        || '';
  document.getElementById('modelResponse').textContent = fb.model_response || '';
  document.getElementById('nextFocus').textContent     = fb.next_focus     || '';

  const usedEl = document.getElementById('usedSkills');
  (fb.used_skills || []).forEach(s => {
    const li = document.createElement('li');
    li.className = 'skill-item';
    const dot = document.createElement('span');
    dot.className = 'skill-dot good';
    dot.textContent = '✓';
    li.appendChild(dot);
    li.appendChild(document.createTextNode(s));
    usedEl.appendChild(li);
  });

  const missedEl = document.getElementById('missedSkills');
  (fb.missed_skills || []).forEach(s => {
    const li = document.createElement('li');
    li.className = 'skill-item';
    const dot = document.createElement('span');
    dot.className = 'skill-dot improve';
    dot.textContent = '△';
    li.appendChild(dot);
    li.appendChild(document.createTextNode(s));
    missedEl.appendChild(li);
  });
}

function showError(msg) {
  document.getElementById('loadingArea').style.display = 'none';
  document.getElementById('errorArea').style.display   = 'block';
  document.getElementById('errorText').textContent     = msg;
}

async function init() {
  const raw = sessionStorage.getItem('sessionData');
  if (!raw) { showError('セッションデータが見つかりませんでした。'); return; }

  let sessionData;
  try { sessionData = JSON.parse(raw); }
  catch (_) { showError('セッションデータの読み込みに失敗しました。'); return; }
  sessionStorage.removeItem('sessionData');

  try {
    const fb = await generateFeedback(sessionData);

    // シナリオモードはAI推定スコアを正答率で上書き
    if (sessionData.mode === 'scenario' && sessionData.scenario_total > 0) {
      fb.score = Math.round((sessionData.scenario_correct / sessionData.scenario_total) * 100);
    }

    showFeedback(fb, sessionData);

    // DBに保存（エラーでもフィードバック表示は継続）
    fetch(BASE_PATH + '/api/save_session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        session_id: sessionData.session_id,
        theme:      sessionData.theme,
        mode:       sessionData.mode,
        messages:   sessionData.messages,
        duration:   sessionData.duration,
        feedback:   fb,
        score:      fb.score,
      }),
    }).catch(() => {});

  } catch (e) {
    console.error('[feedback] generateFeedback failed:', e);
    showError('フィードバックの生成に失敗しました。もう一度お試しください。\n(' + e.message + ')');
  }
}

window.addEventListener('load', init);
</script>
</body>
</html>
