-- ============================================================
-- add_este_tables.sql
-- DB: asagiri_will
-- 用途: エステ関連テーブルの追加作成
--       este_menus / este_visits / keep_items
--
-- 実行方法:
--   phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `este_menus` (
  `id`         varchar(20)  NOT NULL COMMENT 'コースID (M001 など)',
  `name`       varchar(100) NOT NULL COMMENT 'コース名',
  `price`      int(11)      DEFAULT 0  COMMENT '定価（税抜）',
  `tax_rate`   int(11)      DEFAULT 10 COMMENT '税率（%）',
  `active`     tinyint(4)   DEFAULT 1  COMMENT '有効フラグ',
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `este_visits` (
  `id`            varchar(30)  NOT NULL COMMENT '来店ID',
  `visit_date`    date         NOT NULL COMMENT '来店日',
  `customer_id`   varchar(20)  DEFAULT '' COMMENT '顧客番号',
  `customer_name` varchar(100) DEFAULT '' COMMENT '顧客名',
  `menu_id`       varchar(20)  DEFAULT '' COMMENT 'コースID',
  `menu_name`     varchar(100) DEFAULT '' COMMENT 'コース名',
  `staff`         varchar(50)  DEFAULT '' COMMENT '担当者',
  `price`         int(11)      DEFAULT 0  COMMENT '単価（税抜）',
  `tax_rate`      int(11)      DEFAULT 10 COMMENT '税率（%）',
  `tax`           int(11)      DEFAULT 0  COMMENT '消費税',
  `total`         int(11)      DEFAULT 0  COMMENT '合計（税込）',
  `next_visit`    date         DEFAULT NULL COMMENT '次回予約日',
  `note`          text         DEFAULT NULL COMMENT '備考',
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  `keep_item_id`  varchar(30)  DEFAULT NULL,
  `used_ticket`   tinyint(1)   DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_visit_date` (`visit_date`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `keep_items` (
  `id`            varchar(30)  NOT NULL COMMENT 'キープID',
  `item_date`     date         NOT NULL COMMENT '日付',
  `customer_id`   varchar(20)  DEFAULT '' COMMENT '顧客番号',
  `customer_name` varchar(100) DEFAULT '' COMMENT '顧客名',
  `menu_id`       varchar(20)  DEFAULT '',
  `menu_name`     varchar(200) DEFAULT '' COMMENT '商品名・回数券名',
  `qty`           int(11)      DEFAULT 1  COMMENT '数量',
  `count_val`     int(11)      DEFAULT 1  COMMENT '回数',
  `used_count`    int(11)      DEFAULT 0,
  `price`         int(11)      DEFAULT 0  COMMENT '金額',
  `note`          text         DEFAULT NULL COMMENT '備考',
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'add_este_tables.sql 完了: 3テーブル作成済み' AS status;
