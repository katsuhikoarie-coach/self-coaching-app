import os
from pathlib import Path
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from dotenv import load_dotenv

BASE_DIR = Path(__file__).parent.parent
load_dotenv(dotenv_path=BASE_DIR / ".env")
os.makedirs(BASE_DIR / "sessions", exist_ok=True)

app = FastAPI(title="セルフコーチングAPI")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

from routers import chat, session, coach

app.include_router(chat.router, prefix="/api")
app.include_router(session.router, prefix="/api")
app.include_router(coach.router, prefix="/api")

# フロントエンドをルートで配信（html=True で / → index.html を自動配信）
app.mount("/", StaticFiles(directory=str(BASE_DIR / "frontend"), html=True), name="frontend")
