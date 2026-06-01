#!/usr/bin/env python3
"""
朝霧店 Firebase HTML生成スクリプト
既存のv2.htmlからFirebase対応版index.htmlを生成します
"""
import re, os, sys

SRC = os.path.join(os.path.dirname(__file__), '..', '朝霧店_売上顧客管理_v2.html')
DST = os.path.join(os.path.dirname(__file__), 'index.html')

# ─────────────────────────────────────────
# Firebase SDK (</head>の直前に挿入)
# ─────────────────────────────────────────
FIREBASE_SDK = """
  <!-- ★ Firebase SDK v9 compat ★ -->
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
"""

# ─────────────────────────────────────────
# ログインオーバーレイ + ローディング画面 (<body>の直後に挿入)
# ─────────────────────────────────────────
LOGIN_OVERLAY = """
<!-- ★ ログインオーバーレイ ★ -->
<div id="login-overlay" style="
  position:fixed;inset:0;background:#0f0f11;z-index:9999;
  display:flex;align-items:center;justify-content:center;flex-direction:column;gap:28px;">
  <div style="text-align:center">
    <div style="font-size:28px;font-weight:700;color:#c8a96e;letter-spacing:.05em;margin-bottom:6px">朝霧店</div>
    <div style="font-size:13px;color:#5a5856;font-family:'DM Mono',monospace;letter-spacing:.1em">SALES MANAGEMENT SYSTEM</div>
  </div>
  <div style="background:#16161a;border:1px solid #2a2a35;border-radius:14px;padding:32px 36px;width:340px;text-align:center">
    <div style="font-size:15px;font-weight:500;margin-bottom:6px">ログインが必要です</div>
    <div style="font-size:12px;color:#5a5856;margin-bottom:24px">Googleアカウントでログインしてください</div>
    <button id="login-btn" onclick="doLogin()" style="
      width:100%;display:flex;align-items:center;justify-content:center;gap:10px;
      background:#c8a96e;color:#0f0f11;border:none;border-radius:8px;
      padding:11px 20px;font-size:14px;font-weight:500;cursor:pointer;
      font-family:'Noto Sans JP',sans-serif;transition:background .15s;">
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
        <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
        <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
        <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
        <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 6.29C4.672 4.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
      </svg>
      Googleでログイン
    </button>
    <div id="login-error" style="color:#e06060;font-size:12px;margin-top:12px;display:none"></div>
  </div>
  <div style="font-size:11px;color:#3a3836">朝霧店スタッフ専用システム</div>
</div>

<!-- ★ ローディング画面 ★ -->
<div id="app-loading" style="
  position:fixed;inset:0;background:rgba(15,15,17,.85);z-index:8999;
  display:none;align-items:center;justify-content:center;flex-direction:column;gap:16px;
  backdrop-filter:blur(4px);">
  <div style="width:36px;height:36px;border:3px solid #2a2a35;border-top-color:#c8a96e;
    border-radius:50%;animation:spin .7s linear infinite;"></div>
  <div style="font-size:13px;color:#9a9890" id="loading-msg">データを読み込み中...</div>
  <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</div>

<!-- ★ オフラインバナー ★ -->
<div id="offline-banner" style="
  display:none;position:fixed;top:0;left:0;right:0;z-index:7000;
  background:#c8a96e;color:#0f0f11;text-align:center;padding:7px;
  font-size:12px;font-weight:500;">
  ⚠ オフライン中 — データは次回オンライン時に同期されます
</div>
"""

# ─────────────────────────────────────────
# Firebase 初期化JS (</body>の直前に挿入)
# ─────────────────────────────────────────
FIREBASE_INIT = """
<!-- ★ Firebase 初期化 ★ -->
<script>
// ──────────────────────────────────────────────────────────────
// ★ Firebaseプロジェクト設定 ★
// Firebaseコンソール → プロジェクト設定 → マイアプリ から取得して入力してください
// ──────────────────────────────────────────────────────────────
const firebaseConfig = {
  apiKey:            "YOUR_API_KEY",
  authDomain:        "asagiri-sale.firebaseapp.com",
  databaseURL:       "https://asagiri-sale-default-rtdb.firebaseio.com",
  projectId:         "asagiri-sale",
  storageBucket:     "asagiri-sale.appspot.com",
  messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
  appId:             "YOUR_APP_ID"
};

firebase.initializeApp(firebaseConfig);
const fbAuth = firebase.auth();
const fbDb   = firebase.database();

// ── ログイン ──────────────────────────────────────────────────
function doLogin() {
  const provider = new firebase.auth.GoogleAuthProvider();
  document.getElementById('login-btn').disabled = true;
  document.getElementById('login-btn').textContent = '認証中...';
  fbAuth.signInWithPopup(provider).catch(err => {
    const errEl = document.getElementById('login-error');
    errEl.textContent = 'ログインに失敗しました: ' + err.message;
    errEl.style.display = '';
    document.getElementById('login-btn').disabled = false;
    document.getElementById('login-btn').innerHTML = 'Googleでログイン';
  });
}

// ── 認証状態監視 ──────────────────────────────────────────────
fbAuth.onAuthStateChanged(async (user) => {
  if (user) {
    document.getElementById('login-overlay').style.display = 'none';
    // ユーザー名をサイドバーに表示
    const userEl = document.getElementById('firebase-user');
    if (userEl) userEl.textContent = user.displayName || user.email;
    await loadDataFromFirebase();
  } else {
    document.getElementById('login-overlay').style.display = 'flex';
    document.getElementById('app-loading').style.display = 'none';
  }
});

// ── サインアウト ───────────────────────────────────────────────
function doSignOut() {
  if (confirm('ログアウトしますか？')) fbAuth.signOut();
}

// ── オフライン検知 ────────────────────────────────────────────
fbDb.ref('.info/connected').on('value', snap => {
  const banner = document.getElementById('offline-banner');
  if (!snap.val()) {
    banner.style.display = '';
  } else {
    banner.style.display = 'none';
  }
});

// ── データ読み込み ────────────────────────────────────────────
let _realtimeInitialized = false;

async function loadDataFromFirebase() {
  showLoading('データを読み込み中...');

  // まずlocalStorageキャッシュがあれば即時表示
  const cached = loadCachedData();
  if (cached) {
    applyData(cached);
    refreshUI('キャッシュから起動 — 最新データを同期中...');
  }

  try {
    const [salesSnap, custSnap, prodSnap, stockSnap, staffSnap, ranksSnap] = await Promise.all([
      fbDb.ref('/sales').once('value'),
      fbDb.ref('/customers').once('value'),
      fbDb.ref('/products').once('value'),
      fbDb.ref('/stock').once('value'),
      fbDb.ref('/staff').once('value'),
      fbDb.ref('/ranks').once('value'),
    ]);

    const data = {
      sales:     salesSnap.val()  ? Object.values(salesSnap.val())  : [],
      customers: custSnap.val()   ? Object.values(custSnap.val())   : [],
      products:  prodSnap.val()   ? Object.values(prodSnap.val())   : [],
      stock:     stockSnap.val()  ? Object.values(stockSnap.val())  : [],
      staff:     staffSnap.val()  || {},
      ranks:     ranksSnap.val()  || {},
    };

    applyData(data);
    saveCachedData(data);

    if (!_realtimeInitialized) {
      setupRealtimeSync();
      _realtimeInitialized = true;
    }
  } catch (err) {
    console.error('Firebase読み込みエラー:', err);
    if (!cached) {
      alert('データの読み込みに失敗しました。\\n' + err.message);
    }
  } finally {
    hideLoading();
    refreshUI();
  }
}

function applyData(data) {
  store.sales     = data.sales;
  store.customers = data.customers;
  store.products  = data.products;
  store.stock     = data.stock;
  if (data.staff) store.staff   = data.staff;
  if (data.ranks) store.ranks   = data.ranks;

  // custTotalCacheを再構築
  Object.keys(custTotalCache).forEach(k => delete custTotalCache[k]);
  store.sales.forEach(s => {
    if (!custTotalCache[s.cid]) custTotalCache[s.cid] = { total: 0, count: 0 };
    custTotalCache[s.cid].total += s.total;
    custTotalCache[s.cid].count++;
  });
}

function refreshUI(msg) {
  if (msg) showLoading(msg);
  document.getElementById('current-date').textContent =
    new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
  document.getElementById('db-stats').innerHTML =
    `売上 ${store.sales.length.toLocaleString()}件<br>顧客 ${store.customers.filter(c=>c.name).length}名`;

  // レポート年リスト更新
  const reportSel = document.getElementById('report-year');
  reportSel.innerHTML = '';
  const reportYears = [...new Set(store.sales.filter(s=>s.d).map(s=>s.d.slice(0,4)))].sort().reverse();
  reportYears.forEach(y => reportSel.appendChild(new Option(y + '年', y)));

  renderDashboard();
}

// ── リアルタイム同期 ──────────────────────────────────────────
function setupRealtimeSync() {
  // 新規伝票のリアルタイム更新
  fbDb.ref('/sales').on('child_added', snap => {
    const sale = snap.val();
    if (!store.sales.find(s => s.id === sale.id)) {
      store.sales.unshift(sale);
      if (!custTotalCache[sale.cid]) custTotalCache[sale.cid] = { total: 0, count: 0 };
      custTotalCache[sale.cid].total += sale.total;
      custTotalCache[sale.cid].count++;
      document.getElementById('db-stats').innerHTML =
        `売上 ${store.sales.length.toLocaleString()}件<br>顧客 ${store.customers.filter(c=>c.name).length}名`;
    }
  });

  // 新規顧客のリアルタイム更新
  fbDb.ref('/customers').on('child_added', snap => {
    const cust = snap.val();
    if (!store.customers.find(c => c.id === cust.id)) {
      store.customers.push(cust);
      document.getElementById('db-stats').innerHTML =
        `売上 ${store.sales.length.toLocaleString()}件<br>顧客 ${store.customers.filter(c=>c.name).length}名`;
    }
  });

  // 在庫変更のリアルタイム更新
  fbDb.ref('/stock').on('child_changed', snap => {
    const updated = snap.val();
    const idx = store.stock.findIndex(i => i.code === updated.code);
    if (idx >= 0) store.stock[idx] = updated;
  });
}

// ── Firebase書き込み（saveSalesをオーバーライド） ────────────
const _origSaveSales = saveSales;
saveSales = async function() {
  const no   = document.getElementById('ns-no').value.trim();
  const date = document.getElementById('ns-date').value;
  const cid  = document.getElementById('ns-customer').value;
  if (!no || !date || !cid) { alert('伝票番号・日付・顧客は必須です'); return; }
  const items = newSalesDetailRows.filter(r => r.code).map(r => {
    const p = getProduct(r.code);
    return { c: r.code, n: p ? p.name : r.code, q: r.qty, a: p ? p.price * r.qty : 0 };
  });
  if (items.length === 0) { alert('明細を1件以上入力してください'); return; }
  const total = items.reduce((s, it) => s + it.a, 0);
  const c = getCustomer(cid);
  const ym = date.slice(0, 7);
  const saleObj = { id: no, d: date, ym, cid, cn: c?.name||'', ck: c?.kana||'', total, items };

  try {
    await fbDb.ref('/sales/' + no).set(saleObj);
    store.sales.unshift(saleObj);
    if (!custTotalCache[cid]) custTotalCache[cid] = { total: 0, count: 0 };
    custTotalCache[cid].total += total;
    custTotalCache[cid].count++;
    alert('伝票を登録しました：' + no);
    showPage('sales', null);
  } catch (err) {
    alert('Firebase保存エラー: ' + err.message);
  }
};

// ── Firebase書き込み（saveCustomerをオーバーライド） ──────────
const _origSaveCustomer = saveCustomer;
saveCustomer = async function() {
  const name = document.getElementById('nc-name').value.trim();
  if (!name) { alert('名前は必須です'); return; }
  const newId = String(Math.max(...store.customers.map(c => parseInt(c.id)||0)) + 1);
  const custObj = {
    id: newId, name,
    kana:   document.getElementById('nc-kana').value,
    tel:    document.getElementById('nc-tel').value,
    mobile: document.getElementById('nc-mobile').value,
    email:  document.getElementById('nc-email').value,
    zip:    document.getElementById('nc-zip').value,
    addr:   document.getElementById('nc-addr').value,
    bday:   document.getElementById('nc-bday').value,
    rid:    document.getElementById('nc-rank').value,
    rank:   store.ranks[document.getElementById('nc-rank').value] || '',
    staff:  store.staff[document.getElementById('nc-staff').value] || document.getElementById('nc-staff').value,
    note:   document.getElementById('nc-note').value,
    hc: '0', kp: '0'
  };

  try {
    await fbDb.ref('/customers/' + newId).set(custObj);
    store.customers.push(custObj);
    alert(name + '（No.' + newId + '）を登録しました');
    filteredCustomers = null;
    showPage('customers', null);
  } catch (err) {
    alert('Firebase保存エラー: ' + err.message);
  }
};

// ── Firebase書き込み（saveInoutをオーバーライド） ─────────────
const _origSaveInout = saveInout;
saveInout = async function() {
  const code = document.getElementById('inout-product').value;
  const qty  = parseInt(document.getElementById('inout-qty').value) || 0;
  if (!qty) { alert('数量を入力してください'); return; }
  const inv = store.stock.find(i => i.code === code);
  if (!inv) return;
  const newQty = currentInoutType === 'in' ? inv.qty + qty : Math.max(0, inv.qty - qty);

  try {
    await fbDb.ref('/stock/' + code).update({ qty: newQty });
    inv.qty = newQty;
    closeModal('inout-modal');
    renderInventory();
    renderInvAlert();
    alert((currentInoutType === 'in' ? '入庫' : '出庫') + '登録しました（在庫: ' + newQty + '個）');
  } catch (err) {
    alert('Firebase保存エラー: ' + err.message);
  }
};

// ── CSVバックアップ ──────────────────────────────────────────
function downloadSalesCSV() {
  const rows = [['伝票番号','日付','年月','顧客ID','顧客名','フリガナ','合計（税抜）','合計（税込）','明細数']];
  store.sales.slice().sort((a,b) => a.d.localeCompare(b.d)).forEach(s => {
    rows.push([
      s.id, s.d, s.ym, s.cid, s.cn, s.ck,
      s.total, Math.round(s.total * 1.1), s.items.length
    ]);
  });
  const csv = rows.map(r => r.map(v => '"' + String(v||'').replace(/"/g, '""') + '"').join(',')).join('\\n');
  const blob = new Blob(['\\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = '朝霧店_売上データ_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
  URL.revokeObjectURL(url);
}

function downloadCustomersCSV() {
  const rows = [['顧客ID','名前','フリガナ','電話','携帯','メール','郵便番号','住所','生年月日','ランク','担当','備考']];
  store.customers.filter(c=>c.name).forEach(c => {
    rows.push([c.id, c.name, c.kana, c.tel, c.mobile, c.email, c.zip, c.addr, c.bday, c.rank, c.staff, c.note]);
  });
  const csv = rows.map(r => r.map(v => '"' + String(v||'').replace(/"/g, '""') + '"').join(',')).join('\\n');
  const blob = new Blob(['\\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = '朝霧店_顧客データ_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
  URL.revokeObjectURL(url);
}

// ── localStorage キャッシュ ───────────────────────────────────
const CACHE_KEY = 'asagiri_cache_v1';

function saveCachedData(data) {
  try {
    localStorage.setItem(CACHE_KEY, JSON.stringify({
      ts: Date.now(),
      sales:     data.sales,
      customers: data.customers,
      products:  data.products,
      stock:     data.stock,
      staff:     data.staff,
      ranks:     data.ranks,
    }));
  } catch (e) { /* localStorage容量超過時は無視 */ }
}

function loadCachedData() {
  try {
    const raw = localStorage.getItem(CACHE_KEY);
    if (!raw) return null;
    const cached = JSON.parse(raw);
    // 7日以上古いキャッシュは無視
    if (Date.now() - cached.ts > 7 * 24 * 60 * 60 * 1000) return null;
    return cached;
  } catch (e) { return null; }
}

// ── ローディング表示 ─────────────────────────────────────────
function showLoading(msg) {
  const el = document.getElementById('app-loading');
  el.style.display = 'flex';
  if (msg) document.getElementById('loading-msg').textContent = msg;
}

function hideLoading() {
  document.getElementById('app-loading').style.display = 'none';
}
</script>
"""

# ─────────────────────────────────────────
# サインアウトボタン＋バックアップボタン (サイドバーに追加)
# ─────────────────────────────────────────
SIDEBAR_EXTRA = """
      <div class="nav-section">BACKUP</div>
      <div class="nav-item" onclick="downloadSalesCSV()"><span class="icon">⬇</span> 売上CSVダウンロード</div>
      <div class="nav-item" onclick="downloadCustomersCSV()"><span class="icon">⬇</span> 顧客CSVダウンロード</div>
      <div class="nav-section">ACCOUNT</div>
      <div class="nav-item" onclick="doSignOut()"><span class="icon">⏻</span> ログアウト</div>
      <div style="padding:8px 20px 4px;font-size:11px;color:var(--text3)" id="firebase-user"></div>
"""


def main():
    print(f'読み込み: {SRC}')
    with open(SRC, 'r', encoding='utf-8') as f:
        html = f.read()

    # 1) データスクリプトブロックを空のDBに置換
    print('データスクリプトを置換中...')
    html = re.sub(
        r'<script id="data-script">.*?</script>',
        '<script id="data-script">\n'
        'const DB={sales:[],customers:[],products:[],stock:[],staff:{},ranks:{}};\n'
        '</script>',
        html, flags=re.DOTALL
    )

    # 2) Firebase SDKを</head>直前に挿入
    html = html.replace('</head>', FIREBASE_SDK + '</head>')

    # 3) ログインオーバーレイを<body>直後に挿入
    html = html.replace('<body>', '<body>\n' + LOGIN_OVERLAY)

    # 4) 既存の初期化コードをコメントアウト（renderDashboard()など）
    #    最後のscriptブロック末尾の初期化処理を無効化
    old_init = (
        "document.getElementById('current-date').textContent =\n"
        "  new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });\n"
        "\n"
        "document.getElementById('db-stats').innerHTML =\n"
        "  `売上 ${store.sales.length.toLocaleString()}件<br>顧客 ${store.customers.filter(c=>c.name).length}名`;\n"
        "\n"
        "// レポート年リスト初期化\n"
        "const reportYears = [...new Set(store.sales.filter(s=>s.d).map(s=>s.d.slice(0,4)))].sort().reverse();\n"
        "const reportSel = document.getElementById('report-year');\n"
        "reportYears.forEach(y => reportSel.appendChild(new Option(y + '年', y)));\n"
        "\n"
        "renderDashboard();"
    )
    new_init = (
        "// ★ Firebase版: 初期化はloadDataFromFirebase()内で実行\n"
        "// (上記コードはFirebase初期化スクリプト内のrefreshUI()に移動)"
    )
    if old_init in html:
        html = html.replace(old_init, new_init)
        print('既存の初期化コードを無効化しました')
    else:
        print('警告: 既存の初期化コードが見つかりませんでした（手動確認が必要）')

    # 5) サイドバーにバックアップ・ログアウトボタンを追加
    sidebar_anchor = '<div style="padding:16px 20px;border-top:1px solid var(--border)">'
    html = html.replace(sidebar_anchor, SIDEBAR_EXTRA + '\n    ' + sidebar_anchor)

    # 6) Firebase初期化JSを</body>直前に挿入
    html = html.replace('</body>', FIREBASE_INIT + '\n</body>')

    os.makedirs(os.path.dirname(DST), exist_ok=True)
    print(f'書き込み: {DST}')
    with open(DST, 'w', encoding='utf-8') as f:
        f.write(html)

    size_mb = os.path.getsize(DST) / 1024 / 1024
    print(f'完了! ファイルサイズ: {size_mb:.2f} MB')
    print()
    print('次のステップ:')
    print('  1. asagiri-firebase/index.html を開き、firebaseConfig を設定してください')
    print('  2. python firebase_import.py でデータをFirebaseにインポートしてください')
    print('  3. firebase deploy でFirebase Hostingにデプロイしてください')


if __name__ == '__main__':
    main()
