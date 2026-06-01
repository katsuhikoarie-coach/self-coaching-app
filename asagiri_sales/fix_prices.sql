-- 価格修正SQL（MDに揃える）
-- 生成日：2025-07-21
-- 対象DB：asagiri_sales

START TRANSACTION;

-- ヤマノＱ１０ベリークリーム　DB:0 → MD:8300
UPDATE products SET price = 8300.00 WHERE code = '0270';

-- AWﾄﾞﾛﾝｺﾊﾟｯｸ　DB:13700 → MD:13500
UPDATE products SET price = 13500.00 WHERE code = '0280';

-- 21ｲﾐｭｱｵｲﾙ　40日　DB:5300 → MD:4500
UPDATE products SET price = 4500.00 WHERE code = '0320';

-- 琥珀Ａ　ローション　DB:0 → MD:21000
UPDATE products SET price = 21000.00 WHERE code = '0401';

-- MD プレミアムクレンジングL　DB:0 → MD:8100
UPDATE products SET price = 8100.00 WHERE code = '0850';

-- MD プレミアムフェイシャルC　DB:0 → MD:9100
UPDATE products SET price = 9100.00 WHERE code = '0851';

-- MD プレミアムスキンL　DB:0 → MD:9100
UPDATE products SET price = 9100.00 WHERE code = '0852';

-- MD プレミアムミルクL　DB:0 → MD:9900
UPDATE products SET price = 9900.00 WHERE code = '0853';

-- MD プレミアムナリッシングCRY　DB:0 → MD:9900
UPDATE products SET price = 9900.00 WHERE code = '0854';

-- クレミルクL　DB:4200 → MD:4900
UPDATE products SET price = 4900.00 WHERE code = '0868';

-- クレナリクリ　DB:4200 → MD:4900
UPDATE products SET price = 4900.00 WHERE code = '0869';

-- クレクリ－ム　DB:4500 → MD:5200
UPDATE products SET price = 5200.00 WHERE code = '0870';

-- ﾍｱｶﾗ-ﾄﾘ-ﾄﾒﾝﾄ　1ヶ月用　DB:3800 → MD:4400
UPDATE products SET price = 4400.00 WHERE code = '5509';

-- ヤマノ　プロポリスＧ　DB:0 → MD:12000
UPDATE products SET price = 12000.00 WHERE code = '6282';

-- ｂパフ　2枚　DB:400 → MD:500
UPDATE products SET price = 500.00 WHERE code = '8113';

-- ＣＳ リフレッシュナー　DB:2100 → MD:1600
UPDATE products SET price = 1600.00 WHERE code = '9003';

-- CS ヤマノVTLマッサージC　R　DB:5000 → MD:4000
UPDATE products SET price = 4000.00 WHERE code = '9005';

-- C美水2　カ－トリッジ　DB:12700 → MD:10400
UPDATE products SET price = 10400.00 WHERE code = '9006';

-- CSアルコ－ルハンドジェル　500mi FC　DB:2000 → MD:1600
UPDATE products SET price = 1600.00 WHERE code = '9018';

-- CS ﾔﾏﾉﾊﾀﾞ　ｸﾚ-ｵﾘｼﾞﾅﾙ WH　280g　DB:0 → MD:1500
UPDATE products SET price = 1500.00 WHERE code = '9043';

-- CS ﾔﾏﾉ肌ｸﾚｰｵﾘｼﾞﾅﾙ BK　370g　DB:0 → MD:1800
UPDATE products SET price = 1800.00 WHERE code = '9044';

-- CSAWパック　DB:6500 → MD:5200
UPDATE products SET price = 5200.00 WHERE code = '9052';

-- ＣＳｾﾞﾛｸﾗﾘﾌｧｲﾝｸﾞﾛ-ｼｮﾝ　DB:4400 → MD:3400
UPDATE products SET price = 3400.00 WHERE code = '9054';

-- 24美水Ⅲカ－トリッジ　DB:8000 → MD:6500
UPDATE products SET price = 6500.00 WHERE code = '9070';

-- yamanoｵﾘｼﾞﾅﾙ手提袋（アカ大）　DB:150 → MD:130
UPDATE products SET price = 130.00 WHERE code = '9128';

-- yamanoｵﾘｼﾞﾅﾙ手提袋（アカ小）　DB:100 → MD:80
UPDATE products SET price = 80.00 WHERE code = '9129';

-- yamanoｵﾘｼﾞﾅﾙ紙袋（アカ大）　DB:25 → MD:23
UPDATE products SET price = 23.00 WHERE code = '9130';

-- yamanoｵﾘｼﾞﾅﾙ紙袋（アカ小）　DB:14 → MD:12
UPDATE products SET price = 12.00 WHERE code = '9131';

-- ﾌｪｲｼｬﾙﾍﾞｯﾄﾞ・ﾏｲﾃｨｰ (ｸﾘｰﾑ)　DB:94000 → MD:90000
UPDATE products SET price = 90000.00 WHERE code = '9161';

-- ﾌｪｲｼｬﾙﾍﾞｯﾄﾞ・ﾏｲﾃｨｰ (有孔ﾌﾀ付・ｸﾘｰﾑ)　DB:101500 → MD:97000
UPDATE products SET price = 97000.00 WHERE code = '9162';

-- クエン酸　DB:1700 → MD:1400
UPDATE products SET price = 1400.00 WHERE code = '9199';

-- CSﾌﾟﾗｽﾀｰﾏｽｸOP　DB:13650 → MD:12950
UPDATE products SET price = 12950.00 WHERE code = '9278';

-- CS  ﾔﾏﾉ ﾏｯｻｰｼﾞｸﾘｽﾀﾙ　270g　DB:0 → MD:4000
UPDATE products SET price = 4000.00 WHERE code = '9411';

-- C専用炭酸ガスカートリッジ（５本入り）　DB:7500 → MD:5500
UPDATE products SET price = 5500.00 WHERE code = '9450';

-- CSMu2ｴﾓﾘｴﾝﾄL　fc3900　DB:3900 → MD:3500
UPDATE products SET price = 3500.00 WHERE code = '9488';

-- CSBIDOU　ｸﾚﾝｼﾞﾝｸﾞｍ　290ｍｌ　DB:5800 → MD:5000
UPDATE products SET price = 5000.00 WHERE code = '9503';

-- ＣＳ　薬用美道ﾅﾘｯｼﾝｸﾞｸﾘｽﾀﾙ　300ｇ　DB:0 → MD:5200
UPDATE products SET price = 5200.00 WHERE code = '9504';

-- CS ホットキャビ HT-501　DB:30400 → MD:32000
UPDATE products SET price = 32000.00 WHERE code = '9590';

-- 純水器　DB:1 → MD:8900
UPDATE products SET price = 8900.00 WHERE code = '9628';

-- どろんこ実験(粉)　DB:777 → MD:680
UPDATE products SET price = 680.00 WHERE code = '9717';

-- どろんこ実験(液)　DB:777 → MD:680
UPDATE products SET price = 680.00 WHERE code = '9718';

-- エステスツール 斜楽　DB:40000 → MD:41100
UPDATE products SET price = 41100.00 WHERE code = '9769';

-- セルセル 30　DB:140 → MD:100
UPDATE products SET price = 100.00 WHERE code = '9813';

-- セルセル 70　DB:250 → MD:150
UPDATE products SET price = 150.00 WHERE code = '9816';

-- ポイント用 納品書　DB:161 → MD:150
UPDATE products SET price = 150.00 WHERE code = '9903';

-- CS ｴｽﾃﾜｺﾞﾝ N2　DB:28000 → MD:27000
UPDATE products SET price = 27000.00 WHERE code = '9932';

-- スポンジキット ボディ　DB:4400 → MD:4900
UPDATE products SET price = 4900.00 WHERE code = '9935';

-- スポンジキット(フェイス)　DB:4100 → MD:4500
UPDATE products SET price = 4500.00 WHERE code = '9939';

-- ギフト DFS-2　DB:2300 → MD:2900
UPDATE products SET price = 2900.00 WHERE code = 'A040';

-- ギフト DFS-1　DB:1050 → MD:1350
UPDATE products SET price = 1350.00 WHERE code = 'A044';

-- 琥珀球（２個）　DB:19000 → MD:25000
UPDATE products SET price = 25000.00 WHERE code = 'A116';

-- オリジナル コットン　DB:100 → MD:60
UPDATE products SET price = 60.00 WHERE code = 'AA01';

-- クレオリ24ＷＨ・ＢＫセット15ｇ　DB:320 → MD:280
UPDATE products SET price = 280.00 WHERE code = 'H507';

-- ローシヨンボトル２本　DB:3500 → MD:2650
UPDATE products SET price = 2650.00 WHERE code = 'M007';

-- ﾌｪｲｼｬﾙｶﾙﾃ2023　DB:18 → MD:17
UPDATE products SET price = 17.00 WHERE code = 'Y208';

-- ゼロブランドブック　DB:240 → MD:200
UPDATE products SET price = 200.00 WHERE code = 'Y834';

-- クレオリ24シリーズフライヤー　　(500枚)　DB:1000 → MD:900
UPDATE products SET price = 900.00 WHERE code = 'Y866';

-- 咲ブランドブック　DB:170 → MD:150
UPDATE products SET price = 150.00 WHERE code = 'Y868';

-- SHIROブランドブック　DB:200 → MD:180
UPDATE products SET price = 180.00 WHERE code = 'Y882';

COMMIT;

-- 合計 59 件