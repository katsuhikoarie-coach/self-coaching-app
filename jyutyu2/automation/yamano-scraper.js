'use strict';
// ============================================================
// ヤマノ注文履歴取込スクリプト
// 使用技術: Playwright（DB接続なし・API経由でサーバーに保存）
// 実行: npm run scrape
//       または automation\run-scraper.bat（タスクスケジューラ）
// オプション: --force  既存チェックをスキップして全件再取込
// ============================================================

const { chromium } = require('playwright');

// ── 設定 ────────────────────────────────────────────────────
const API_BASE       = 'https://fc-order.asagiriyamano.jp';
const API_KEY        = process.env.API_KEY || '';
const FORCE_REIMPORT = process.argv.includes('--force');

const YAMANO_BASE = 'https://yamano-order.shop';
const LOGIN_URL   = `${YAMANO_BASE}/Form/Login.aspx`;
const HISTORY_URL = `${YAMANO_BASE}/Form/OrderHistory/OrderHistoryList.aspx`;
const LOGIN_ID    = '40633';
const LOGIN_PASS  = 'Y2637985';

// ────────────────────────────────────────────────────────────

async function main() {
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('  ヤマノ注文履歴取込スクリプト');
  console.log(`  実行日時: ${new Date().toLocaleString('ja-JP')}`);
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

  if (!API_KEY) {
    console.error('✗ API_KEYが設定されていません。');
    console.error('  run-scraper.bat の "set API_KEY=..." を確認してください。');
    process.exit(1);
  }

  // ① サーバーから既存注文番号を取得（差分チェック用）
  let existingIds;
  if (FORCE_REIMPORT) {
    existingIds = new Set();
    console.log('⚠️  --force: 既存チェックをスキップして全件再取込します\n');
  } else {
    console.log('既存注文番号をサーバーから取得中...');
    try {
      existingIds = await fetchExistingIds();
      console.log(`✓ 既存注文数: ${existingIds.size}件\n`);
    } catch (err) {
      console.error('✗ 既存ID取得失敗:', err.message);
      process.exit(1);
    }
  }

  // ② Playwright起動
  const browser = await chromium.launch({ headless: true });
  const page    = await browser.newPage();
  await page.setViewportSize({ width: 1280, height: 900 });

  try {
    // ③ ログイン
    console.log('ヤマノサイトにログイン中...');
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.fill('#ctl00_ContentPlaceHolder1_tbLoginId',  LOGIN_ID);
    await page.fill('#ctl00_ContentPlaceHolder1_tbPassword', LOGIN_PASS);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load', timeout: 30000 }),
      page.click('#ctl00_ContentPlaceHolder1_lbLogin'),
    ]);
    await page.waitForTimeout(1000);
    console.log(`✓ ログイン完了 → ${page.url()}\n`);

    // ④ 購入履歴一覧ページへ移動
    await page.goto(HISTORY_URL, { waitUntil: 'load', timeout: 20000 });
    await page.waitForTimeout(800);
    console.log(`✓ 購入履歴ページ → ${page.url()}\n`);

    // ⑤ 全注文の ID + 詳細URLを一括収集
    console.log('注文一覧を収集中...');
    const orderMap = await collectAllOrders(page);
    console.log(`✓ 注文番号収集: ${orderMap.size}件\n`);

    // ⑥ 差分のみ
    const newEntries = [...orderMap.entries()].filter(([id]) => !existingIds.has(id));
    console.log(`新規取込対象: ${newEntries.length}件\n`);

    if (newEntries.length === 0) {
      console.log('新規注文なし。終了します。');
      return;
    }

    // ⑦ 各注文の詳細を取得
    const collected = [];
    let fetchFailed = 0;

    for (const [orderId, detailUrl] of newEntries) {
      try {
        process.stdout.write(`  取得中: ${orderId} ... `);
        const detail = await fetchOrderDetail(page, orderId, detailUrl);
        if (!detail) {
          console.log('スキップ（詳細取得不可）');
          fetchFailed++;
          continue;
        }

        // キャンセルのみスキップ（注文済み・受注承認・出荷完了 等は取込対象）
        if (detail.status.includes('キャンセル')) {
          console.log(`スキップ（ステータス: ${detail.status || '不明'}）`);
          continue;
        }

        collected.push({ yamano_order_id: orderId, ...detail });
        console.log(`✓ ${detail.shipping_name || '(配送先不明)'} / ${detail.items.length}品目`);
      } catch (err) {
        console.log(`✗ ${err.message}`);
        fetchFailed++;
      }
      await new Promise(r => setTimeout(r, 1000));
    }

    console.log(`\n取得完了: ${collected.length}件収集 / ${fetchFailed}件失敗\n`);

    if (collected.length === 0) {
      console.log('送信データなし。終了します。');
      return;
    }

    // ⑧ サーバーAPIにPOSTして一括保存
    console.log(`サーバーに ${collected.length}件 を送信中...`);
    try {
      const result = await postOrders(collected);
      console.log(`\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);
      console.log(`完了: 保存 ${result.saved}件 / スキップ ${result.skipped}件 / エラー ${result.errors.length}件`);
      if (result.errors.length > 0) {
        console.log('エラー詳細:');
        result.errors.forEach(e => console.log(`  - ${e}`));
      }
      console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    } catch (err) {
      console.error('✗ サーバー送信失敗:', err.message);
      process.exit(1);
    }

  } finally {
    await browser.close();
  }
}

// ── サーバーから既存注文番号を取得 ──────────────────────────
async function fetchExistingIds() {
  const res = await fetch(`${API_BASE}/api/admin/yamano-import.php`, {
    headers: { 'X-Api-Key': API_KEY },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return new Set(data.existing_ids || []);
}

// ── 収集した注文データをサーバーにPOST ─────────────────────
async function postOrders(orders) {
  const res = await fetch(`${API_BASE}/api/admin/yamano-import.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Api-Key': API_KEY },
    body: JSON.stringify({ orders }),
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return await res.json();
}

// ── 全注文の ID + 詳細URL を一括収集 ────────────────────────
// 注文IDは DIV>UL>LI>A 構造の <a> タグに入っており、
// href に ?odid=Y... 形式の詳細URLが含まれる。
async function collectAllOrders(page) {
  const orderMap = new Map();

  while (true) {
    const links = await page.locator('a').all();
    for (const link of links) {
      const text = (await link.textContent().catch(() => '')).trim();
      if (!/^Y\d{13}$/.test(text)) continue;
      if (orderMap.has(text)) continue;

      const href = (await link.getAttribute('href').catch(() => null)) || '';
      const full = href
        ? (href.startsWith('http') ? href : `${YAMANO_BASE}${href.startsWith('/') ? '' : '/'}${href}`)
        : null;
      orderMap.set(text, full);
    }

    const nextLink = page.locator([
      'a:has-text("次へ")',
      'a:has-text("次のページ")',
      'a:has-text(">>")',
      '[title="次のページ"]',
    ].join(', ')).first();
    if (await nextLink.count() === 0) break;

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load', timeout: 15000 }).catch(() => {}),
      nextLink.click(),
    ]);
    await page.waitForTimeout(600);
  }

  return orderMap;
}

// ── 注文詳細を取得 ───────────────────────────────────────────
// orderIdを含むLIコンテナ内の「購入履歴詳細」ボタンをクリックして
// 詳細ページへ移動し、データを取得する。
async function fetchOrderDetail(page, orderId, detailUrl) {
  // 履歴一覧ページを確実に表示
  if (!page.url().includes('OrderHistoryList')) {
    await page.goto(HISTORY_URL, { waitUntil: 'load', timeout: 20000 });
    await page.waitForTimeout(800);
  }

  // orderIdを含む LI コンテナを特定し、その中の「購入履歴詳細」ボタンをクリック
  const container = page.locator('li').filter({
    has: page.locator(`a:has-text("${orderId}")`),
  }).first();

  let navigated = false;

  if (await container.count() > 0) {
    const detailBtn = container.locator(
      'a:has-text("購入履歴詳細"), button:has-text("購入履歴詳細")'
    ).first();
    if (await detailBtn.count() > 0) {
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load', timeout: 20000 }).catch(() => {}),
        detailBtn.click(),
      ]);
      await page.waitForTimeout(500);
      navigated = true;
    }
  }

  // フォールバック: detailUrl（?odid=...）で直接遷移
  if (!navigated && detailUrl) {
    await page.goto(detailUrl, { waitUntil: 'load', timeout: 20000 });
    await page.waitForTimeout(500);
    navigated = true;
  }

  if (!navigated) return null;

  // 詳細ページ到達確認
  const currentUrl = page.url();
  const body = await page.textContent('body').catch(() => '');
  if (!body || currentUrl.includes('OrderHistoryList') ||
      currentUrl.includes('Error') || currentUrl.includes('Login')) {
    return null;
  }

  // ── 注文日 ──────────────────────────────────────────────
  const dateMatch = body.match(/(\d{4})[\/年](\d{1,2})[\/月](\d{1,2})/);
  const order_date = dateMatch
    ? `${dateMatch[1]}-${dateMatch[2].padStart(2, '0')}-${dateMatch[3].padStart(2, '0')}`
    : new Date().toISOString().slice(0, 10);

  // ── 注文状況 ─────────────────────────────────────────────
  // table[0]（注文情報テーブル）の「注文状況」行から取得する。
  // body全体から regex 検索するとナビの「キャンセル」ボタン等を誤検出するため。
  const status = await page.evaluate(() => {
    const tables = document.querySelectorAll('table');
    if (!tables[0]) return '';
    for (const row of tables[0].rows) {
      if (row.cells.length < 2) continue;
      if (/注文状況|ステータス|状態/.test(row.cells[0].textContent)) {
        return row.cells[1].textContent.trim();
      }
    }
    return '';
  });

  // ── 税別合計 ─────────────────────────────────────────────
  const totalMatch = body.match(/税[別抜][^\d]*?([\d,]+)/);
  const total_pretax = totalMatch ? parseInt(totalMatch[1].replace(/,/g, '')) : 0;

  // ── お届け先情報 ─────────────────────────────────────────
  const { shippingName, shippingAddress, shippingTel } = await extractShipping(page);

  // ── 商品明細 ─────────────────────────────────────────────
  const items = await extractItems(page);

  return {
    order_date,
    total_pretax,
    shipping_name:    shippingName,
    shipping_address: shippingAddress,
    shipping_tel:     shippingTel,
    status,
    items,
  };
}

// ── お届け先情報を抽出 ───────────────────────────────────────
// table[1] が発送先テーブル（縦型ラベル-値形式）
async function extractShipping(page) {
  let shippingName = '', shippingAddress = '', shippingTel = '';

  const rows = await page.locator('tr').all();
  for (const row of rows) {
    const cells = await row.locator('th, td').all();
    if (cells.length < 2) continue;
    const label = (await cells[0].textContent().catch(() => '')).trim();
    const value = (await cells[1].textContent().catch(() => '')).trim();

    if (!shippingName    && /氏名|お名前|届け先名/.test(label)) shippingName    = value;
    if (!shippingAddress && /住所/.test(label))                  shippingAddress = value;
    if (!shippingTel     && /電話|TEL|tel/i.test(label))         shippingTel     = value.replace(/[^\d-]/g, '');
  }

  if (!shippingTel) {
    const body = await page.textContent('body').catch(() => '');
    const m = body.match(/(\d{2,4}-\d{2,4}-\d{3,4})/);
    if (m) shippingTel = m[1];
  }

  return { shippingName, shippingAddress, shippingTel };
}

// ── 商品明細を抽出 ───────────────────────────────────────────
// 詳細ページは「table[2]以降が商品テーブル」の縦型4行1商品形式。
//   row0: 商品名  | 商品名テキスト [コード]
//   row1: 単価    | 単価（税別）:¥X,XXX
//   row2: 注文数  | 注文数 :N
//   row3: 小計    | 小計（税別）:¥X,XXX
async function extractItems(page) {
  const items = [];

  const allRows = await page.evaluate(() => {
    // table[2]以降の全テーブルの行データを収集
    const tables = Array.from(document.querySelectorAll('table'));
    const rows = [];
    for (let t = 2; t < tables.length; t++) {
      for (const row of tables[t].rows) {
        rows.push(Array.from(row.cells).map(c => c.textContent.trim().replace(/\s+/g, ' ')));
      }
    }
    return rows;
  });

  // 4行ずつグループ化して商品を抽出
  for (let i = 0; i + 3 < allRows.length; i += 4) {
    const nameCell     = (allRows[i][1]     || '').trim();
    const priceCell    = (allRows[i + 1][1] || '').trim();
    const qtyCell      = (allRows[i + 2][1] || '').trim();
    const subtotalCell = (allRows[i + 3][1] || '').trim();

    // 商品名末尾の [コード] を抽出（英数字混在コード N011 等にも対応）
    const codeMatch = nameCell.match(/\[([A-Za-z0-9]{2,8})\]\s*$/);
    const product_code = codeMatch ? codeMatch[1] : '';
    const product_name = codeMatch
      ? nameCell.slice(0, nameCell.lastIndexOf('[')).trim()
      : nameCell;

    if (!product_name) continue;

    // 金額: 末尾の数字（¥6,000 → 6000）
    const toNum = str => {
      const m = str.match(/[\d,]+(?=\s*$)/);
      return m ? parseInt(m[0].replace(/,/g, '')) : 0;
    };

    // 数量: 末尾の整数（注文数 :1 → 1）
    const qtyMatch = qtyCell.match(/(\d+)\s*$/);
    const quantity  = qtyMatch ? parseInt(qtyMatch[1]) : 1;

    items.push({
      product_code,
      product_name,
      unit_price: toNum(priceCell),
      quantity,
      subtotal:   toNum(subtotalCell),
    });
  }

  return items;
}

// ────────────────────────────────────────────────────────────
main().catch(err => {
  console.error('\n致命的なエラー:', err);
  process.exit(1);
});
