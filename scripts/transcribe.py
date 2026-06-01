import whisper
import sys
import os

def transcribe(audio_path, model_name="medium", language="ja"):
    if not os.path.exists(audio_path):
        print(f"エラー: ファイルが見つかりません: {audio_path}")
        sys.exit(1)

    print(f"モデル読み込み中: {model_name}")
    model = whisper.load_model(model_name)

    print(f"文字起こし開始: {audio_path}")
    result = model.transcribe(audio_path, language=language)

    output_path = os.path.splitext(audio_path)[0] + "_transcript.txt"
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(result["text"])

    print(f"完了！出力ファイル: {output_path}")
    return output_path

if __name__ == "__main__":
    audio = sys.argv[1] if len(sys.argv) > 1 else input("音声ファイルのパスを入力: ")
    model = sys.argv[2] if len(sys.argv) > 2 else "medium"
    transcribe(audio, model)
