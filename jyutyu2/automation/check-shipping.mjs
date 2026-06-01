// 配送先ドロップダウンの全オプションを確認するスクリプト
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

// ログイン
await page.goto('https://yamano-order.shop/Form/Login.aspx', { waitUntil: 'domcontentloaded' });
await page.fill('#ctl00_ContentPlaceHolder1_tbLoginId', '40633');
await page.fill('#ctl00_ContentPlaceHolder1_tbPassword', 'Y2637985');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle' }),
  page.click('#ctl00_ContentPlaceHolder1_lbLogin'),
]);

// カートに商品を1つ入れてお届け先ページへ
await page.goto('https://yamano-order.shop/Form/Order/CartList.aspx', { waitUntil: 'networkidle' });
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductId', '0303');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
  page.click('#ctl00_ContentPlaceHolder1_lbGetAllproduct'),
]);
await page.click('a:has-text("ご注文手続き")');
await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
await page.waitForTimeout(1500);

// 配送先ドロップダウンのオプション全取得
const options = await page.evaluate(() => {
  const sel = document.querySelector('#ctl00_ContentPlaceHolder1_rCartList_ctl00_ddlShippingKbnList');
  if (!sel) return [];
  return Array.from(sel.options).map(o => ({ value: o.value, text: o.text.trim() }));
});

console.log('=== 配送先ドロップダウン 全オプション ===\n');
options.forEach(o => console.log(`  value="${o.value}"  "${o.text}"`));

// 朝霧関連を抽出
console.log('\n=== 「朝霧」または「ヤマノ」を含むオプション ===\n');
options.filter(o => o.text.includes('朝霧') || o.text.includes('ヤマノ'))
       .forEach(o => console.log(`  value="${o.value}"  "${o.text}"`));

await browser.close();
