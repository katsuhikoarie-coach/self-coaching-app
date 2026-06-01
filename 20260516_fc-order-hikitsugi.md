# FC受注システム 引き継ぎプロンプト

最終更新：2026年5月16日

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
| バックエンド | PHP（コアサーバー） |
| DB | MySQL（asagiri_fcorder） |
| 認証 | Firebase（asagiri-sale プロジェクト） |
| ローカル開発 | C:\Users\User\Documents\claudeCode\jyutyu2 |

---

## ローカル開発フォルダ

**作業フォルダ：`C:\Users\User\Documents\claudeCode\jyutyu2`**（jyutyuは古い、使わない）

```
jyutyu2/
├── src/              ← ソースコード
├── out/              ← ビルド成果物（WinSCPでアップするのはここ）
├── api/              ← PHPバックエンド
├── config/           ← DB接続設定
└── automation/       ← ヤマノ発注自動化スクリプト
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
| status | received/confirmed/shipped |
| billing_status | unbilled/billed |
| month_period | 月度（2026-05形式） |

**order_items**（受注明細）

---

## 登録ユーザー（2026年5月16日現在）

| ID | 担当者名 | メール | FC名 |
|----|---------|--------|------|
| 1 | 白神千富美 | whitegod0829@gmail.com | 西明石白神FC |
| 2 | 藤沢寛子 | aiuozumi0111@gmail.com | 藍魚住FC |
| 3 | 家入禮子 | ieiri888888@gmail.com | 学園東町FC |
| 4 | 有江健彦 | katsuhiko.arie@gmail.com | 朝霧ヤマノ |
| 5 | 有江啓子 | ya.asagiriten@gmail.com | 朝霧ヤマノ |
| 6 | 有江真吾 | asagiriyamano@gmail.com | 朝霧ヤマノ |
| 7 | 伊藤成子 | seiko.i.3124@gmail.com | セイコ朝霧 |

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
/cart/      ← カート確認
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

## 商品マスタ

- ファイル：`src/lib/products.ts`
- 件数：374品目
- ヤマノ正式商品コードを使用
- CS・販促品はFC価格で登録
- 健康食品（茶・ゼリー・ドリンク）は軽減税率8%

---

## 月度定義

当月21日〜翌月20日を1月度とする。
例：2026年5月度 = 4月21日〜5月20日

---

## 管理画面機能（/admin/）

- 注文管理（月度フィルター・ステータス変更）
- センター管理（ユーザー追加・有効無効切替）
- CSVダウンロード（月度指定、Shift-JIS）

---

## メール通知

注文確定時に2通自動送信。

| 送信先 | 内容 |
|--------|------|
| katsuhiko.arie@gmail.com | 【FC受注】管理者通知 |
| 注文したFCのメール | 【注文確認】注文者確認メール |

---

## 今後の開発予定

### 優先度高
- BCさんの追加（西舞子岩田BC、西舞子ゆかりBC、マキ朝霧BC、三木すずBC）
- BC/FCの価格体系の切替（CS商品・販促品の価格差）
- 軽減税率8%の商品表示の改善

### 将来実装予定
- 請求書発行（月度ごとにPDF生成）
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
| api/admin/ | /public_html/fc-order.asagiriyamano.jp/api/admin/ |

※ api/ と config/ はout/に含まれないので上書きしても消えない

---

## 関係者

| 名前 | 役割 |
|------|------|
| 在りさん（有江健彦） | オーナー・意思決定 |
| 有江啓子 | 朝霧店責任者・受注業務 |
| くろちゃん | Claude（設計・相談） |
| クロコ | Claude Code（実装） |

