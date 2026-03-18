# セルフコーチングWebアプリ 構築指示書

## プロジェクト概要

AIコーチと対話するセルフコーチングWebアプリを構築する。
バックエンドはPython（FastAPI）。フロントエンドはHTML/CSS/JSのシンプル構成。
コーチは3種類（ナラティブ・GROW・ポジションチェンジ）で、ユーザーが選択する。
コーチプロンプトは `/coaches` ディレクトリのmdファイルから動的に読み込む。

---

## ディレクトリ構成（目標）

```
/
├── CLAUDE.md                  ← この指示書
├── coaches/                   ← コーチプロンプト（mdファイル）
│   ├── _coach_base.md
│   ├── ナラティブコーチ.md
│   ├── GROWコーチ.md
│   └── ポジションチェンジコーチ.md
├── core/
│   ├── セッションフロー.md
│   └── 危機介入.md
├── meta/
│   ├── 在り方.md
│   └── コーチ選択ロジック.md
├── backend/
│   ├── main.py                ← FastAPIエントリーポイント
│   ├── routers/
│   │   ├── chat.py            ← チャットAPI
│   │   ├── session.py         ← セッション管理API
│   │   └── coach.py           ← コーチ情報API
│   ├── services/
│   │   ├── coach_loader.py    ← mdファイル読み込み
│   │   ├── prompt_builder.py  ← システムプロンプト構築
│   │   ├── crisis_detector.py ← 危機介入キーワード検知
│   │   └── summary_generator.py ← サマリー生成
│   ├── models/
│   │   └── schemas.py         ← Pydanticスキーマ
│   └── requirements.txt
├── frontend/
│   ├── index.html             ← コーチ選択画面
│   ├── chat.html              ← チャット画面
│   ├── privacy.html           ← プライバシーポリシー
│   ├── howto.html             ← 使い方説明
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── coach_select.js
│       ├── chat.js
│       └── api.js
└── sessions/                  ← セッション履歴（JSONファイル）
```

---

## リードエージェントへの指示

以下の手順でサブエージェントに並列で作業を分配すること。
各サブエージェントは独立して作業できる。依存関係がある場合は順序を守る。

### フェーズ1：基盤（並列実行可）

以下の3つを同時に走らせる。

**サブエージェントA：バックエンド基盤**
```
backend/requirements.txt と backend/main.py を作成せよ。
FastAPIのアプリ起動・CORSの設定・ルーター登録まで行う。
requirements.txtには以下を含めること：
fastapi, uvicorn, anthropic, python-dotenv, pydantic
```

**サブエージェントB：コーチローダー**
```
backend/services/coach_loader.py を作成せよ。
/coaches/_coach_base.md と 指定されたコーチ名のmdファイルを読み込み、
結合してシステムプロンプト文字列を返す関数を実装する。
さらに /core/危機介入.md の内容も必ずシステムプロンプトの末尾に追加する。
関数名: load_coach_prompt(coach_id: str) -> str
```

**サブエージェントC：危機介入検知**
```
backend/services/crisis_detector.py を作成せよ。
以下のキーワードリストを持ち、メッセージにいずれかが含まれる場合Trueを返す関数を実装する。
キーワード: 死にたい、消えたい、消えてしまいたい、死んでしまいたい、希死念慮、
自殺、自害、終わりにしたい、いなくなりたい、生きていたくない、
誰かに殺してほしい、事故に遭いたい
関数名: detect_crisis(text: str) -> bool
```

### フェーズ2：コア機能（フェーズ1完了後に並列実行）

**サブエージェントD：チャットAPI**
```
backend/routers/chat.py を作成せよ。
POST /api/chat エンドポイントを実装する。

リクエスト:
{
  "session_id": str,
  "coach_id": str,          # "narrative" | "grow" | "position"
  "messages": [{"role": str, "content": str}],
  "user_message": str
}

処理の流れ:
1. crisis_detector.detect_crisis(user_message) を実行
2. Trueの場合はAIを呼ばず crisis_response を即返す
3. Falseの場合はcoach_loader.load_coach_prompt(coach_id) でプロンプト取得
4. Anthropic APIを呼ぶ（モデル: claude-opus-4-5、max_tokens: 1024）
5. レスポンスを返す

レスポンス:
{
  "reply": str,
  "is_crisis": bool,
  "phase": str              # AIのレスポンスから現在フェーズを推定
}

crisis_responseの内容はcore/危機介入.mdのStep 1〜4の内容をそのまま使う。
```

**サブエージェントE：セッション管理API**
```
backend/routers/session.py を作成せよ。
セッション履歴をsessions/{session_id}.jsonに保存・取得・削除する。

エンドポイント:
GET  /api/session/{session_id}   ← 履歴取得
POST /api/session/{session_id}   ← メッセージ追記
DELETE /api/session/{session_id} ← 履歴削除

履歴のJSON構造:
{
  "session_id": str,
  "coach_id": str,
  "created_at": str,
  "messages": [{"role": str, "content": str, "timestamp": str}]
}
```

**サブエージェントF：サマリー生成**
```
backend/services/summary_generator.py を作成せよ。
セッションの messages リストを受け取り、以下の構造のサマリーを生成して返す関数を実装する。

関数名: generate_summary(messages: list, coach_id: str) -> dict

Anthropic APIを呼び、以下のJSON形式で返すよう指示する:
{
  "theme": str,        # 今日のテーマ
  "insight": str,      # 見えてきた本音
  "obstacle": str,     # 主な障害
  "action": str,       # コミットメントした行動
  "strength": str      # 発見した強み
}

POST /api/summary エンドポイントも routers/coach.py に追加する。
```

### フェーズ3：フロントエンド（フェーズ2完了後に並列実行）

**サブエージェントG：コーチ選択画面**
```
frontend/index.html と frontend/js/coach_select.js を作成せよ。

コーチは3種類:
- ナラティブコーチ（id: narrative）「自分の物語を、書き直す」
- GROWコーチ（id: grow）「目標を決めて、動き出す」
- ポジションチェンジコーチ（id: position）「視点を変えると、見えてくる」

気分チップ（複数選択可）でコーチをハイライト表示する。
選択後「セッションを始める」ボタンでchat.html?coach={id}に遷移する。
デザインは温かみのある丸み・やわらかい色調。スマホ・PC両対応。
```

**サブエージェントH：チャット画面**
```
frontend/chat.html と frontend/js/chat.js を作成せよ。

URLパラメータからcoach_idを取得する。
session_idはUUIDをlocalStorageで管理する。
メッセージ送信時はPOST /api/chat を呼ぶ。
is_crisis: true の場合は通常の返答の代わりに危機介入バナーを表示し、
相談窓口（いのちの電話 0120-783-556、よりそいホットライン 0120-279-338）を必ず表示する。
フェーズバー（Phase 1〜7）を上部に表示する。
「コーチを変える」ボタンでindex.htmlに戻れる。
「セッションを終える」ボタンでGET /api/summary を呼びサマリーを表示する。
「履歴を削除」ボタンでDELETE /api/session/{session_id} を呼ぶ。
デザインはスマホ・PC両対応。コーチ選択画面と統一したトーン。
```

**サブエージェントI：静的ページ**
```
frontend/howto.html と frontend/privacy.html を作成せよ。

howto.html の内容:
- このアプリでできること（3コーチの説明）
- セッションの流れ（Phase 0〜6の簡単な説明）
- 危機介入について（AIの限界と専門機関の案内）
- よくある質問

privacy.html の内容:
- 収集する情報（セッション履歴はローカルのみ）
- 外部送信される情報（Anthropic APIにメッセージが送信される旨）
- データの削除方法
- お問い合わせ
```

### フェーズ4：統合・仕上げ（全フェーズ完了後）

**リードエージェント自身が行う:**
```
1. backend/main.py にすべてのルーターが正しくincludeされているか確認・修正
2. frontend/css/style.css を作成し、全画面で共通のデザイントークンを定義する
3. frontend/js/api.js を作成し、バックエンドのベースURLと共通fetch関数を定義する
4. .env.example を作成する（ANTHROPIC_API_KEY=your_key_here）
5. README.md を作成する（セットアップ手順・起動方法）
6. 全体を通して動作確認し、エラーがあれば修正する
```

---

## 共通の実装ルール

- Anthropic APIキーは環境変数 ANTHROPIC_API_KEY から読む。コードにハードコードしない。
- コーチIDは "narrative" / "grow" / "position" の3種類のみ。それ以外は400エラー。
- セッションIDはUUID4形式。
- すべてのAPIレスポンスはJSON。エラー時も {"error": str} 形式で返す。
- フロントエンドはフレームワーク不使用。バニラJS・HTML・CSSのみ。
- CSSはCSS変数でカラーを管理する。ハードコードしない。
- スマホ（375px〜）とPC（1024px〜）の両方でレイアウトが崩れないこと。
- 危機介入の検知はバックエンドで行う。フロントエンドには判定ロジックを持たせない。

---

## 起動確認コマンド

```bash
cd backend
pip install -r requirements.txt
uvicorn main:app --reload
```

フロントエンドは `frontend/index.html` をブラウザで直接開くか、
バックエンドから静的ファイルとして配信する（main.pyにStaticFiles設定を追加）。
