// ヤマノ発注サイト自動入力スクリプト
// 使用技術: Playwright (Chromium)
// サイト: https://yamano-order.shop/（ASP.NET WebForms）
import { chromium } from 'playwright';

// ─── 設定 ────────────────────────────────────────────────────
const LOGIN_URL = 'https://yamano-order.shop/Form/Login.aspx';
const CART_URL  = 'https://yamano-order.shop/Form/Order/CartList.aspx';
const LOGIN_ID   = '40633';
const LOGIN_PASS = 'Y2637985';

// FCアプリのFC名 → ヤマノ発注サイトの配送先 SELECT値
// （OrderShipping.aspx の ddlShippingKbnList で確認済み）
const FC_TO_SHIPPING_VALUE = {
  '朝霧ヤマノ':   '121', // 朝霧　朝霧ヤマノ　有江健彦
  '西明石白神FC': '21',  // 朝霧　★西明石白神
  '藍魚住FC':    '25',  // 朝霧　★藍魚住
  '学園東町FC':  '17',  // ★学園東町
};

// 初期表示行数（ctl00〜ctl08 = 9行）
const INITIAL_ROW_COUNT = 9;
// ────────────────────────────────────────────────────────────

/**
 * ヤマノ発注サイトへの自動入力を実行する
 * @param {object} orderData - { orderId, fcName, items: [{code, qty}] }
 * @param {function} send - (type, message) => void
 */
export async function runYamanoOrder(orderData, send) {
  const { orderId, fcName, items } = orderData;
  const shippingValue = FC_TO_SHIPPING_VALUE[fcName];

  if (!shippingValue) {
    throw new Error(
      `配送先マッピングが未設定: 「${fcName}」\n` +
      `automation/yamano-order.mjs の FC_TO_SHIPPING_VALUE に追加してください。`
    );
  }

  send('progress', 'ブラウザを起動中...');
  const browser = await chromium.launch({
    headless: false,
    slowMo: 400,
  });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1280, height: 900 });

  try {
    // ─── Step 1: ログイン ──────────────────────────────────────
    send('progress', `ログイン中 (ID: ${LOGIN_ID})...`);
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

    await page.fill('#ctl00_ContentPlaceHolder1_tbLoginId', LOGIN_ID);
    await page.fill('#ctl00_ContentPlaceHolder1_tbPassword', LOGIN_PASS);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
      page.click('#ctl00_ContentPlaceHolder1_lbLogin'),
    ]);
    send('progress', 'ログイン完了');

    // ─── Step 2: カートページへ移動 ───────────────────────────
    send('progress', 'カートページへ移動中...');
    await page.goto(CART_URL, { waitUntil: 'networkidle' });

    // ─── Step 3: 商品コードと個数を一括入力 ───────────────────
    // カートは最初 9行（ctl00〜ctl08）。足りなければ「入力欄追加」で増やす
    send('progress', `${items.length}品目を入力中...`);

    let availableRows = INITIAL_ROW_COUNT;

    // 9行を超える場合は入力欄を追加する（追加ボタン1クリックで3行追加される模様）
    while (items.length > availableRows) {
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 }).catch(() => {}),
        page.click('#ctl00_ContentPlaceHolder1_lbAddProduct'),
      ]);
      await page.waitForTimeout(500);
      // 追加後の行数を再カウント
      availableRows = await page.locator('input[id*="tbProductId"]').count();
      send('progress', `入力欄追加 → ${availableRows}行に拡張`);
    }

    // 全商品を入力
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const pad = String(i).padStart(2, '0'); // 00, 01, 02...
      const codeInput = `#ctl00_ContentPlaceHolder1_rInputProduct_ctl${pad}_tbProductId`;
      const qtyInput  = `#ctl00_ContentPlaceHolder1_rInputProduct_ctl${pad}_tbProductQuantity`;

      await page.fill(codeInput, item.code);
      await page.fill(qtyInput, String(item.qty));

      send('progress', `[${i + 1}/${items.length}] 商品コード: ${item.code}　数量: ${item.qty}個`);
    }

    // ─── Step 4: 取得ボタンをクリック（全商品を一括取得）──────
    send('progress', '「取得」ボタンをクリック中（全商品を一括取得）...');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
      page.click('#ctl00_ContentPlaceHolder1_lbGetAllproduct'),
    ]);
    await page.waitForTimeout(1000);

    // 取得後のカート確認（ヘッダーのカート件数で確認）
    const cartCount = await page.locator('header, #ctl00_Header1_lblCartCnt, [id*="CartCnt"]')
      .first().textContent().catch(() => '');
    send('progress', `取得完了 ${cartCount ? '（カート: ' + cartCount.trim() + '）' : ''}`);

    // ─── Step 5: ご注文手続きボタンをクリック ─────────────────
    send('progress', '「ご注文手続き」ボタンをクリック中...');
    await page.click('a:has-text("ご注文手続き")');
    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1500);

    const shippingUrl = page.url();
    if (!shippingUrl.includes('OrderShipping')) {
      send('error', `お届け先ページへの遷移に失敗しました。現在URL: ${shippingUrl}`);
    }
    send('progress', 'お届け先ページに到達');

    // ─── Step 6: 配送先を SELECT で選択 ───────────────────────
    send('progress', `配送先を選択中: ${fcName} → value="${shippingValue}"`);
    const shippingSelect = '#ctl00_ContentPlaceHolder1_rCartList_ctl00_ddlShippingKbnList';
    await page.selectOption(shippingSelect, { value: shippingValue });
    await page.waitForTimeout(800);

    // 選択された配送先のテキストを確認
    const selectedText = await page.locator(shippingSelect + ' option:checked').textContent().catch(() => '');
    send('progress', `配送先確認: ${selectedText?.trim()}`);

    // ─── Step 7: ご注文内容確認へボタンをクリック ─────────────
    send('progress', '「ご注文内容確認へ」ボタンをクリック中...');
    await page.click('a:has-text("ご注文内容確認へ")');
    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1500);

    const confirmUrl = page.url();
    send('progress', `確認ページURL: ${confirmUrl}`);

    // ─── Step 8: ここで止まる ─────────────────────────────────
    send('done',
      '✅ 自動入力が完了しました！\n' +
      'ブラウザで注文内容を確認して「ご注文を確定する」ボタンを押してください。\n' +
      '（確定後はブラウザを手動で閉じてください）'
    );

    // ブラウザは閉じない → 在りさんが確認して確定ボタンを押す

  } catch (err) {
    // エラー時もブラウザを閉じない（画面を確認して原因を特定できるように）
    throw err;
  }
}
