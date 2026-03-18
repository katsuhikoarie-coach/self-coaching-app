from google import genai
from google.genai import types
import os
import json


def generate_summary(messages: list, coach_id: str) -> dict:
    default_summary = {
        "theme": "セッション完了",
        "insight": "内省の時間を持てました",
        "obstacle": "特定できませんでした",
        "action": "次のステップを考える",
        "strength": "対話を続けられた力"
    }

    try:
        client = genai.Client(api_key=os.environ.get("GEMINI_API_KEY"))

        messages_json = json.dumps(messages, ensure_ascii=False, indent=2)
        user_message = f"""以下はコーチングセッションの会話履歴です。
この会話の内容だけを元に、以下の5項目を日本語で具体的に生成してください。
会話に出てきた具体的なキーワードや言葉をそのまま使うこと。
汎用的な表現は使わないこと。

{messages_json}

以下のJSON形式のみで返してください（他の文字は不要）：
{{
  "theme": "今日話したメインテーマを一言で",
  "insight": "会話の中で見えてきた本音や気づき",
  "obstacle": "話の中で出てきた障害や懸念",
  "action": "コミットメントした具体的な行動",
  "strength": "会話から見えたクライアントの強み"
}}"""

        config = types.GenerateContentConfig(
            max_output_tokens=1024,
        )

        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=user_message,
            config=config,
        )

        response_text = response.text.strip()

        # ```json ... ``` ブロックが含まれる場合に除去
        if response_text.startswith("```"):
            response_text = response_text.split("```")[1]
            if response_text.startswith("json"):
                response_text = response_text[4:]

        return json.loads(response_text)

    except Exception:
        return default_summary
