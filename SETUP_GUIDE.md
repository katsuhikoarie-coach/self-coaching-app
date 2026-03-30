# 毎朝情報収集システム セットアップガイド

このガイドでは、毎朝9時（JST）に情報収集メールを自動送信するシステムを
GitHubにセットアップする手順をステップごとに説明します。

---

## 必要なもの

- GitHubアカウント
- Googleアカウント（Gemini API用 & Gmail用）

---

## STEP 1: Gemini API キーを取得する

1. ブラウザで **Google AI Studio** を開く
   → https://aistudio.google.com/apikey

2. 「APIキーを作成」をクリック

3. 表示された API キー（`AIza...` で始まる文字列）をコピーして、
   どこか安全な場所に一時保存しておく

> 無料枠で Gemini 2.0 Flash が使えます。課金設定は不要です。

---

## STEP 2: Gmail App Password（アプリパスワード）を設定する

> 通常のGmailパスワードではなく、専用の「アプリパスワード」が必要です。

### 2-1. Googleアカウントの2段階認証を有効にする（まだの場合）

1. https://myaccount.google.com/security を開く
2. 「2段階認証プロセス」をクリックして有効化する

### 2-2. アプリパスワードを発行する

1. https://myaccount.google.com/apppasswords を開く
2. 「アプリを選択」→「その他（カスタム名）」→「毎朝情報収集」と入力
3. 「生成」をクリック
4. 表示された 16文字のパスワード（スペース除く）をコピーして保存しておく

---

## STEP 3: GitHub にリポジトリを用意する

### 新規リポジトリを作成する場合

1. https://github.com/new を開く
2. リポジトリ名を入力（例: `daily-info-bot`）
3. Public または Private を選ぶ（どちらでも動作します）
4. 「Create repository」をクリック

### このコードをプッシュする

ターミナル（コマンドプロンプト）で以下を実行：

```bash
# リポジトリのURLをあなたのものに変更してください
git remote add origin https://github.com/あなたのユーザー名/daily-info-bot.git
git branch -M main
git push -u origin main
```

> すでに既存リポジトリにコードがある場合は、通常の `git push` でOKです。

---

## STEP 4: GitHub Secrets に API キーを登録する

GitHub Secrets に登録することで、APIキーやパスワードをコードに書かずに
安全に管理できます。

1. GitHubのリポジトリページを開く
2. 上部メニューの「Settings」をクリック
3. 左サイドバーの「Secrets and variables」→「Actions」をクリック
4. 「New repository secret」ボタンをクリック

以下の4つを登録する：

| Name（名前）    | Secret（値）                              |
|----------------|------------------------------------------|
| `GEMINI_API_KEY` | STEP 1 で取得した Gemini API キー         |
| `GMAIL_USER`    | 送信元のGmailアドレス（例: yourname@gmail.com） |
| `GMAIL_APP_PASS` | STEP 2 で取得した16文字のアプリパスワード  |
| `MAIL_TO`       | 送信先メールアドレス（katsuhiko.arie@gmail.com） |

> `MAIL_TO` を登録しない場合は、デフォルトで `katsuhiko.arie@gmail.com` に送信されます。

---

## STEP 5: 動作確認（手動実行）

スケジュール実行（毎朝9時）を待たずに、手動でテスト実行できます。

1. GitHubのリポジトリページを開く
2. 上部メニューの「Actions」をクリック
3. 左サイドバーの「毎朝情報収集メール」をクリック
4. 右側の「Run workflow」ボタンをクリック → 「Run workflow」を押す
5. 数分後に完了し、メールが届くことを確認する

---

## スケジュール実行のタイミング

`.github/workflows/daily.yml` に設定されているスケジュール：

```yaml
cron: '0 0 * * *'   # UTC 0:00 = JST 9:00
```

毎日朝9時（日本時間）に自動で実行されます。

> GitHub Actionsの無料枠：パブリックリポジトリは無制限、プライベートリポジトリは月2,000分まで無料。
> このスクリプトは1回あたり約2〜5分で完了するため、無料枠で十分です。

---

## トラブルシューティング

### メールが届かない場合

- Gmail App Password が正しいか確認（スペースなしの16文字）
- `GMAIL_USER` と App Password が同じGoogleアカウントのものか確認
- Gmailの「迷惑メール」フォルダを確認する

### GitHub Actions が失敗する場合

1. GitHubの「Actions」タブでエラーログを確認する
2. Secrets の名前が正確に登録されているか確認する（大文字小文字も一致させる）

### Gemini API エラーの場合

- API キーが正しいか確認する
- Google AI Studio でAPIキーが有効になっているか確認する

---

## ファイル構成

```
daily_info.py                   ← メインスクリプト
requirements.txt                ← Python依存パッケージ
.github/
  workflows/
    daily.yml                   ← GitHub Actionsスケジュール設定
SETUP_GUIDE.md                  ← このファイル
```
