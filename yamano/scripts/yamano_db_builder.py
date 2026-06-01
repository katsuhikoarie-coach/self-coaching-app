"""
yamano_db_builder.py
E:\ヤマノ\商品知識 以下の全サブフォルダを含む全PDFからテキストを抽出して
商品データベース(JSON)を構築するスクリプト
"""

import json
import time
import sys
from pathlib import Path
from datetime import datetime

try:
    import fitz  # pymupdf
except ImportError:
    print("エラー: pymupdf がインストールされていません。")
    print("インストール: pip install pymupdf")
    sys.exit(1)


# ==================== 設定 ====================
PDF_DIR       = Path(r"E:\ヤマノ\商品知識")
OUTPUT_JSON   = PDF_DIR / "yamano_products.json"
PROGRESS_FILE = PDF_DIR / "yamano_db_progress.json"
WAIT_SECONDS  = 3   # 各ファイル処理後の待機時間
# ==============================================


def load_progress() -> set:
    """処理済みファイル名のセットを返す"""
    if PROGRESS_FILE.exists():
        with open(PROGRESS_FILE, encoding="utf-8") as f:
            data = json.load(f)
        return set(data.get("processed", []))
    return set()


def save_progress(processed: set) -> None:
    """処理済みファイル名を保存"""
    with open(PROGRESS_FILE, "w", encoding="utf-8") as f:
        json.dump({"processed": sorted(processed)}, f, ensure_ascii=False, indent=2)


def load_database() -> list:
    """既存のJSONデータを読み込む（なければ空リスト）"""
    if OUTPUT_JSON.exists():
        with open(OUTPUT_JSON, encoding="utf-8") as f:
            return json.load(f)
    return []


def save_database(db: list) -> None:
    """データベースをJSONに保存"""
    with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
        json.dump(db, f, ensure_ascii=False, indent=2)


def extract_text_from_pdf(pdf_path: Path) -> dict:
    """
    PDFからテキストを抽出してレコードを返す

    Returns:
        dict: {
            "filename": str,
            "filepath": str,
            "page_count": int,
            "pages": [{"page": int, "text": str}, ...],
            "full_text": str,
            "processed_at": str (ISO形式)
        }
    """
    doc = fitz.open(str(pdf_path))
    pages_data = []

    for page_num in range(len(doc)):
        page = doc[page_num]
        text = page.get_text("text")
        pages_data.append({
            "page": page_num + 1,
            "text": text.strip()
        })

    full_text = "\n\n".join(
        f"--- Page {p['page']} ---\n{p['text']}"
        for p in pages_data
        if p["text"]
    )
    doc.close()

    return {
        "filename":     pdf_path.name,
        "filepath":     str(pdf_path),
        "page_count":   len(pages_data),
        "pages":        pages_data,
        "full_text":    full_text,
        "processed_at": datetime.now().isoformat(timespec="seconds")
    }


def main() -> None:
    # PDFフォルダの存在確認
    if not PDF_DIR.exists():
        print(f"エラー: フォルダが見つかりません: {PDF_DIR}")
        sys.exit(1)

    # PDFファイル一覧（サブフォルダを含む再帰検索）
    pdf_files = sorted(PDF_DIR.rglob("*.pdf"))
    if not pdf_files:
        print(f"PDFファイルが見つかりません: {PDF_DIR}")
        sys.exit(0)

    print(f"対象フォルダ : {PDF_DIR}（サブフォルダ含む）")
    print(f"PDFファイル数: {len(pdf_files)} 件")
    print(f"出力JSON    : {OUTPUT_JSON}")
    print("-" * 50)

    # 既存データと進捗を読み込み
    db        = load_database()
    processed = load_progress()

    skip_count  = 0
    done_count  = 0
    error_count = 0

    for i, pdf_path in enumerate(pdf_files, start=1):
        # 進捗管理キーは相対パス（サブフォルダ内の同名ファイルを区別するため）
        rel_key = str(pdf_path.relative_to(PDF_DIR))

        # 処理済みならスキップ
        if rel_key in processed:
            print(f"[{i:>3}/{len(pdf_files)}] スキップ（処理済み）: {rel_key}")
            skip_count += 1
            continue

        print(f"[{i:>3}/{len(pdf_files)}] 処理中: {rel_key}", end=" ... ", flush=True)

        try:
            record = extract_text_from_pdf(pdf_path)
            db.append(record)
            processed.add(rel_key)

            # 1ファイル処理するたびに即保存（途中終了対策）
            save_database(db)
            save_progress(processed)

            print(f"OK ({record['page_count']} ページ)")
            done_count += 1

        except Exception as e:
            print(f"エラー: {e}")
            error_count += 1
            # エラーが出ても次のファイルへ進む

        # レート制限対策の待機（最後のファイルは不要）
        if i < len(pdf_files):
            time.sleep(WAIT_SECONDS)

    # 結果サマリー
    print("-" * 50)
    print(f"完了     : {done_count} 件")
    print(f"スキップ : {skip_count} 件（処理済み）")
    print(f"エラー   : {error_count} 件")
    print(f"合計レコード数: {len(db)} 件")
    print(f"保存先  : {OUTPUT_JSON}")


if __name__ == "__main__":
    main()
