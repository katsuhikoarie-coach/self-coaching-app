// 商品マスタ（受注システム商品管理リスト_v01.md 準拠）
// システム・一般 → 記載価格（税抜）/ CS・販促品 → FC価格
// 更新日: 2025年7月21日

export type Category =
  | 'スキンケア'
  | 'メイク'
  | 'ボディ＆ヘアケア'
  | 'エステキープ'
  | '健康食品'
  | 'キット'
  | 'CS商品'
  | '販促品'
  | '一般';

export const CATEGORIES: Category[] = [
  'スキンケア',
  'メイク',
  'ボディ＆ヘアケア',
  'エステキープ',
  '健康食品',
  'キット',
  'CS商品',
  '販促品',
  '一般',
];

export interface Product {
  code: string;
  name: string;
  price_fc: number;   // FC価格（税抜）
  price_bc?: number;  // BC価格（CS商品・販促品のみ設定、未設定時はprice_fcと同じ）
  taxRate: number;    // 0.10 or 0.08（軽減税率対象品）
  category: Category;
  volume?: string;    // 容量表示（任意）
}

export const products: Product[] = [

  // ── スキンケア ─────────────────────────────────────────────
  { code: '0038', name: 'ヤマノ肌 Ｍｕ－２ クレンジングミルク',                              price_fc:  6200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0039', name: 'ヤマノ肌 Ｍｕ－２ フェイシャルクリーム',                            price_fc:  6200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0040', name: 'ヤマノ肌 Ｍｕ－２ コハク エモリエントローション',                   price_fc:  7500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0041', name: 'ヤマノ肌 Ｍｕ－２ モイスチュア ミルクローション',                   price_fc:  7800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0042', name: 'ヤマノ トーニングローション☆',                                       price_fc:  4900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0274', name: 'ＡＷ クレンジングオイル ＷＨ',                                       price_fc:  7500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0275', name: 'ＡＷ ドロンコフェイシャルクリーム ＷＨ',                             price_fc:  7500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0276', name: 'ＡＷ エッセンス ＷＨ',                                               price_fc:  9800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0277', name: 'ＡＷ エマルジョン ＷＨ',                                             price_fc:  9800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0279', name: 'ＡＷ クリーム ＷＨ',                                                 price_fc: 11600, taxRate: 0.10, category: 'スキンケア' },
  { code: '0280', name: 'ＡＷ ドロンコパック ＷＨ',                                           price_fc: 13500, taxRate: 0.10, category: 'スキンケア' },
  { code: '7619', name: '薬用美道 ４点セット',                                                 price_fc: 35800, taxRate: 0.10, category: 'スキンケア' },
  { code: '7620', name: '薬用美道 クレンジングミルク',                                         price_fc: 10000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7621', name: '薬用美道 フェイシャルクリーム',                                       price_fc:  9600, taxRate: 0.10, category: 'スキンケア' },
  { code: '7622', name: '薬用美道 スキンローション',                                           price_fc:  9800, taxRate: 0.10, category: 'スキンケア' },
  { code: '7624', name: '薬用美道 ナリッシングクリスタル',                                     price_fc: 10000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7625', name: '薬用美道 コハクミルクローション',                                     price_fc: 10000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7626', name: '薬用美道 コハククリーム',                                             price_fc: 14300, taxRate: 0.10, category: 'スキンケア' },
  { code: '7628', name: 'コハクマウスウォッシュ',                                              price_fc:  6700, taxRate: 0.10, category: 'スキンケア' },
  { code: '7629', name: 'BIDOU バランスパウダー',                                              price_fc:  5700, taxRate: 0.08, category: 'スキンケア' },
  { code: '7630', name: 'BIDOU KOHAKU・Ｈ',                                                    price_fc: 10800, taxRate: 0.08, category: 'スキンケア' },
  { code: '7638', name: 'BIDOU クレンジングセットB',                                           price_fc: 18000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7639', name: 'BIDOU クレンジングセットG',                                           price_fc: 18000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7640', name: 'BIDOU クレンジングセットH',                                           price_fc: 18000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7641', name: 'BIDOU フェイシャルセットB',                                           price_fc: 17600, taxRate: 0.10, category: 'スキンケア' },
  { code: '7642', name: 'BIDOU フェイシャルセットG',                                           price_fc: 17600, taxRate: 0.10, category: 'スキンケア' },
  { code: '7643', name: 'BIDOU フェイシャルセットH',                                           price_fc: 17600, taxRate: 0.10, category: 'スキンケア' },
  { code: '7644', name: '薬用美道 スキンローション サロン専用 150ml',                          price_fc: 11200, taxRate: 0.10, category: 'スキンケア' },
  { code: '7645', name: '薬用美道 クレンジングミルク サロン専用 150ml',                        price_fc: 11400, taxRate: 0.10, category: 'スキンケア' },
  { code: '0401', name: '琥珀美 Ａローション☆',                                               price_fc: 21000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0515', name: 'モンプレーヌ ドロンコパックセット',                                   price_fc: 16500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0516', name: 'モンプレーヌ モイスチュアライジングドリュー☆',                       price_fc: 12000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0531', name: 'エクセレント Ｌゴールドマッサージジェリー☆',                         price_fc: 13000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0850', name: 'ＭＤプレミアム クレンジングローション☆',                             price_fc:  8100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0851', name: 'ＭＤプレミアム ドロンコフェイシャルクリーム☆',                       price_fc:  9100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0852', name: 'ＭＤプレミアム スキンローション☆',                                   price_fc:  9100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0853', name: 'ＭＤプレミアム ミルクローション☆',                                   price_fc:  9900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0854', name: 'ＭＤプレミアム ナリッシングクリスタル☆',                             price_fc:  9900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0856', name: 'ＭＤプレミアム コハクスキンローションＡ☆',                           price_fc:  9100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0857', name: 'コハクセンチュリーゼロ コハクナノ ネック＆デコルテクリーム',          price_fc:  9900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0858', name: 'コハクセンチュリーゼロ コハクナノ＆ドロンコ クレンジングクリーム',   price_fc: 14400, taxRate: 0.10, category: 'スキンケア' },
  { code: '0859', name: 'コハクセンチュリーゼロ コハクナノ＆ドロンコ フェイシャルクリーム',   price_fc: 14400, taxRate: 0.10, category: 'スキンケア' },
  { code: '0950', name: 'コハクセンチュリーゼロ コハクナノ パーフェクトローションN',           price_fc: 17600, taxRate: 0.10, category: 'スキンケア' },
  { code: '0951', name: 'コハクセンチュリーゼロ コハクナノ セラムミルクN',                    price_fc: 18700, taxRate: 0.10, category: 'スキンケア' },
  { code: '0862', name: 'コハクセンチュリーゼロ コハクナノ リッチクリーム',                   price_fc: 31800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0952', name: 'コハクセンチュリーゼロ ４点スペシャルセット',                         price_fc: 65000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0865', name: 'ヤマノ肌 クレオリ２４ クレンジングクリーム',                         price_fc:  4200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0866', name: 'ヤマノ肌 クレオリ２４ フェイシャルクリーム',                         price_fc:  4200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0867', name: 'ヤマノ肌 クレオリ２４ コハク スキンローション',                      price_fc:  4200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0868', name: 'ヤマノ肌 クレオリ２４ コハク ミルクローション',                      price_fc:  4900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0869', name: 'ヤマノ肌 クレオリ２４ コハク ナリッシングクリスタル',                price_fc:  4900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0870', name: 'ヤマノ肌 クレオリ２４ コハク クリーム',                              price_fc:  5200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0871', name: 'ヤマノ肌 クレオリ２４ ４点セット（ＷＨ）',                           price_fc: 17500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0872', name: 'ヤマノ肌 クレオリ２４ ４点セット（ＢＫ）',                           price_fc: 17500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0874', name: 'コハクセンチュリー白ーSHIROーセット（1ｹ月用）',                     price_fc: 35000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0875', name: 'コハクセンチュリー白ーSHIROーパーフェクトセット',                    price_fc:105000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0303', name: 'ヤマノ肌ドロンコクレー２４ ＷＨ',                                    price_fc:  4500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0304', name: 'ヤマノ肌ドロンコクレー２４ ＢＫ',                                    price_fc:  4500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0321', name: 'ヤマノ肌ドロンコクレー２４ ＷＨ ハーフ',                             price_fc:  2800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0322', name: 'ヤマノ肌ドロンコクレー２４ ＢＫ ハーフ',                             price_fc:  2800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0156', name: 'ワールドドロンコ ドイツ（クリーン＆アクティブ）',                    price_fc:  6600, taxRate: 0.10, category: 'スキンケア' },
  { code: '0157', name: 'ワールドドロンコ ブラジル（モイスト）',                               price_fc:  6600, taxRate: 0.10, category: 'スキンケア' },
  { code: '0138', name: 'CLAYORI24 for men フェイシャルクリーム',                              price_fc:  4100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0139', name: 'CLAYORI24 for men コハクスキンローション',                            price_fc:  4700, taxRate: 0.10, category: 'スキンケア' },
  { code: '0140', name: 'CLAYORI24 for men セット',                                            price_fc:  6980, taxRate: 0.10, category: 'スキンケア' },
  { code: '0216', name: 'ヤマノ フレッシュアップ クレーミスト 24',                             price_fc:  4000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0220', name: 'アイメークアップリムーバーＲＥ',                                      price_fc:  3200, taxRate: 0.10, category: 'スキンケア' },
  { code: '0258', name: 'メイクアップベース',                                                   price_fc:  4000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0234', name: 'ＵＶデイエマルジョンＲ',                                              price_fc: 11000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0237', name: 'サンカットアクアジェリー',                                             price_fc:  4700, taxRate: 0.10, category: 'スキンケア' },
  { code: '6112', name: 'サンカットプルーフRE',                                                 price_fc:  4700, taxRate: 0.10, category: 'スキンケア' },
  { code: '0238', name: 'アイコントゥアジェル 24N',                                            price_fc:  7800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0270', name: 'Ｑ１０ベリークリーム☆',                                              price_fc:  8300, taxRate: 0.10, category: 'スキンケア' },
  { code: '0312', name: 'オプティマα　N☆',                                                    price_fc:  5400, taxRate: 0.10, category: 'スキンケア' },
  { code: '0221', name: 'カームサンテ',                                                          price_fc:  3900, taxRate: 0.10, category: 'スキンケア' },
  { code: '2016', name: 'Ｌ－０６ ローション☆',                                                price_fc:  5500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0330', name: 'モイスチュア２４Ｓ',                                                   price_fc: 12000, taxRate: 0.10, category: 'スキンケア' },
  { code: '0335', name: 'ローションスキンクリアＣ',                                             price_fc:  8100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0337', name: 'モイストナノジェル',                                                    price_fc: 15000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7516', name: 'ヤマノ肌コハクトーニングローション☆',                                 price_fc:  4000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7518', name: 'インナー＆アウタービューティ コハクマッサージクリーム',               price_fc:  4500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0227', name: 'バイタライジングマッサージクリスタル',                                  price_fc:  8100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0261', name: 'バイタライジングマッサージクリームＲ',                                 price_fc:  8100, taxRate: 0.10, category: 'スキンケア' },
  { code: '0222', name: 'ヤマノ リフレッシュナー',                                              price_fc:  3700, taxRate: 0.10, category: 'スキンケア' },
  { code: '0917', name: 'ゼロクラリファイイング Ｌ レギュラーサイズ',                          price_fc:  7900, taxRate: 0.10, category: 'スキンケア' },
  { code: '0918', name: 'ゼロクラリファイイング Ｌ ミディアムサイズ',                          price_fc:  5800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0919', name: 'ゼロクラリファイイング Ｌ ＳＰセット',                               price_fc: 15800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0235', name: 'アクアマスク',                                                          price_fc:  3500, taxRate: 0.10, category: 'スキンケア' },
  { code: 'L149', name: 'キャビア フェイス マスク☆',                                           price_fc:  4000, taxRate: 0.10, category: 'スキンケア' },
  { code: '7551', name: 'インナー＆アウタービューティ 琥珀＆プラセンタマスク',                 price_fc:  5800, taxRate: 0.10, category: 'スキンケア' },
  { code: '7523', name: 'ヤマノ肌 超シンプルスキンケアセット',                                  price_fc:  5900, taxRate: 0.10, category: 'スキンケア' },
  { code: '7524', name: 'ヤマノ肌 ドロンコクレーオリジナル２４ ブラックフェイスソープ',        price_fc:  2500, taxRate: 0.10, category: 'スキンケア' },
  { code: '7525', name: 'ヤマノ肌 琥珀オールインワンジェル',                                   price_fc:  4200, taxRate: 0.10, category: 'スキンケア' },
  { code: '7526', name: 'ヤマノ肌 琥珀パワーエキスオールインワン 美溶液ＥＸ',                 price_fc:  5200, taxRate: 0.10, category: 'スキンケア' },
  { code: '6598', name: 'ヤマノ ＥＧＦセラム KL',                                              price_fc: 13500, taxRate: 0.10, category: 'スキンケア' },
  { code: '0320', name: 'コハク イミュアオイルセラム',                                           price_fc:  4500, taxRate: 0.10, category: 'スキンケア' },
  { code: '6601', name: 'ハリケアローション',                                                    price_fc: 12500, taxRate: 0.10, category: 'スキンケア' },
  { code: '601S', name: 'ハリケアローション（1本）＆専用炭酸ガス（２本）セット',               price_fc: 15500, taxRate: 0.10, category: 'スキンケア' },
  { code: '6500', name: 'コハクソームセラム',                                                    price_fc:  6400, price_bc:  6400, taxRate: 0.10, category: 'スキンケア' },
  { code: '6502', name: 'ソーム+シセアマスク',                                                   price_fc:  9800, price_bc:  9800, taxRate: 0.10, category: 'スキンケア' },
  { code: '0239', name: 'シセアマスク4枚',                                                        price_fc:  3400, price_bc:  3400, taxRate: 0.10, category: 'スキンケア' },
  { code: '0517', name: 'ミュートパウダーパック',                                                  price_fc: 11500, price_bc: 11500, taxRate: 0.10, category: 'スキンケア' },
  { code: 'Y628', name: 'コハクマウスウォッシュ3個セット',                                        price_fc: 20100, price_bc: 20100, taxRate: 0.10, category: 'スキンケア' },

  // ── メイク（システム） ──────────────────────────────────────
  { code: '3086', name: 'ルースパウダー トランスルーセント',                                    price_fc:  6200, taxRate: 0.10, category: 'メイク' },
  { code: '3087', name: 'プレストパウダー トランスルーセント',                                  price_fc:  5600, taxRate: 0.10, category: 'メイク' },
  { code: '3088', name: 'プレストパウダー トランスルーセント レフィル',                        price_fc:  4500, taxRate: 0.10, category: 'メイク' },
  { code: '3171', name: 'パウダリーファンデーション レフィル／ピンク',                          price_fc:  4500, taxRate: 0.10, category: 'メイク' },
  { code: '3172', name: 'パウダリーファンデーション レフィル／ナチュラルベージュ☆',            price_fc:  4500, taxRate: 0.10, category: 'メイク' },
  { code: '3173', name: 'パウダリーファンデーション レフィル／ナチュラルオークル',              price_fc:  4500, taxRate: 0.10, category: 'メイク' },
  { code: '3174', name: 'パウダリーファンデーション レフィル／オークル☆',                      price_fc:  4500, taxRate: 0.10, category: 'メイク' },
  { code: '3175', name: 'パウダリーファンデーションセット／ピンク',                              price_fc:  5700, taxRate: 0.10, category: 'メイク' },
  { code: '3176', name: 'パウダリーファンデーションセット／ナチュラルベージュ',                 price_fc:  5700, taxRate: 0.10, category: 'メイク' },
  { code: '3177', name: 'パウダリーファンデーションセット／ナチュラルオークル',                 price_fc:  5700, taxRate: 0.10, category: 'メイク' },
  { code: '3178', name: 'パウダリーファンデーションセット／オークル',                            price_fc:  5700, taxRate: 0.10, category: 'メイク' },
  { code: '3179', name: 'パウダリーファンデーション レフィル／サンドベージュ☆',                price_fc:  4000, taxRate: 0.10, category: 'メイク' },
  { code: '3191', name: 'リキッドファンデーション／ピンク',                                     price_fc:  5100, taxRate: 0.10, category: 'メイク' },
  { code: '3192', name: 'リキッドファンデーション／ナチュラルベージュ☆',                       price_fc:  5100, taxRate: 0.10, category: 'メイク' },
  { code: '3193', name: 'リキッドファンデーション／ナチュラルオークル',                         price_fc:  5100, taxRate: 0.10, category: 'メイク' },
  { code: '3194', name: 'リキッドファンデーション／オークル☆',                                 price_fc:  5100, taxRate: 0.10, category: 'メイク' },
  { code: '3197', name: 'ヤマノ シドナスコハクプラス ウォーターファンデーションN／ライトベージュ', price_fc:4400, taxRate: 0.10, category: 'メイク' },
  { code: '3198', name: 'ヤマノ シドナスコハクプラス ウォーターファンデーションN／ライトピンク☆', price_fc:4400, taxRate: 0.10, category: 'メイク' },
  { code: '3474', name: 'コハクセンチュリー咲スキントリートメントセット（ピンク）',             price_fc: 27300, taxRate: 0.10, category: 'メイク' },
  { code: '3475', name: 'コハクセンチュリー咲スキントリートメントセット（ベージュ）',           price_fc: 27300, taxRate: 0.10, category: 'メイク' },
  { code: '3476', name: 'コハクセンチュリー咲スキントリートメントセット（オークル）',           price_fc: 27300, taxRate: 0.10, category: 'メイク' },
  { code: '3477', name: 'コハクセンチュリー咲クリームファンデーション（ピンク）',               price_fc: 13800, taxRate: 0.10, category: 'メイク' },
  { code: '3478', name: 'コハクセンチュリー咲クリームファンデーション（ベージュ）',             price_fc: 13800, taxRate: 0.10, category: 'メイク' },
  { code: '3479', name: 'コハクセンチュリー咲クリームファンデーション（オークル）',             price_fc: 13800, taxRate: 0.10, category: 'メイク' },
  { code: '3480', name: 'コハクセンチュリー咲メイクアップパウダーレフィル（ピンク）',          price_fc:  9600, taxRate: 0.10, category: 'メイク' },
  { code: '3481', name: 'コハクセンチュリー咲メイクアップパウダーレフィル（ベージュ）',        price_fc:  9600, taxRate: 0.10, category: 'メイク' },
  { code: '3482', name: 'コハクセンチュリー咲メイクアップパウダーレフィル（オークル）',        price_fc:  9600, taxRate: 0.10, category: 'メイク' },
  { code: '0271', name: 'エッセンス 35 BBクリーム（ピンク）',                                  price_fc:  4200, taxRate: 0.10, category: 'メイク' },
  { code: '0272', name: 'エッセンス 35 BBクリーム（ベージュ）☆',                              price_fc:  4200, taxRate: 0.10, category: 'メイク' },
  { code: '3485', name: 'シドナスｋプラスアイブロウペンシル Ｎ オリーブ',                     price_fc:  3200, taxRate: 0.10, category: 'メイク' },
  { code: '3486', name: 'シドナスＫプラスアイブロウペンシル Ｎ ブラウン',                     price_fc:  3200, taxRate: 0.10, category: 'メイク' },
  { code: '3912', name: 'Ｋ＋ペンシルアイライナー／ブラック',                                  price_fc:  2800, taxRate: 0.10, category: 'メイク' },
  { code: '3913', name: 'Ｋ＋ペンシルアイライナー／ブラウン☆',                                price_fc:  2500, taxRate: 0.10, category: 'メイク' },
  { code: '3981', name: 'Ｋ＋リキッドペンアイライナー／ブラック',                              price_fc:  3400, taxRate: 0.10, category: 'メイク' },
  { code: '3923', name: 'アイシャドウ グレイッシュカーキバリエーション☆',                     price_fc:  4300, taxRate: 0.10, category: 'メイク' },
  { code: '3978', name: 'アイシャドウ ベーシックブラウンバリエーション☆',                     price_fc:  4300, taxRate: 0.10, category: 'メイク' },
  { code: '3979', name: 'アイシャドウ パープルバリエーション☆',                                price_fc:  4300, taxRate: 0.10, category: 'メイク' },
  { code: '3971', name: 'ＣＹ Ｋプラス リップカラー シェルローズ☆',                          price_fc:  3500, taxRate: 0.10, category: 'メイク' },
  { code: '3975', name: 'ＣＹ Ｋプラス リップカラー ダークレッド☆',                          price_fc:  3500, taxRate: 0.10, category: 'メイク' },
  { code: '3976', name: 'ＣＹ Ｋプラス リップカラー スモーキーピンク☆',                      price_fc:  3500, taxRate: 0.10, category: 'メイク' },
  { code: '3977', name: 'ＣＹ Ｋプラス リップカラー オレンジベージュ☆',                      price_fc:  3500, taxRate: 0.10, category: 'メイク' },
  { code: '3974', name: 'ヤマノシドナスＫプラスリップグロスセラムN',                            price_fc:  3200, taxRate: 0.10, category: 'メイク' },
  { code: '3972', name: 'チークカラー（ピンク）',                                               price_fc:  5500, taxRate: 0.10, category: 'メイク' },
  { code: '3973', name: 'チークカラー（オレンジ）',                                             price_fc:  5500, taxRate: 0.10, category: 'メイク' },
  // メイクパフ&ブラシ → 一般カテゴリ
  { code: '3170', name: 'CY コハクプラス パウダリーF コンパクト別売り',                       price_fc:  1800, taxRate: 0.10, category: '一般' },
  { code: '8154', name: 'CY トランスルーセント コンパクト別売り',                              price_fc:  1800, taxRate: 0.10, category: '一般' },
  { code: '8046', name: 'センチュリー咲 コンパクト別売り（袋付き）',                           price_fc:  3900, taxRate: 0.10, category: '一般' },
  { code: '8105', name: 'スポンジ #042R☆',                                                     price_fc:   200, taxRate: 0.10, category: '一般' },
  { code: '8155', name: 'パウダリーＦ パフ（2枚入り）',                                        price_fc:   450, taxRate: 0.10, category: '一般' },
  { code: '8156', name: 'ルースパウダー パフ',                                                  price_fc:   700, taxRate: 0.10, category: '一般' },
  { code: '8157', name: 'プレストパウダー パフ（2枚入り）',                                    price_fc:   850, taxRate: 0.10, category: '一般' },
  { code: '8110', name: 'スポンジ#022Rエッグ☆',                                               price_fc:   200, taxRate: 0.10, category: '一般' },
  { code: '8111', name: 'スポンジパフFA（2枚入り）☆',                                         price_fc:   450, taxRate: 0.10, category: '一般' },
  { code: '8113', name: 'スポンジパフ R（2枚入り）',                                           price_fc:   500, taxRate: 0.10, category: '一般' },
  { code: '8133', name: 'シャドゥブラシ b3☆',                                                  price_fc:  3500, taxRate: 0.10, category: '一般' },
  { code: '8137', name: 'リップブラシ b7☆',                                                    price_fc:  2000, taxRate: 0.10, category: '一般' },
  { code: '8139', name: 'アンダーシャドゥブラシ b9☆',                                         price_fc:  1500, taxRate: 0.10, category: '一般' },
  { code: '810B', name: 'メイクブラシセット',                                                   price_fc: 29500, taxRate: 0.10, category: '一般' },
  { code: '8159', name: 'リップブラシ（リップブラッシュ）☆',                                  price_fc:   700, taxRate: 0.10, category: '一般' },
  { code: '8150', name: 'ボディブラシ☆',                                                        price_fc:  2700, taxRate: 0.10, category: '一般' },

  // ── ボディ＆ヘアケア ────────────────────────────────────────
  { code: '7552', name: 'ヤマノアルコールハンドジェル〈携帯用〉60㎖☆',                        price_fc:   980, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '7553', name: 'ヤマノアルコールハンドジェル〈置き型〉300㎖☆',                       price_fc:  2800, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '6604', name: 'ヤマノ モイスト ボディ ローション☆',                                 price_fc:  3500, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '4016', name: 'ＥＳＳドロンコボディシャンプー',                                      price_fc:  6600, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '4015', name: 'ナチュラルエセンシャルドロンコヘアパック',                              price_fc:  4500, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '7503', name: 'ヤマノ肌ヘアケアセット ２点セット',                                   price_fc:  4200, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '7520', name: 'ヤマノ肌クレーパックヘアソープ',                                       price_fc:  2500, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '7521', name: 'ヤマノ肌コハクリペアモイスチュア コンディショナー',                   price_fc:  2700, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '7522', name: 'ヤマノ肌琥珀プレミアム ヘア美容液',                                   price_fc:  4300, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '7540', name: 'ヤマノ肌ヘアケアセットBOX ３点セット',                                price_fc:  8100, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: 'A753', name: 'ヤマノ肌ヘアケア2点セット（価格シール貼）',                           price_fc:  3000, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: 'A754', name: 'ヤマノ肌ヘアケア3点セット（価格シール貼）',                           price_fc:  6000, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5511', name: '薬用育毛エッセンス 琥珀パワーエキス和漢',                              price_fc:  9100, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5512', name: '薬用和漢スカルプ ドロンコシャンプー',                                  price_fc:  4400, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5513', name: '薬用和漢スカルプ コンディショナー',                                    price_fc:  4400, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5514', name: '薬用和漢スカルプ ヘアケアセット',                                      price_fc:  8000, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5515', name: '薬用育毛エッセンス ３ヶ月実感セット',                                  price_fc: 24900, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5520', name: '薬用和漢スカルプ コンディショナー リッターサイズ☆',                  price_fc:  7500, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5509', name: 'ヘアカラートリートメント和漢',                                          price_fc:  4400, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: '5510', name: 'ヘアカラートリートメント和漢３本セット',                                price_fc: 11500, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: 'A040', name: 'ギフト ＤＦＳ－２（フレグランスソープ２個入り）',                    price_fc:  2900, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: 'A041', name: 'ギフト ＤＦＳ－３（フレグランスソープ３個入り）☆',                  price_fc:  3300, taxRate: 0.10, category: 'ボディ＆ヘアケア' },
  { code: 'A044', name: 'ギフト ＤＦＳ－１（フレグランスソープ１個入り）',                    price_fc:  1350, taxRate: 0.10, category: 'ボディ＆ヘアケア' },

  // ── エステキープ ────────────────────────────────────────────
  { code: '9394', name: 'コハクセンチュリー エステキープセット',                                price_fc: 12000, taxRate: 0.10, category: 'エステキープ' },
  { code: '9395', name: 'コハクセンチュリー エステキープセット（３個セット）',                  price_fc: 30000, taxRate: 0.10, category: 'エステキープ' },
  { code: '9396', name: 'クレオリ 24 エステキープセット（３回分）',                             price_fc:  4500, taxRate: 0.10, category: 'エステキープ' },
  { code: '9397', name: 'Ｍｕ－２ エステキープセット（３回分）',                               price_fc:  5100, taxRate: 0.10, category: 'エステキープ' },
  { code: '9480', name: 'エステキープ用 クレー24 オリジナル ＷＨ',                             price_fc:  1800, taxRate: 0.10, category: 'エステキープ' },
  { code: '9481', name: 'エステキープ用 バイタマッサージクリーム',                               price_fc:  3900, taxRate: 0.10, category: 'エステキープ' },
  { code: '9482', name: 'エステキープ用 バイタマッサージクリスタル',                             price_fc:  3900, taxRate: 0.10, category: 'エステキープ' },
  { code: '9483', name: 'クレオリ 24 エステキープ応援セット Ａ',                                price_fc:  8100, taxRate: 0.10, category: 'エステキープ' },
  { code: '9484', name: 'クレオリ 24 エステキープ応援セット Ｂ',                                price_fc:  8100, taxRate: 0.10, category: 'エステキープ' },
  { code: '9485', name: 'Ｍｕ－２ エステキープ応援セット Ａ',                                  price_fc:  8600, taxRate: 0.10, category: 'エステキープ' },
  { code: '9486', name: 'Ｍｕ－２ エステキープ応援セット Ｂ',                                  price_fc:  8600, taxRate: 0.10, category: 'エステキープ' },

  // ── 健康食品 ────────────────────────────────────────────────
  { code: '6224', name: '田七液',                                                                 price_fc: 15000, taxRate: 0.08, category: '健康食品' },
  { code: '6251', name: 'ヤマノ肌 プラセンタ＆コラーゲン ドリンク',                             price_fc:  7900, taxRate: 0.08, category: '健康食品' },
  { code: '6253', name: 'ヒアルロン酸＆コラーゲン＆ハトムギエキス☆',                           price_fc: 13000, taxRate: 0.08, category: '健康食品' },
  { code: '6254', name: 'グルコサミン＆コンドロイチンＲＥ☆',                                   price_fc:  7600, taxRate: 0.08, category: '健康食品' },
  { code: '6278', name: 'ビタミンＣ＆Ｅ',                                                        price_fc:  7300, taxRate: 0.08, category: '健康食品' },
  { code: '6282', name: 'プロポリスＧ☆',                                                        price_fc: 12000, taxRate: 0.08, category: '健康食品' },
  { code: '6303', name: '琥珀健寿茶 S型（抹茶入り緑茶ブレンド）1ヶ月用（30包入）☆',           price_fc:  4980, taxRate: 0.08, category: '健康食品' },
  { code: '6340', name: '琥珀健寿茶ST型 緑茶ブレンド（30包入）',                               price_fc:  4980, taxRate: 0.08, category: '健康食品' },
  { code: '6341', name: '琥珀健寿茶ＫＴ型 ほうじ茶ブレンド（30包入）',                        price_fc:  4980, taxRate: 0.08, category: '健康食品' },
  { code: '6305', name: '琥珀健寿茶 K型（ほうじ茶ブレンド）1ヶ月用（30包入）☆',              price_fc:  4980, taxRate: 0.08, category: '健康食品' },
  { code: '6315', name: '琥珀健寿イミュアゼリー1箱（30包入）',                                  price_fc:  5500, taxRate: 0.08, category: '健康食品' },
  { code: '6317', name: '琥珀健寿イミュアゼリー3箱（30包入×3）',                               price_fc: 14940, taxRate: 0.08, category: '健康食品' },

  // ── キット ──────────────────────────────────────────────────
  { code: '9030', name: '増客応援キット2020 (B) ①WH ≪クレオリ24ver.≫',                       price_fc: 47400, taxRate: 0.10, category: 'キット' },
  { code: '9031', name: '増客応援キット2020 (B) ②BK ≪クレオリ24ver.≫',                       price_fc: 47400, taxRate: 0.10, category: 'キット' },
  { code: '9032', name: '増客応援キット2020 (B) ③WH・BK≪クレオリ24ver.≫',                    price_fc: 47400, taxRate: 0.10, category: 'キット' },
  { code: '9033', name: '増客応援キット2020 (A) ①WH ≪クレオリ24ver.≫',                       price_fc: 47400, taxRate: 0.10, category: 'キット' },
  { code: '9034', name: '増客応援キット2020 (A) ②BK ≪クレオリ24ver.≫',                       price_fc: 47400, taxRate: 0.10, category: 'キット' },
  { code: '9035', name: '増客応援キット2020 (A) ③WH・BK≪クレオリ24ver.≫',                    price_fc: 47400, taxRate: 0.10, category: 'キット' },
  { code: '035H', name: '①CFキット〈クレオリ＆ゼロクラリ〉',                                  price_fc: 43800, taxRate: 0.10, category: 'キット' },
  { code: '031H', name: '②CFキット〈クレオリ24シリーズ WH3ｹ〉',                              price_fc: 45000, taxRate: 0.10, category: 'キット' },
  { code: '032H', name: '②CFキット〈クレオリ24シリーズ BK3ｹ〉',                              price_fc: 45000, taxRate: 0.10, category: 'キット' },
  { code: '034H', name: '②CFキット〈クレオリ24シリーズ WH2/BK1〉',                           price_fc: 45000, taxRate: 0.10, category: 'キット' },
  { code: '037H', name: '②CFキット〈クレオリ24シリーズ WH1/BK2〉',                           price_fc: 45000, taxRate: 0.10, category: 'キット' },
  { code: '036H', name: '③CFキット〈ゼロクラリSセット3ｹ〉',                                  price_fc: 43200, taxRate: 0.10, category: 'キット' },
  { code: '040H', name: '④CFキット〈Mu-2〉',                                                    price_fc: 59400, taxRate: 0.10, category: 'キット' },
  { code: '033H', name: '⑤CFキット〈ゼロ5点＆ゼロクラリSセット〉',                            price_fc:102400, taxRate: 0.10, category: 'キット' },
  { code: '0902', name: '⑥CFキット〈SHIRO３組セット〉',                                        price_fc:100500, taxRate: 0.10, category: 'キット' },

  // ── CS商品 ───────────────────────────────────────────────────
  { code: '9003', name: 'CS リフレッシュナー 290ml',                                            price_fc:  2100, price_bc:  1900, taxRate: 0.10, category: 'CS商品' },
  { code: '9005', name: 'CSバイタライジングMC R 235g',                                         price_fc:  5000, price_bc:  4600, taxRate: 0.10, category: 'CS商品' },
  { code: '9411', name: 'CS ヤマノ バイタライジング マッサージクリスタル 270g',                price_fc:  5000, price_bc:  4600, taxRate: 0.10, category: 'CS商品' },
  { code: '9043', name: 'CS ヤマノ肌 クレーオリジナル WH 280g',                               price_fc:  1900, price_bc:  1700, taxRate: 0.10, category: 'CS商品' },
  { code: '9044', name: 'CS ヤマノ肌クレーオリジナル BK 370g',                                price_fc:  2200, price_bc:  2000, taxRate: 0.10, category: 'CS商品' },
  { code: '9052', name: 'CS AW ドロンコパック WH 180g',                                        price_fc:  6500, price_bc:  6000, taxRate: 0.10, category: 'CS商品' },
  { code: '9054', name: 'ＣＳコハクセンチュリーゼロコハクナノ クラリファイイングローション 290ml', price_fc: 4400, price_bc:  4000, taxRate: 0.10, category: 'CS商品' },
  { code: '9431', name: 'CS MD P*クレンジングL☆ 290ml',                                       price_fc:  6000, price_bc:  5600, taxRate: 0.10, category: 'CS商品' },
  { code: '9432', name: 'CS MD P*スキンL☆ 290ml',                                             price_fc:  6200, price_bc:  5800, taxRate: 0.10, category: 'CS商品' },
  { code: '9487', name: 'ＣＳ Mu-2ドロンコクレンジングミルク 290ml',                           price_fc:  4000, price_bc:  3600, taxRate: 0.10, category: 'CS商品' },
  { code: '9488', name: 'ＣＳ Mu-2 コハクエモリエントローション 290ml',                       price_fc:  4500, price_bc:  4100, taxRate: 0.10, category: 'CS商品' },
  { code: '9502', name: 'ＣＳ 薬用美道スキンローション 290ml',                                 price_fc:  6200, price_bc:  5800, taxRate: 0.10, category: 'CS商品' },
  { code: '9503', name: 'ＣＳ 薬用美道クレンジング 290ml',                                     price_fc:  6000, price_bc:  5600, taxRate: 0.10, category: 'CS商品' },
  { code: '9504', name: 'ＣＳ 薬用美道ナリッシングクリスタル 300g',                            price_fc:  6200, price_bc:  5800, taxRate: 0.10, category: 'CS商品' },
  { code: '9018', name: 'ヤマノアルコールハンドジェル〈CS用〉500ml',                           price_fc:  2000, price_bc:  1800, taxRate: 0.10, category: 'CS商品' },
  { code: 'A116', name: '琥珀球２ｹセット',                                                      price_fc: 25000,                  taxRate: 0.10, category: 'CS商品' },
  { code: '9057', name: 'オールパーパス マッサージオイル 200ml',                                price_fc:  3500, price_bc:  3400, taxRate: 0.10, category: 'CS商品' },
  { code: '9060', name: 'プラスターオプションセット（3回分）',                                   price_fc:  3500, price_bc:  3200, taxRate: 0.10, category: 'CS商品' },
  { code: '9278', name: 'プラスターオプション 13袋セット',                                       price_fc: 14000, price_bc: 13650, taxRate: 0.10, category: 'CS商品' },
  // CS リネン他
  { code: '9710', name: 'ヤマノオリジナルユニフォーム Ｆ（クリーム）',                         price_fc:  9000, price_bc:  8800, taxRate: 0.10, category: 'CS商品' },
  { code: '9712', name: 'ヤマノオリジナルユニフォーム LL（クリーム）☆',                       price_fc:  9500, price_bc:  9300, taxRate: 0.10, category: 'CS商品' },
  { code: 'D972', name: 'デミ フェイシャルタオルN',                                             price_fc:   290, price_bc:   290, taxRate: 0.10, category: 'CS商品' },
  { code: 'D973', name: 'デミ バスタオルN',                                                      price_fc:  3300, price_bc:  3300, taxRate: 0.10, category: 'CS商品' },
  { code: 'D974', name: 'デミ フィットシーツN',                                                  price_fc:  4300, price_bc:  4300, taxRate: 0.10, category: 'CS商品' },
  { code: 'D975', name: 'デミ タオルケット',                                                     price_fc:  5900, price_bc:  5900, taxRate: 0.10, category: 'CS商品' },
  { code: 'D976', name: 'デミ バスローブN',                                                      price_fc:  5400, price_bc:  5400, taxRate: 0.10, category: 'CS商品' },
  // CS エステ備品&機器
  { code: '9935', name: 'スポンジキット ボディ 10枚入り',                                       price_fc:  4900, price_bc:  4900, taxRate: 0.10, category: 'CS商品' },
  { code: '9939', name: 'スポンジキット（フェイス）10枚入り',                                   price_fc:  4600, price_bc:  4600, taxRate: 0.10, category: 'CS商品' },
  { code: '9894', name: 'CS コットン N（4㎏）',                                                  price_fc: 11200, price_bc: 11200, taxRate: 0.10, category: 'CS商品' },
  { code: '9633', name: 'エステ看板2019',                                                        price_fc:  5500, price_bc:  5500, taxRate: 0.10, category: 'CS商品' },
  { code: '9921', name: 'ヤマノ エステフォーティⅠ・フリマなし',                                price_fc:302100, taxRate: 0.10, category: 'CS商品' },
  { code: '9922', name: 'ヤマノ エステフォーティⅡ',                                            price_fc:349500, taxRate: 0.10, category: 'CS商品' },
  { code: '9161', name: 'フェイシャルベッド・マイティー（クリーム）',                           price_fc: 94000, taxRate: 0.10, category: 'CS商品' },
  { code: '9162', name: 'フェイシャルベッド・マイティー（有孔フタ付・クリーム）',               price_fc:101500, taxRate: 0.10, category: 'CS商品' },
  { code: '9769', name: 'エステスツール 斜楽',                                                   price_fc: 41900, taxRate: 0.10, category: 'CS商品' },
  { code: '9932', name: 'CS エステワゴン N2',                                                    price_fc: 28000, taxRate: 0.10, category: 'CS商品' },
  { code: '9948', name: 'ワゴン T3-A',                                                           price_fc: 28000, taxRate: 0.10, category: 'CS商品' },
  { code: '9949', name: 'CS 小型消毒器',                                                         price_fc: 50400, taxRate: 0.10, category: 'CS商品' },
  { code: '9590', name: 'CS ホットキャビ HC-10F（12～15本用）',                                 price_fc: 32000, taxRate: 0.10, category: 'CS商品' },
  { code: '9628', name: '純水器（タカラ製）',                                                    price_fc:  8900, taxRate: 0.10, category: 'CS商品' },
  { code: '9631', name: '純水器カートリッジ（タカラ製）',                                        price_fc:  5600, taxRate: 0.10, category: 'CS商品' },
  { code: '0999', name: '美顔器タンク洗浄剤',                                                    price_fc:   300, taxRate: 0.10, category: 'CS商品' },
  { code: 'T562', name: 'US-100L用浄水カートリッジ',                                            price_fc: 10360, taxRate: 0.10, category: 'CS商品' },
  // 一般 ヘルスケア&美顔器
  { code: '8020', name: '美水Smart Ⅲ',                                                          price_fc:223300, taxRate: 0.10, category: '一般' },
  { code: '8300', name: 'エアフット マッサージャー（レッグウォーマー付）',                     price_fc:198000, taxRate: 0.10, category: '一般' },
  { code: '8302', name: 'レッグウォーマー３組セット',                                            price_fc: 35800, taxRate: 0.10, category: '一般' },
  { code: '8305', name: 'エアフットマッサージャーＬサイズファスナー',                           price_fc:  4200, taxRate: 0.10, category: '一般' },
  // 一般 琥珀の夢関連
  { code: 'Q325', name: '琥珀の夢Ⅳ 羽毛合掛けふとんS（ピンク）',                              price_fc:240000, taxRate: 0.10, category: '一般' },
  { code: 'Q326', name: '琥珀の夢Ⅳ 羽毛合掛けふとんS（ブルー）',                              price_fc:240000, taxRate: 0.10, category: '一般' },
  { code: 'Q327', name: '琥珀の夢Ⅳ 羽毛合掛けふとんD（ピンク）',                              price_fc:300000, taxRate: 0.10, category: '一般' },
  { code: 'Q328', name: '琥珀の夢Ⅳ 羽毛合掛けふとんD（ブルー）',                              price_fc:300000, taxRate: 0.10, category: '一般' },
  { code: 'Q329', name: '琥珀の夢Ⅳ 体圧分散敷ふとんS（ピンク）',                              price_fc:210000, taxRate: 0.10, category: '一般' },
  { code: 'Q330', name: '琥珀の夢Ⅳ 体圧分散敷ふとんS（ブルー）',                              price_fc:210000, taxRate: 0.10, category: '一般' },
  { code: 'Q331', name: '琥珀の夢Ⅳ 体圧分散敷ふとんD（ピンク）',                              price_fc:270000, taxRate: 0.10, category: '一般' },
  { code: 'Q332', name: '琥珀の夢Ⅳ 体圧分散敷ふとんD（ブルー）',                              price_fc:270000, taxRate: 0.10, category: '一般' },
  { code: 'Q333', name: '琥珀の夢Ⅳ カセット肌掛けふとんS（ピンク）',                          price_fc:280000, taxRate: 0.10, category: '一般' },
  { code: 'Q334', name: '琥珀の夢Ⅳ カセット肌掛けふとんS（ブルー）',                          price_fc:280000, taxRate: 0.10, category: '一般' },
  { code: 'Q335', name: '琥珀の夢Ⅳ カセット肌掛けふとんD（ピンク）',                          price_fc:360000, taxRate: 0.10, category: '一般' },
  { code: 'Q336', name: '琥珀の夢Ⅳ カセット肌掛けふとんD（ブルー）',                          price_fc:360000, taxRate: 0.10, category: '一般' },
  { code: 'Q337', name: '琥珀の夢Ⅳ シングルセット羽毛合掛+体圧分散敷（ピンク）',              price_fc:420000, taxRate: 0.10, category: '一般' },
  { code: 'Q338', name: '琥珀の夢Ⅳ シングルセット羽毛合掛+体圧分散敷（ブルー）',              price_fc:420000, taxRate: 0.10, category: '一般' },
  { code: 'Q339', name: '琥珀の夢Ⅳ ダブルセット羽毛合掛+体圧分散敷（ピンク）',               price_fc:540000, taxRate: 0.10, category: '一般' },
  { code: 'Q340', name: '琥珀の夢Ⅳ ダブルセット羽毛合掛+体圧分散敷（ブルー）',               price_fc:540000, taxRate: 0.10, category: '一般' },
  { code: 'Q341', name: '琥珀の夢Ⅳ 涼感ケットS',                                               price_fc:158500, taxRate: 0.10, category: '一般' },
  { code: 'Q342', name: '琥珀の夢Ⅳ 涼感ケットD',                                               price_fc:208500, taxRate: 0.10, category: '一般' },
  { code: 'Q343', name: '琥珀の夢Ⅳ 涼感敷パットS',                                             price_fc:133500, taxRate: 0.10, category: '一般' },
  { code: 'Q344', name: '琥珀の夢Ⅳ 涼感敷パットD',                                             price_fc:192000, taxRate: 0.10, category: '一般' },
  { code: 'Q345', name: '琥珀の夢Ⅳ 涼感シングルセット',                                        price_fc:275000, taxRate: 0.10, category: '一般' },
  { code: 'Q346', name: '琥珀の夢Ⅳ 涼感ダブルセット',                                         price_fc:375000, taxRate: 0.10, category: '一般' },
  { code: 'Q501', name: 'シングルふとんカバー 掛け敷きセット',                                  price_fc: 70000, taxRate: 0.10, category: '一般' },
  { code: 'Q502', name: 'ダブルふとんカバー 掛け敷きセット',                                    price_fc: 95000, taxRate: 0.10, category: '一般' },
  { code: 'Q503', name: 'シングルふとんカバー掛け',                                              price_fc: 38000, taxRate: 0.10, category: '一般' },
  { code: 'Q504', name: 'シングルふとんカバー敷き',                                              price_fc: 32000, taxRate: 0.10, category: '一般' },
  { code: 'Q505', name: 'ダブルふとんカバー掛け',                                                price_fc: 50000, taxRate: 0.10, category: '一般' },
  { code: 'Q506', name: 'ダブルふとんカバー敷き',                                                price_fc: 45000, taxRate: 0.10, category: '一般' },
  { code: 'Q500', name: '琥珀の夢Ⅲピロー（高さ調節フリー枕）',                                 price_fc: 48000, taxRate: 0.10, category: '一般' },
  { code: 'Q516', name: '琥珀の夢Ⅲピローカバー',                                                price_fc:  4500, taxRate: 0.10, category: '一般' },
  { code: '8244', name: '琥珀の夢シート',                                                        price_fc:  1000, taxRate: 0.10, category: '一般' },
  { code: '8344', name: '琥珀の夢シートカバー',                                                  price_fc:  1000, taxRate: 0.10, category: '一般' },
  { code: 'Q889', name: 'ブランケット ダブル☆',                                                 price_fc:120000, taxRate: 0.10, category: '一般' },
  { code: 'Q901', name: 'サロン用琥珀の夢Ⅱ上掛けふとん☆',                                    price_fc:120000, taxRate: 0.10, category: '一般' },
  { code: 'Q902', name: 'サロン用琥珀の夢Ⅱベッドパット☆',                                    price_fc: 60000, taxRate: 0.10, category: '一般' },
  { code: 'Q903', name: 'サロン用琥珀の夢Ⅱセット☆',                                          price_fc:160000, taxRate: 0.10, category: '一般' },
  // 一般 各種パーツ
  { code: '8202', name: '真空管 丸型',                                                           price_fc:  2500, taxRate: 0.10, category: '一般' },
  { code: '8228', name: 'スプーンガラスカン（N)',                                                price_fc:  3700, taxRate: 0.10, category: '一般' },
  { code: '8203', name: '真空管 クシ型',                                                         price_fc:  3100, taxRate: 0.10, category: '一般' },
  { code: '8215', name: 'クリーナー ゴム S',                                                     price_fc:   900, taxRate: 0.10, category: '一般' },
  { code: '8222', name: 'サクションマッサージセット',                                             price_fc:  2400, taxRate: 0.10, category: '一般' },
  { code: '8223', name: 'サクションマッサージ ガラス管',                                         price_fc:  1600, taxRate: 0.10, category: '一般' },
  { code: '8224', name: 'クリーナー チューブセット',                                             price_fc:  2500, taxRate: 0.10, category: '一般' },
  { code: '8225', name: 'トーニング リードセット',                                               price_fc:  4200, taxRate: 0.10, category: '一般' },
  { code: '8226', name: 'スチームコイル(N)',                                                      price_fc:  2900, taxRate: 0.10, category: '一般' },
  { code: 'Y017', name: 'プチシルマ替プラスター',                                                price_fc:  2500, taxRate: 0.10, category: '一般' },
  { code: '9080', name: 'SUIカートリッジ',                                                        price_fc: 13000, price_bc: 13000, taxRate: 0.10, category: 'CS商品' },

  // ── 販促品 ───────────────────────────────────────────────────
  { code: '9813', name: 'セルセル 30',                                                           price_fc:   110, price_bc:   110, taxRate: 0.10, category: '販促品' },
  { code: '9816', name: 'セルセル 70',                                                           price_fc:   170, price_bc:   170, taxRate: 0.10, category: '販促品' },
  { code: '9897', name: 'ポケットティッシュ',                                                    price_fc:     7, price_bc:     7, taxRate: 0.08, category: '販促品' },
  { code: 'AA01', name: 'オリジナル コットン',                                                   price_fc:    60, price_bc:    60, taxRate: 0.10, category: '販促品' },
  { code: 'H600', name: 'ＡＷトライアル６点セット☆',                                           price_fc:  1700, price_bc:  1600, taxRate: 0.10, category: '販促品' },
  { code: 'Y833', name: 'ゼロ5点パウチセット',                                                   price_fc:   500, price_bc:   480, taxRate: 0.10, category: '販促品' },
  { code: 'Y930', name: 'ゼロクラリファイイング パウチ１０枚',                                  price_fc:   500, price_bc:   450, taxRate: 0.10, category: '販促品' },
  { code: 'H507', name: 'クレオリ24ＷＨ・ＢＫセット15ｇ',                                      price_fc:   320, price_bc:   300, taxRate: 0.10, category: '販促品' },
  { code: '9201', name: 'ミニ モイスチュアハンドC',                                              price_fc:   200, taxRate: 0.10, category: '販促品' },
  { code: '9920', name: 'ヤマノサロンプロミス マスクケース 30枚',                               price_fc:   750, taxRate: 0.10, category: '販促品' },
  { code: '9717', name: 'どろんこ実験（粉）',                                                    price_fc:   777, taxRate: 0.10, category: '販促品' },
  { code: '9718', name: 'どろんこ実験（液）',                                                    price_fc:   777, taxRate: 0.10, category: '販促品' },
  { code: 'Y270', name: 'コンセプトブック2023年度版',                                            price_fc:   190, taxRate: 0.10, category: '販促品' },
  { code: 'Y268', name: '販促品カタログ',                                                        price_fc:    25, taxRate: 0.10, category: '販促品' },
  { code: 'Y793', name: '美道５原則ポスター',                                                    price_fc:   900, taxRate: 0.10, category: '販促品' },
  { code: 'Y834', name: 'ゼロブランドブック',                                                    price_fc:   240, taxRate: 0.10, category: '販促品' },
  { code: 'Y866', name: 'クレオリ24シリーズフライヤー（500枚）',                                price_fc:  1000, taxRate: 0.10, category: '販促品' },
  { code: 'Y868', name: '咲ブランドブック',                                                      price_fc:   170, taxRate: 0.10, category: '販促品' },
  { code: 'Y871', name: '黒どろ白どろフライヤー',                                                price_fc:     5, taxRate: 0.10, category: '販促品' },
  { code: 'Y903', name: 'クレオリ版 サロン誘客フライヤー（100枚）',                             price_fc:   400, taxRate: 0.10, category: '販促品' },
  { code: 'Y882', name: 'SHIROブランドブック',                                                   price_fc:   200, taxRate: 0.10, category: '販促品' },
  { code: 'Y507', name: '天空の眠りポスター（大）',                                              price_fc:   500, taxRate: 0.10, category: '販促品' },
  { code: 'Y509', name: '天空の眠りポスター（小）',                                              price_fc:   400, taxRate: 0.10, category: '販促品' },
  { code: 'Y506', name: 'BIDOU ポスター（大）',                                                  price_fc:   500, taxRate: 0.10, category: '販促品' },
  { code: 'Y508', name: 'BIDOU ポスター（小）',                                                  price_fc:   400, taxRate: 0.10, category: '販促品' },
  { code: 'E030', name: '2021開業パンフレット',                                                  price_fc:    25, taxRate: 0.10, category: '販促品' },
  { code: 'E061', name: 'フェイシャル専科教室パンフレット',                                      price_fc:    25, taxRate: 0.10, category: '販促品' },
  { code: 'E062', name: 'フェイシャル専科教室 生徒募集チラシ０８',                              price_fc:     3, taxRate: 0.10, category: '販促品' },
  { code: '9128', name: 'yamanoオリジナル手提袋（アカ大）',                                     price_fc:   150, price_bc:   140, taxRate: 0.10, category: '販促品' },
  { code: '9129', name: 'yamanoオリジナル手提袋（アカ小）',                                     price_fc:   100, price_bc:    90, taxRate: 0.10, category: '販促品' },
  { code: '9130', name: 'yamanoオリジナル紙袋（アカ大）',                                       price_fc:    25, price_bc:    24, taxRate: 0.10, category: '販促品' },
  { code: '9131', name: 'yamanoオリジナル紙袋（アカ小）',                                       price_fc:    14, price_bc:    13, taxRate: 0.10, category: '販促品' },
  { code: '9135', name: 'ＳＨＩＲＯ手提袋',                                                     price_fc:   200, price_bc:   180, taxRate: 0.10, category: '販促品' },
  { code: '9819', name: '包装紙（ダイ）B2',                                                      price_fc:    30, taxRate: 0.10, category: '販促品' },
  { code: '9820', name: '包装紙（ショウ）B3',                                                    price_fc:    18, taxRate: 0.10, category: '販促品' },
  { code: '981H', name: '包装紙仏（ダイ）B2グレー',                                             price_fc:    30, taxRate: 0.10, category: '販促品' },
  { code: '982H', name: '包装紙仏（ショウ）B3グレー',                                           price_fc:    18, taxRate: 0.10, category: '販促品' },
  { code: '9895', name: 'ギフト シール',                                                         price_fc:    24, taxRate: 0.10, category: '販促品' },
  // 販促備品
  { code: '9903', name: 'ポイント用 納品書',                                                     price_fc:   161, taxRate: 0.10, category: '販促品' },
  { code: '9904', name: 'FLC スタンプカード',                                                    price_fc:     5, taxRate: 0.10, category: '販促品' },
  { code: '9997', name: 'お買い上げ台帳Ｎ',                                                      price_fc:     5, taxRate: 0.10, category: '販促品' },
  { code: '9998', name: '施術帳Ｎ',                                                              price_fc:     5, taxRate: 0.10, category: '販促品' },
  { code: 'Y208', name: 'フェイシャルカルテ2012',                                                price_fc:    18, taxRate: 0.10, category: '販促品' },
  { code: '9576', name: '美容小切手',                                                            price_fc:     4, taxRate: 0.10, category: '販促品' },
  { code: '9810', name: 'コンサル伝票（商品お買い上げ伝票）',                                   price_fc:   258, taxRate: 0.10, category: '販促品' },
  { code: '9811', name: '統一領収証',                                                            price_fc:   150, taxRate: 0.10, category: '販促品' },
  { code: '9882', name: 'インプリンター（クレジット売上伝票用）',                                price_fc:  2428, taxRate: 0.10, category: '販促品' },
  // 機器備品
  { code: '9450', name: '専用炭酸ガスカートリッジ〈5本〉',                                      price_fc:  6500, price_bc:  6000, taxRate: 0.10, category: '販促品' },
  { code: 'M011', name: '炭酸ミスト吸上げチューブ☆',                                           price_fc:    60, price_bc:    60, taxRate: 0.10, category: '販促品' },
  { code: 'M006', name: '炭酸ミストエアホース☆',                                                price_fc:   960, price_bc:   940, taxRate: 0.10, category: '販促品' },
  { code: 'M007', name: '炭酸ミストローションボトル（2個入）☆',                                 price_fc:  2800, price_bc:  2750, taxRate: 0.10, category: '販促品' },
  { code: 'M008', name: '炭酸ミストクリーンワイヤー☆',                                         price_fc:   160, price_bc:   160, taxRate: 0.10, category: '販促品' },
  { code: 'Y161', name: '美水素パンフ',                                                          price_fc:     0, price_bc:     0, taxRate: 0.10, category: '販促品' },
  { code: '9199', name: '美水素用クエン酸',                                                      price_fc:  1500, price_bc:  1450, taxRate: 0.10, category: '販促品' },
  { code: '8001', name: 'EP ボーテACアダプター☆',                                              price_fc:  1200, price_bc:  1200, taxRate: 0.10, category: '販促品' },
  { code: 'B550', name: 'ソニックボーテ アースグリップ',                                        price_fc:  2000, price_bc:  1700, taxRate: 0.10, category: '販促品' },
  { code: 'B551', name: 'ソニックボーテ コットンカバー',                                        price_fc:   220, price_bc:   200, taxRate: 0.10, category: '販促品' },
  { code: '6620', name: 'クレンズボーテ USB',                                                   price_fc:  1000, price_bc:  1000, taxRate: 0.10, category: '販促品' },
  { code: '6621', name: 'クレンズボーテ アダプター',                                            price_fc:   500, price_bc:   500, taxRate: 0.10, category: '販促品' },
  { code: '973R', name: '美水（ビスイ）カートリッジ（マイクロカーボンタイプ）トリム',          price_fc: 12000, price_bc: 10900, taxRate: 0.10, category: '販促品' },
  { code: '9006', name: '美水（ビスイ）Ⅱ カートリッジ',                                        price_fc: 12700, price_bc: 11500, taxRate: 0.10, category: '販促品' },
  { code: '9070', name: '美水SmartⅢ 交換用カートリッジ トリム',                                price_fc:  7600, price_bc:  7100, taxRate: 0.10, category: '販促品' },
];

export async function fetchProducts(centerType: string): Promise<Product[]> {
  const res = await fetch(
    `/api/products.php?center_type=${encodeURIComponent(centerType)}`
  );
  if (!res.ok) throw new Error('商品データの取得に失敗しました');
  const data: Array<{
    code: string;
    name: string;
    category: string;
    price_fc: number;
    price_bc: number;
    tax_rate: number;
  }> = await res.json();
  return data.map((item) => ({
    code: item.code,
    name: item.name,
    category: item.category as Category,
    price_fc: item.price_fc,
    price_bc: item.price_bc,
    taxRate: item.tax_rate,
  }));
}

export function searchProducts(query: string, category?: Category): Product[] {
  const q = query.trim().toLowerCase();
  return products.filter((p) => {
    if (category && p.category !== category) return false;
    if (!q) return true;
    return (
      p.name.toLowerCase().includes(q) ||
      p.code.toLowerCase().includes(q)
    );
  });
}

export function formatPrice(n: number): string {
  return n.toLocaleString('ja-JP');
}

export function getPrice(product: Product, centerType: string): number {
  if (centerType === 'BC' && product.price_bc !== undefined) {
    return product.price_bc;
  }
  return product.price_fc;
}

export function calcTax(price: number, qty: number, taxRate: number): number {
  return Math.floor(price * qty * taxRate);
}
