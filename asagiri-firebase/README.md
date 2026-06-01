# 朝霧店管理アプリ 引き継ぎプロンプト

## システム構成
- **URL:** https://asagiri-sale.web.app
- **Firebase Project ID:** asagiri-sale
- **Firebase Realtime Database:** https://asagiri-sale-default-rtdb.firebaseio.com
- **ローカルファイル:** `C:\Users\User\Documents\claudeCode\asagiri-firebase\index.html`
- **構成:** 単一HTMLファイル（HTML/CSS/JS すべて index.html）

## Firebaseの設定値
```js
const firebaseConfig = {
  apiKey: "AIzaSyDZtuZa4RvofqIB375zbmPb9X-UwOUTMC0",
  authDomain: "asagiri-sale.firebaseapp.com",
  databaseURL: "https://asagiri-sale-default-rtdb.firebaseio.com",
  projectId: "asagiri-sale",
  storageBucket: "asagiri-sale.firebasestorage.app",
  messagingSenderId: "800075177148",
  appId: "1:800075177148:web:f343f8daaf31bcc301c4b9"
};
```

## アクセス権限（ホワイトリスト）
- `katsuhiko.arie@gmail.com`（管理者）
- `ya.asagiriten@gmail.com`（スタッフ）
- スタッフ追加時: index.html の `ALLOWED_EMAILS` + `database.rules.json` を両方更新してから `firebase deploy`

## データベース構造（Firebase Realtime Database）

| パス | 内容 |
|---|---|
| /sales/ | 売上伝票 |
| /customers/ | 顧客マスタ |
| /products/ | 商品マスタ |
| /stock/ | 在庫 |
| /inout/ | 入出庫履歴 |
| /estemenus/ | エステコースマスタ（id/name/price/tax/active） |
| /estevisits/ | エステ来店履歴（id/date/custNo/custName/menuId/menuName/staff/price/taxRate/tax/total/nextVisit/note） |
| /keepitems/ | キープ商品・回数券（id/date/custNo/custName/menuName/qty/count/price/note） |

## store オブジェクト
```js
const store = {
  customers: [], products: [], inventory: [], sales: [], inout: [],
  estemenus: [], estevisits: [], keepitems: []
};
```

## 元データ
Access MDB（20240527_消費税10_朝霧店_新価格.mdb）→ mdbtools で抽出 → Firebase インポート

## 消費税8%対象商品コード（自動判定）
```
0329,0330,0670,1659,2646,3025,3304,
6224,6225,6226,6228,6250,6251,6252,6253,6254,
6278,6282,6288,6289,6291,6292,
6303,6304,6305,6306,6307,6308,630u,
6311,6312,6313,6314,6315,6316,6317,631u,
6323,6325,6329,6335,6336,6337,6515,
7628,7629,7630,
Y001,Y002,Y007,Y008,Y059,Y067,Y068,Y085,
Y628,Y629,Y633,Y635,Y636,
Z025,Z601,z101,z102,ｚ101,ｚ102
```

## 実装済み機能

### 売上管理
- 売上伝票一覧（検索・絞込・詳細モーダル・50件ページネーション）
- 新規伝票入力（自動採番・顧客サジェスト・商品コード補完・掛率プルダウン・税率自動判定・印刷プレビュー）
- 売上日計表（期間指定・KPIカード・日別集計・CSVエクスポート）
- 月次レポート（年選択・KPIカード4枚・グループ棒グラフ・月別テーブル）

### 顧客管理
- 顧客一覧（フリーワード検索・ランク/担当フィルタ）
- 顧客詳細モーダル（基本情報・購入累計・エステ来店履歴セクション）
- 顧客新規登録
- 退会処理（soft-delete: `active: false`）・復元（トグル表示）
- 今月未来店顧客一覧
- 誕生日DM（月選択→対象者一覧→BOM付きUTF-8 CSVダウンロード）

### エステ管理
- エステコースマスタ（一覧・インライン編集・新規追加・終息/復活 → `/estemenus/`）
- エステ来店入力（顧客サジェスト・コース選択→価格自動補完・税計算・Firebase保存）
- エステ来店一覧・集計（期間/顧客名/コースフィルタ・KPIカード4枚・一覧テーブル）
- キープ商品・回数券（登録フォーム・履歴一覧 → `/keepitems/`）

### 在庫・商品
- 在庫管理（一覧・アラート・入出庫登録）
- 商品マスタ（検索・フィルタ）
- 印刷機能（A4縦・サイドバー非表示・税率別合計）

### データ管理
- CSVインポート（顧客・商品マスタ、BOM付きUTF-8/Shift-JIS対応）
- 売上CSVエクスポート

## 月次レポートの集計内容
- **KPIカード:** 年間商品売上 / 年間エステ売上 / 年間合計 / エステ構成比
- **グループ棒グラフ:** 青=商品売上、緑=エステ売上（月別・並列表示）
- **月別テーブル列:** 月 / 商品売上（税込）/ エステ件数 / エステ売上（税込）/ 合計

## 重要な実装ルール
- 商品コード照合は `normalizeCode()` で全角・半角・大文字小文字を正規化して比較
- 終息商品（`disc=true`）もコード直接入力時は `[終息]` ラベルで表示
- `calcSlip(s)` で10%/8%を分けて `Math.floor` 計算、戻り値: `{ sub10, sub8, tax10, tax8, total }`
- `mapCustomer()` に `active` フィールドが含まれていない（退会フィルタはFirebase直接更新で対応）
- Firebase無料枠（Sparkプラン）の範囲内で実装
- 既存のダークテーマデザインを維持

## Firebase 書き込み関数
```
saveCustomers_FB()        顧客マスタ一括保存
saveProducts_FB()         商品マスタ一括保存
saveInventory_FB()        在庫一括保存
updateStock_FB(code, qty) 在庫数量更新
saveSale_FB(slip)         売上伝票1件保存
```

エステ系は直接参照:
```
fbDb.ref('estemenus/{id}').set(menu)      コース登録
fbDb.ref('estemenus/{id}').update({...})  コース更新
fbDb.ref('estevisits/{id}').set(visit)   来店登録
fbDb.ref('keepitems/{id}').set(item)     キープ商品登録
```

## デプロイ方法
```
firebase deploy --only hosting
```
**コマンドプロンプト（cmd）で実行すること**（PowerShellは権限エラーの場合あり）
