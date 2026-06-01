"""
dify_push.py
============
ローカルのプロンプトファイルを Dify に自動反映・公開するスクリプト。

使い方:
    python dify_push.py

必要な環境変数（.env に記載）:
    DIFY_SESSION_COOKIE  ... ブラウザのDevToolsで取得したセッションCookie文字列
    DIFY_CSRF_TOKEN      ... __Host-csrf_token の値
    GEMINI_API_KEY       ... GeminiのAPIキー（このスクリプトでは使用しないが.envに存在）

取得方法（DIFY_SESSION_COOKIE と DIFY_CSRF_TOKEN）:
    1. ブラウザで cloud.dify.ai にログイン
    2. DevTools > Network > 任意のリクエスト > Request Headers
    3. "cookie" ヘッダーの値をまるごとコピー → DIFY_SESSION_COOKIE に貼る
    4. "x-csrf-token" または cookie 内の __Host-csrf_token の値 → DIFY_CSRF_TOKEN に貼る
"""

import os
import sys
import json
import requests
from pathlib import Path
from dotenv import load_dotenv

# .env 読み込み
load_dotenv()

# ===== 設定 =====
APP_ID         = "0589a1e0-e669-491a-9382-37480eb85322"
BASE_URL       = "https://cloud.dify.ai"
PROMPT_FILE    = Path(__file__).parent.parent / "yamano" / "prompts" / "yamano_counseling_prompt_v10_slim.txt"

SESSION_COOKIE = os.getenv("DIFY_SESSION_COOKIE")
CSRF_TOKEN     = os.getenv("DIFY_CSRF_TOKEN")
# ================


def load_prompt() -> str:
    if not PROMPT_FILE.exists():
        print(f"❌ エラー: プロンプトファイルが見つかりません → {PROMPT_FILE}")
        sys.exit(1)
    text = PROMPT_FILE.read_text(encoding="utf-8")
    print(f"✅ プロンプト読み込み完了（{len(text):,} 文字）")
    return text


def build_headers() -> dict:
    if not SESSION_COOKIE or not CSRF_TOKEN:
        print("❌ エラー: .env に DIFY_SESSION_COOKIE と DIFY_CSRF_TOKEN を設定してください")
        print("    取得方法: ブラウザ DevTools > Network > Request Headers > cookie / x-csrf-token")
        sys.exit(1)
    return {
        "Content-Type": "application/json",
        "Cookie": SESSION_COOKIE,
        "X-Csrf-Token": CSRF_TOKEN,
        "Referer": f"{BASE_URL}/app/{APP_ID}/configuration",
        "Origin": BASE_URL,
    }


def update_model_config(prompt_text: str, headers: dict) -> bool:
    """プロンプトを保存する"""
    url = f"{BASE_URL}/console/api/apps/{APP_ID}/model-config"

    # Dify の model-config POSTボディ（最小構成）
    # pre_prompt 以外のフィールドは既存設定を維持するため最低限の値を渡す
    payload = {
        "pre_prompt": prompt_text,
        "prompt_type": "simple",
        "chat_prompt_config": {},
        "completion_prompt_config": {},
        "user_input_form": [],
        "dataset_query_variable": "",
        "opening_statement": "こんにちは！朝霧ヤマノのAIカウンセラーです🌿\n山の恵みで、あなたのお肌を整えるお手伝いをします。\nまず、お客様の年代を教えていただけますか？",
        "more_like_this": {"enabled": False},
        "suggested_questions": [],
        "suggested_questions_after_answer": {"enabled": False},
        "speech_to_text": {"enabled": False},
        "text_to_speech": {"enabled": False, "voice": "", "language": ""},
        "retriever_resource": {"enabled": False},
        "sensitive_word_avoidance": {"enabled": False},
        "agent_mode": {
            "enabled": False,
            "max_iteration": 5,
            "strategy": "react",
            "tools": []
        },
        "model": {
            "provider": "google",
            "name": "gemini-2.5-flash-lite",
            "mode": "chat",
            "completion_params": {}
        },
        "dataset_configs": {
            "retrieval_model": "single",
            "datasets": {"datasets": []}
        },
        "file_upload": {"image": {"enabled": False, "number_limits": 3, "transfer_methods": ["remote_url", "local_file"]}},
        "annotation_reply": {"enabled": False}
    }

    print(f"📤 model-config を送信中...")
    res = requests.post(url, headers=headers, json=payload, timeout=30)

    if res.status_code == 200:
        print(f"✅ model-config 保存成功")
        return True
    else:
        print(f"❌ model-config 保存失敗: HTTP {res.status_code}")
        try:
            print(f"   レスポンス: {res.json()}")
        except Exception:
            print(f"   レスポンス: {res.text[:200]}")
        return False


def publish_app(headers: dict) -> bool:
    """更新を公開する"""
    url = f"{BASE_URL}/console/api/apps/{APP_ID}/publish"
    print(f"🚀 公開中...")
    res = requests.post(url, headers=headers, json={}, timeout=30)

    if res.status_code in (200, 201, 204):
        print(f"✅ 公開成功")
        return True
    else:
        print(f"❌ 公開失敗: HTTP {res.status_code}")
        try:
            print(f"   レスポンス: {res.json()}")
        except Exception:
            print(f"   レスポンス: {res.text[:200]}")
        return False


def main():
    print("=" * 50)
    print("  Dify プロンプト自動更新スクリプト")
    print("=" * 50)

    prompt_text = load_prompt()
    headers     = build_headers()

    if not update_model_config(prompt_text, headers):
        sys.exit(1)

    if not publish_app(headers):
        print("⚠️  保存は成功しましたが公開に失敗しました。Difyの管理画面から手動で公開してください。")
        sys.exit(1)

    print()
    print(f"🎉 完了！ プロンプト（{len(prompt_text):,}文字）を更新・公開しました")
    print(f"   アプリURL: https://udify.app/chat/waoRWWk9jBK9e7uu")


if __name__ == "__main__":
    main()
