# センター受注システム（jyutyu2）

最終更新：2026-05-30

有限会社まゆ企画が運営する、朝霧ヤマノのFC/BCセンター向け受注管理システム。
在りさん（有江健彦）がオペレーター兼開発コーディネーター。

---

## 技術スタック

- フロントエンド：Next.js 14（static export）
- バックエンド：PHP
- データベース：MySQL（DB名：asagirifcorder）
- 認証：Firebase Authentication
- ホスティング：fc-order.asagiriyamano.jp

---

## ローカル環境

- 作業フォルダ：C:\Users\User\Documents\claudeCode\jyutyu2
- Node.js / npm でビルド
- デプロイツール：WinSCP

---

## デプロイ手順

1. src/ を修正
2. npm run build
3. out/ フォルダをWinSCPでサーバーにアップロード

---

## 発注スケジュール

| 締切 | 出荷 |
|------|------|
| 日曜 17:00 | 月曜 |
| 火曜 17:00 | 水曜 |
| 木曜 17:00 | 金曜 |

---

## センターユーザー一覧

| 氏名 | 種別 | センター番号 |
|------|------|------------|
| 白神千富美 | FC | 46582 |
| 藤沢寛子 | FC | 47807 |
| 家入禮子 | FC | 45829 |
| 伊藤成子 | FC | 46967 |
| 岩田裕美子 | BC | 42951 |
| 稲岡ゆかり | BC | 47881 |
| 土野麻希子 | BC | 46481 |
| 森本すずみ | BC | 44628 |

ユーザー登録・変更はphpMyAdminでSQL操作。

---

## 重要な仕様・注意事項

### FC / BC 価格の切り替え
- 商品データにpricefc・pricebcの両フィールドあり
- getPrice()関数がcenter_typeに応じて自動切り替え
- auth.phpが必ずcenter_typeをJSONレスポンスに含めること

### Google OAuth 認証
- LINEの内部ブラウザでは動作しない（disallowed_useragent エラー）
- ユーザーにはSafariまたはChromeで開くよう案内する

### データベース操作
- phpMyAdminで管理
- 本番DBの直接編集は慎重に（バックアップ確認後）
- ユーザー登録時はメールアドレス重複に注意（INSERT前に確認）

---

## 主要ファイル構成

```
jyutyu2/
├── src/
│   ├── app/
│   │   ├── layout.tsx       # システム名表示
│   │   ├── page.tsx         # トップページ
│   │   └── home/page.tsx    # ホーム画面
│   └── lib/
│       └── products.ts      # 商品データ（pricefc/pricebc）
├── public/
│   └── api/
│       ├── auth.php         # 認証（center_type返却必須）
│       └── orders.php       # 受注処理
└── out/                     # ビルド出力（デプロイ対象）
```

---

## よくあるエラーと対処

| エラー | 原因 | 対処 |
|--------|------|------|
| Google OAuth 403 | LINEブラウザ使用 | Safari/Chromeで開くよう案内 |
| 価格がFCと同じになる | auth.phpがcenter_typeを返していない | auth.phpのJSONレスポンス確認 |
| ユーザー登録できない | メールアドレス重複 | phpMyAdminでDUPLICATE確認後UPDATE |

---

## 今後の予定

- 有江啓子さんのPCへの自動入力システム導入（Node.js インストール・jyutyu2フォルダコピー・起動スクリプト登録）
- 掛率管理機能の実装
- 請求書発行機能の実装

---

## 更新ルール

作業終了時に必ず以下を更新すること。

- 最終更新日（ファイル冒頭に記載）
- 完了した作業を「完了済み作業」に追記
- 未完了・次回やることを「今後の予定」に反映
- 新たに発覚した仕様・注意事項を該当セクションに追記

### クロコへの指示例
```
今日の作業をCLAUDE.mdに反映して
```

---

## 完了済み作業

| 日付 | 内容 |
|------|------|
| 2026-05 | Google OAuth 403エラー修正（LINEブラウザ対応） |
| 2026-05 | BC専用価格フィールド追加（pricebc・getPrice関数） |
| 2026-05 | auth.phpのcenter_type返却バグ修正 |
| 2026-05 | システム名を「センター受注システム」に統一 |
| 2026-05 | BCユーザー登録（重複メール解消含む） |

---

## ツール一覧

| ツール | 用途 |
|--------|------|
| phpMyAdmin | DB管理・ユーザー登録 |
| WinSCP | サーバーへのファイルデプロイ |
| Firebase Console | 認証管理 |
| npm / Node.js | ローカルビルド |
