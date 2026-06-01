<?php
require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDB();
$msg = '';
$err = '';

// ---- POST アクション ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $name  = trim($_POST['name']     ?? '');
        $email = trim($_POST['email']    ?? '');
        $pass  =      $_POST['password'] ?? '';
        $role  = in_array($_POST['role'] ?? '', ['admin','user']) ? $_POST['role'] : 'user';

        if ($name === '' || $email === '' || $pass === '') {
            $err = '全項目を入力してください';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'メールアドレスの形式が正しくありません';
        } elseif (mb_strlen($pass) < 6) {
            $err = 'パスワードは6文字以上で入力してください';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)')
                    ->execute([$name, $email, $hash, $role]);
                $msg = htmlspecialchars($name, ENT_QUOTES) . ' を追加しました';
            } catch (PDOException $e) {
                $err = 'そのメールアドレスはすでに登録されています';
            }
        }
    }

    elseif ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === (int)$_SESSION['user_id']) {
            $err = '自分自身は削除できません';
        } elseif ($uid > 0) {
            $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt->execute([$uid]);
            $target = $stmt->fetch();
            if ($target) {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                $msg = htmlspecialchars($target['name'], ENT_QUOTES) . ' を削除しました';
            }
        }
    }
}

// ---- ユーザー一覧（学習サマリー付き）----
$users = $pdo->query(
    'SELECT u.id, u.name, u.email, u.role, u.created_at,
            COUNT(DISTINCT s.id)                              AS session_count,
            ROUND(AVG(COALESCE(f.overall_score, s.score)), 1) AS avg_score
     FROM users u
     LEFT JOIN sessions s            ON s.user_id      = u.id
     LEFT JOIN feedback_details f    ON f.session_id   = s.session_id
     GROUP BY u.id, u.name, u.email, u.role, u.created_at
     ORDER BY u.created_at ASC'
)->fetchAll();

$totalSessions = (int)$pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();
$totalUsers    = count($users);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理画面 — コーチングトレーナー</title>
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
    --white: #ffffff;
  }
  body { font-family: 'Zen Kaku Gothic New', sans-serif; background: var(--paper); min-height: 100vh; color: var(--ink); }

  header {
    padding: 18px 36px;
    background: var(--white);
    border-bottom: 1px solid var(--mist);
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .header-left { display: flex; align-items: center; gap: 14px; }
  .back-link {
    padding: 5px 13px; border: 1.5px solid var(--mist);
    border-radius: 8px; font-size: 13px; text-decoration: none; color: #888;
    transition: all .2s;
  }
  .back-link:hover { color: var(--ink); border-color: #999; }
  .page-title { font-family: 'Noto Serif JP', serif; font-weight: 300; font-size: 19px; }
  .header-right { display: flex; align-items: center; gap: 16px; }
  .nav-user { font-size: 13px; color: #555; }
  .logout-link { font-size: 13px; color: #bbb; text-decoration: none; }
  .logout-link:hover { color: var(--rust); }

  .container { max-width: 960px; margin: 0 auto; padding: 36px 22px 80px; }

  /* 統計 */
  .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 32px; }
  .stat-card {
    background: var(--white); border-radius: 13px; padding: 22px 18px;
    text-align: center; box-shadow: 0 2px 10px rgba(26,26,46,.04);
  }
  .stat-label { font-size: 10px; color: #999; letter-spacing: .09em; margin-bottom: 8px; }
  .stat-value { font-family: 'Noto Serif JP', serif; font-size: 32px; font-weight: 300; color: var(--sage); }
  .stat-unit  { font-size: 12px; color: #aaa; }

  /* フラッシュ */
  .flash-ok  { background: #e8f5e9; color: #2e7d32; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
  .flash-err { background: #ffebee; color: #c62828; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }

  /* セクション */
  .section { margin-bottom: 36px; }
  .section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; padding-bottom: 12px;
    border-bottom: 1px solid var(--mist);
  }
  .section-title { font-size: 12px; letter-spacing: .1em; color: #999; }
  .toggle-btn {
    padding: 6px 14px; border: 1.5px solid var(--mist);
    border-radius: 8px; font-size: 12px; font-family: inherit;
    background: transparent; cursor: pointer; color: #666;
    transition: all .2s;
  }
  .toggle-btn:hover { border-color: var(--sage); color: var(--sage); }

  /* 追加フォーム */
  .add-form {
    background: var(--white); border-radius: 13px; padding: 24px 22px;
    box-shadow: 0 2px 10px rgba(26,26,46,.04);
    display: none;
  }
  .add-form.open { display: block; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; }
  .form-group.full { grid-column: 1 / -1; }
  .form-label { font-size: 11px; color: #888; letter-spacing: .05em; }
  .form-input, .form-select {
    padding: 10px 13px;
    border: 1.5px solid var(--mist);
    border-radius: 9px;
    font-size: 14px;
    font-family: inherit;
    background: var(--paper);
    color: var(--ink);
    outline: none;
    transition: border-color .2s;
  }
  .form-input:focus, .form-select:focus { border-color: var(--sage); }
  .pw-wrap { position: relative; }
  .pw-wrap .form-input { padding-right: 44px; }
  .pw-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; padding: 4px;
    color: #aaa; font-size: 16px; line-height: 1;
    transition: color .2s;
  }
  .pw-toggle:hover { color: var(--sage); }
  .submit-btn {
    padding: 11px 26px;
    background: var(--sage); color: #fff; border: none;
    border-radius: 9px; font-size: 14px; font-family: inherit; font-weight: 700;
    cursor: pointer; transition: background .2s;
  }
  .submit-btn:hover { background: #6a8e6e; }

  /* ユーザーテーブル */
  .user-table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  thead th {
    text-align: left; padding: 10px 14px;
    font-size: 10px; letter-spacing: .08em; color: #999;
    border-bottom: 1.5px solid var(--mist);
    white-space: nowrap;
  }
  tbody tr { border-bottom: 1px solid var(--mist); transition: background .15s; }
  tbody tr:hover { background: rgba(122,158,126,.04); }
  tbody td { padding: 14px 14px; vertical-align: middle; }
  .user-name { font-weight: 700; font-size: 14px; }
  .user-email { font-size: 12px; color: #888; }
  .role-badge {
    display: inline-block; padding: 3px 10px;
    border-radius: 20px; font-size: 11px; font-weight: 700;
  }
  .role-badge.admin { background: #e8f5e9; color: var(--sage); }
  .role-badge.user  { background: var(--mist); color: #888; }
  .score-cell { font-family: 'Noto Serif JP', serif; font-size: 18px; color: var(--sage); }
  .score-none { color: #ccc; }
  .delete-btn {
    padding: 5px 12px;
    background: transparent; border: 1.5px solid var(--mist);
    border-radius: 7px; font-size: 12px; font-family: inherit;
    color: #aaa; cursor: pointer; transition: all .2s;
  }
  .delete-btn:hover { border-color: var(--rust); color: var(--rust); }
  .delete-btn:disabled { opacity: .35; cursor: default; }
  .date-cell { font-size: 12px; color: #aaa; white-space: nowrap; }

  @media (max-width: 640px) {
    header { padding: 14px 16px; }
    .container { padding: 24px 14px 60px; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .stats-grid .stat-card:last-child { grid-column: 1 / -1; }
    .form-grid { grid-template-columns: 1fr; }
    .nav-user { display: none; }
  }
</style>
</head>
<body>
<header>
  <div class="header-left">
    <a href="<?= APP_BASE_PATH ?>/index.php" class="back-link">← ホーム</a>
    <span class="page-title">管理画面</span>
  </div>
  <div class="header-right">
    <span class="nav-user"><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) ?></span>
    <a href="<?= APP_BASE_PATH ?>/login.php?logout=1" class="logout-link"
       onclick="return confirm('ログアウトしますか？')">ログアウト</a>
  </div>
</header>

<div class="container">

  <!-- 統計 -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">TOTAL USERS</div>
      <div class="stat-value"><?= $totalUsers ?><span class="stat-unit"> 人</span></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">TOTAL SESSIONS</div>
      <div class="stat-value"><?= $totalSessions ?><span class="stat-unit"> 回</span></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">AVG SESSIONS / USER</div>
      <div class="stat-value"><?= $totalUsers > 0 ? round($totalSessions / $totalUsers, 1) : 0 ?><span class="stat-unit"> 回</span></div>
    </div>
  </div>

  <?php if ($msg): ?><div class="flash-ok"><?= $msg ?></div><?php endif; ?>
  <?php if ($err): ?><div class="flash-err"><?= htmlspecialchars($err, ENT_QUOTES) ?></div><?php endif; ?>

  <!-- ユーザー追加 -->
  <div class="section">
    <div class="section-header">
      <span class="section-title">USER MANAGEMENT</span>
      <button class="toggle-btn" id="toggleAddForm" onclick="toggleForm()">＋ ユーザーを追加</button>
    </div>

    <div class="add-form" id="addForm">
      <form method="post">
        <input type="hidden" name="action" value="add_user">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">名前</label>
            <input class="form-input" type="text" name="name"
                   value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES) ?>"
                   placeholder="山田 太郎" required>
          </div>
          <div class="form-group">
            <label class="form-label">メールアドレス</label>
            <input class="form-input" type="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
                   placeholder="user@example.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">パスワード（6文字以上）</label>
            <div class="pw-wrap">
              <input class="form-input" type="password" id="newPassword" name="password"
                     placeholder="●●●●●●" required minlength="6">
              <button type="button" class="pw-toggle" onclick="togglePw()" title="パスワードを表示/非表示">
                <span id="pwIcon">👁</span>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">役割</label>
            <select class="form-select" name="role">
              <option value="user">一般ユーザー</option>
              <option value="admin">管理者</option>
            </select>
          </div>
        </div>
        <button class="submit-btn" type="submit">追加する</button>
      </form>
    </div>
  </div>

  <!-- ユーザー一覧 -->
  <div class="section">
    <div class="section-header">
      <span class="section-title">USER LIST &nbsp;（<?= $totalUsers ?> 名）</span>
    </div>
    <div class="user-table-wrap">
      <table>
        <thead>
          <tr>
            <th>名前 / メール</th>
            <th>役割</th>
            <th style="text-align:right">セッション数</th>
            <th style="text-align:right">平均スコア</th>
            <th>登録日</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="user-name"><?= htmlspecialchars($u['name'], ENT_QUOTES) ?></div>
              <div class="user-email"><?= htmlspecialchars($u['email'], ENT_QUOTES) ?></div>
            </td>
            <td>
              <span class="role-badge <?= $u['role'] ?>">
                <?= $u['role'] === 'admin' ? '管理者' : '一般' ?>
              </span>
            </td>
            <td style="text-align:right">
              <?= (int)$u['session_count'] ?> 回
            </td>
            <td style="text-align:right">
              <?php if ($u['avg_score'] !== null): ?>
                <span class="score-cell"><?= $u['avg_score'] ?></span>
                <span style="font-size:11px;color:#aaa"> 点</span>
              <?php else: ?>
                <span class="score-none">—</span>
              <?php endif; ?>
            </td>
            <td class="date-cell">
              <?= htmlspecialchars(date('Y/m/d', strtotime($u['created_at'])), ENT_QUOTES) ?>
            </td>
            <td>
              <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="post" style="display:inline"
                    onsubmit="return confirm('<?= htmlspecialchars($u['name'], ENT_QUOTES) ?> を削除しますか？\nこの操作は取り消せません。')">
                <input type="hidden" name="action"  value="delete_user">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button class="delete-btn" type="submit">削除</button>
              </form>
              <?php else: ?>
                <button class="delete-btn" disabled title="自分自身は削除できません">削除</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
          <tr><td colspan="6" style="text-align:center;padding:40px;color:#aaa">ユーザーがいません</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
<?php if ($err && isset($_POST['action']) && $_POST['action'] === 'add_user'): ?>
// エラー時はフォームを開いた状態にする
document.getElementById('addForm').classList.add('open');
<?php endif; ?>

function togglePw() {
  const input = document.getElementById('newPassword');
  const icon  = document.getElementById('pwIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.textContent = '🙈';
  } else {
    input.type = 'password';
    icon.textContent = '👁';
  }
}

function toggleForm() {
  const form = document.getElementById('addForm');
  const btn  = document.getElementById('toggleAddForm');
  const open = form.classList.toggle('open');
  btn.textContent = open ? '✕ 閉じる' : '＋ ユーザーを追加';
}
</script>
</body>
</html>
