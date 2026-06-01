-- salesテーブルに deliver_date カラムを追加
-- year_month の直後に挿入

ALTER TABLE `sales`
  ADD COLUMN `deliver_date` date DEFAULT NULL COMMENT '納品日'
  AFTER `year_month`;
