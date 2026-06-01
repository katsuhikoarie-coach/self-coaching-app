# FC受注システム 引き継ぎプロンプト

最終更新：2026年5月26日

---

## 関係者

| 名前 | 役割 |
|------|------|
| 在りさん（有江健彦） | オーナー・意思決定 |
| 有江啓子 | 朝霧店責任者・受注業務 |
| くろちゃん | Claude（設計・相談） |
| クロコ | Claude Code（実装） |

---

## システム概要

朝霧ヤマノ（有限会社まゆ企画）のFC・BC向け受注システム。

- URL：https://fc-order.asagiriyamano.jp
- ログイン方式：Google OAuth（Firebase）
- ホスティング：コアサーバー

---

## 技術スタック

| 項目 | 内容 |
|------|------|
| フロントエンド | Next.js 14（静的書き出し） |
| バックエンド | PHP 7.4.33（コアサーバー） |
| DB | MySQL（asagiri_fcorder） |
| 認証 | Firebase（asagiri-saleプロジェクト） |
| ローカル開発 | C:\Users\User\Documents\claudeCode\jyutyu2 |

---

## ローカル開発フォルダ

**作業フォルダ：`C:\Users\User\Documents\claudeCode\jyutyu2`**（jyutyuは古い、使わない）

```
jyutyu2/
├── src/              ← ソースコード
├── out/              ← ビルド成果物（WinSCPでアップするのはここ）
├── api/              ← PHPバックエンド
│   └── admin/        ← 管理者向けAPI
├── config/           ← DB接続設定
├── sql/              ← SQLファイル（テーブル作成・データ投入）
└── automation/       ← ヤマノ発注自動化スクリプト
    ├── yamano-scraper.js   ← ヤマノ注文履歴取込スクリプト
    ├── run-scraper.bat     ← タスクスケジューラ用バッチ（Shift-JIS保存）
    └── logs/               ← ログ出力フォルダ
```

### 毎回の作業フロー
```
①コード修正（jyutyu2/src/）
②npm run build
③WinSCPで out/ の中身を
　fc-order.asagiriyamano.jp/ にアップ
```

---

## データベース

- DB名：asagiri_fcorder
- ホスト：localhost
- ポート：3306

### テーブル構成

**fc_users**（ユーザー管理）
| カラム | 内容 |
|--------|------|
| id | 自動採番 |
| email | Gmailアドレス |
| fc_name | FC/センター名 |
| contact_name | 担当者名 |
| center_code | センターコード |
| center_type | BC/FC/販社 |
| grade | 資格 |
| active | 有効フラグ |

**orders**（受注）
| カラム | 内容 |
|--------|------|
| id | 受注番号（2026-05-16-0001形式） |
| fc_user_id | ユーザーID |
| fc_name | FC名（スナップショット） |
| order_date | 注文日 |
| subtotal | 税抜合計 |
| tax_total | 消費税 |
| total | 税込合計 |
| status | received/confirmed/shipped/cancelled |
| billing_status | unbilled/billed |
| month_period | 月度（2026-05形式） |

**order_items**（受注明細）

**products**（商品マスタ）
| カラム | 内容 |
|--------|------|
| id | 自動採番 |
| code | 商品コード（UNIQUE） |
| name | 商品名 |
| category | 商品区分 |
| price_hansha | 販社価格（税抜） |
| price_bc | BC価格（税抜） |
| price_fc | FC価格（税抜） |
| tax_rate | 消費税率（0.10 or 0.08） |
| active | 有効フラグ（0=非表示） |
| note | 備考 |

**yamano_orders**（ヤマノ注文履歴取込）
| カラム | 内容 |
|--------|------|
| id | 自動採番 |
| yamano_order_id | ヤマノ注文番号（Y2026052100017形式・UNIQUE） |
| order_date | 注文日 |
| total_pretax | 総合計（税別） |
| shipping_name | お届け先氏名 |
| shipping_address | お届け先住所 |
| shipping_tel | お届け先電話番号 |
| status | 注文状況（注文済み／受注承認／出荷完了など） |
| fc_user_id | 紐づけたセンターのID（NULLなら未分類） |
| month_period | 月度（2026-05形式） |
| fetched_at | 取込日時 |

**yamano_order_items**（ヤマノ注文明細）
| カラム | 内容 |
|--------|------|
| id | 自動採番 |
| yamano_order_id | yamano_ordersへの外部キー |
| product_code | 商品コード（例：0857） |
| product_name | 商品名 |
| unit_price | 単価（税別） |
| quantity | 注文数 |
| subtotal | 小計（税別） |

**yamano_shipping_map**（配送先→センター紐づけマスタ）
| カラム | 内容 |
|--------|------|
| id | 自動採番 |
| shipping_name | お届け先氏名（例：土野麻希子） |
| fc_user_id | 対応するセンターID |
| note | 備考（例：マキ朝霧BC顧客） |

---

## 登録ユーザー（2026年5月20日現在）

| ID | 担当者名 | メール | センター名 | 種別 |
|----|---------|--------|-----------|------|
| 1 | 白神千富美 | whitegod0829@gmail.com | 西明石白神 | FC |
| 2 | 藤沢寛子 | aiuozumi0111@gmail.com | 藍魚住 | FC |
| 3 | 家入禮子 | ieiri888888@gmail.com | 学園東町 | FC |
| 4 | 有江健彦 | katsuhiko.arie@gmail.com | 朝霧ヤマノ | 販社 |
| 5 | 有江啓子 | ya.asagiriten@gmail.com | 朝霧ヤマノ | 販社 |
| 6 | 有江真吾 | asagiriyamano@gmail.com | 朝霧ヤマノ | 販社 |
| 7 | 伊藤成子 | seiko.i.3124@gmail.com | 成子朝霧 | FC |
| 21 | 岩田裕美子 | yumikoiwata212@gmail.com | 西舞子岩田 | BC |
| 22 | 稲岡ゆかり | salonkotori1010@gmail.com | 西舞子ゆかり | BC |
| 23 | 土野麻希子 | makimaki19.0412@gmail.com | マキ朝霧 | BC |
| 24 | 森本すずみ | sue69222@gmail.com | 三木すず | BC |

---

## アクセス権限

| ユーザー種別 | 注文履歴閲覧 | ヤマノ発注ボタン | 管理画面 |
|-------------|------------|----------------|---------|
| 朝霧ヤマノ | 全センター分 | 表示 | アクセス可 |
| FC/BC | 自分の分のみ | 非表示 | アクセス不可 |

---

## 画面構成

```
/           ← ログイン画面
/home/      ← ホーム（注文履歴・ヤマノ発注・管理画面リンク）
/products/  ← 商品検索・カート追加
/cart/      ← カート確認（税抜・税込・送料無料バナー表示）
/confirm/   ← 注文確認
/complete/  ← 注文完了
/admin/     ← 管理画面（朝霧ヤマノのみ）
```

---

## ヤマノ発注自動化

### 概要
FCアプリで受注確定後、「ヤマノ発注」ボタンを押すと、ヤマノの発注サイト（yamano-order.shop）に自動入力される。

### 起動方法
```
cd C:\Users\User\Documents\claudeCode\jyutyu2
npm run automate
```

※ Windowsスタートアップに登録済み（PC起動時に自動起動）

### ヤマノ発注サイト
- URL：https://yamano-order.shop
- ログインID：40633

### 配送先マッピング（FC_TO_SHIPPING_VALUE）
| FC名 | value |
|------|-------|
| 朝霧ヤマノ | 121 |
| 西明石白神FC | 21 |
| 藍魚住FC | 25 |
| 学園東町FC | 17 |

### ヤマノログインセレクタ
- ログインID：`#ctl00_ContentPlaceHolder1_tbLoginId`
- パスワード：`#ctl00_ContentPlaceHolder1_tbPassword`
- 取得ボタン：`#ctl00_ContentPlaceHolder1_lbGetAllproduct`
- ご注文手続き：`a:has-text("ご注文手続き")`

---

## ヤマノ注文履歴取込（実績管理機能）

### 背景・目的

ヤマノへの発注は2種類ある。

- 朝霧ヤマノ経由の発注：センター受注システムで受注→クロコが自動発注
- BCの各自発注：BCが直接ヤマノにログインして発注（朝霧ヤマノを通さない）

どちらもヤマノID **40633** で発注されるため、注文履歴は40633に一元集約されている。

### 取込スクリプト（本番稼働済み）

- ファイル：`automation/yamano-scraper.js`
- 使用技術：Playwright
- 実行タイミング：毎日9:30（Windowsタスクスケジューラで自動実行）
- バッチファイル：`automation/run-scraper.bat`（Shift-JIS保存済み）
- ログ出力：`automation/logs/`
- 取込対象：注文済み・受注承認・出荷完了（キャンセルのみスキップ）
- 重複対策：ON DUPLICATE KEY UPDATE実装済み

### 取込フロー

```
①40633でyamano-order.shopにログイン
②マイページ → 購入履歴一覧を開く
③注文一覧から全件の注文番号を取得
④すでにDBに存在する注文番号はスキップ（差分のみ取込）
⑤各注文の詳細ページを開いて以下を取得
　- 注文番号・注文日・総合計（税別）・注文状況
　- お届け先：氏名・住所・電話番号
　- 購入商品：商品コード・商品名・単価・数量・小計
⑥yamano_shipping_mapと照合してセンターを自動紐づけ
⑦マッチングできない場合はfc_user_id=NULL（未分類）でDB保存
```

### センター自動マッチングのルール

yamano_shipping_mapテーブルに配送先氏名とセンターIDの対応を登録しておく。
お届け先氏名が完全一致すれば自動紐づけ。一致しない場合は未分類。

初期登録済みマッピング：
| shipping_name | fc_user_id | note |
|--------------|-----------|------|
| 岩田裕美子 | 21 | 西舞子岩田BC本人 |
| 稲岡ゆかり | 22 | 西舞子ゆかりBC本人 |
| 土野麻希子 | 23 | マキ朝霧BC本人 |
| 森本すずみ | 24 | 三木すずBC本人 |
| 有江健彦 | 4 | 朝霧ヤマノ |
| 有江啓子 | 5 | 朝霧ヤマノ |

※BCが顧客に直送する場合、配送先は顧客氏名になる。
　未分類として管理画面に表示→在りさんが手動紐づけ→yamano_shipping_mapに自動登録。

---

## 商品マスタ

- 管理方法：MySQLの `products` テーブルで管理（2026年5月20日にDB移行完了）
- 件数：380品目
- ヤマノ正式商品コードを使用
- CS・販促品はFC価格・BC価格・販社価格を3列で登録
- BCユーザーにはBC価格を表示（`getPrice()` 関数で切替）
- 健康食品（茶・ゼリー・ドリンク）・マウスウォッシュは軽減税率8%
- APIエンドポイント：`api/products.php?center_type=FC|BC|販社`
- 管理画面から追加・編集・無効化が可能（`api/admin/products.php`）

### SQLファイル
- `sql/create_products_table.sql` ← テーブル定義
- `sql/insert_products.sql` ← 初期データ投入（ON DUPLICATE KEY UPDATE）

---

## 月度定義

当月21日〜翌月20日を1月度とする。
例：2026年5月度 = 4月21日〜5月20日

---

## 管理画面機能（/admin/）

- 注文管理（月度フィルター・ステータス変更）
- センター管理（ユーザー追加・有効無効切替）
- 商品管理（商品一覧・追加・編集・無効化）
- CSVダウンロード（月度指定、Shift-JIS）
- ヤマノ実績（センター別集計・未分類紐づけ）
- 請求書発行（月度・センター指定→Excelダウンロード）← 2026年5月26日実装予定

---

## 請求書発行機能（2026年5月26日実装）

### フロー
```
①管理画面で月度・センターを選択
②「請求書ダウンロード」ボタンを押す
③api/admin/invoice.phpがDBからデータ取得→Excelファイル生成→ダウンロード
④在りさんがExcelで内容確認・必要なら修正
⑤Excel→PDF（Excelの印刷→PDF保存）
```

### データソースと価格ルール

| センター種別 | データソース | 使用価格 |
|------------|------------|---------|
| FC | ordersテーブル | price_fc |
| BC | yamano_ordersテーブル | price_bc（productsと商品コードで突き合わせ） |

BCの突き合わせ失敗時（商品コードがproductsに存在しない場合）：
→ ヤマノ取込金額をそのまま使用し「※要確認」フラグを付ける

### 税率処理

- productsテーブルのtax_rateで判定（0.10 or 0.08）
- BCの突き合わせ失敗商品はtax_rate=0.10（10%）をデフォルトとする
- インボイス対応：8%対象と10%対象を分けて記載

### 請求書の記載項目

表紙：
- 宛名（センター名・担当者名）
- 請求金額（税込合計）
- 発行者：有限会社まゆ企画
- インボイス登録番号：T3140002005245
- 取引年月日（月度期間）
- 税率別合計・消費税額

明細：
- 商品コード・商品名・単価・数量・金額・税率
- 8%対象合計・10%対象合計・消費税額・税込合計

### Excel生成

- サーバーのPHP拡張 `xlswriter` を使用（composerなし・手動設置不要）
- ファイル出力先：`api/admin/invoice.php`

### 関連ファイル（新規作成）

| ファイル | 内容 |
|---------|------|
| `api/admin/invoice.php` | DB取得・Excel生成API |

---

## メール通知

注文確定時に2通自動送信。

| 送信先 | 内容 |
|--------|------|
| katsuhiko.arie@gmail.com | 【FC受注】管理者通知 |
| 注文したセンターのメール | 【注文受付】注文受付メール ※未実装→次フェーズ |

※ヤマノ発注ボタン押下時にセンターへ「発注完了メール」を送る予定（次フェーズ）

---

## キャンセル・削除の仕様（次フェーズで実装）

### キャンセル（センター側）
- `status === 'received'`（発注前）の注文のみキャンセル可能
- ヤマノ発注ボタンを押した後（`confirmed`以降）はキャンセル不可
- キャンセル後は履歴に「キャンセル済」バッジ表示

### 発注フロー（メール通知込み）
```
センターが注文確定
　↓
センターに「注文受付メール」送信
　↓ ← この間はキャンセル可能
朝霧ヤマノがヤマノ発注ボタンを押す
　↓
センターに「発注完了メール」送信
　↓ ← この時点以降キャンセル不可
```

### 削除（管理者のみ）
- 管理画面の注文管理タブに「削除」ボタン
- 確認ダイアログ：「このデータを保存しましたか？削除すると元に戻せません。」
- 物理削除（`api/admin/orders.php` にDELETE APIを追加）

---

## 完了済み作業

### 2026年5月20日
- BCさんの追加（西舞子岩田BC、西舞子ゆかりBC、マキ朝霧BC、三木すずBC）
- BC/FCの価格体系の切替（CS商品・販促品の価格差）→ getPrice()関数で実装済み
- auth.phpにcenter_typeを返すよう修正
- アプリ名称を「FC受注システム」→「センター受注システム」に変更
- 商品7品目追加
- 商品マスタをMySQLに移行（products テーブル新設・380品目・price_hansha/price_bc/price_fc の3価格体系）
- 管理画面に商品管理タブ追加（追加・編集・無効化）
- カート・確認画面の合計金額表示改善（税抜・消費税・税込の3行表示・送料無料バナー）

### 2026年5月21日
- ヤマノ注文履歴取込スクリプト設計（yamano-scraper.js）
- yamano_orders・yamano_order_items・yamano_shipping_map テーブル設計
- 管理画面「ヤマノ実績」タブ設計

### 2026年5月26日
- ヤマノ自動取込（yamano-scraper.js）本番稼働開始
- Windowsタスクスケジューラ登録（毎日9:30自動実行）
- 請求書発行機能の設計確定・実装開始

---

## 今後の開発予定

### 次フェーズ（優先度高）
- 請求書発行機能（api/admin/invoice.php）← 本日実装中
- キャンセルボタン実装（センター側・received状態のみ）
- 発注完了メール送信（ヤマノ発注ボタン押下時にセンターへ）
- 注文受付メール修正（「発注完了メールをお送りします」の一文追加）
- 削除ボタン実装（管理者のみ・物理削除・確認ダイアログあり）

### 将来実装予定
- 実績管理（6ヶ月累計で仕入掛率管理）
- ヤマノへの実績報告書自動生成

---

## Firebase設定

- プロジェクト：asagiri-sale
- 承認済みドメイン：fc-order.asagiriyamano.jp
- Google Cloud Console：承認済みJavaScript生成元に追加済み

---

## WinSCPアップ先

| ファイル | サーバーパス |
|---------|-------------|
| out/ の中身 | /public_html/fc-order.asagiriyamano.jp/ |
| api/orders.php | /public_html/fc-order.asagiriyamano.jp/api/orders.php |
| api/products.php | /public_html/fc-order.asagiriyamano.jp/api/products.php |
| api/admin/ | /public_html/fc-order.asagiriyamano.jp/api/admin/ |

※ api/ と config/ はout/に含まれないので上書きしても消えない

---

## サーバー環境メモ

- PHPバージョン：7.4.33
- Excel生成：xlswriter拡張が利用可能（composerなしでOK）
- PDF生成ライブラリ：TCPDFもmPDFも未インストール
- 画像処理：imagick・gd 利用可能
