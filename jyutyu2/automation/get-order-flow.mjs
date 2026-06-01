// 取得ボタン以降のページ構造を確認するスクリプト
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false, slowMo: 300 });
const page = await browser.newPage();

// ログイン
await page.goto('https://yamano-order.shop/Form/Login.aspx', { waitUntil: 'domcontentloaded' });
await page.fill('#ctl00_ContentPlaceHolder1_tbLoginId', '40633');
await page.fill('#ctl00_ContentPlaceHolder1_tbPassword', 'Y2637985');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle' }),
  page.click('#ctl00_ContentPlaceHolder1_lbLogin'),
]);
console.log('ログイン完了');

// カートページへ
await page.goto('https://yamano-order.shop/Form/Order/CartList.aspx', { waitUntil: 'networkidle' });

// テスト用商品コードを1行目に入力（コードは実際のヤマノ商品コードで確認してください）
// ここでは仮に先ほどユーザーが指定した '0303' を使います
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductId', '0303');
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductQuantity', '1');
console.log('商品コード入力完了 (0303 x1)');

// 取得ボタンをクリック
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
  page.click('#ctl00_ContentPlaceHolder1_lbGetAllproduct'),
]);
console.log('取得後URL:', page.url());
await page.waitForTimeout(2000);

// ボタン一覧を取得
const buttons = await page.evaluate(() => {
  return Array.from(document.querySelectorAll('a, input[type="submit"], input[type="button"], button'))
    .map(el => ({
      tag: el.tagName, id: el.id || '',
      text: (el.textContent?.trim() || el.value || '').slice(0, 40),
      href: el.href || '',
    }))
    .filter(b => b.text)
    .slice(0, 30); // 最初の30件だけ
});

console.log('\n=== 取得後ページのボタン/リンク ===');
buttons.forEach(b => console.log(`  [${b.text}] id=${b.id} href=${b.href.slice(0, 60)}`));

// スクリーンショット
await page.screenshot({ path: 'automation/cart-after-fetch.png', fullPage: false });
console.log('\nスクリーンショット保存: automation/cart-after-fetch.png');

console.log('\nブラウザを確認してください。Enterを押すと次に進みます。');
// 少し待つ
await page.waitForTimeout(5000);
await browser.close();
