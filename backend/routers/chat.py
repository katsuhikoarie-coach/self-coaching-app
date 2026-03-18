from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from google import genai
from google.genai import types
import os

from services.crisis_detector import detect_crisis
from services.coach_loader import load_coach_prompt

router = APIRouter()

CRISIS_RESPONSE = """今あなたがそう感じていることを、受け取りました。何も急がなくていいです。ここにいます。

今、自分を傷つけようとしている、またはすでに行動していますか？
緊急の場合は今すぐ119番または110番に連絡してください。

🆘 今すぐ話せる相談窓口

📞 いのちの電話（無料）
　フリーダイヤル：0120-783-556
　毎日16時〜21時、毎月10日は8時〜翌8時24時間対応

📞 よりそいホットライン（24時間・無料）
　0120-279-338

📞 こころの健康相談統一ダイヤル
　0570-064-556

話してくれたこと、ここに届いています。専門家の人たちは、あなたの声を待っています。"""


class ChatRequest(BaseModel):
    session_id: str
    coach_id: str  # "narrative" | "grow" | "position"
    messages: list[dict]  # [{"role": str, "content": str}]
    user_message: str


class ChatResponse(BaseModel):
    reply: str
    is_crisis: bool
    phase: str


@router.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    try:
        is_crisis = detect_crisis(request.user_message)

        if is_crisis:
            return ChatResponse(
                reply=CRISIS_RESPONSE,
                is_crisis=True,
                phase="unknown",
            )

        try:
            system_prompt = load_coach_prompt(request.coach_id)
        except ValueError as e:
            raise HTTPException(status_code=400, detail=str(e))

        client = genai.Client(api_key=os.environ.get("GEMINI_API_KEY"))

        # 会話履歴を Gemini の形式に変換（role: user/model）
        history = []
        for msg in request.messages:
            role = "model" if msg["role"] == "assistant" else "user"
            history.append(types.Content(role=role, parts=[types.Part(text=msg["content"])]))

        config = types.GenerateContentConfig(
            system_instruction=system_prompt,
            max_output_tokens=1024,
        )

        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=history + [types.Content(role="user", parts=[types.Part(text=request.user_message)])],
            config=config,
        )

        reply_text = response.text

        phase = "unknown"
        for word in reply_text.split():
            if word.startswith("phase_"):
                phase = word
                break

        return ChatResponse(
            reply=reply_text,
            is_crisis=False,
            phase=phase,
        )

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
