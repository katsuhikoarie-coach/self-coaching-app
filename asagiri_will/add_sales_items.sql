-- ============================================================
-- add_sales_items.sql
-- DB: asagiri_will
-- 用途: 売上明細テーブルの追加作成
--
-- ※ asagiri_will の sales.id は INT AUTO_INCREMENT のため
--    sale_id も INT に変更（asagiri_sales は VARCHAR）
--
-- 実行方法:
--   phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `sales_items` (
  `id`           int(11)       NOT NULL AUTO_INCREMENT,
  `sale_id`      int(11)       NOT NULL                  COMMENT '伝票ID（sales.id）',
  `product_code` varchar(20)   DEFAULT ''                COMMENT '商品コード',
  `product_name` varchar(100)  DEFAULT ''                COMMENT '商品名',
  `qty`          int(11)       DEFAULT 1                 COMMENT '数量',
  `kake`         decimal(5,4)  DEFAULT 1.0000            COMMENT '掛率（0.6〜1.0）',
  `tax_rate`     decimal(4,2)  DEFAULT 0.10              COMMENT '税率',
  `amount`       decimal(10,2) DEFAULT 0.00              COMMENT '下代 × 数量（税抜）',
  PRIMARY KEY (`id`),
  KEY `idx_sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'add_sales_items.sql 完了: sales_items テーブル作成済み' AS status;
