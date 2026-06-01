<?php
// ログアウト処理（HTML出力前に行う）
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . APP_BASE_PATH . '/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'メールアドレスとパスワードを入力してください';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: ' . APP_BASE_PATH . '/index.php');
                exit;
            } else {
                $error = 'メールアドレスまたはパスワードが違います';
            }
        } catch (Exception $e) {
            $error = 'ログインに失敗しました。しばらく待ってから再試行してください';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ログイン — コーチングトレーナー</title>
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
  }
  body {
    font-family: 'Zen Kaku Gothic New', sans-serif;
    background: var(--paper);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse at 30% 40%, rgba(122,158,126,.12) 0%, transparent 55%),
      radial-gradient(ellipse at 70% 65%, rgba(196, 98, 45,.07) 0%, transparent 55%);
    pointer-events: none;
  }
  .wrap {
    text-align: center;
    padding: 60px 32px;
    position: relative;
    z-index: 1;
  }
  .logo-circle {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: var(--sage);
    margin: 0 auto 28px;
    display: flex; align-items: center; justify-content: center;
  }
  .logo-circle svg { width: 34px; height: 34px; fill: white; }
  h1 {
    font-family: 'Noto Serif JP', serif;
    font-weight: 300;
    font-size: 26px;
    color: var(--ink);
    letter-spacing: .08em;
    margin-bottom: 6px;
  }
  .sub { font-size: 13px; color: #888; letter-spacing: .05em; margin-bottom: 44px; }
  .card {
    background: #fff;
    border-radius: 16px;
    padding: 36px 32px;
    width: 320px;
    box-shadow: 0 4px 36px rgba(26,26,46,.09);
  }
  .error {
    background: #fef0eb;
    color: var(--rust);
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 18px;
    text-align: left;
  }
  label {
    display: block;
    font-size: 11px;
    color: #888;
    letter-spacing: .06em;
    margin-bottom: 6px;
    text-align: left;
  }
  input[type=email],
  input[type=password] {
    width: 100%;
    padding: 13px 15px;
    border: 1.5px solid var(--mist);
    border-radius: 10px;
    font-size: 16px;
    font-family: inherit;
    background: var(--paper);
    color: var(--ink);
    outline: none;
    transition: border-color .2s;
    margin-bottom: 18px;
  }
  input[type=email]:focus,
  input[type=password]:focus { border-color: var(--sage); }
  button {
    width: 100%;
    padding: 13px;
    background: var(--sage);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-family: inherit;
    font-weight: 700;
    letter-spacing: .05em;
    cursor: pointer;
    transition: background .2s, transform .1s;
  }
  button:hover { background: #6a8e6e; }
  button:active { transform: scale(.98); }
</style>
</head>
<body>
<div class="wrap">
  <div class="logo-circle">
    <svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm-1-11h2v6h-2zm0 8h2v2h-2z"/></svg>
  </div>
  <h1>コーチングトレーナー</h1>
  <p class="sub">メンタルコーチング練習アプリ</p>
  <div class="card">
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <form method="post">
      <label>メールアドレス</label>
      <input type="email" name="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
             placeholder="example@mail.com" autofocus autocomplete="email">
      <label>パスワード</label>
      <input type="password" name="password" placeholder="●●●●●●●●" autocomplete="current-password">
      <button type="submit">ログイン</button>
    </form>
  </div>
</div>
</body>
</html>
