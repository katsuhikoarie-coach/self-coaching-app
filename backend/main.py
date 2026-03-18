import os
from fastapi import FastAPI, Request, Response
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import PlainTextResponse, JSONResponse
from fastapi.staticfiles import StaticFiles
from dotenv import load_dotenv

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
FRONTEND_DIR = os.path.join(BASE_DIR, "..", "frontend")
SESSIONS_DIR = os.path.join(BASE_DIR, "..", "sessions")

load_dotenv(dotenv_path=os.path.join(BASE_DIR, "..", ".env"))
os.makedirs(SESSIONS_DIR, exist_ok=True)

APP_SECRET_KEY = os.environ.get("APP_SECRET_KEY", "")
ENVIRONMENT = os.environ.get("ENVIRONMENT", "development")

docs_url = None if ENVIRONMENT == "production" else "/docs"
openapi_url = None if ENVIRONMENT == "production" else "/openapi.json"

app = FastAPI(title="セルフコーチングAPI", docs_url=docs_url, openapi_url=openapi_url)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.middleware("http")
async def api_key_middleware(request: Request, call_next):
    if request.url.path.startswith("/api/") and APP_SECRET_KEY:
        if request.headers.get("X-API-Key") != APP_SECRET_KEY:
            return JSONResponse(status_code=403, content={"detail": "Forbidden"})
    return await call_next(request)


@app.get("/js/config.js", response_class=PlainTextResponse)
async def config_js():
    return PlainTextResponse(
        f'window.APP_SECRET_KEY = "{APP_SECRET_KEY}";',
        media_type="application/javascript",
    )


from routers import chat, session, coach

app.include_router(chat.router, prefix="/api")
app.include_router(session.router, prefix="/api")
app.include_router(coach.router, prefix="/api")

# フロントエンドをルートで配信（html=True で / → index.html を自動配信）
app.mount("/", StaticFiles(directory=FRONTEND_DIR, html=True), name="frontend")
