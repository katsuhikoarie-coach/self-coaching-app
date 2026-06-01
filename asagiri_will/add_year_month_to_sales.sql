-- salesテーブルに year_month カラムを追加
-- sale_date の直後に挿入

ALTER TABLE `sales`
  ADD COLUMN `year_month` varchar(7) NOT NULL COMMENT '年月 (YYYY-MM)'
  AFTER `sale_date`;

-- 既存データの year_month を sale_date から補完
UPDATE `sales` SET `year_month` = LEFT(`sale_date`, 7) WHERE `year_month` = '';
