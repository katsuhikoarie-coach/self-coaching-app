// 取得後のカートページ全体と注文フローを確認するスクリプト
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false, slowMo: 200 });
const page = await browser.newPage();
await page.setViewportSize({ width: 1280, height: 900 });

// ログイン
await page.goto('https://yamano-order.shop/Form/Login.aspx', { waitUntil: 'domcontentloaded' });
await page.fill('#ctl00_ContentPlaceHolder1_tbLoginId', '40633');
await page.fill('#ctl00_ContentPlaceHolder1_tbPassword', 'Y2637985');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle' }),
  page.click('#ctl00_ContentPlaceHolder1_lbLogin'),
]);

// カートページ
await page.goto('https://yamano-order.shop/Form/Order/CartList.aspx', { waitUntil: 'networkidle' });

// 商品0303を入力して取得
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductId', '0303');
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductQuantity', '1');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
  page.click('#ctl00_ContentPlaceHolder1_lbGetAllproduct'),
]);
await page.waitForTimeout(1000);
console.log('取得後 URL:', page.url());

// フルページスクリーンショット
await page.screenshot({ path: 'automation/cart-full.png', fullPage: true });
console.log('フルページ SS: automation/cart-full.png');

// 全ボタン取得（件数制限なし）
const allButtons = await page.evaluate(() => {
  return Array.from(document.querySelectorAll('a[id], input[type="submit"], input[type="button"], button'))
    .map(el => ({
      tag: el.tagName, id: el.id || '',
      text: (el.textContent?.trim() || el.value || '').replace(/\s+/g, ' ').slice(0, 50),
      href: (el.href || '').slice(0, 80),
    }))
    .filter(b => b.text);
});

console.log('\n=== IDつきのボタン/リンク一覧 ===');
allButtons.forEach(b => console.log(`  id="${b.id}" [${b.text}]`));

// カートアイコン（ヘッダー）をクリックして次のページへ
console.log('\nカートアイコンをクリック...');
await page.click('a[href*="CartList"]');
await page.waitForLoadState('networkidle');
await page.screenshot({ path: 'automation/cart-icon-click.png', fullPage: false });

const cartPageButtons = await page.evaluate(() =>
  Array.from(document.querySelectorAll('a, input[type="submit"], button'))
    .map(el => ({
      id: el.id || '',
      text: (el.textContent?.trim() || el.value || '').replace(/\s+/g, ' ').slice(0, 50),
      href: (el.href || '').slice(0, 80),
    }))
    .filter(b => b.text && b.text.length > 1)
    .slice(0, 40)
);
console.log('\n=== カートアイコンクリック後のボタン ===');
cartPageButtons.forEach(b => console.log(`  id="${b.id}" [${b.text}] ${b.href}`));

await browser.close();
