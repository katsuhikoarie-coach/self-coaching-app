from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from pathlib import Path
from datetime import datetime
import json

router = APIRouter()

SESSIONS_DIR = Path(__file__).parent.parent / "sessions"


class MessageRequest(BaseModel):
    role: str
    content: str
    coach_id: str = ""
    input_tokens: int = 0
    output_tokens: int = 0


def get_session_path(session_id: str) -> Path:
    return SESSIONS_DIR / f"{session_id}.json"


def save_message_to_session(
    session_id: str,
    role: str,
    content: str,
    coach_id: str = "",
    input_tokens: int = 0,
    output_tokens: int = 0,
) -> dict:
    """セッションファイルにメッセージを追記する共通ヘルパー。chat.py からも呼び出せる。"""
    session_path = get_session_path(session_id)
    SESSIONS_DIR.mkdir(parents=True, exist_ok=True)

    if session_path.exists():
        with open(session_path, "r", encoding="utf-8") as f:
            session_data = json.load(f)
    else:
        session_data = {
            "session_id": session_id,
            "coach_id": coach_id,
            "created_at": datetime.utcnow().isoformat(),
            "messages": [],
        }

    message = {
        "role": role,
        "content": content,
        "timestamp": datetime.utcnow().isoformat(),
        "input_tokens": input_tokens,
        "output_tokens": output_tokens,
    }
    session_data["messages"].append(message)

    with open(session_path, "w", encoding="utf-8") as f:
        json.dump(session_data, f, ensure_ascii=False, indent=2)

    return session_data


@router.get("/session/{session_id}")
def get_session(session_id: str):
    session_path = get_session_path(session_id)
    if not session_path.exists():
        raise HTTPException(status_code=404, detail="Session not found")
    with open(session_path, "r", encoding="utf-8") as f:
        return json.load(f)


@router.post("/session/{session_id}")
def post_session(session_id: str, body: MessageRequest):
    return save_message_to_session(
        session_id=session_id,
        role=body.role,
        content=body.content,
        coach_id=body.coach_id,
        input_tokens=body.input_tokens,
        output_tokens=body.output_tokens,
    )


@router.delete("/session/{session_id}")
def delete_session(session_id: str):
    session_path = get_session_path(session_id)
    if not session_path.exists():
        raise HTTPException(status_code=404, detail="Session not found")
    session_path.unlink()
    return {"message": "Session deleted"}


@router.get("/session/{session_id}/tokens")
def get_session_tokens(session_id: str):
    session_path = get_session_path(session_id)
    if not session_path.exists():
        raise HTTPException(status_code=404, detail="Session not found")
    with open(session_path, "r", encoding="utf-8") as f:
        session_data = json.load(f)

    messages = session_data.get("messages", [])
    total_input = sum(m.get("input_tokens", 0) for m in messages)
    total_output = sum(m.get("output_tokens", 0) for m in messages)

    return {
        "session_id": session_id,
        "total_input_tokens": total_input,
        "total_output_tokens": total_output,
        "total_tokens": total_input + total_output,
        "message_count": len(messages),
    }
