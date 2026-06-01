// カートページのフォーム構造を確認するスクリプト
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

// ログイン
await page.goto('https://yamano-order.shop/Form/Login.aspx', { waitUntil: 'domcontentloaded' });
await page.fill('#ctl00_ContentPlaceHolder1_tbLoginId', '40633');
await page.fill('#ctl00_ContentPlaceHolder1_tbPassword', 'Y2637985');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
  page.click('#ctl00_ContentPlaceHolder1_lbLogin'),
]);

// カートページへ
await page.goto('https://yamano-order.shop/Form/Order/CartList.aspx', { waitUntil: 'networkidle' });
console.log('カートページURL:', page.url());

const formInfo = await page.evaluate(() => {
  const inputs = Array.from(document.querySelectorAll('input:not([type="hidden"])')).map(el => ({
    type: el.type, name: el.name, id: el.id,
    placeholder: el.placeholder, value: el.value.slice(0, 30),
    className: el.className.slice(0, 40),
  }));
  const buttons = Array.from(document.querySelectorAll('input[type="submit"], input[type="button"], button, a'))
    .filter(el => {
      const t = el.textContent?.trim() || el.value || '';
      return t && (t.includes('取得') || t.includes('追加') || t.includes('注文') || t.includes('手続') || t.includes('確認') || t.includes('削除'));
    })
    .map(el => ({
      tag: el.tagName, type: el.type || '', name: el.name || '', id: el.id || '',
      value: el.value || '', text: el.textContent?.trim().slice(0, 40) || '',
      href: el.href || '',
    }));
  const tables = Array.from(document.querySelectorAll('table')).map(t => ({
    id: t.id,
    rows: t.rows.length,
    className: t.className.slice(0, 40),
    firstRowText: t.rows[0]?.textContent?.trim().slice(0, 60) || '',
  }));
  return { url: location.href, inputs, buttons, tables };
});

console.log('\n=== カートページ フォーム要素 ===');
console.log('inputs:', JSON.stringify(formInfo.inputs, null, 2));
console.log('\nbuttons:', JSON.stringify(formInfo.buttons, null, 2));
console.log('\ntables:', JSON.stringify(formInfo.tables, null, 2));

await browser.close();
