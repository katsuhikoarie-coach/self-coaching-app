# クレスティサロンWiLL セットアップ手順書

## 概要
| 項目 | 値 |
|------|-----|
| 店舗名 | 山野愛子どろんこ美容 クレスティサロンWiLL |
| 店舗コード | will |
| URL | https://demo-will.asagiriyamano.jp |
| DB名 | asagiri_will |
| DBユーザー | asagiri_will |
| ログイン | katsuhiko.arie@gmail.com |

---

## STEP 1: ファイルの準備（FTP）

### 1-1. テンプレートのダウンロード
FTPクライアント（FileZilla 等）で以下から全ファイルをダウンロード：
```
FTPホスト : asagiri.s168.coreserver.jp
ユーザー  : asagiri
ディレクトリ: /public_html/sales.asagiriyamano.jp/
```

### 1-2. ファイルの修正
ダウンロードしたファイルに対して以下を変更：

**index.html** — `PATCH_index.html.md` を参照
- 店舗名「朝霧店」→「クレスティサロンWiLL」（全て置換）
- ALLOWED_EMAILS = ['katsuhiko.arie@gmail.com']
- APIエンドポイント → https://demo-will.asagiriyamano.jp/api/

**config/db.php** — 同梱ファイルを使用
- DB_NAME    : asagiri_will
- DB_USER    : asagiri_will
- DB_PASSWORD: 在りさんが直接入力

### 1-3. FTPアップロード
修正済みファイルを以下にアップロード：
```
アップロード先: /public_html/demo-will.asagiriyamano.jp/
```

---

## STEP 2: コアサーバー サブドメイン設定

### 手順
1. コアサーバーコントロールパネル (https://cp.coreserver.jp) にログイン
2. 左メニュー「サイト設定」→「サイト一覧/追加」をクリック
3. 「サイトを追加する」ボタンをクリック
4. 以下を入力して保存：

| 項目 | 値 |
|------|-----|
| ドメイン | demo-will.asagiriyamano.jp |
| ディレクトリ | /public_html/demo-will.asagiriyamano.jp |
| PHP バージョン | （既存サイトと同じバージョン） |
| SSL | 有効（Let's Encrypt） |

5. SSL証明書の発行に数分〜数十分かかります
6. https://demo-will.asagiriyamano.jp にアクセスして表示確認

> **注意**: ディレクトリは事前にFTPで作成 or アップロード済みであること

---

## STEP 3: DB セットアップ（phpMyAdmin）

### 3-1. DBとユーザーの作成
コアサーバーコントロールパネル → 「MySQL」→「DB一覧/追加」

| 項目 | 値 |
|------|-----|
| DB名 | asagiri_will |
| ユーザー名 | asagiri_will |
| パスワード | （在りさんが設定） |

### 3-2. テーブル作成
1. phpMyAdmin にログイン
2. 左ペインから **asagiri_will** を選択
3. 「SQL」タブを開く
4. **setup_will.sql** の内容を全て貼り付けて「実行」
5. 「setup_will.sql 完了: テーブルが作成されました」と表示されればOK

### 3-3. 商品マスタのインポート
1. 引き続き **asagiri_will** を選択した状態で「SQL」タブ
2. **import_products_will.sql** の内容を全て貼り付けて「実行」
3. 実行結果に以下が表示されること確認：
   - total_products: 1364（件数は実際の朝霧店データに依存）
   - tax_8_count: 8%対象コード件数
   - tax_10_count: 残り全件

> **方法Aが失敗する場合（権限エラー等）**
> import_products_will.sql 内の「方法B」の手順でエクスポート→インポートしてください

---

## STEP 4: Firebase 認証ドメインの追加

### 手順
1. Firebase Console (https://console.firebase.google.com) を開く
2. 朝霧店と同じFirebaseプロジェクトを選択
3. 左メニュー「Authentication」→「Settings」→「承認済みドメイン」タブ
4. 「ドメインを追加」ボタンをクリック
5. 以下を入力して追加：
   ```
   demo-will.asagiriyamano.jp
   ```
6. 保存して完了

> **ポイント**: この設定をしないと Google ログインがブロックされます

---

## STEP 5: 動作確認チェックリスト

- [ ] https://demo-will.asagiriyamano.jp が表示される（SSL有効）
- [ ] katsuhiko.arie@gmail.com でGoogleログインできる
- [ ] 他のGoogleアカウントでログインしようとすると弾かれる
- [ ] 商品一覧が表示される（朝霧店と同じ商品）
- [ ] 8%対象商品の税率が正しく表示される
- [ ] 売上入力・保存ができる

---

## 完了報告

```
店舗名  : クレスティサロンWiLL
URL     : https://demo-will.asagiriyamano.jp
DB名    : asagiri_will
ログイン: katsuhiko.arie@gmail.com
商品マスタ: 朝霧店共通（asagiri_sales.products からコピー）
顧客データ: 空（新規入力から）
残作業  : なし
```

---

## ファイル一覧（本フォルダ）

| ファイル | 用途 |
|----------|------|
| setup_will.sql | DBテーブル作成（phpMyAdminで実行） |
| import_products_will.sql | 商品マスタコピー（phpMyAdminで実行） |
| config/db.php | DB接続設定（パスワードを記入してアップロード） |
| PATCH_index.html.md | index.html の変更箇所チェックリスト |
| SETUP_GUIDE.md | 本手順書 |
