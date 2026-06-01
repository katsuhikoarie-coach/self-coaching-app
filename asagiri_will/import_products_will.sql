-- ============================================================
-- import_products_will.sql
-- 用途: asagiri_sales の products テーブルを asagiri_will へコピー
--       かつ 消費税8%対象コードの tax_rate を 8 に更新する
--
-- 実行前提:
--   1. setup_will.sql を先に実行済みであること
--   2. phpMyAdmin のユーザーが asagiri_sales への SELECT 権限を持つこと
--      ※ 権限がない場合は「方法B（エクスポート）」を使用してください
--
-- 実行方法:
--   phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

-- ============================================================
-- 方法A: 同一MySQLサーバー内でのDB間コピー（推奨）
-- ============================================================

-- 既存データをクリア（再実行時の重複防止）
TRUNCATE TABLE `asagiri_will`.`products`;

-- asagiri_sales.products から全件コピー（tax_rate はデフォルト10%でコピー）
INSERT INTO `asagiri_will`.`products`
  (`code`, `name`, `price`, `tax_rate`, `category`, `unit`, `discontinued`, `created_at`, `updated_at`)
SELECT
  `code`, `name`, `price`, 10, `category`, `unit`, `discontinued`, NOW(), NOW()
FROM `asagiri_sales`.`products`;

-- ============================================================
-- 消費税8%対象コードを tax_rate = 8 に更新
-- ============================================================
UPDATE `asagiri_will`.`products`
SET `tax_rate` = 8
WHERE `code` IN (
  '0329','0330','0670','1659','2646','3025','3304',
  '6224','6225','6226','6228','6250','6251','6252','6253','6254',
  '6278','6282','6288','6289','6291','6292',
  '6303','6304','6305','6306','6307','6308','630u',
  '6311','6312','6313','6314','6315','6316','6317','631u',
  '6323','6325','6329','6335','6336','6337','6515',
  '7628','7629','7630',
  'Y001','Y002','Y007','Y008','Y059','Y067','Y068','Y085',
  'Y628','Y629','Y633','Y635','Y636',
  'Z025','Z601','z101','z102','ｚ101','ｚ102'
);

-- 件数確認
SELECT
  COUNT(*)                                  AS total_products,
  SUM(CASE WHEN tax_rate = 8  THEN 1 END)  AS tax_8_count,
  SUM(CASE WHEN tax_rate = 10 THEN 1 END)  AS tax_10_count
FROM `asagiri_will`.`products`;

SELECT 'import_products_will.sql 完了' AS status;


-- ============================================================
-- 方法B: DB間コピーが使えない場合（エクスポート → インポート）
-- ============================================================
-- 1. phpMyAdmin で asagiri_sales を選択
-- 2. products テーブルをエクスポート（形式: SQL、INSERT文）
-- 3. エクスポートしたSQLを開き、テーブル名を
--    `products` → `asagiri_will`.`products` に一括置換
-- 4. asagiri_will を選択して SQLタブで実行
-- 5. 上記の「消費税8%対象コードを tax_rate = 8 に更新」のUPDATE文を実行
-- ============================================================
