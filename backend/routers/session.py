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


def get_session_path(session_id: str) -> Path:
    return SESSIONS_DIR / f"{session_id}.json"


@router.get("/session/{session_id}")
def get_session(session_id: str):
    session_path = get_session_path(session_id)
    if not session_path.exists():
        raise HTTPException(status_code=404, detail="Session not found")
    with open(session_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    return data


@router.post("/session/{session_id}")
def post_session(session_id: str, body: MessageRequest):
    session_path = get_session_path(session_id)
    SESSIONS_DIR.mkdir(parents=True, exist_ok=True)

    if session_path.exists():
        with open(session_path, "r", encoding="utf-8") as f:
            session_data = json.load(f)
    else:
        session_data = {
            "session_id": session_id,
            "coach_id": body.coach_id,
            "created_at": datetime.utcnow().isoformat(),
            "messages": [],
        }

    message = {
        "role": body.role,
        "content": body.content,
        "timestamp": datetime.utcnow().isoformat(),
    }
    session_data["messages"].append(message)

    with open(session_path, "w", encoding="utf-8") as f:
        json.dump(session_data, f, ensure_ascii=False, indent=2)

    return session_data


@router.delete("/session/{session_id}")
def delete_session(session_id: str):
    session_path = get_session_path(session_id)
    if not session_path.exists():
        raise HTTPException(status_code=404, detail="Session not found")
    session_path.unlink()
    return {"message": "Session deleted"}
