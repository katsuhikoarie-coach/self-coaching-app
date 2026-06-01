<?php
require_once __DIR__ . '/config.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>コーチングトレーナー</title>
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
    padding: 22px 40px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--mist);
    background: rgba(245,240,232,.9);
    position: sticky; top: 0; z-index: 10;
    backdrop-filter: blur(6px);
  }
  .logo { font-family: 'Noto Serif JP', serif; font-weight: 300; font-size: 17px; letter-spacing: .06em; color: var(--ink); text-decoration: none; }
  .nav-links { display: flex; gap: 24px; align-items: center; }
  .nav-links a { font-size: 13px; color: #888; text-decoration: none; letter-spacing: .04em; transition: color .2s; }
  .nav-links a:hover { color: var(--sage); }
  .nav-links a.logout { color: #bbb; }
  .nav-links a.logout:hover { color: var(--rust); }
  .nav-links a.admin-link { color: var(--sage); }
  .nav-user { font-size: 13px; color: #555; }

  .hero { padding: 60px 40px 36px; max-width: 900px; margin: 0 auto; }
  .hero-label { font-size: 11px; letter-spacing: .15em; color: var(--sage); text-transform: uppercase; margin-bottom: 14px; }
  .hero h2 { font-family: 'Noto Serif JP', serif; font-weight: 300; font-size: 34px; line-height: 1.55; margin-bottom: 14px; }
  .hero p { font-size: 14px; color: #666; line-height: 1.95; max-width: 540px; }

  .section { max-width: 900px; margin: 0 auto; padding: 0 40px 72px; }
  .section-title {
    font-size: 11px; letter-spacing: .12em; color: #999;
    margin-bottom: 18px; padding-bottom: 12px;
    border-bottom: 1px solid var(--mist);
  }

  /* テーマカード */
  .theme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; margin-bottom: 44px; }
  .theme-card {
    background: #fff; border-radius: 14px; padding: 26px 24px;
    cursor: pointer; border: 2px solid transparent;
    transition: all .2s; position: relative; overflow: hidden;
  }
  .theme-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 4px; height: 100%;
    background: var(--accent, var(--sage));
    border-radius: 14px 0 0 14px;
  }
  .theme-card:hover { border-color: var(--accent, var(--sage)); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(26,26,46,.09); }
  .theme-card.selected { border-color: var(--accent, var(--sage)); background: #f8faf8; }
  .theme-icon { font-size: 26px; margin-bottom: 12px; }
  .theme-name { font-weight: 700; font-size: 15px; margin-bottom: 7px; }
  .theme-desc { font-size: 12px; color: #888; line-height: 1.75; }

  /* モード選択 */
  .mode-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 44px; }
  .mode-card {
    background: #fff; border-radius: 14px; padding: 26px 22px;
    cursor: pointer; border: 2px solid transparent;
    transition: all .2s; text-align: center;
  }
  .mode-card:hover { border-color: var(--sage); box-shadow: 0 4px 18px rgba(26,26,46,.07); }
  .mode-card.selected { border-color: var(--sage); background: #f8faf8; }
  .mode-icon { font-size: 32px; margin-bottom: 10px; }
  .mode-name { font-weight: 700; font-size: 15px; margin-bottom: 7px; }
  .mode-desc { font-size: 12px; color: #888; line-height: 1.75; }
  .mode-badge {
    display: inline-block; margin-top: 9px;
    padding: 3px 10px; background: #e8f5e9;
    color: var(--sage); border-radius: 20px;
    font-size: 11px; font-weight: 700;
  }

  .start-btn {
    display: block; width: 100%; max-width: 340px;
    margin: 0 auto; padding: 17px;
    background: var(--sage); color: #fff; border: none; border-radius: 12px;
    font-size: 15px; font-family: inherit; font-weight: 700;
    letter-spacing: .06em; cursor: pointer; transition: all .2s;
    opacity: .4; pointer-events: none;
  }
  .start-btn.active { opacity: 1; pointer-events: all; }
  .start-btn.active:hover { background: #6a8e6e; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(122,158,126,.35); }

  @media (max-width: 600px) {
    header { padding: 18px 20px; }
    .hero { padding: 36px 20px 24px; }
    .hero h2 { font-size: 26px; }
    .section { padding: 0 20px 56px; }
    .mode-grid { grid-template-columns: 1fr; }
    .theme-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<header>
  <a href="<?= APP_BASE_PATH ?>/index.php" class="logo">コーチングトレーナー</a>
  <nav class="nav-links">
    <?php if (isAdmin()): ?>
    <a href="<?= APP_BASE_PATH ?>/admin.php" class="admin-link">管理</a>
    <?php endif; ?>
    <a href="<?= APP_BASE_PATH ?>/history.php">学習履歴</a>
    <span class="nav-user"><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) ?></span>
    <a href="<?= APP_BASE_PATH ?>/login.php?logout=1" class="logout" onclick="return confirm('ログアウトしますか？')">ログアウト</a>
  </nav>
</header>

<div class="hero">
  <p class="hero-label">Mental Coaching Trainer</p>
  <h2>今日のセッションを<br>始めましょう</h2>
  <p>アドラー心理学をベースにしたコーチングを、AIクライアントと練習できます。テーマとモードを選んでスタートしてください。</p>
</div>

<div class="section">
  <p class="section-title">STEP 1 &nbsp; テーマを選ぶ</p>
  <div class="theme-grid">
    <div class="theme-card" style="--accent:#7a9e7e" data-theme="職場の悩み" onclick="selectTheme(this)">
      <div class="theme-icon">💼</div>
      <div class="theme-name">職場の悩み</div>
      <div class="theme-desc">仕事のプレッシャー、上司・同僚との関係、キャリアの方向性など</div>
    </div>
    <div class="theme-card" style="--accent:#8b7ec4" data-theme="人間関係" onclick="selectTheme(this)">
      <div class="theme-icon">🤝</div>
      <div class="theme-name">人間関係</div>
      <div class="theme-desc">家族、パートナー、友人、職場の人間関係とコミュニケーション</div>
    </div>
    <div class="theme-card" style="--accent:#c4962d" data-theme="自己実現" onclick="selectTheme(this)">
      <div class="theme-icon">🌱</div>
      <div class="theme-name">自己実現</div>
      <div class="theme-desc">自分らしく生きたい、やりたいことがわからない、変わりたいなど</div>
    </div>
    <div class="theme-card" style="--accent:#c4622d" data-theme="その他" onclick="selectTheme(this)">
      <div class="theme-icon">💭</div>
      <div class="theme-name">その他・自由テーマ</div>
      <div class="theme-desc">上記以外のテーマで自由にセッションを行う</div>
    </div>
  </div>

  <p class="section-title">STEP 2 &nbsp; モードを選ぶ</p>
  <div class="mode-grid">
    <div class="mode-card" data-mode="roleplay" onclick="selectMode(this)">
      <div class="mode-icon">🎭</div>
      <div class="mode-name">ロールプレイモード</div>
      <div class="mode-desc">AIがクライアント役を演じます。実際にコーチとして会話しながら練習できます。スーパーバイザーからのヒントも表示されます。</div>
      <span class="mode-badge">実践向き</span>
    </div>
    <div class="mode-card" data-mode="scenario" onclick="selectMode(this)">
      <div class="mode-icon">📖</div>
      <div class="mode-name">シナリオ学習モード</div>
      <div class="mode-desc">クライアントの発言を読み、4択から最適な返答を選びます。正解・不正解の理由と模範解答が表示されます。</div>
      <span class="mode-badge">学習向き</span>
    </div>
  </div>

  <button class="start-btn" id="startBtn" onclick="startSession()">セッションを開始する →</button>
</div>

<script>
let selectedTheme = null, selectedMode = null;

function selectTheme(el) {
  document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedTheme = el.dataset.theme;
  updateBtn();
}
function selectMode(el) {
  document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedMode = el.dataset.mode;
  updateBtn();
}
function updateBtn() {
  document.getElementById('startBtn').classList.toggle('active', !!(selectedTheme && selectedMode));
}
function startSession() {
  if (!selectedTheme || !selectedMode) return;
  location.href = '<?= APP_BASE_PATH ?>/session.php?' + new URLSearchParams({ theme: selectedTheme, mode: selectedMode });
}
</script>
</body>
</html>
