<?php
require 'config.php';

// ── AJAX: 商品検索 ────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    try {
        $pdo  = getDB();
        if ($q === '') {
            // 空検索 → 全件（最大200件）
            $stmt = $pdo->query(
                "SELECT code, name, price, tax_rate, volume FROM products ORDER BY code LIMIT 200"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT code, name, price, tax_rate, volume FROM products
                 WHERE code LIKE ? OR name LIKE ?
                 ORDER BY code LIMIT 100"
            );
            $like = '%' . $q . '%';
            $stmt->execute([$like, $like]);
        }
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>見積書作成</title>
<style>
/* ── 基本リセット ── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Hiragino Kaku Gothic Pro', 'Meiryo', sans-serif;
    font-size: 14px;
    background: #f0f2f5;
    color: #333;
}

/* ── レイアウト ── */
.screen-header {
    background: #1a5276;
    color: #fff;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.screen-header h1 { font-size: 1.2em; font-weight: bold; }
.screen-header a { color: #aed6f1; font-size: 0.85em; text-decoration: none; }
.screen-header a:hover { text-decoration: underline; }

.main-wrapper {
    display: flex;
    gap: 16px;
    padding: 16px;
    max-width: 1400px;
    margin: 0 auto;
}

/* ── 左パネル（入力・検索）── */
.panel-left {
    width: 320px;
    flex-shrink: 0;
}
.panel-section {
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,.1);
    padding: 16px;
    margin-bottom: 12px;
}
.panel-section h2 {
    font-size: 0.9em;
    font-weight: bold;
    color: #555;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

label { display: block; font-size: 0.85em; color: #666; margin-bottom: 4px; margin-top: 10px; }
label:first-child { margin-top: 0; }

input[type=text], input[type=number] {
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 7px 10px;
    font-size: 0.95em;
    transition: border-color .2s;
}
input[type=text]:focus, input[type=number]:focus {
    outline: none;
    border-color: #2471a3;
}

.search-row { display: flex; gap: 6px; margin-top: 10px; }
.search-row input { flex: 1; }

.btn {
    background: #2471a3;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    white-space: nowrap;
}
.btn:hover { background: #1a5276; }
.btn-sm { padding: 4px 10px; font-size: 0.82em; }
.btn-danger { background: #e74c3c; }
.btn-danger:hover { background: #c0392b; }
.btn-success { background: #27ae60; }
.btn-success:hover { background: #1e8449; }
.btn-print {
    background: #8e44ad;
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    width: 100%;
    margin-top: 6px;
}
.btn-print:hover { background: #6c3483; }
.btn-reset {
    background: #e67e22;
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    width: 100%;
    margin-top: 6px;
}
.btn-reset:hover { background: #ca6f1e; }

/* ── 検索結果 ── */
#search-results {
    margin-top: 10px;
    max-height: 360px;
    overflow-y: auto;
}
.product-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 4px;
    background: #fafafa;
    gap: 8px;
}
.product-card:hover { background: #f0f7ff; border-color: #aed6f1; }
.product-card-info { flex: 1; font-size: 0.85em; line-height: 1.4; }
.product-card-code { color: #777; font-size: 0.8em; }
.product-card-name { font-weight: bold; color: #222; }
.product-card-detail { color: #555; font-size: 0.8em; }
.no-results { color: #999; text-align: center; padding: 16px; font-size: 0.9em; }

/* ── 右パネル（見積書）── */
.panel-right { flex: 1; min-width: 0; }

/* ── 見積書ドキュメント ── */
.quotation-doc {
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,.1);
    padding: 30px;
}

/* 印刷ヘッダー */
.quote-header { margin-bottom: 24px; }
.quote-title { font-size: 2em; font-weight: bold; text-align: center; letter-spacing: 0.3em; margin-bottom: 20px; }
.quote-meta {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.quote-customer h2 {
    font-size: 1.3em;
    font-weight: bold;
    border-bottom: 2px solid #333;
    padding-bottom: 4px;
    min-width: 200px;
}
.quote-customer .honor { font-size: 0.9em; color: #555; margin-top: 2px; }
.quote-info { text-align: right; font-size: 0.88em; color: #555; line-height: 1.8; }
.quote-info strong { color: #222; }

/* 商品テーブル */
.products-table-wrap { overflow-x: auto; margin: 20px 0; }
.products-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.88em;
}
.products-table th {
    background: #1a5276;
    color: #fff;
    padding: 8px 10px;
    text-align: center;
    white-space: nowrap;
    font-weight: normal;
}
.products-table td {
    border-bottom: 1px solid #e0e0e0;
    padding: 8px 10px;
    vertical-align: middle;
}
.products-table tbody tr:hover { background: #f8f9fa; }
.products-table tbody tr.unchecked { opacity: 0.4; }
.products-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.products-table .center { text-align: center; }
.products-table .empty-row td {
    text-align: center;
    color: #999;
    padding: 30px;
    border: 2px dashed #e0e0e0;
}

input.qty-input {
    width: 64px;
    text-align: right;
    padding: 4px 6px;
    border: 1px solid #ccc;
    border-radius: 3px;
}

/* 合計エリア */
.totals-wrap {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
}
.totals-table { border-collapse: collapse; min-width: 340px; }
.totals-table tr td {
    padding: 7px 12px;
    font-size: 0.92em;
}
.totals-table tr td:first-child { color: #555; text-align: right; }
.totals-table tr td:last-child { text-align: right; font-weight: bold; min-width: 120px; font-variant-numeric: tabular-nums; }
.totals-table .subtotal td { border-top: 1px solid #ddd; }
.totals-table .total-row { background: #1a5276; }
.totals-table .total-row td { color: #fff; font-size: 1.05em; border-radius: 0; }
.totals-table .kakuritsu-row td { color: #888; font-size: 0.85em; }

/* 備考・メモ */
.quote-note {
    margin-top: 20px;
    border-top: 1px solid #eee;
    padding-top: 16px;
    font-size: 0.85em;
    color: #777;
    min-height: 40px;
}

/* ── 商品コード＋商品名の2行表示（モバイル用） ── */
.name-mobile { display: none; }
.col-volume { display: none; }

/* ── レスポンシブ（スマートフォン対応）── */
@media (max-width: 768px) {
    .screen-header {
        padding: 10px 14px;
        flex-wrap: wrap;
        gap: 6px;
    }
    .screen-header h1 { font-size: 1em; }

    .main-wrapper {
        flex-direction: column;
        padding: 10px;
        gap: 12px;
    }

    .panel-left {
        width: 100%;
    }

    /* 検索結果を少し低めに抑える */
    #search-results { max-height: 240px; }

    /* 見積書の余白を縮小 */
    .quotation-doc { padding: 16px 12px; }

    .quote-title { font-size: 1.4em; letter-spacing: 0.15em; }

    /* お客様名と発行日を縦並びに */
    .quote-meta { flex-direction: column; gap: 10px; }
    .quote-info { text-align: left; }

    /* 合計テーブルを全幅に */
    .totals-wrap { justify-content: stretch; }
    .totals-table { width: 100%; min-width: unset; }

    /* 掛率入力のレイアウト崩れを防止 */
    input[type=number]#kakuritsu { width: 80px; }

    /* 商品名列を非表示にして、コード列内の2行表示に切り替え */
    .col-name { display: none; }
    .name-mobile { display: block; font-size: 0.95em; color: #222; font-weight: bold; margin-top: 2px; }
    .code-text { font-size: 0.8em; color: #777; }
}

/* ── 印刷スタイル ── */
@media print {
    body { background: #fff; font-size: 10.5pt; }
    .no-print { display: none !important; }
    .main-wrapper { display: block; padding: 0; }
    .panel-left { display: none; }
    .panel-right { width: 100%; }
    .quotation-doc { box-shadow: none; border-radius: 0; padding: 10mm; }
    .quote-title { font-size: 22pt; }
    .screen-header { display: none; }
    .products-table tbody tr.unchecked { display: none; }
    .products-table th { background: transparent !important; color: #333 !important; border-bottom: 2px solid #333; }
    .totals-table .total-row { background: transparent !important; border: 2px solid #333; }
    .totals-table .total-row td { color: #333 !important; }
    .no-print-col { display: none; }
    input.qty-input { border: none; background: transparent; }
    .products-table { font-size: 8pt; }
    .col-volume { display: none; }
    .products-table td { padding: 4px 8px; }
    .products-table th { padding: 4px 8px; }
}

@page {
    size: A4;
    margin: 10mm 12mm 12mm;
}
</style>
</head>
<body>

<!-- ── 画面ヘッダー ── -->
<header class="screen-header no-print">
    <h1>見積書作成システム</h1>
    <a href="import.php">商品CSVインポート →</a>
</header>

<div class="main-wrapper">

    <!-- ── 左パネル ── -->
    <div class="panel-left no-print">

        <!-- お客様情報 -->
        <div class="panel-section">
            <h2>お客様・条件</h2>
            <label for="customer-name">お客様名</label>
            <input type="text" id="customer-name" placeholder="○○商事株式会社" oninput="updateHeader()">

            <label for="kakuritsu">掛率（%）</label>
            <div style="display:flex;align-items:center;gap:6px;">
                <input type="number" id="kakuritsu" value="100" min="0" max="200" step="0.1"
                       style="width:90px;" oninput="recalc()">
                <span style="color:#666;font-size:0.85em;">%（例: 70 → 7掛）</span>
            </div>
        </div>

        <!-- 商品検索 -->
        <div class="panel-section">
            <h2>商品検索・追加</h2>
            <div class="search-row">
                <input type="text" id="search-input"
                       placeholder="商品コードまたは商品名"
                       oninput="onSearchInput()"
                       onkeydown="if(event.key==='Enter') doSearch()">
                <button class="btn" onclick="doSearch()">検索</button>
            </div>
            <div style="margin-top:6px;font-size:0.78em;color:#999;">空欄で検索すると全件表示</div>
            <div id="search-results"></div>
        </div>

        <!-- 操作ボタン -->
        <div class="panel-section">
            <h2>操作</h2>
            <button class="btn-print" onclick="window.print()">🖨 印刷する</button>
            <button class="btn-reset" onclick="resetAll()">↺ リセット</button>
        </div>

    </div><!-- /panel-left -->

    <!-- ── 右パネル（見積書） ── -->
    <div class="panel-right">
        <div class="quotation-doc">

            <!-- タイトル -->
            <div class="quote-header">
                <div class="quote-title">見　積　書</div>
                <div class="quote-meta">
                    <div class="quote-customer">
                        <h2 id="print-customer">（お客様名）</h2>
                        <div class="honor">御中</div>
                    </div>
                    <div class="quote-info">
                        <div>発行日：<strong id="print-date"></strong></div>
                        <div>掛率：<strong><span id="print-kakuritsu">100</span>%</strong></div>
                    </div>
                </div>
            </div>

            <!-- 商品テーブル -->
            <div class="products-table-wrap">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th class="no-print-col" style="width:36px;">✓</th>
                            <th class="col-code" style="width:90px;">商品コード</th>
                            <th class="col-name">商品名</th>
                            <th class="col-volume" style="width:80px;">内容量</th>
                            <th style="width:80px;">単価（円）</th>
                            <th style="width:70px;">個数</th>
                            <th style="width:90px;">上代（円）</th>
                            <th style="width:90px;">下代（円）</th>
                            <th class="col-taxrate" style="width:50px;">税率</th>
                            <th class="no-print-col" style="width:46px;"></th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        <tr class="empty-row">
                            <td colspan="10">← 左の検索から商品を追加してください</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 合計 -->
            <div class="totals-wrap">
                <table class="totals-table">
                    <tr>
                        <td>上代合計</td>
                        <td id="total-jodai">0円</td>
                    </tr>
                    <tr class="kakuritsu-row">
                        <td>掛率</td>
                        <td><span id="total-kakuritsu">100</span>%</td>
                    </tr>
                    <tr class="subtotal">
                        <td>下代合計（税抜）</td>
                        <td id="total-shitadai">0円</td>
                    </tr>
                    <tr>
                        <td>　うち消費税（10%）</td>
                        <td id="total-tax10">0円</td>
                    </tr>
                    <tr>
                        <td>　うち消費税（8%）</td>
                        <td id="total-tax8">0円</td>
                    </tr>
                    <tr class="total-row">
                        <td>税込合計</td>
                        <td id="total-taxinc">0円</td>
                    </tr>
                </table>
            </div>

            <!-- 備考 -->
            <div class="quote-note">
                <span class="no-print" style="color:#bbb;">（備考欄）</span>
            </div>

        </div><!-- /quotation-doc -->
    </div><!-- /panel-right -->

</div><!-- /main-wrapper -->

<script>
// ── 状態 ────────────────────────────────────────────────────────
let products = [];   // 選択済み商品リスト
let searchResults = [];
let nextId = 0;
let searchTimer = null;

// ── ユーティリティ ───────────────────────────────────────────────
function fmt(n) {
    return Math.round(n).toLocaleString('ja-JP');
}
function esc(s) {
    return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}
function today() {
    const d = new Date();
    return d.getFullYear() + '年' +
           String(d.getMonth()+1).padStart(2,'0') + '月' +
           String(d.getDate()).padStart(2,'0') + '日';
}

// ── 初期化 ───────────────────────────────────────────────────────
document.getElementById('print-date').textContent = today();

// ── 検索 ────────────────────────────────────────────────────────
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(doSearch, 350);
}

async function doSearch() {
    clearTimeout(searchTimer);
    const q = document.getElementById('search-input').value.trim();
    const container = document.getElementById('search-results');
    container.innerHTML = '<div class="no-results">検索中…</div>';
    try {
        const res = await fetch('?action=search&q=' + encodeURIComponent(q));
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        searchResults = data;
        renderSearchResults(data);
    } catch (e) {
        container.innerHTML = '<div class="no-results" style="color:#c0392b;">エラー: ' + esc(e.message) + '</div>';
    }
}

function renderSearchResults(list) {
    const container = document.getElementById('search-results');
    if (list.length === 0) {
        container.innerHTML = '<div class="no-results">見つかりませんでした</div>';
        return;
    }
    container.innerHTML = list.map((p, i) => {
        const added = products.some(x => x.code === p.code);
        return `<div class="product-card">
            <div class="product-card-info">
                <div class="product-card-code">${esc(p.code)}</div>
                <div class="product-card-name">${esc(p.name)}</div>
                <div class="product-card-detail">${esc(p.volume)} &nbsp; <strong>${fmt(p.price)}円</strong> &nbsp; 税${p.tax_rate}%</div>
            </div>
            ${added
                ? '<span style="color:#27ae60;font-size:0.82em;white-space:nowrap;">追加済み</span>'
                : `<button class="btn btn-sm btn-success" onclick="addProductByIdx(${i})">追加</button>`
            }
        </div>`;
    }).join('');
}

// ── 商品追加 ─────────────────────────────────────────────────────
function addProductByIdx(idx) {
    const p = searchResults[idx];
    if (!p) return;
    if (products.some(x => x.code === p.code)) {
        alert('既に追加されています。');
        return;
    }
    products.push({
        id:      nextId++,
        code:    p.code,
        name:    p.name,
        price:   parseInt(p.price) || 0,
        taxRate: parseInt(p.tax_rate) || 10,
        volume:  p.volume || '',
        qty:     1,
        checked: true,
    });
    renderTable();
    recalc();
    // 検索結果の「追加」ボタンを「追加済み」に変更
    renderSearchResults(searchResults);
}

// ── 削除 ────────────────────────────────────────────────────────
function removeProduct(id) {
    products = products.filter(p => p.id !== id);
    renderTable();
    recalc();
    renderSearchResults(searchResults);
}

// ── テーブル描画 ─────────────────────────────────────────────────
function renderTable() {
    const tbody = document.getElementById('products-tbody');
    const kakuritsu = parseFloat(document.getElementById('kakuritsu').value) || 100;

    if (products.length === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="10">← 左の検索から商品を追加してください</td></tr>';
        return;
    }

    tbody.innerHTML = products.map(p => {
        const jodai    = p.price * p.qty;
        const shitadai = Math.floor(jodai * kakuritsu / 100);
        return `<tr id="row-${p.id}" class="${p.checked ? '' : 'unchecked'}">
            <td class="center no-print-col">
                <input type="checkbox" id="chk-${p.id}" ${p.checked ? 'checked' : ''}
                       onchange="toggleCheck(${p.id}, this.checked)">
            </td>
            <td class="col-code"><span class="code-text">${esc(p.code)}</span><span class="name-mobile">${esc(p.name)}</span></td>
            <td class="col-name">${esc(p.name)}</td>
            <td class="col-volume">${esc(p.volume)}</td>
            <td class="num">${fmt(p.price)}</td>
            <td class="center">
                <input type="number" class="qty-input" id="qty-${p.id}"
                       value="${p.qty}" min="0" step="1"
                       oninput="onQtyInput(${p.id}, this.value)">
            </td>
            <td class="num" id="jodai-${p.id}">${fmt(jodai)}</td>
            <td class="num" id="shitadai-${p.id}">${fmt(shitadai)}</td>
            <td class="center col-taxrate">${p.taxRate}%</td>
            <td class="center no-print-col">
                <button class="btn btn-sm btn-danger" onclick="removeProduct(${p.id})">削除</button>
            </td>
        </tr>`;
    }).join('');
}

// ── チェック切り替え ─────────────────────────────────────────────
function toggleCheck(id, checked) {
    const p = products.find(x => x.id === id);
    if (p) {
        p.checked = checked;
        const row = document.getElementById('row-' + id);
        if (row) row.className = checked ? '' : 'unchecked';
        recalc();
    }
}

// ── 個数変更 ────────────────────────────────────────────────────
function onQtyInput(id, val) {
    const p = products.find(x => x.id === id);
    if (!p) return;
    p.qty = Math.max(0, parseInt(val) || 0);
    const kakuritsu = parseFloat(document.getElementById('kakuritsu').value) || 100;
    const jodai    = p.price * p.qty;
    const shitadai = Math.floor(jodai * kakuritsu / 100);
    const jodaiEl    = document.getElementById('jodai-'    + id);
    const shitadaiEl = document.getElementById('shitadai-' + id);
    if (jodaiEl)    jodaiEl.textContent    = fmt(jodai);
    if (shitadaiEl) shitadaiEl.textContent = fmt(shitadai);
    recalc();
}

// ── 再計算・表示更新 ─────────────────────────────────────────────
function recalc() {
    const kakuritsu = parseFloat(document.getElementById('kakuritsu').value) || 100;

    let jodaiTotal  = 0;
    let shitadai10  = 0;
    let shitadai8   = 0;

    products.forEach(p => {
        // 最新の個数をDOMから読む
        const qtyEl = document.getElementById('qty-' + p.id);
        if (qtyEl) p.qty = Math.max(0, parseInt(qtyEl.value) || 0);

        const jodai    = p.price * p.qty;
        const shitadai = Math.floor(jodai * kakuritsu / 100);

        // 行のセルを更新
        const jodaiEl    = document.getElementById('jodai-'    + p.id);
        const shitadaiEl = document.getElementById('shitadai-' + p.id);
        if (jodaiEl)    jodaiEl.textContent    = fmt(jodai);
        if (shitadaiEl) shitadaiEl.textContent = fmt(shitadai);

        if (!p.checked) return;

        jodaiTotal += jodai;
        if (p.taxRate === 10) shitadai10 += shitadai;
        else                  shitadai8  += shitadai;
    });

    const shitadaiTotal = shitadai10 + shitadai8;
    const tax10         = Math.floor(shitadai10 * 0.10);
    const tax8          = Math.floor(shitadai8  * 0.08);
    const taxincTotal   = shitadaiTotal + tax10 + tax8;

    setText('total-jodai',    fmt(jodaiTotal)    + '円');
    setText('total-shitadai', fmt(shitadaiTotal) + '円');
    setText('total-tax10',    fmt(tax10)          + '円');
    setText('total-tax8',     fmt(tax8)           + '円');
    setText('total-taxinc',   fmt(taxincTotal)    + '円');
    setText('total-kakuritsu', kakuritsu);
    setText('print-kakuritsu', kakuritsu);
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

// ── ヘッダー更新 ─────────────────────────────────────────────────
function updateHeader() {
    const name = document.getElementById('customer-name').value.trim();
    setText('print-customer', name || '（お客様名）');
}

// ── リセット ────────────────────────────────────────────────────
function resetAll() {
    if (!confirm('選択した商品をすべてリセットしますか？')) return;
    products = [];
    document.getElementById('customer-name').value = '';
    document.getElementById('kakuritsu').value = '100';
    document.getElementById('search-input').value = '';
    document.getElementById('search-results').innerHTML = '';
    searchResults = [];
    renderTable();
    recalc();
    updateHeader();
}
</script>
</body>
</html>
