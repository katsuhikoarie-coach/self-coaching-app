<?php
require_once __DIR__ . '/config.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>学習履歴 — コーチングトレーナー</title>
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
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .header-left { display: flex; align-items: center; gap: 14px; }
  .header-right { display: flex; align-items: center; gap: 16px; }
  .back-link {
    padding: 5px 13px; border: 1.5px solid var(--mist);
    border-radius: 8px; font-size: 13px; text-decoration: none; color: #888;
    transition: all .2s;
  }
  .back-link:hover { color: var(--ink); border-color: #999; }
  h1 { font-family: 'Noto Serif JP', serif; font-weight: 300; font-size: 19px; }
  .nav-link { font-size: 13px; color: #888; text-decoration: none; transition: color .2s; }
  .nav-link:hover { color: var(--sage); }
  .nav-user { font-size: 13px; color: #555; }
  .logout-link { font-size: 13px; color: #bbb; text-decoration: none; }
  .logout-link:hover { color: var(--rust); }

  .container { max-width: 880px; margin: 0 auto; padding: 36px 22px 72px; }

  /* サマリーカード */
  .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 36px; }
  .summary-card {
    background: #fff; border-radius: 13px; padding: 22px 18px;
    text-align: center; box-shadow: 0 2px 10px rgba(26,26,46,.04);
  }
  .summary-label { font-size: 10px; color: #999; letter-spacing: .09em; margin-bottom: 8px; }
  .summary-value { font-family: 'Noto Serif JP', serif; font-size: 32px; font-weight: 300; color: var(--sage); }
  .summary-unit  { font-size: 12px; color: #aaa; }

  /* 履歴リスト */
  .history-list { display: flex; flex-direction: column; gap: 10px; }
  .history-item {
    background: #fff; border-radius: 13px; padding: 20px 22px;
    box-shadow: 0 2px 9px rgba(26,26,46,.04);
    display: flex; align-items: center; gap: 14px;
    transition: box-shadow .2s;
  }
  .history-item:hover { box-shadow: 0 4px 18px rgba(26,26,46,.08); }
  .score-circle {
    width: 52px; height: 52px; border-radius: 50%;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .score-circle.high { background: #e8f5e9; }
  .score-circle.mid  { background: #fef8e7; }
  .score-circle.low  { background: #fce4ec; }
  .sc-num { font-family: 'Noto Serif JP', serif; font-size: 17px; font-weight: 400; line-height: 1; }
  .sc-num.high { color: var(--sage); }
  .sc-num.mid  { color: var(--gold); }
  .sc-num.low  { color: var(--rust); }
  .sc-label { font-size: 9px; color: #bbb; letter-spacing: .04em; }
  .history-info { flex: 1; min-width: 0; }
  .history-theme { font-weight: 700; font-size: 14px; margin-bottom: 5px; }
  .history-meta  { font-size: 11px; color: #999; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
  .meta-badge {
    padding: 2px 8px; border-radius: 20px; font-size: 10px;
  }
  .meta-badge.roleplay { background: #fef3e2; color: var(--rust); }
  .meta-badge.scenario { background: #e3f2fd; color: #1565c0; }
  .history-summary { font-size: 12px; color: #666; margin-top: 7px; line-height: 1.75; }

  .empty  { text-align: center; padding: 72px 24px; color: #aaa; font-size: 14px; line-height: 2; }
  .loading-msg { text-align: center; padding: 40px; color: #aaa; font-size: 14px; }

  @media (max-width: 600px) {
    header { padding: 14px 18px; }
    .container { padding: 24px 16px 56px; }
    .summary-grid { grid-template-columns: 1fr 1fr; }
    .summary-grid .summary-card:last-child { grid-column: 1 / -1; }
  }
</style>
</head>
<body>
<header>
  <div class="header-left">
    <a href="<?= APP_BASE_PATH ?>/index.php" class="back-link">← ホーム</a>
    <h1>学習履歴</h1>
  </div>
  <div class="header-right">
    <?php if (isAdmin()): ?>
    <a href="<?= APP_BASE_PATH ?>/admin.php" class="nav-link">管理</a>
    <?php endif; ?>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) ?></span>
    <a href="<?= APP_BASE_PATH ?>/login.php?logout=1" class="logout-link"
       onclick="return confirm('ログアウトしますか？')">ログアウト</a>
  </div>
</header>

<div class="container">
  <div id="content">
    <p class="loading-msg">読み込み中...</p>
  </div>
</div>

<script>
const BASE_PATH = <?= json_encode(APP_BASE_PATH) ?>;

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

async function loadHistory() {
  try {
    const res     = await fetch(BASE_PATH + '/api/get_history.php');
    const data    = await res.json();
    const sessions = data.sessions || [];
    const isAdmin  = data.is_admin  || false;
    const content  = document.getElementById('content');

    if (sessions.length === 0) {
      content.innerHTML = `<div class="empty">まだセッション履歴がありません。<br>ホームからセッションを始めてみましょう。</div>`;
      return;
    }

    const withScore  = sessions.filter(s => s.score || s.overall_score);
    const avgScore   = withScore.length
      ? Math.round(withScore.reduce((a, b) => a + (b.overall_score || b.score || 0), 0) / withScore.length)
      : null;
    const totalSec   = sessions.reduce((a, b) => a + (b.duration_seconds || 0), 0);
    const totalMin   = Math.floor(totalSec / 60);

    const summaryHtml = `
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">TOTAL SESSIONS</div>
          <div class="summary-value">${sessions.length}<span class="summary-unit"> 回</span></div>
        </div>
        <div class="summary-card">
          <div class="summary-label">AVG SCORE</div>
          <div class="summary-value">${avgScore ?? '--'}<span class="summary-unit"> 点</span></div>
        </div>
        <div class="summary-card">
          <div class="summary-label">TOTAL TIME</div>
          <div class="summary-value">${totalMin}<span class="summary-unit"> 分</span></div>
        </div>
      </div>`;

    // DOMで組み立て（XSS対策）
    content.innerHTML = summaryHtml + '<div class="history-list" id="historyList"></div>';
    const list = document.getElementById('historyList');

    sessions.forEach(s => {
      const score     = s.overall_score || s.score || 0;
      const scoreClass = score >= 70 ? 'high' : score >= 50 ? 'mid' : 'low';
      const date      = new Date(s.created_at).toLocaleDateString('ja-JP', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
      });
      const duration  = s.duration_seconds ? Math.floor(s.duration_seconds / 60) + '分' : '';
      const modeClass = s.mode === 'roleplay' ? 'roleplay' : 'scenario';
      const modeLabel = s.mode === 'roleplay' ? 'ロールプレイ' : 'シナリオ';

      const item = document.createElement('div');
      item.className = 'history-item';

      // スコアサークル
      const circle = document.createElement('div');
      circle.className = `score-circle ${scoreClass}`;
      circle.innerHTML = `<div class="sc-num ${scoreClass}">${score || '--'}</div><div class="sc-label">score</div>`;

      // 情報
      const info = document.createElement('div');
      info.className = 'history-info';

      const theme = document.createElement('div');
      theme.className = 'history-theme';
      theme.textContent = s.theme;

      const meta = document.createElement('div');
      meta.className = 'history-meta';
      meta.textContent = date;
      if (duration) {
        const dur = document.createElement('span');
        dur.textContent = duration;
        meta.appendChild(dur);
      }
      const badge = document.createElement('span');
      badge.className = `meta-badge ${modeClass}`;
      badge.textContent = modeLabel;
      meta.appendChild(badge);

      info.appendChild(theme);
      info.appendChild(meta);

      // 管理者: ユーザー名バッジ
      if (isAdmin && s.user_name) {
        const userBadge = document.createElement('span');
        userBadge.className = 'meta-badge scenario';
        userBadge.style.cssText = 'background:#f3e5f5;color:#7b1fa2';
        userBadge.textContent = s.user_name;
        meta.appendChild(userBadge);
      }

      // サマリー（テキストノードで安全に）
      const summary = s.summary || '';
      if (summary) {
        const summaryEl = document.createElement('div');
        summaryEl.className = 'history-summary';
        summaryEl.textContent = summary.length > 90 ? summary.slice(0, 90) + '…' : summary;
        info.appendChild(summaryEl);
      }

      item.appendChild(circle);
      item.appendChild(info);
      list.appendChild(item);
    });

  } catch (e) {
    document.getElementById('content').innerHTML =
      '<p style="color:#c62828;text-align:center;padding:40px">履歴の読み込みに失敗しました。</p>';
  }
}

loadHistory();
</script>
</body>
</html>
