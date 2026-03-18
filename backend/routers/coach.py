from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.summary_generator import generate_summary

router = APIRouter()

COACHES = [
    {
        "id": "narrative",
        "name": "ナラティブコーチ",
        "tagline": "自分の物語を、書き直す",
        "description": "今の自分を縛っている「物語」を外から眺め、新しい解釈を見つけます。",
        "moods": ["モヤモヤしている", "自分を責めてしまう", "過去が頭から離れない"]
    },
    {
        "id": "grow",
        "name": "GROWコーチ",
        "tagline": "目標を決めて、動き出す",
        "description": "目標・現実・選択肢・行動の4ステップで、具体的な一歩を一緒に見つけます。",
        "moods": ["やりたいことがある", "行動に移せない", "頭を整理したい"]
    },
    {
        "id": "position",
        "name": "ポジションチェンジコーチ",
        "tagline": "視点を変えると、見えてくる",
        "description": "自分・相手・第三者の3つの視点を移動しながら、新しい気づきを引き出します。",
        "moods": ["人間関係で悩んでいる", "決断できない", "同じことを繰り返している"]
    }
]


class SummaryRequest(BaseModel):
    session_id: str
    coach_id: str
    messages: list[dict]


@router.get("/coaches")
def get_coaches():
    return COACHES


@router.post("/summary")
def post_summary(request: SummaryRequest):
    try:
        result = generate_summary(request.messages, request.coach_id)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
