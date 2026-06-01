export type Category = 'スキンケア' | 'ヘアケア' | '健康食品' | 'メイク' | 'その他';

export interface Product {
  code: string;
  name: string;
  price: number;
  category: Category;
  taxRate: 0.08 | 0.10;
  volume?: string;
}

export const CATEGORIES: Category[] = ['スキンケア', 'ヘアケア', '健康食品', 'メイク', 'その他'];

export const products: Product[] = [
  // ── スキンケア ──────────────────────────────────────────────────────────
  { code: '20',   name: 'キャビアフェイスマスク(ゴールド)',                          price:  4000, category: 'スキンケア', taxRate: 0.10 },
  { code: '21',   name: 'キャビアフェイスマスク(ゴールド)5個セット',                 price: 20000, category: 'スキンケア', taxRate: 0.10 },
  { code: '38',   name: 'ヤマノ肌 Mu-2 ドロンコ クレンジングミルク',               price:  6200, category: 'スキンケア', taxRate: 0.10, volume: '130ml' },
  { code: '39',   name: 'ヤマノ肌 Mu-2 ドロンコ フェイシャルクリーム',             price:  6200, category: 'スキンケア', taxRate: 0.10, volume: '100ml' },
  { code: '40',   name: 'ヤマノ肌 Mu-2 KO・HA・KU エモリエントローション',         price:  7500, category: 'スキンケア', taxRate: 0.10, volume: '130ml' },
  { code: '41',   name: 'ヤマノ肌 Mu-2 KO・HA・KU モイスチュア ミルクローション',  price:  7800, category: 'スキンケア', taxRate: 0.10, volume: '85ml' },
  { code: '42',   name: 'ヤマノ トーニングローション',                              price:  4900, category: 'スキンケア', taxRate: 0.10 },
  { code: '138',  name: 'for men フェイシャルC',                                   price:  4100, category: 'スキンケア', taxRate: 0.10, volume: '100ml' },
  { code: '139',  name: 'for men KO・HA・KU スキンL',                             price:  4700, category: 'スキンケア', taxRate: 0.10, volume: '130ml' },
  { code: '140',  name: 'for men セット',                                          price:  6980, category: 'スキンケア', taxRate: 0.10 },
  { code: '156',  name: 'ワールドパック ドイツ（クリーン&アクティブ）',             price:  6600, category: 'スキンケア', taxRate: 0.10, volume: '255ml' },
  { code: '157',  name: 'ワールドパック ブラジル（モイスト）',                      price:  6600, category: 'スキンケア', taxRate: 0.10, volume: '215ml' },
  { code: '216',  name: 'ヤマノ フレッシュアップ ドロンコミスト 24',               price:  4000, category: 'スキンケア', taxRate: 0.10, volume: '55ml' },
  { code: '221',  name: 'カームサンテ',                                             price:  3900, category: 'スキンケア', taxRate: 0.10 },
  { code: '222',  name: 'ヤマノ リフレッシュナー',                                 price:  3700, category: 'スキンケア', taxRate: 0.10 },
  { code: '227',  name: 'ヤマノVIT マッサージクリスタル',                          price:  8100, category: 'スキンケア', taxRate: 0.10 },
  { code: '234',  name: 'UVデイエマルジョンR',                                     price: 11000, category: 'スキンケア', taxRate: 0.10 },
  { code: '235',  name: 'アクアマスク',                                             price:  3500, category: 'スキンケア', taxRate: 0.10 },
  { code: '237',  name: 'サンカットアクアジェリー',                                price:  4700, category: 'スキンケア', taxRate: 0.10 },
  { code: '239',  name: 'シセア フェイス マスク',                                  price:  3400, category: 'スキンケア', taxRate: 0.10 },
  { code: '261',  name: 'ヤマノVIT マッサージクリームR',                          price:  8100, category: 'スキンケア', taxRate: 0.10 },
  { code: '274',  name: 'AW クレンジングオイル WH',                               price:  7500, category: 'スキンケア', taxRate: 0.10 },
  { code: '275',  name: 'AW ドロンコフェイシャルクリーム WH',                     price:  7500, category: 'スキンケア', taxRate: 0.10 },
  { code: '276',  name: 'AW エッセンス WH',                                       price:  9800, category: 'スキンケア', taxRate: 0.10 },
  { code: '277',  name: 'AW エマルジョン WH',                                     price:  9800, category: 'スキンケア', taxRate: 0.10 },
  { code: '279',  name: 'AW クリーム WH',                                         price: 11600, category: 'スキンケア', taxRate: 0.10 },
  { code: '280',  name: 'AW ドロンコパック WH',                                   price: 13700, category: 'スキンケア', taxRate: 0.10 },
  { code: '303',  name: 'ヤマノ肌ドロンコクレー24 WH',                            price:  4500, category: 'スキンケア', taxRate: 0.10 },
  { code: '304',  name: 'ヤマノ肌ドロンコクレー24 BK',                            price:  4500, category: 'スキンケア', taxRate: 0.10 },
  { code: '313',  name: 'キャンペーン特別ドロンコクレー24 オリジナルWH',           price:  3700, category: 'スキンケア', taxRate: 0.10 },
  { code: '314',  name: 'キャンペーン特別ドロンコクレー24 オリジナルBK',           price:  3700, category: 'スキンケア', taxRate: 0.10 },
  { code: '320',  name: 'ヤマノ KO・HA・KU イミュア オイルセラム',                price:  5300, category: 'スキンケア', taxRate: 0.10, volume: '30ml' },
  { code: '321',  name: 'ヤマノ肌ドロンコクレー24 WH ハーフ',                    price:  2800, category: 'スキンケア', taxRate: 0.10 },
  { code: '322',  name: 'ヤマノ肌ドロンコクレー24 BK ハーフ',                    price:  2800, category: 'スキンケア', taxRate: 0.10 },
  { code: '330',  name: 'モイスチュア24S',                                         price: 12000, category: 'スキンケア', taxRate: 0.10 },
  { code: '335',  name: 'ローションスキンクリアC',                                 price:  8100, category: 'スキンケア', taxRate: 0.10 },
  { code: '337',  name: 'モイストナノジェル',                                      price: 15000, category: 'スキンケア', taxRate: 0.10 },
  { code: '515',  name: 'モンプレーヌ ドロンコパックセット',                       price: 16500, category: 'スキンケア', taxRate: 0.10 },
  { code: '812',  name: '期間限定チューブ ゼロ フェイシャルC',                    price: 12000, category: 'スキンケア', taxRate: 0.10 },
  { code: '850',  name: 'MDプレミアム クレンジングローション',                     price:  8100, category: 'スキンケア', taxRate: 0.10 },
  { code: '851',  name: 'ヤマノ肌 ゼロ NK Pローション',                           price: 17600, category: 'スキンケア', taxRate: 0.10 },
  { code: '852',  name: 'ヤマノ肌 ゼロ NK セラムミルク',                          price: 18700, category: 'スキンケア', taxRate: 0.10 },
  { code: '857',  name: 'コハクセンチュリーゼロ ネック&デコルテクリーム',          price:  9900, category: 'スキンケア', taxRate: 0.10 },
  { code: '858',  name: 'コハクセンチュリーゼロ ドロンコ クレンジングクリーム',    price: 14400, category: 'スキンケア', taxRate: 0.10 },
  { code: '865',  name: 'ヤマノ肌 クレオリ24 クレンジングクリーム',               price:  4200, category: 'スキンケア', taxRate: 0.10 },
  { code: '866',  name: 'ヤマノ肌 クレオリ24 フェイシャルクリーム',               price:  4200, category: 'スキンケア', taxRate: 0.10 },
  { code: '867',  name: 'ヤマノ肌 クレオリ24 KO・HA・KU スキンローション',        price:  4200, category: 'スキンケア', taxRate: 0.10 },
  { code: '868',  name: 'ヤマノ肌 クレオリ24 KO・HA・KU ミルクローション',        price:  4900, category: 'スキンケア', taxRate: 0.10 },
  { code: '869',  name: 'ヤマノ肌 クレオリ24 KO・HA・KU ナリクリ',               price:  4900, category: 'スキンケア', taxRate: 0.10 },
  { code: '870',  name: 'ヤマノ肌 クレオリ24 KO・HA・KU クリーム',               price:  5200, category: 'スキンケア', taxRate: 0.10 },
  { code: '871',  name: 'ヤマノ肌 クレオリ24 4点セット（WH）',                   price: 17500, category: 'スキンケア', taxRate: 0.10 },
  { code: '872',  name: 'ヤマノ肌 クレオリ24 4点セット（BK）',                   price: 17500, category: 'スキンケア', taxRate: 0.10 },
  { code: '874',  name: 'コハクセンチュリー白-SHIRO-セット（1ヶ月用）',           price: 35000, category: 'スキンケア', taxRate: 0.10 },
  { code: '875',  name: 'コハクセンチュリー白-SHIRO-パーフェクトセット',           price:105000, category: 'スキンケア', taxRate: 0.10 },
  { code: '917',  name: 'ゼロクラリファイイングローション レギュラーサイズ',       price:  7900, category: 'スキンケア', taxRate: 0.10 },
  { code: '918',  name: 'ゼロクラリファイイングローション ミディアムサイズ',       price:  5800, category: 'スキンケア', taxRate: 0.10 },
  { code: '919',  name: 'ゼロクラリファイイングローション スペシャルセット',       price: 15800, category: 'スキンケア', taxRate: 0.10 },
  { code: '950',  name: 'コハクセンチュリーゼロ ローションN',                      price: 17600, category: 'スキンケア', taxRate: 0.10 },
  { code: '951',  name: 'コハクセンチュリーゼロ セラムミルクN',                    price: 18700, category: 'スキンケア', taxRate: 0.10 },
  { code: '952',  name: 'コハクセンチュリーゼロ 4点スペシャルセットN',             price: 65000, category: 'スキンケア', taxRate: 0.10 },
  { code: '6112', name: 'サンカットプルーフRE',                                    price:  4700, category: 'スキンケア', taxRate: 0.10 },
  { code: '6500', name: 'コハクソーム Kボタニカルセラム',                          price:  6400, category: 'スキンケア', taxRate: 0.10 },
  { code: '6598', name: 'ヤマノ EGFセラム KL',                                    price: 13500, category: 'スキンケア', taxRate: 0.10 },
  { code: '6601', name: 'ハリケアローション',                                      price: 12500, category: 'スキンケア', taxRate: 0.10 },
  { code: '6604', name: 'ヤマノ モイスト ボディ ローション',                       price:  3500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7518', name: 'インナー&アウタービューティ KO・HA・KU マッサージクリーム', price: 4500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7523', name: 'ヤマノ肌 超シンプルスキンケアセット',                     price:  5900, category: 'スキンケア', taxRate: 0.10 },
  { code: '7524', name: 'ヤマノ肌 ドロンコクレーオリジナル24 ブラックフェイスソープ', price: 2500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7525', name: 'KO・HA・KU オールインワンジェル',                        price:  4200, category: 'スキンケア', taxRate: 0.10 },
  { code: '7526', name: 'KO・HA・KU パワーエキスオールインワン 美溶液EX',          price:  5200, category: 'スキンケア', taxRate: 0.10 },
  { code: '7551', name: 'インナー&アウタービューティ KO・HA・KU マスク',           price:  5800, category: 'スキンケア', taxRate: 0.10 },
  { code: '7611', name: '美道 ドロンコクレンジングクリーム',                       price: 10500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7612', name: '美道 ドロンコフェイシャルクリーム',                       price: 11500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7613', name: '美道 コハクスキンローション',                             price: 12500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7614', name: '美道 コハクミルクローション',                             price: 13500, category: 'スキンケア', taxRate: 0.10 },
  { code: '7619', name: '薬用美道 4点セット',                                      price: 39400, category: 'スキンケア', taxRate: 0.10 },
  { code: '7620', name: '薬用美道 ドロンコ クレンジングミルク',                    price: 10000, category: 'スキンケア', taxRate: 0.10 },
  { code: '7621', name: '薬用美道 ドロンコ フェイシャルクリーム',                  price:  9600, category: 'スキンケア', taxRate: 0.10 },
  { code: '7622', name: '薬用美道 KO・HA・KU スキンローション',                   price:  9800, category: 'スキンケア', taxRate: 0.10 },
  { code: '7624', name: '薬用美道 KO・HA・KU ナリクリ',                           price: 10000, category: 'スキンケア', taxRate: 0.10 },
  { code: '7625', name: '薬用美道 KO・HA・KU コハクミルクローション',              price: 10000, category: 'スキンケア', taxRate: 0.10 },
  { code: '7626', name: '薬用美道 KO・HA・KU コハククリーム',                      price: 14300, category: 'スキンケア', taxRate: 0.10 },
  { code: '7646', name: '美道MDスキンローション',                                  price:  8800, category: 'スキンケア', taxRate: 0.10 },
  { code: '7647', name: '美道MDミルクローション',                                  price:  9000, category: 'スキンケア', taxRate: 0.10 },

  // ── ヘアケア ────────────────────────────────────────────────────────────
  { code: '4015', name: 'ナチュラルエセンシャルドロンコヘアパック',                price:  4500, category: 'ヘアケア', taxRate: 0.10 },
  { code: '4016', name: 'ESSドロンコボディシャンプー',                             price:  6600, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5509', name: '和漢ヘアカラートリートメント',                            price:  4400, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5510', name: '和漢ヘアカラートリートメント3本セット',                   price: 11500, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5511', name: '薬用和漢 育毛エッセンス',                                 price:  9100, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5512', name: '薬用和漢スカルプ ドロンコシャンプー',                    price:  4400, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5513', name: '薬用和漢スカルプ コンディショナー',                       price:  4400, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5514', name: '薬用和漢スカルプ ヘアケアセット',                         price:  8000, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5515', name: '薬用和漢 育毛エッセンス 3ヶ月実感セット',                price: 24900, category: 'ヘアケア', taxRate: 0.10 },
  { code: '5520', name: '薬用和漢スカルプ コンディショナー リッターサイズ',        price:  7500, category: 'ヘアケア', taxRate: 0.10 },
  { code: '7503', name: 'ヤマノ肌ヘアケアセット 2点セット',                        price:  4200, category: 'ヘアケア', taxRate: 0.10 },
  { code: '7520', name: 'ヤマノ肌ドロンコ ヘアソープ',                            price:  2500, category: 'ヘアケア', taxRate: 0.10 },
  { code: '7521', name: 'ヤマノ肌モイスチュア コンディショナー',                   price:  2700, category: 'ヘアケア', taxRate: 0.10 },
  { code: '7522', name: 'ヤマノ肌琥珀プレミアム ヘア美容液',                      price:  4300, category: 'ヘアケア', taxRate: 0.10 },
  { code: '7540', name: 'ヤマノ肌ヘアケアセットBOX 3点セット',                    price:  8100, category: 'ヘアケア', taxRate: 0.10 },

  // ── 健康食品 ────────────────────────────────────────────────────────────
  { code: '6224', name: '田七液',                                                   price: 15000, category: '健康食品', taxRate: 0.08 },
  { code: '6251', name: 'ヤマノ肌 プラセンタ&コラーゲン ドリンク',                price:  7900, category: '健康食品', taxRate: 0.08 },
  { code: '6253', name: 'ヒアルロン酸&コラーゲン&ハトムギエキス',                 price: 13000, category: '健康食品', taxRate: 0.08 },
  { code: '6254', name: 'グルコサミン&コンドロイチンRE',                           price:  7600, category: '健康食品', taxRate: 0.08 },
  { code: '6278', name: 'ビタミンC&E',                                             price:  7300, category: '健康食品', taxRate: 0.08 },

  // ── メイク ──────────────────────────────────────────────────────────────
  { code: '220',  name: 'アイメークアップリムーバーRE',                            price:  3200, category: 'メイク', taxRate: 0.10 },
  { code: '238',  name: 'アイコントゥアジェル 24N',                               price:  7800, category: 'メイク', taxRate: 0.10 },
  { code: '258',  name: 'メイクアップベース',                                       price:  4000, category: 'メイク', taxRate: 0.10 },
  { code: '271',  name: 'エッセンス35 BBクリーム（ピンク）',                       price:  4200, category: 'メイク', taxRate: 0.10 },
  { code: '272',  name: 'エッセンス35 BBクリーム（ベージュ）',                     price:  4200, category: 'メイク', taxRate: 0.10 },

  // ── その他 ──────────────────────────────────────────────────────────────
  { code: '999',  name: '美顔器タンク洗浄剤',                                       price:   300, category: 'その他', taxRate: 0.10 },
  { code: '7552', name: 'ヤマノアルコールハンドジェル 携帯用 60ml',                price:   980, category: 'その他', taxRate: 0.10 },
  { code: '7553', name: 'ヤマノアルコールハンドジェル 置き型 300ml',               price:  2800, category: 'その他', taxRate: 0.10 },
];

export function searchProducts(query: string, category?: Category): Product[] {
  const q = query.trim().toLowerCase();
  return products.filter((p) => {
    const matchCategory = !category || p.category === category;
    const matchQuery = !q || p.name.toLowerCase().includes(q) || p.code.includes(q);
    return matchCategory && matchQuery;
  });
}

export function formatPrice(price: number): string {
  return price.toLocaleString('ja-JP');
}

export function calcTax(price: number, qty: number, rate: number): number {
  return Math.floor(price * qty * rate);
}
