-- ============================================================
-- add_este_sales_tables.sql
-- DB: asagiri_will
-- 用途: エステ売上テーブルの追加作成
--       este_sales / este_sales_items
--
-- 実行方法:
--   phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `este_sales` (
  `id`         varchar(30)  NOT NULL,
  `sale_date`  date         NOT NULL,
  `cust_no`    varchar(20)  DEFAULT '',
  `cust_name`  varchar(100) DEFAULT '',
  `staff`      varchar(50)  DEFAULT '',
  `subtotal`   int(11)      DEFAULT 0,
  `tax_amt`    int(11)      DEFAULT 0,
  `total`      int(11)      DEFAULT 0,
  `note`       text         DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sale_date` (`sale_date`),
  KEY `idx_cust_no`   (`cust_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `este_sales_items` (
  `id`           int(11)                    NOT NULL AUTO_INCREMENT,
  `sale_id`      varchar(30)                NOT NULL,
  `menu_id`      varchar(20)                DEFAULT '',
  `menu_name`    varchar(100)               DEFAULT '',
  `item_type`    enum('ticket','single')    DEFAULT 'single',
  `ticket_count` int(11)                    DEFAULT 1,
  `qty`          int(11)                    DEFAULT 1,
  `unit_price`   int(11)                    DEFAULT 0,
  `subtotal`     int(11)                    DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'add_este_sales_tables.sql 完了: 2テーブル作成済み' AS status;
