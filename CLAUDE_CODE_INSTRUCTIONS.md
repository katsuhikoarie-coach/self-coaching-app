# Claude Code への指示：Dify プロンプト自動反映スクリプトのセットアップと実行

## やること
`dify_push.py` を実行して、ローカルのプロンプトファイルを Dify に自動反映・公開する。

---

## ファイル構成（同じフォルダに置く）
```
your-project/
├── dify_push.py                        ← メインスクリプト
├── yamano_counseling_prompt_v10_slim.txt  ← 反映するプロンプト
├── .env                                ← 認証情報（要設定）
└── .env.example                        ← .envの書き方サンプル
```

---

## STEP 1: .env を設定する

`.env.example` を `.env` にコピーして、以下の値を埋める。

### DIFY_SESSION_COOKIE と DIFY_CSRF_TOKEN の取得方法
1. ブラウザで https://cloud.dify.ai にログインする
2. F12（DevTools）を開く
3. Network タブ → 「XHR」または「Fetch」でフィルター
4. ページをリロード or 何かクリックして、`console/api` へのリクエストを1つ選ぶ
5. Headers タブ → **Request Headers** を確認
6. `cookie` の値をまるごとコピー → `.env` の `DIFY_SESSION_COOKIE=` に貼る
7. 同じ Request Headers の `x-csrf-token` の値をコピー → `DIFY_CSRF_TOKEN=` に貼る

```env
DIFY_SESSION_COOKIE=cookieyes-consent=...; __Host-csrf_token=xxx; （全部貼る）
DIFY_CSRF_TOKEN=（x-csrf-tokenの値だけ）
GEMINI_API_KEY=（既存のキー）
```

---

## STEP 2: 依存パッケージをインストールする

```bash
pip install requests python-dotenv
```

---

## STEP 3: 実行する

```bash
python dify_push.py
```

### 成功時の出力例
```
==================================================
  Dify プロンプト自動更新スクリプト
==================================================
✅ プロンプト読み込み完了（8,215 文字）
📤 model-config を送信中...
✅ model-config 保存成功
🚀 公開中...
✅ 公開成功

🎉 完了！ プロンプト（8,215文字）を更新・公開しました
   アプリURL: https://udify.app/chat/waoRWWk9jBK9e7uu
```

---

## トラブルシューティング

### HTTP 401 / 403 が返ってくる場合
→ セッションが切れています。ブラウザで再ログインして Cookie を取り直してください。

### HTTP 400 が返ってくる場合
→ model-config のボディ構造が Dify のバージョンと合っていない可能性があります。
  `dify_push.py` の `payload` 内の `model.name` が Dify 管理画面と一致しているか確認してください。
  現在の設定: `"name": "gemini-2.5-flash-lite"`

### 保存は成功するが公開が失敗する場合
→ Dify の管理画面（https://cloud.dify.ai/app/0589a1e0-e669-491a-9382-37480eb85322/configuration）
  を開いて「更新を公開」ボタンを手動で押してください。

---

## 注意事項
- Cookie 認証は Dify の非公式 Console API を使います
- セッションは数時間〜数日で切れます。エラーが出たら Cookie を取り直してください
- `.env` を Git にコミットしないよう `.gitignore` に追加してください
