-- ============================================================
-- fix_sales_items_sale_id.sql
-- DB: asagiri_will
-- 用途: sales_items.sale_id を VARCHAR(20) に変更
--
-- 背景:
--   setup_will.sql で sales.id = INT AUTO_INCREMENT で作成
--   add_sales_items.sql で sales_items.sale_id = INT で作成
--   alter_sales_add_columns.sql で sales.id を VARCHAR(20) に変更
--   → sales_items.sale_id が INT のまま残り、型不一致で sale_id が 0 になるバグ
--
-- 実行方法:
--   phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;

-- sale_id を VARCHAR(20) に変更して sales.id と型を合わせる
ALTER TABLE `sales_items`
  MODIFY `sale_id` VARCHAR(20) NOT NULL COMMENT '伝票番号（sales.id）';

-- 既存の sale_id=0 のゴミデータを削除
DELETE FROM `sales_items` WHERE `sale_id` = '0' OR `sale_id` = '';

SELECT 'fix_sales_items_sale_id.sql 完了: sales_items.sale_id を VARCHAR(20) に変更しました' AS status;
