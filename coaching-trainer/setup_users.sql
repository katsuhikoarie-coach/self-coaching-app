-- =====================================================
-- ユーザー管理機能 追加マイグレーション
-- phpMyAdmin で実行してください
-- =====================================================

-- 1. users テーブル
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT AUTO_INCREMENT NOT NULL,
  `name`       VARCHAR(100)  NOT NULL,
  `email`      VARCHAR(255)  NOT NULL,
  `password`   VARCHAR(255)  NOT NULL,
  `role`       ENUM('admin','user') NOT NULL DEFAULT 'user',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. sessions テーブルに user_id を追加
--    ※ すでにカラムが存在する場合はエラーになります。その場合はスキップしてください。
ALTER TABLE `sessions`
  ADD COLUMN `user_id` INT NULL AFTER `session_id`,
  ADD INDEX  `idx_session_user_id` (`user_id`);

-- 3. 初期管理者アカウント
--    次のファイルにブラウザでアクセスして作成してください:
--    https://<ドメイン>/coaching-trainer/setup_admin.php
--    実行後はファイルを削除してください
