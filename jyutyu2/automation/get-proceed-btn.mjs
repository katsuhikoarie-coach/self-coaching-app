// 「ご注文手続き」ボタンと配送先ページの構造を確認するスクリプト
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
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductId', '0303');
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductQuantity', '1');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
  page.click('#ctl00_ContentPlaceHolder1_lbGetAllproduct'),
]);
await page.waitForTimeout(1000);

// 「ご注文手続き」関連の要素を全て探す
const proceedBtns = await page.evaluate(() => {
  const keywords = ['ご注文手続き', '注文手続', 'お届け先', '次へ', 'proceed', 'checkout'];
  const results = [];
  document.querySelectorAll('a, button, input[type="submit"], input[type="button"]').forEach(el => {
    const text = (el.textContent?.trim() || el.value || '').replace(/\s+/g, ' ');
    const matchesKeyword = keywords.some(k => text.includes(k));
    if (matchesKeyword || el.id?.includes('Proceed') || el.id?.includes('Order') || el.id?.includes('Next')) {
      results.push({
        tag: el.tagName, id: el.id, name: el.name || '',
        text: text.slice(0, 60),
        href: el.href || '',
        type: el.type || '',
        className: el.className.slice(0, 60),
      });
    }
  });
  return results;
});

console.log('=== ご注文手続き関連ボタン ===');
console.log(JSON.stringify(proceedBtns, null, 2));

// 全inputも確認
const allInputs = await page.evaluate(() =>
  Array.from(document.querySelectorAll('input[type="submit"], input[type="button"], button'))
    .map(el => ({
      tag: el.tagName, id: el.id, name: el.name || '',
      text: (el.textContent?.trim() || el.value || '').slice(0, 40),
      type: el.type,
    }))
);
console.log('\n=== 全submit/buttonタグ ===');
console.log(JSON.stringify(allInputs, null, 2));

// scriptタグでidを含む文字列を検索
const scriptMatches = await page.evaluate(() => {
  const scripts = Array.from(document.querySelectorAll('script')).map(s => s.textContent || '');
  const combined = scripts.join('\n');
  const matches = [];
  const re = /lbProceed|lbOrder|lbNext|lbCheckout|Proceed|OrderProc/g;
  let m;
  while ((m = re.exec(combined)) !== null) {
    matches.push(combined.slice(Math.max(0, m.index - 20), m.index + 40));
  }
  return matches.slice(0, 10);
});
console.log('\n=== スクリプト内の注文関連ID ===');
scriptMatches.forEach(m => console.log(' ', m));

await browser.close();
