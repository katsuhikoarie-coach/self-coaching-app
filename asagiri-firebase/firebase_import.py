#!/usr/bin/env python3
"""
朝霧店 Firebase データ移行スクリプト
HTMLファイルに埋め込まれたJSONデータをFirebase Realtime Databaseに一括インポートします

【使い方】
  1. firebase login を完了してください（別ターミナルで実行）
  2. python firebase_import.py           → Dryrun（確認のみ）
  3. python firebase_import.py --execute → 本番インポート実行

【依存ライブラリ】
  pip install requests  （標準ライブラリのみで動作します）
"""
import json, re, sys, os, time, subprocess
import urllib.request
import urllib.error

# ──────────────────────────────────────────────────────────────
# 設定
# ──────────────────────────────────────────────────────────────
FIREBASE_URL  = "https://asagiri-sale-default-rtdb.firebaseio.com"
HTML_FILE     = os.path.join(os.path.dirname(__file__), '..', '朝霧店_売上顧客管理_v2.html')
BATCH_SIZE    = 100


def get_access_token() -> str:
    """firebase login 済みの認証トークンを取得する"""
    try:
        result = subprocess.run(
            ['firebase', 'login:ci', '--no-localhost'],
            capture_output=True, text=True, timeout=10
        )
        # CIトークン取得は別フロー。まず通常ログインのトークンを使う
    except Exception:
        pass

    # firebase login 後のトークンを configstore から取得
    config_paths = [
        os.path.expanduser('~/.config/configstore/firebase-tools.json'),
        os.path.expanduser('~\\.config\\configstore\\firebase-tools.json'),
        os.path.expandvars('%APPDATA%\\..\\Local\\configstore\\firebase-tools.json'),
    ]
    for path in config_paths:
        path = os.path.expandvars(path)
        if os.path.exists(path):
            with open(path, 'r', encoding='utf-8') as f:
                config = json.load(f)
            token = (
                config.get('tokens', {}).get('access_token') or
                config.get('tokens', {}).get('id_token')
            )
            if token:
                print(f'認証トークン取得: {path}')
                return token

    # firebase CLI で access_token を取得
    try:
        result = subprocess.run(
            ['firebase', 'auth:export', '--format=json', '--project', 'asagiri-sale'],
            capture_output=True, text=True, timeout=15
        )
    except Exception:
        pass

    return None


def get_token_via_gcloud() -> str:
    """gcloud auth print-access-token で取得を試みる"""
    try:
        result = subprocess.run(
            ['gcloud', 'auth', 'print-access-token'],
            capture_output=True, text=True, timeout=10
        )
        if result.returncode == 0:
            return result.stdout.strip()
    except Exception:
        pass
    return None


def firebase_request(method: str, path: str, data=None, token: str = None, dry_run: bool = True):
    """Firebase REST APIにリクエストを送る"""
    url = f'{FIREBASE_URL}/{path}.json'
    if token:
        url += f'?access_token={token}'

    payload = json.dumps(data, ensure_ascii=False).encode('utf-8') if data is not None else None

    if dry_run:
        size_kb = len(payload) / 1024 if payload else 0
        count = len(data) if isinstance(data, dict) else '?'
        print(f'  [DRYRUN] {method} {path} ({size_kb:.1f} KB, {count}件)')
        return True

    try:
        req = urllib.request.Request(
            url, data=payload, method=method,
            headers={'Content-Type': 'application/json'} if payload else {}
        )
        with urllib.request.urlopen(req, timeout=60) as resp:
            resp.read()
        return True
    except urllib.error.HTTPError as e:
        body = e.read().decode('utf-8', errors='replace')
        print(f'  HTTP ERROR {e.code}: {body[:300]}')
        if e.code == 401:
            print('  → 認証エラー: firebase login を実行してください')
        return False
    except Exception as e:
        print(f'  ERROR: {e}')
        return False


def import_in_batches(path: str, data: dict, batch_size: int, token: str, dry_run: bool) -> int:
    items = list(data.items())
    total = len(items)
    success = 0
    for i in range(0, total, batch_size):
        batch = dict(items[i:i + batch_size])
        end = min(i + batch_size, total)
        print(f'  バッチ {i+1}〜{end} / {total}...', end='', flush=True)

        url = f'{FIREBASE_URL}/{path}.json'
        if token:
            url += f'?access_token={token}'
        payload = json.dumps(batch, ensure_ascii=False).encode('utf-8')
        size_kb = len(payload) / 1024

        if dry_run:
            print(f' [DRYRUN] ({size_kb:.1f} KB) OK')
            success += len(batch)
            continue

        try:
            req = urllib.request.Request(
                url, data=payload, method='PATCH',
                headers={'Content-Type': 'application/json'}
            )
            with urllib.request.urlopen(req, timeout=60) as resp:
                resp.read()
            success += len(batch)
            print(f' OK ({size_kb:.1f} KB)')
        except urllib.error.HTTPError as e:
            body = e.read().decode('utf-8', errors='replace')
            print(f' NG ({e.code}: {body[:100]})')
            if e.code == 401:
                print('認証エラー。firebase login を実行してから再実行してください。')
                sys.exit(1)
        except Exception as e:
            print(f' NG ({e})')

        time.sleep(0.3)

    return success


def extract_db_from_html(html_path: str) -> dict:
    print(f'HTML読み込み: {html_path}')
    with open(html_path, 'r', encoding='utf-8') as f:
        content = f.read()
    for line in content.split('\n'):
        if line.startswith('const DB='):
            db_json = line[len('const DB='):].rstrip().rstrip(';')
            print('JSONパース中...')
            db = json.loads(db_json)
            print('抽出完了:')
            for k, v in db.items():
                print(f'  {k}: {len(v)} items')
            return db
    raise ValueError('const DB= が見つかりませんでした')


def to_obj(items: list, key: str) -> dict:
    return {str(item.get(key, '')): item for item in items if item.get(key)}


def main():
    dry_run = '--execute' not in sys.argv

    print('=' * 60)
    if dry_run:
        print('DRYRUNモード（実際の書き込みは行いません）')
        print('本番実行: python firebase_import.py --execute')
    else:
        print('本番実行モード - Firebaseへの書き込みを開始します')
    print('=' * 60)

    # 認証トークン取得（dryrunでも確認する）
    token = None
    if not dry_run:
        token = get_access_token()
        if not token:
            # Firebase CLIのアクセストークンを直接取得
            try:
                result = subprocess.run(
                    ['firebase', '--project', 'asagiri-sale', 'database:get', '/', '--shallow'],
                    capture_output=True, text=True, timeout=15
                )
                if 'Error' in result.stderr and 'login' in result.stderr:
                    print('\nエラー: firebase login が必要です')
                    print('別のターミナルで: firebase login')
                    sys.exit(1)
            except Exception:
                pass
        print(f'認証: {"OK" if token else "firebase CLIのセッションを使用"}')

    # データ抽出
    db = extract_db_from_html(HTML_FILE)

    print()
    sales_obj = to_obj(db['sales'], 'id')
    cust_obj  = to_obj(db['customers'], 'id')
    prod_obj  = to_obj(db['products'], 'code')
    stock_obj = to_obj(db['stock'], 'code')

    print('[1/4] 売上データのインポート...')
    n = import_in_batches('sales', sales_obj, BATCH_SIZE, token, dry_run)
    print(f'  → {n}/{len(sales_obj)} 件完了\n')

    print('[2/4] 顧客データのインポート...')
    n = import_in_batches('customers', cust_obj, BATCH_SIZE, token, dry_run)
    print(f'  → {n}/{len(cust_obj)} 件完了\n')

    print('[3/4] 商品マスタのインポート...')
    n = import_in_batches('products', prod_obj, BATCH_SIZE, token, dry_run)
    print(f'  → {n}/{len(prod_obj)} 件完了\n')

    print('[4/4] 在庫データのインポート...')
    n = import_in_batches('stock', stock_obj, BATCH_SIZE, token, dry_run)
    print(f'  → {n}/{len(stock_obj)} 件完了\n')

    if 'staff' in db:
        print('[オプション] スタッフデータのインポート...')
        firebase_request('PUT', 'staff', db['staff'], token, dry_run)
    if 'ranks' in db:
        print('[オプション] ランクデータのインポート...')
        firebase_request('PUT', 'ranks', db['ranks'], token, dry_run)

    print()
    print('=' * 60)
    if dry_run:
        print('Dryrun完了。本番実行: python firebase_import.py --execute')
    else:
        print(f'インポート完了! Firebase Console: {FIREBASE_URL}')
    print('=' * 60)


if __name__ == '__main__':
    main()
