import subprocess
import sys
import os

def extract_audio(video_path):
    if not os.path.exists(video_path):
        print(f"エラー: ファイルが見つかりません: {video_path}")
        sys.exit(1)

    audio_path = os.path.splitext(video_path)[0] + "_audio.mp3"

    print(f"音声抽出中: {video_path} → {audio_path}")
    result = subprocess.run([
        "ffmpeg", "-i", video_path,
        "-vn", "-acodec", "mp3", "-q:a", "2",
        audio_path, "-y"
    ], capture_output=True, text=True)

    if result.returncode != 0:
        print("エラー:", result.stderr)
        sys.exit(1)

    print(f"音声抽出完了: {audio_path}")
    return audio_path

if __name__ == "__main__":
    video = sys.argv[1] if len(sys.argv) > 1 else input("動画ファイルのパスを入力: ")
    extract_audio(video)
