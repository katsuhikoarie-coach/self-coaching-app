CRISIS_KEYWORDS = [
    "死にたい", "消えたい", "消えてしまいたい", "死んでしまいたい",
    "希死念慮", "自殺", "自害", "終わりにしたい", "いなくなりたい",
    "生きていたくない", "誰かに殺してほしい", "事故に遭いたい",
]


def detect_crisis(text: str) -> bool:
    lowered = text.lower()
    return any(keyword in lowered for keyword in CRISIS_KEYWORDS)
