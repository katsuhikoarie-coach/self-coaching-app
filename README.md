# セルフコーチング Web アプリ

AIコーチと対話するセルフコーチングWebアプリです。
ナラティブ・GROW・ポジションチェンジの3種のコーチから選んで、セッションを始められます。

---

## セットアップ

### 1. APIキーを設定する

```bash
cp .env.example .env
```

`.env` を開き、`ANTHROPIC_API_KEY` に Anthropic のAPIキーを入力してください。

### 2. 依存パッケージをインストールする

```bash
cd backend
pip install -r requirements.txt
```

### 3. サーバーを起動する

```bash
cd backend
uvicorn main:app --reload
```

ブラウザで http://localhost:8000 を開くとアプリが表示されます。

---

## ディレクトリ構成

```
/
├── .env.example          ← 環境変数テンプレート
├── coaches/              ← コーチプロンプト（mdファイル）
│   ├── _coach_base.md
│   ├── ナラティブコーチ.md
│   ├── GROWコーチ.md
│   ├── ポジションチェンジコーチ.md
│   ├── セッションフロー.md
│   └── 危機介入.md
├── backend/
│   ├── main.py           ← FastAPIエントリーポイント
│   ├── requirements.txt
│   ├── routers/
│   │   ├── chat.py       ← POST /api/chat
│   │   ├── session.py    ← GET/POST/DELETE /api/session/{id}
│   │   └── coach.py      ← GET /api/coaches, POST /api/summary
│   └── services/
│       ├── coach_loader.py    ← mdファイル読み込み
│       ├── crisis_detector.py ← 危機介入キーワード検知
│       └── summary_generator.py ← サマリー生成
├── frontend/
│   ├── index.html        ← コーチ選択画面
│   ├── chat.html         ← チャット画面
│   ├── howto.html        ← 使い方
│   ├── privacy.html      ← プライバシーポリシー
│   ├── css/style.css
│   └── js/
│       ├── api.js
│       ├── coach_select.js
│       └── chat.js
└── sessions/             ← セッション履歴（自動作成）
```

---

## 3つのコーチ

| コーチ | ID | 特徴 |
|--------|-----|------|
| ナラティブコーチ | `narrative` | 問題の外在化・物語の書き換え |
| GROWコーチ | `grow` | GROW/WOOPフレームワークで行動計画 |
| ポジションチェンジコーチ | `position` | 3視点移動・タイムライン |

---

## 危機介入

「死にたい」などのキーワードを検知した場合、AIを呼ばずに即座に相談窓口を案内します。
検知はバックエンドで行われます。

- いのちの電話: 0120-783-556
- よりそいホットライン: 0120-279-338
