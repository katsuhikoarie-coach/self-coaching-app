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

        system_instruction = """あなたはコーチングセッションのサマリーを作成する専門家です。
会話履歴を分析し、必ず以下のJSON形式のみで返答してください。
他の文字は一切含めないでください。

{
  "theme": "今日のテーマ（一文）",
  "insight": "見えてきた本音（一文）",
  "obstacle": "主な障害（一文）",
  "action": "コミットメントした行動（一文）",
  "strength": "発見した強み（一文）"
}"""

        messages_json = json.dumps(messages, ensure_ascii=False)
        user_message = f"以下のコーチングセッション（コーチ: {coach_id}）のサマリーを作成してください。\n\n{messages_json}"

        config = types.GenerateContentConfig(
            system_instruction=system_instruction,
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
