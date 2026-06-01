-- ============================================================
-- ヤマノ注文履歴取込 テーブル定義
-- DB: asagiri_fcorder
-- ============================================================

CREATE TABLE IF NOT EXISTS `yamano_orders` (
  `id`               INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `yamano_order_id`  VARCHAR(30)      NOT NULL UNIQUE COMMENT 'Y2026052100017形式',
  `order_date`       DATE             NOT NULL,
  `total_pretax`     INT UNSIGNED     NOT NULL DEFAULT 0 COMMENT '総合計（税別）',
  `shipping_name`    VARCHAR(100)     NOT NULL DEFAULT '' COMMENT 'お届け先氏名',
  `shipping_address` VARCHAR(255)     NOT NULL DEFAULT '' COMMENT 'お届け先住所',
  `shipping_tel`     VARCHAR(20)      NOT NULL DEFAULT '' COMMENT 'お届け先電話番号',
  `status`           VARCHAR(30)      NOT NULL DEFAULT '' COMMENT '注文状況',
  `fc_user_id`       INT UNSIGNED     NULL DEFAULT NULL COMMENT '紐づけセンターID（NULLは未分類）',
  `month_period`     VARCHAR(7)       NOT NULL COMMENT 'YYYY-MM形式',
  `fetched_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '取込日時',
  INDEX idx_month_period (`month_period`),
  INDEX idx_fc_user_id   (`fc_user_id`),
  INDEX idx_order_date   (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yamano_order_items` (
  `id`              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `yamano_order_id` VARCHAR(30)   NOT NULL COMMENT 'yamano_ordersへの外部キー',
  `product_code`    VARCHAR(20)   NOT NULL DEFAULT '' COMMENT '商品コード（例：0857）',
  `product_name`    VARCHAR(200)  NOT NULL DEFAULT '' COMMENT '商品名',
  `unit_price`      INT UNSIGNED  NOT NULL DEFAULT 0 COMMENT '単価（税別）',
  `quantity`        INT UNSIGNED  NOT NULL DEFAULT 1 COMMENT '注文数',
  `subtotal`        INT UNSIGNED  NOT NULL DEFAULT 0 COMMENT '小計（税別）',
  INDEX idx_yamano_order_id (`yamano_order_id`),
  FOREIGN KEY (`yamano_order_id`)
    REFERENCES `yamano_orders`(`yamano_order_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yamano_shipping_map` (
  `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `shipping_name` VARCHAR(100)  NOT NULL UNIQUE COMMENT 'お届け先氏名（完全一致で紐づけ）',
  `fc_user_id`    INT UNSIGNED  NOT NULL COMMENT '対応するセンターID',
  `note`          VARCHAR(255)  NOT NULL DEFAULT '' COMMENT '備考',
  INDEX idx_shipping_name (`shipping_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初期マッピングデータ
INSERT IGNORE INTO `yamano_shipping_map` (`shipping_name`, `fc_user_id`, `note`) VALUES
  ('岩田裕美子', 21, '西舞子岩田BC本人'),
  ('稲岡ゆかり',  22, '西舞子ゆかりBC本人'),
  ('土野麻希子', 23, 'マキ朝霧BC本人'),
  ('森本すずみ', 24, '三木すずBC本人'),
  ('有江健彦',   4,  '朝霧ヤマノ'),
  ('有江啓子',   5,  '朝霧ヤマノ'),
  ('杉原絵梨子', 22, '西舞子ゆかりBC顧客');
