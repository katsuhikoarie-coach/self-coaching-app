-- ============================================================
-- salesテーブルに不足カラムを追加 & 型修正
-- DB: asagiri_will
-- ============================================================

-- id を varchar(20) に変更（現在 INT AUTO_INCREMENT）
-- ※ api/sales.php は 'SL-XXXXXXX' 形式の文字列IDを使用するため必須
ALTER TABLE `sales`
  MODIFY `id` varchar(20) NOT NULL COMMENT '伝票番号';

-- customer_id を varchar(20) に変更（現在 INT）
ALTER TABLE `sales`
  MODIFY `customer_id` varchar(20) DEFAULT '' COMMENT '顧客番号';

-- 不足カラムを追加
ALTER TABLE `sales`
  ADD COLUMN `year_month`    varchar(7)     NOT NULL          COMMENT '年月 (YYYY-MM)'   AFTER `sale_date`,
  ADD COLUMN `deliver_date`  date           DEFAULT NULL      COMMENT '納品日'           AFTER `year_month`,
  ADD COLUMN `customer_name` varchar(100)   DEFAULT ''        COMMENT '顧客名'           AFTER `customer_id`,
  ADD COLUMN `customer_kana` varchar(100)   DEFAULT ''        COMMENT '顧客フリガナ'     AFTER `customer_name`,
  ADD COLUMN `sub10`         decimal(10,2)  DEFAULT 0.00      COMMENT '10%課税小計（税抜）',
  ADD COLUMN `sub8`          decimal(10,2)  DEFAULT 0.00      COMMENT '8%課税小計（税抜）',
  ADD COLUMN `tax10`         decimal(10,2)  DEFAULT 0.00      COMMENT '消費税10%',
  ADD COLUMN `tax8`          decimal(10,2)  DEFAULT 0.00      COMMENT '消費税8%',
  ADD COLUMN `total`         decimal(10,2)  DEFAULT 0.00      COMMENT '合計（税込）';

-- 既存データの year_month を sale_date から補完
UPDATE `sales` SET `year_month` = LEFT(`sale_date`, 7) WHERE `year_month` = '';
