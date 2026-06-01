-- ============================================================
-- 全ユーザーに仮パスワード「asagiri2026」を設定する
--
-- 手順：
--  1. phpMyAdmin で下記の ALTER TABLE を実行（カラム未追加の場合）
--  2. set_initial_passwords.php をサーバーに配置して一回実行
--     または下記 UPDATE の password_hash 値を
--     php -r "echo password_hash('asagiri2026', PASSWORD_DEFAULT);"
--     で生成した値に置き換えて実行
-- ============================================================

-- Step 1: カラム追加（初回のみ）
ALTER TABLE fc_users
  ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL,
  ADD COLUMN last_login_at TIMESTAMP DEFAULT NULL;

-- Step 2: 仮パスワード設定（下記 hash 値は asagiri2026 のbcrypt）
-- ※ このファイルを直接実行する場合は、まず php -r "echo password_hash('asagiri2026', PASSWORD_DEFAULT);"
--   でハッシュを生成し、以下の値を置き換えてください。
UPDATE fc_users
SET password_hash = '$2y$10$PLACEHOLDER_REPLACE_WITH_REAL_HASH'
WHERE password_hash IS NULL;

-- 確認クエリ
SELECT id, email, fc_name,
       CASE WHEN password_hash IS NOT NULL THEN 'SET' ELSE 'NOT SET' END AS pw_status,
       active
FROM fc_users
ORDER BY fc_name;
