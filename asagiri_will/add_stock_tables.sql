-- ============================================================
-- add_stock_tables.sql
-- DB: asagiri_will
-- 用途: 在庫管理テーブルの追加作成
--       stock / stock_inout
--
-- 実行方法:
--   phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `stock` (
  `code`       varchar(20)    NOT NULL COMMENT '商品コード',
  `name`       varchar(100)   DEFAULT '' COMMENT '商品名',
  `price`      decimal(10,2)  DEFAULT 0.00 COMMENT '定価',
  `qty`        int(11)        DEFAULT 0 COMMENT '在庫数',
  `updated_at` timestamp      NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_inout` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `io_date`     date         NOT NULL COMMENT '日付',
  `code`        varchar(20)  NOT NULL COMMENT '商品コード',
  `name`        varchar(100) DEFAULT '' COMMENT '商品名',
  `in_qty`      int(11)      DEFAULT 0 COMMENT '入庫数',
  `out_qty`     int(11)      DEFAULT 0 COMMENT '出庫数',
  `stock_after` int(11)      DEFAULT 0 COMMENT '入出庫後の在庫数',
  `note`        varchar(200) DEFAULT '' COMMENT '備考',
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_io_date` (`io_date`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'add_stock_tables.sql 完了: 2テーブル作成済み' AS status;
