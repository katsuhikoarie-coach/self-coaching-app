-- ============================================================
-- 朝霧ヤマノ FC受注システム データベース初期設定
-- DB名: asagiri_fcorder
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

-- ── FCユーザーマスタ ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fc_users` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `email`        VARCHAR(255) NOT NULL UNIQUE COMMENT 'GmailアドレスがFirebase認証と紐付く',
  `fc_name`      VARCHAR(100) NOT NULL        COMMENT 'FC正式名称',
  `contact_name` VARCHAR(100)                 COMMENT '担当者名',
  `address`      TEXT                         COMMENT '住所',
  `phone`        VARCHAR(30)                  COMMENT '電話番号',
  `active`       TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=有効 0=無効',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 注文ヘッダー ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`           VARCHAR(20) PRIMARY KEY     COMMENT '受注番号 例: 2025-05-15-0001',
  `fc_user_id`   INT NOT NULL,
  `fc_name`      VARCHAR(100)                COMMENT '注文時点のFC名（スナップショット）',
  `fc_email`     VARCHAR(255),
  `order_date`   DATE NOT NULL,
  `subtotal`     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '税抜合計',
  `tax_total`    DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '消費税合計',
  `total`        DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '税込合計',
  `note`         TEXT                        COMMENT '備考',
  `status`       VARCHAR(20) NOT NULL DEFAULT 'received' COMMENT 'received/confirmed/shipped',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`fc_user_id`) REFERENCES `fc_users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 注文明細 ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`     VARCHAR(20) NOT NULL,
  `product_code` VARCHAR(20) NOT NULL,
  `product_name` VARCHAR(255),
  `price`        DECIMAL(10,2) NOT NULL       COMMENT '税抜単価',
  `quantity`     INT NOT NULL DEFAULT 1,
  `tax_rate`     DECIMAL(4,2) NOT NULL DEFAULT 0.10,
  `amount`       DECIMAL(12,2) NOT NULL       COMMENT '税抜小計 = price × quantity',
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 初期FCユーザー登録（在りさん・担当者を追加してください） ──
-- INSERT INTO fc_users (email, fc_name, contact_name) VALUES
--   ('fc1@example.com', '西明石白神FC', '白神'),
--   ('fc2@example.com', 'サンプルFC', '担当者名');
