#!/usr/bin/env python3
"""
朝霧店 Firebase → MariaDB データ移行スクリプト

【事前準備】
  pip install pymysql requests

【使い方】
  1. Firebase Realtime Database から JSON エクスポート
     Firebase Console → Realtime Database → 「…」メニュー → 「JSONをエクスポート」
     ファイル名を firebase_export.json として同じフォルダに置く

  2. 下記の設定を入力する（DB_PASSを埋める）

  3. Dryrun確認:
     python migrate.py

  4. 本番実行:
     python migrate.py --execute
"""

import json
import sys
import os
import math
from datetime import datetime

# ──────────────────────────────────────────────────────────────
# 設定（実行前に編集してください）
# ──────────────────────────────────────────────────────────────
DB_HOST = 'localhost'
DB_NAME = 'asagiri'
DB_USER = 'asagiri'
DB_PASS = 'YOUR_DB_PASSWORD'   # ← DBパスワードを入力

FIREBASE_EXPORT_FILE = os.path.join(os.path.dirname(__file__), 'firebase_export.json')

# 消費税8%対象商品コード（index.html と同一リスト）
TAX8_RAW = [
    '0329','0330','0670','1659','2646','3025','3304',
    '6224','6225','6226','6228','6250','6251','6252','6253','6254',
    '6278','6282','6288','6289','6291','6292',
    '6303','6304','6305','6306','6307','6308','630u',
    '6311','6312','6313','6314','6315','6316','6317','631u',
    '6323','6325','6329','6335','6336','6337','6515',
    '7628','7629','7630',
    'Y001','Y002','Y007','Y008','Y059','Y067','Y068','Y085',
    'Y628','Y629','Y633','Y635','Y636',
    'Z025','Z601','z101','z102','ｚ101','ｚ102'
]

def normalize_code(code):
    if not code:
        return ''
    code = str(code).strip()
    # 全角英数→半角
    result = ''
    for c in code:
        cp = ord(c)
        if 0xFF01 <= cp <= 0xFF5E:
            result += chr(cp - 0xFEE0)
        else:
            result += c
    return result.upper()

TAX8_CODES = set(normalize_code(c) for c in TAX8_RAW)
def is_8pct(code):
    return normalize_code(code) in TAX8_CODES


# ──────────────────────────────────────────────────────────────
# DB接続
# ──────────────────────────────────────────────────────────────
def get_db():
    try:
        import pymysql
        conn = pymysql.connect(
            host=DB_HOST, db=DB_NAME, user=DB_USER, password=DB_PASS,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        return conn
    except ImportError:
        print('エラー: pymysql が見つかりません。pip install pymysql を実行してください。')
        sys.exit(1)
    except Exception as e:
        print(f'DB接続エラー: {e}')
        sys.exit(1)


# ──────────────────────────────────────────────────────────────
# ユーティリティ
# ──────────────────────────────────────────────────────────────
def safe_date(val):
    """YYYY-MM-DD 形式の日付文字列を返す（不正値はNone）"""
    if not val:
        return None
    s = str(val).strip()
    if len(s) >= 10 and s[4] == '-' and s[7] == '-':
        return s[:10]
    return None

def safe_str(val, maxlen=None):
    s = str(val).strip() if val is not None else ''
    if maxlen:
        s = s[:maxlen]
    return s

def safe_int(val, default=0):
    try:
        return int(val)
    except (TypeError, ValueError):
        return default

def safe_float(val, default=0.0):
    try:
        return float(val)
    except (TypeError, ValueError):
        return default

def calc_slip(details, get_prod_price):
    """伝票明細から税込合計を計算"""
    sub10 = sub8 = 0
    for d in details:
        code = d.get('code', d.get('c', ''))
        qty = safe_int(d.get('qty', d.get('q', 1)), 1)
        kake = safe_float(d.get('kake', 1.0), 1.0)
        price = get_prod_price(code)
        shitane = math.floor(price * kake) * qty
        if is_8pct(code):
            sub8 += shitane
        else:
            sub10 += shitane
    tax10 = math.floor(sub10 * 0.1)
    tax8  = math.floor(sub8  * 0.08)
    return sub10, sub8, tax10, tax8, sub10 + tax10 + sub8 + tax8


# ──────────────────────────────────────────────────────────────
# 顧客マスタ移行
# ──────────────────────────────────────────────────────────────
def migrate_customers(cur, data, dry_run):
    customers = data.get('customers', {})
    if isinstance(customers, list):
        customers = {str(i): c for i, c in enumerate(customers) if c}
    print(f'[顧客] {len(customers)} 件')
    ok = 0
    for key, c in customers.items():
        if not c:
            continue
        cid = safe_str(c.get('id') or c.get('no') or key, 20)
        if not cid:
            continue
        name = safe_str(c.get('name', ''), 100)
        if not name:
            continue
        addr = safe_str(c.get('addr', ''), 200)
        if not addr:
            zip_ = safe_str(c.get('zip', ''), 10)
            addr2 = safe_str(c.get('addr2', ''), 190)
            addr = (zip_ + ' ' + addr2).strip() if zip_ else addr2
        params = (
            cid,
            name,
            safe_str(c.get('kana', ''), 100),
            safe_str(c.get('zip', ''), 10),
            addr[:200],
            safe_str(c.get('tel', ''), 20),
            safe_str(c.get('mobile', ''), 20),
            safe_str(c.get('email', ''), 100),
            safe_str(c.get('bday') or c.get('birth', ''), 20),
            safe_str(c.get('rank_id', ''), 5),
            safe_str(c.get('rank') or c.get('rank_name', '一般'), 50),
            safe_str(c.get('staff', ''), 50),
            safe_str(c.get('note', '')),
            1 if c.get('homecare') else 0,
            1 if c.get('keep_item') else 0,
            0 if c.get('active') is False else 1,
        )
        if not dry_run:
            cur.execute("""
                INSERT INTO customers
                  (id, name, kana, zip, addr, tel, mobile, email, bday,
                   rank_id, rank_name, staff, note, homecare, keep_item, active)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                  name=VALUES(name), kana=VALUES(kana), zip=VALUES(zip),
                  addr=VALUES(addr), tel=VALUES(tel), mobile=VALUES(mobile),
                  email=VALUES(email), bday=VALUES(bday), rank_name=VALUES(rank_name),
                  staff=VALUES(staff), note=VALUES(note), active=VALUES(active)
            """, params)
        ok += 1
    print(f'  → {ok} 件 {"(dryrun)" if dry_run else "INSERT/UPDATE完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# 商品マスタ移行
# ──────────────────────────────────────────────────────────────
def migrate_products(cur, data, dry_run):
    products = data.get('products', {})
    if isinstance(products, list):
        products = {p['code']: p for p in products if p and p.get('code')}
    print(f'[商品] {len(products)} 件')
    ok = 0
    for key, p in products.items():
        if not p:
            continue
        code = safe_str(p.get('code') or key, 20)
        name = safe_str(p.get('name', ''), 100)
        if not code or not name:
            continue
        discontinued = 1 if (p.get('disc') or p.get('discontinued') or p.get('active') is False) else 0
        params = (
            code, name,
            safe_float(p.get('price', 0)),
            safe_str(p.get('genre', 'その他'), 50),
            safe_str(p.get('supplier', ''), 100),
            discontinued,
        )
        if not dry_run:
            cur.execute("""
                INSERT INTO products (code, name, price, genre, supplier, discontinued)
                VALUES (%s,%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                  name=VALUES(name), price=VALUES(price), genre=VALUES(genre),
                  supplier=VALUES(supplier), discontinued=VALUES(discontinued)
            """, params)
        ok += 1
    print(f'  → {ok} 件 {"(dryrun)" if dry_run else "INSERT/UPDATE完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# 在庫移行
# ──────────────────────────────────────────────────────────────
def migrate_stock(cur, data, dry_run):
    stock = data.get('stock', {})
    if isinstance(stock, list):
        stock = {s['code']: s for s in stock if s and s.get('code')}
    print(f'[在庫] {len(stock)} 件')
    ok = 0
    for key, s in stock.items():
        if not s:
            continue
        code = safe_str(s.get('code') or key, 20)
        if not code:
            continue
        params = (
            code,
            safe_str(s.get('name', ''), 100),
            safe_float(s.get('price', 0)),
            safe_int(s.get('qty', 0)),
        )
        if not dry_run:
            cur.execute("""
                INSERT INTO stock (code, name, price, qty)
                VALUES (%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), qty=VALUES(qty)
            """, params)
        ok += 1
    print(f'  → {ok} 件 {"(dryrun)" if dry_run else "INSERT/UPDATE完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# 売上伝票・明細移行
# ──────────────────────────────────────────────────────────────
def migrate_sales(cur, data, dry_run):
    sales = data.get('sales', {})
    if isinstance(sales, list):
        sales = {s.get('id', s.get('no', str(i))): s for i, s in enumerate(sales) if s}
    print(f'[売上] {len(sales)} 件')

    # 商品価格マップ（計算用）
    products = data.get('products', {})
    if isinstance(products, list):
        products = {p['code']: p for p in products if p and p.get('code')}
    price_map = {k: safe_float(v.get('price', 0)) for k, v in products.items() if v}

    def get_price(code):
        return price_map.get(code, price_map.get(normalize_code(code), 0))

    # 顧客マップ（名前・カナ解決用）
    customers = data.get('customers', {})
    if isinstance(customers, list):
        customers = {str(c.get('id') or c.get('no', '')): c for c in customers if c}
    def get_cust(cid):
        return customers.get(str(cid), {})

    ok = 0
    items_ok = 0
    for key, s in sales.items():
        if not s:
            continue
        # 伝票番号
        sale_id = safe_str(s.get('no') or s.get('id') or key, 20)
        if not sale_id:
            continue
        # 日付
        sale_date = safe_date(s.get('orderDate') or s.get('d') or s.get('sale_date'))
        if not sale_date:
            continue
        year_month = sale_date[:7]
        deliver_date = safe_date(s.get('delivDate') or s.get('deliver_date') or sale_date)
        # 顧客情報
        cust_id = safe_str(s.get('custNo') or s.get('cid') or s.get('customer_id', ''), 20)
        cust = get_cust(cust_id)
        cust_name = safe_str(s.get('cn') or s.get('customer_name') or cust.get('name', ''), 100)
        cust_kana = safe_str(s.get('customer_kana') or cust.get('kana', ''), 100)
        # 明細
        details = s.get('details') or s.get('items') or []
        # 金額計算
        if s.get('sub10') is not None:
            # 新形式（計算済み）
            sub10 = safe_float(s.get('sub10', 0))
            sub8  = safe_float(s.get('sub8', 0))
            tax10 = safe_float(s.get('tax10', 0))
            tax8  = safe_float(s.get('tax8', 0))
            total = safe_float(s.get('total', sub10 + tax10 + sub8 + tax8))
        elif s.get('total') and not details:
            # 旧形式（合計のみ）
            total = safe_float(s.get('total', 0))
            sub10 = total; sub8 = tax10 = tax8 = 0
        else:
            sub10, sub8, tax10, tax8, total = calc_slip(details, get_price)

        if not dry_run:
            cur.execute("""
                INSERT INTO sales
                  (id, sale_date, year_month, deliver_date,
                   customer_id, customer_name, customer_kana,
                   sub10, sub8, tax10, tax8, total)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                  sale_date=VALUES(sale_date), year_month=VALUES(year_month),
                  customer_name=VALUES(customer_name), total=VALUES(total)
            """, (
                sale_id, sale_date, year_month, deliver_date,
                cust_id, cust_name, cust_kana,
                sub10, sub8, tax10, tax8, total
            ))
            # 明細（既存を削除して再挿入）
            cur.execute("DELETE FROM sales_items WHERE sale_id=%s", (sale_id,))
            for d in details:
                code = safe_str(d.get('code') or d.get('c', ''), 20)
                qty  = safe_int(d.get('qty') or d.get('q', 1), 1)
                kake = safe_float(d.get('kake', 1.0))
                tax_r = 0.08 if is_8pct(code) else 0.10
                price = get_price(code)
                amount = math.floor(price * kake) * qty
                prod_name = ''
                prod = products.get(code, products.get(normalize_code(code), {}))
                if prod:
                    prod_name = safe_str(prod.get('name', ''), 100)
                cur.execute("""
                    INSERT INTO sales_items
                      (sale_id, product_code, product_name, qty, kake, tax_rate, amount)
                    VALUES (%s,%s,%s,%s,%s,%s,%s)
                """, (sale_id, code, prod_name, qty, kake, tax_r, amount))
                items_ok += 1
        ok += 1

    print(f'  → 伝票 {ok} 件, 明細 {items_ok} 件 {"(dryrun)" if dry_run else "INSERT完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# エステコースマスタ移行
# ──────────────────────────────────────────────────────────────
def migrate_este_menus(cur, data, dry_run):
    menus = data.get('estemenus', {})
    if isinstance(menus, list):
        menus = {m['id']: m for m in menus if m and m.get('id')}
    print(f'[エステコース] {len(menus)} 件')
    ok = 0
    for key, m in menus.items():
        if not m:
            continue
        mid = safe_str(m.get('id') or key, 20)
        if not mid:
            continue
        params = (
            mid,
            safe_str(m.get('name', ''), 100),
            safe_int(m.get('price', 0)),
            safe_int(m.get('tax') or m.get('tax_rate', 10)),
            0 if m.get('active') is False else 1,
        )
        if not dry_run:
            cur.execute("""
                INSERT INTO este_menus (id, name, price, tax_rate, active)
                VALUES (%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                  name=VALUES(name), price=VALUES(price),
                  tax_rate=VALUES(tax_rate), active=VALUES(active)
            """, params)
        ok += 1
    print(f'  → {ok} 件 {"(dryrun)" if dry_run else "INSERT/UPDATE完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# エステ来店履歴移行
# ──────────────────────────────────────────────────────────────
def migrate_este_visits(cur, data, dry_run):
    visits = data.get('estevisits', {})
    if isinstance(visits, list):
        visits = {v['id']: v for v in visits if v and v.get('id')}
    print(f'[エステ来店] {len(visits)} 件')
    ok = 0
    for key, v in visits.items():
        if not v:
            continue
        vid = safe_str(v.get('id') or key, 30)
        visit_date = safe_date(v.get('date') or v.get('visit_date'))
        if not vid or not visit_date:
            continue
        params = (
            vid,
            visit_date,
            safe_str(v.get('custNo') or v.get('customer_id', ''), 20),
            safe_str(v.get('custName') or v.get('customer_name', ''), 100),
            safe_str(v.get('menuId') or v.get('menu_id', ''), 20),
            safe_str(v.get('menuName') or v.get('menu_name', ''), 100),
            safe_str(v.get('staff', ''), 50),
            safe_int(v.get('price', 0)),
            safe_int(v.get('taxRate') or v.get('tax_rate', 10)),
            safe_int(v.get('tax', 0)),
            safe_int(v.get('total', 0)),
            safe_date(v.get('nextVisit') or v.get('next_visit')),
            safe_str(v.get('note', '')),
        )
        if not dry_run:
            cur.execute("""
                INSERT INTO este_visits
                  (id, visit_date, customer_id, customer_name,
                   menu_id, menu_name, staff, price, tax_rate, tax, total,
                   next_visit, note)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                  visit_date=VALUES(visit_date), customer_name=VALUES(customer_name),
                  menu_name=VALUES(menu_name), total=VALUES(total)
            """, params)
        ok += 1
    print(f'  → {ok} 件 {"(dryrun)" if dry_run else "INSERT/UPDATE完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# キープ商品移行
# ──────────────────────────────────────────────────────────────
def migrate_keep_items(cur, data, dry_run):
    items = data.get('keepitems', {})
    if isinstance(items, list):
        items = {it['id']: it for it in items if it and it.get('id')}
    print(f'[キープ商品] {len(items)} 件')
    ok = 0
    for key, it in items.items():
        if not it:
            continue
        kid = safe_str(it.get('id') or key, 30)
        item_date = safe_date(it.get('date') or it.get('item_date'))
        if not kid or not item_date:
            continue
        params = (
            kid,
            item_date,
            safe_str(it.get('custNo') or it.get('customer_id', ''), 20),
            safe_str(it.get('custName') or it.get('customer_name', ''), 100),
            safe_str(it.get('menuName') or it.get('menu_name', ''), 200),
            safe_int(it.get('qty', 1)),
            safe_int(it.get('count') or it.get('count_val', 1)),
            safe_int(it.get('price', 0)),
            safe_str(it.get('note', '')),
        )
        if not dry_run:
            cur.execute("""
                INSERT INTO keep_items
                  (id, item_date, customer_id, customer_name,
                   menu_name, qty, count_val, price, note)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                  menu_name=VALUES(menu_name), qty=VALUES(qty), price=VALUES(price)
            """, params)
        ok += 1
    print(f'  → {ok} 件 {"(dryrun)" if dry_run else "INSERT/UPDATE完了"}')
    return ok


# ──────────────────────────────────────────────────────────────
# メイン
# ──────────────────────────────────────────────────────────────
def main():
    dry_run = '--execute' not in sys.argv

    print('=' * 60)
    if dry_run:
        print('DRYRUNモード（DBへの書き込みは行いません）')
        print('本番実行: python migrate.py --execute')
    else:
        print('本番実行モード - MariaDBへの書き込みを開始します')
    print('=' * 60)

    # Firebase エクスポートファイルの読み込み
    if not os.path.exists(FIREBASE_EXPORT_FILE):
        print(f'エラー: {FIREBASE_EXPORT_FILE} が見つかりません')
        print()
        print('Firebase Console からデータをエクスポートしてください:')
        print('  Realtime Database → 「…」→ 「JSONをエクスポート」')
        print(f'  → firebase_export.json としてこのフォルダに保存')
        sys.exit(1)

    print(f'読み込み中: {FIREBASE_EXPORT_FILE}')
    with open(FIREBASE_EXPORT_FILE, 'r', encoding='utf-8') as f:
        data = json.load(f)
    print('読み込み完了')
    print()

    conn = None
    cur = None
    if not dry_run:
        conn = get_db()
        cur = conn.cursor()
        cur.execute("SET NAMES utf8mb4")
        cur.execute("SET time_zone = '+09:00'")

    try:
        migrate_customers(cur, data, dry_run)
        migrate_products(cur, data, dry_run)
        migrate_stock(cur, data, dry_run)
        migrate_sales(cur, data, dry_run)
        migrate_este_menus(cur, data, dry_run)
        migrate_este_visits(cur, data, dry_run)
        migrate_keep_items(cur, data, dry_run)

        if not dry_run:
            conn.commit()
            print()
            print('コミット完了')

    except Exception as e:
        if conn:
            conn.rollback()
        print(f'\nエラーが発生しました: {e}')
        import traceback
        traceback.print_exc()
        sys.exit(1)
    finally:
        if cur:
            cur.close()
        if conn:
            conn.close()

    print()
    print('=' * 60)
    if dry_run:
        print('Dryrun完了。内容を確認して: python migrate.py --execute')
    else:
        print('移行完了！')
    print('=' * 60)


if __name__ == '__main__':
    main()
