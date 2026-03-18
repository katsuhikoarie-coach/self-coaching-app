from pathlib import Path

COACH_FILE_MAP = {
    "narrative": "ナラティブコーチ.md",
    "grow": "GROWコーチ.md",
    "position": "ポジションチェンジコーチ.md",
}

_BASE_DIR = Path(__file__).parent


def load_coach_prompt(coach_id: str) -> str:
    if coach_id not in COACH_FILE_MAP:
        raise ValueError(
            f"Invalid coach_id: '{coach_id}'. Must be one of: {list(COACH_FILE_MAP.keys())}"
        )

    coach_filename = COACH_FILE_MAP[coach_id]

    paths = [
        _BASE_DIR / "../../coaches/_coach_base.md",
        _BASE_DIR / f"../../coaches/{coach_filename}",
        _BASE_DIR / "../../coaches/セッションフロー.md",
        _BASE_DIR / "../../coaches/危機介入.md",
    ]

    sections = []
    for path in paths:
        resolved = path.resolve()
        try:
            sections.append(resolved.read_text(encoding="utf-8"))
        except FileNotFoundError:
            raise FileNotFoundError(
                f"Coach prompt file not found: {resolved}"
            )

    return "\n\n---\n\n".join(sections)
