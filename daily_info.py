"""
毎朝自動情報収集スクリプト
- Gemini 2.5 Flash + Google Search grounding で各テーマを調査
- Gmail App Password でメール送信
"""

import os
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime, timezone, timedelta

from google import genai
from google.genai.types import Tool, GenerateContentConfig, GoogleSearch

# --- 設定 ---
GEMINI_API_KEY = os.environ["GEMINI_API_KEY"]
GMAIL_USER     = os.environ["GMAIL_USER"]       # 送信元アドレス（例: yourname@gmail.com）
GMAIL_APP_PASS = os.environ["GMAIL_APP_PASS"]   # Gmail App Password
MAIL_TO        = os.environ.get("MAIL_TO", "katsuhiko.arie@gmail.com")
MODEL          = "gemini-2.5-flash"

THEMES = [
    ("AIの最新情報",                     "AIや生成AI、大規模言語モデルに関する最新ニュースや研究成果を日本語で要点を3〜5点、箇条書きでまとめてください。今日の日付を考慮して直近の情報を重視してください。"),
    ("メンタルコーチングの最新情報",       "メンタルコーチング、コーチング手法、マインドフルネス、心理的安全性などに関する最新トレンドや研究・事例を日本語で要点を3〜5点、箇条書きでまとめてください。"),
    ("山野愛子どろんこ美容・化粧品関連の情報", "山野愛子ジャポネスクグループ、どろんこ美容、山野愛子関連の化粧品・スキンケア・美容に関する最新情報を日本語で要点を3〜5点、箇条書きでまとめてください。"),
    ("世界情勢の最新情報",                "今日の世界情勢、国際ニュース、地政学的動向に関する重要トピックを日本語で要点を3〜5点、箇条書きでまとめてください。"),
    ("海外での日本に関する最新情報",       "海外メディアや国際社会における日本への注目ニュース、日本関連の国際的トピックを日本語で要点を3〜5点、箇条書きでまとめてください。"),
]


def search_and_summarize(client: genai.Client, theme: str, prompt: str) -> str:
    """Gemini + Google Search でテーマを調査してまとめる"""
    search_tool = Tool(google_search=GoogleSearch())
    try:
        response = client.models.generate_content(
            model=MODEL,
            contents=prompt,
            config=GenerateContentConfig(
                tools=[search_tool],
                response_modalities=["TEXT"],
            ),
        )
        return response.text.strip()
    except Exception as e:
        return f"（情報取得中にエラーが発生しました: {e}）"


def build_email_body(summaries: list[tuple[str, str]], jst_now: datetime) -> str:
    date_str = jst_now.strftime("%Y年%m月%d日")
    lines = [
        f"【毎朝情報】{date_str}",
        f"配信時刻: {jst_now.strftime('%H:%M')} JST",
        "=" * 50,
        "",
    ]
    for theme, content in summaries:
        lines.append(f"## {theme}")
        lines.append("")
        lines.append(content)
        lines.append("")
        lines.append("-" * 40)
        lines.append("")
    lines.append("※ このメールはGitHub Actionsにより自動送信されています。")
    return "\n".join(lines)


def send_email(subject: str, body: str) -> None:
    msg = MIMEMultipart("alternative")
    msg["Subject"] = subject
    msg["From"]    = GMAIL_USER
    msg["To"]      = MAIL_TO

    # プレーンテキストとHTMLの両方を用意
    text_part = MIMEText(body, "plain", "utf-8")

    # HTML版：見出しやセクションを読みやすく整形
    html_body = body.replace("\n", "<br>\n")
    html_body = html_body.replace("## ", "<h2>").replace("<br>\n<h2>", "<br>\n</h2><h2>")
    html_body = f"<html><body><pre style='font-family:sans-serif;font-size:14px;'>{body}</pre></body></html>"
    html_part = MIMEText(html_body, "html", "utf-8")

    msg.attach(text_part)
    msg.attach(html_part)

    with smtplib.SMTP_SSL("smtp.gmail.com", 465) as server:
        server.login(GMAIL_USER, GMAIL_APP_PASS)
        server.sendmail(GMAIL_USER, MAIL_TO, msg.as_string())


def main():
    jst = timezone(timedelta(hours=9))
    jst_now = datetime.now(jst)
    date_str = jst_now.strftime("%Y年%m月%d日")

    print(f"[{jst_now.strftime('%Y-%m-%d %H:%M')} JST] 情報収集を開始します")

    client = genai.Client(api_key=GEMINI_API_KEY)

    summaries = []
    for theme, prompt in THEMES:
        print(f"  調査中: {theme} ...")
        content = search_and_summarize(client, theme, prompt)
        summaries.append((theme, content))
        print(f"  完了: {theme}")

    body    = build_email_body(summaries, jst_now)
    subject = f"【毎朝情報】{date_str}"

    print("メールを送信中 ...")
    send_email(subject, body)
    print(f"送信完了 → {MAIL_TO}")


if __name__ == "__main__":
    main()
