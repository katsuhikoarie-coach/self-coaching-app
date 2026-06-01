<?php
/**
 * Firebase → MariaDB 移行スクリプト（サーバー上でPHP実行）
 * 実行方法: ブラウザで https://sales.asagiriyamano.jp/migrate_run.php?key=asagiri2026 にアクセス
 * ※ 実行後は必ずこのファイルをサーバーから削除してください
 */

// 簡易アクセスキー
if (($_GET['key'] ?? '') !== 'asagiri2026') {
    http_response_code(403);
    die('Access denied');
}

set_time_limit(300);
ini_set('memory_limit', '256M');
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/config/db.php';
$db = getDB();

$json_file = __DIR__ . '/firebase_export.json';
if (!file_exists($json_file)) {
    die('firebase_export.json が見つかりません');
}

echo "<pre style='font-family:monospace;font-size:14px;padding:20px'>\n";
echo "=== Firebase → MariaDB 移行開始 ===\n\n";
flush();

$data = json_decode(file_get_contents($json_file), true);
if (!$data) { die('JSONパースエラー'); }

$db->exec("SET NAMES utf8mb4");
$db->exec("SET time_zone = '+09:00'");

// ────────────────────────────
// 消費税8%対象コード
// ────────────────────────────
$TAX8_RAW = ['0329','0330','0670','1659','2646','3025','3304',
    '6224','6225','6226','6228','6250','6251','6252','6253','6254',
    '6278','6282','6288','6289','6291','6292',
    '6303','6304','6305','6306','6307','6308','630u',
    '6311','6312','6313','6314','6315','6316','6317','631u',
    '6323','6325','6329','6335','6336','6337','6515',
    '7628','7629','7630',
    'Y001','Y002','Y007','Y008','Y059','Y067','Y068','Y085',
    'Y628','Y629','Y633','Y635','Y636',
    'Z025','Z601','z101','z102','ｚ101','ｚ102'];
function normalizeCode($c) {
    $c = trim((string)$c);
    $c = mb_convert_kana($c, 'a', 'UTF-8'); // 全角英数→半角
    return strtoupper($c);
}
$tax8_set = array_flip(array_map('normalizeCode', $TAX8_RAW));
function is8pct($code) { global $tax8_set; return isset($tax8_set[normalizeCode($code)]); }
function safe_date($v) {
    if (!$v) return null;
    $s = substr((string)$v, 0, 10);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
}

// ────────────────────────────
// 顧客マスタ
// ────────────────────────────
echo "[1/5] 顧客マスタ...\n"; flush();
$customers_raw = $data['customers'] ?? [];
if (!is_array($customers_raw)) $customers_raw = [];
$stmt = $db->prepare("
    INSERT INTO customers
      (id, name, kana, zip, addr, tel, mobile, email, bday,
       rank_id, rank_name, staff, note, homecare, keep_item, active)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      name=VALUES(name), kana=VALUES(kana), addr=VALUES(addr),
      tel=VALUES(tel), mobile=VALUES(mobile), email=VALUES(email),
      bday=VALUES(bday), rank_name=VALUES(rank_name),
      staff=VALUES(staff), note=VALUES(note), active=VALUES(active)
");
$db->beginTransaction();
$ok = 0;
foreach ($customers_raw as $key => $c) {
    if (!$c || !is_array($c)) continue;
    $cid = (string)($c['id'] ?? $c['no'] ?? $key);
    if (!$cid || !($c['name'] ?? '')) continue;
    $addr = $c['addr'] ?? '';
    if (!$addr) $addr = trim(($c['zip'] ?? '') . ' ' . ($c['addr2'] ?? ''));
    $stmt->execute([
        $cid, $c['name'], $c['kana'] ?? '', $c['zip'] ?? '',
        substr($addr, 0, 200), $c['tel'] ?? '', $c['mobile'] ?? '',
        $c['email'] ?? '', $c['bday'] ?? $c['birth'] ?? '',
        $c['rank_id'] ?? '', $c['rank'] ?? $c['rank_name'] ?? '一般',
        $c['staff'] ?? '', $c['note'] ?? '', 0, 0,
        ($c['active'] === false) ? 0 : 1,
    ]);
    $ok++;
}
$db->commit();
echo "  → {$ok} 件 INSERT/UPDATE\n\n"; flush();

// ────────────────────────────
// 商品マスタ
// ────────────────────────────
echo "[2/5] 商品マスタ...\n"; flush();
$products_raw = $data['products'] ?? [];
if (!is_array($products_raw)) $products_raw = [];
$stmt = $db->prepare("
    INSERT INTO products (code, name, price, genre, supplier, discontinued)
    VALUES (?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      name=VALUES(name), price=VALUES(price), genre=VALUES(genre),
      supplier=VALUES(supplier), discontinued=VALUES(discontinued)
");
$prod_price = []; // 計算用
$prod_name  = []; // 商品名キャッシュ
$db->beginTransaction();
$ok = 0;
foreach ($products_raw as $key => $p) {
    if (!$p || !is_array($p)) continue;
    $code = (string)($p['code'] ?? $key);
    if (!$code || !($p['name'] ?? '')) continue;
    $disc = (isset($p['active']) && $p['active'] === false) || !empty($p['disc']) ? 1 : 0;
    $price = (float)($p['price'] ?? 0);
    $prod_price[$code] = $price;
    $prod_name[$code]  = $p['name'];
    $stmt->execute([$code, $p['name'], $price, $p['genre'] ?? 'その他', $p['supplier'] ?? '', $disc]);
    $ok++;
}
$db->commit();
echo "  → {$ok} 件 INSERT/UPDATE\n\n"; flush();

// ────────────────────────────
// 在庫
// ────────────────────────────
echo "[3/5] 在庫...\n"; flush();
$stock_raw = $data['stock'] ?? [];
if (!is_array($stock_raw)) $stock_raw = [];
$stmt = $db->prepare("
    INSERT INTO stock (code, name, price, qty)
    VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price), qty=VALUES(qty)
");
$db->beginTransaction();
$ok = 0;
foreach ($stock_raw as $key => $s) {
    if (!$s || !is_array($s)) continue;
    $code = (string)($s['code'] ?? $key);
    if (!$code) continue;
    $stmt->execute([$code, $s['name'] ?? '', (float)($s['price'] ?? 0), (int)($s['qty'] ?? 0)]);
    $ok++;
}
$db->commit();
echo "  → {$ok} 件 INSERT/UPDATE\n\n"; flush();

// ────────────────────────────
// 顧客マップ（売上の名前解決用）
// ────────────────────────────
$cust_map = [];
foreach ($customers_raw as $k => $c) {
    if (!$c) continue;
    $cid = (string)($c['id'] ?? $c['no'] ?? $k);
    $cust_map[$cid] = ['name' => $c['name'] ?? '', 'kana' => $c['kana'] ?? ''];
}

// ────────────────────────────
// 売上伝票・明細
// ────────────────────────────
echo "[4/5] 売上伝票・明細...\n"; flush();
$sales_raw = $data['sales'] ?? [];
if (!is_array($sales_raw)) $sales_raw = [];

$sale_stmt = $db->prepare("
    INSERT INTO sales
      (id, sale_date, `year_month`, deliver_date,
       customer_id, customer_name, customer_kana,
       sub10, sub8, tax10, tax8, total)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      sale_date=VALUES(sale_date), customer_name=VALUES(customer_name),
      total=VALUES(total)
");
$item_del_stmt  = $db->prepare("DELETE FROM sales_items WHERE sale_id=?");
$item_stmt = $db->prepare("
    INSERT INTO sales_items (sale_id, product_code, product_name, qty, kake, tax_rate, amount)
    VALUES (?,?,?,?,?,?,?)
");

try {
$db->beginTransaction();
$ok = 0; $items_ok = 0;
foreach ($sales_raw as $key => $s) {
    if (!$s || !is_array($s)) continue;
    $sale_id = (string)($s['no'] ?? $s['id'] ?? $key);
    if (!$sale_id) continue;
    $sale_id = preg_replace('/[.#$\[\]\/]/', '_', $sale_id);

    $sale_date = safe_date($s['orderDate'] ?? $s['d'] ?? null);
    if (!$sale_date) continue;
    $ym = substr($sale_date, 0, 7);
    $del_date = safe_date($s['delivDate'] ?? $sale_date) ?? $sale_date;

    $cust_id = (string)($s['custNo'] ?? $s['cid'] ?? '');
    $cust = $cust_map[$cust_id] ?? [];
    $cust_name = $s['cn'] ?? $cust['name'] ?? '';
    $cust_kana = $cust['kana'] ?? '';

    $details = $s['details'] ?? $s['items'] ?? [];

    // 金額計算
    if (isset($s['sub10'])) {
        $sub10 = (float)($s['sub10'] ?? 0); $sub8 = (float)($s['sub8'] ?? 0);
        $tax10 = (float)($s['tax10'] ?? 0); $tax8 = (float)($s['tax8'] ?? 0);
        $total = (float)($s['total'] ?? ($sub10+$tax10+$sub8+$tax8));
    } else {
        $sub10 = $sub8 = $tax10 = $tax8 = 0;
        foreach ($details as $d) {
            $code = (string)($d['code'] ?? $d['c'] ?? '');
            $qty  = (int)($d['qty'] ?? $d['q'] ?? 1);
            $kake = (float)($d['kake'] ?? 1.0);
            $price = $prod_price[$code] ?? $prod_price[strtoupper($code)] ?? 0;
            $shitane = floor($price * $kake) * $qty;
            if (is8pct($code)) $sub8 += $shitane; else $sub10 += $shitane;
        }
        $tax10 = floor($sub10 * 0.1); $tax8 = floor($sub8 * 0.08);
        $total = $sub10 + $tax10 + $sub8 + $tax8;
        if ($total == 0 && isset($s['total'])) $total = (float)$s['total'];
    }

    $sale_stmt->execute([$sale_id, $sale_date, $ym, $del_date,
        $cust_id, $cust_name, $cust_kana, $sub10, $sub8, $tax10, $tax8, $total]);
    $item_del_stmt->execute([$sale_id]);
    foreach ($details as $d) {
        $code   = (string)($d['code'] ?? $d['c'] ?? '');
        $qty    = (int)($d['qty']  ?? $d['q']  ?? 1);
        $kake   = (float)($d['kake'] ?? 1.0);
        $taxr   = is8pct($code) ? 0.08 : 0.10;
        $price  = $prod_price[$code] ?? 0;
        $amount = floor($price * $kake) * $qty;
        $pname  = $prod_name[$code] ?? '';
        $item_stmt->execute([$sale_id, $code, $pname, $qty, $kake, $taxr, $amount]);
        $items_ok++;
    }
    $ok++;
    if ($ok % 200 === 0) {
        $db->commit();
        echo "  ... {$ok} 件処理済み\n"; flush();
        $db->beginTransaction();
    }
}
$db->commit();
echo "  → 伝票 {$ok} 件、明細 {$items_ok} 件 INSERT\n\n"; flush();
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "  [ERROR] " . $e->getMessage() . "\n";
    echo "  sale_id=" . ($sale_id ?? 'unknown') . "\n";
    flush();
}

// ────────────────────────────
// エステコースマスタ
// ────────────────────────────
echo "[5/5] エステ・キープ商品...\n"; flush();
$este_menus = $data['estemenus'] ?? [];
if (is_array($este_menus)) {
    $stmt = $db->prepare("
        INSERT INTO este_menus (id, name, price, tax_rate, active)
        VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE name=VALUES(name), price=VALUES(price)
    ");
    $db->beginTransaction();
    $mok = 0;
    foreach ($este_menus as $k => $m) {
        if (!$m || !is_array($m)) continue;
        $mid = (string)($m['id'] ?? $k);
        $stmt->execute([$mid, $m['name'] ?? '', (int)($m['price'] ?? 0),
            (int)($m['tax'] ?? 10), ($m['active'] === false) ? 0 : 1]);
        $mok++;
    }
    $db->commit();
    echo "  エステコース: {$mok} 件\n"; flush();
}

// ────────────────────────────
// 最終レポート
// ────────────────────────────
echo "\n=== 移行結果 ===\n";
$tables = ['customers','products','stock','sales','sales_items','este_menus'];
foreach ($tables as $t) {
    $cnt = $db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    printf("  %-20s %5d 件\n", $t, $cnt);
}
echo "\n移行完了！\n";
echo "⚠ このファイル (migrate_run.php) とfirebase_export.jsonをサーバーから削除してください。\n";
echo "</pre>\n";
