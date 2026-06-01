// ログイン後のナビゲーション構造を確認するスクリプト
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

console.log('ログイン後URL:', page.url());

// ナビゲーションリンク一覧
const links = await page.evaluate(() => {
  return Array.from(document.querySelectorAll('a')).map(a => ({
    text: a.textContent?.trim().replace(/\s+/g, ' ').slice(0, 40),
    href: a.href,
    id: a.id,
  })).filter(l => l.text && l.href && !l.href.includes('javascript:void'));
});

console.log('\n=== ログイン後のリンク一覧 ===');
links.forEach(l => console.log(`  [${l.text}] → ${l.href}`));

// カート/注文系のページを探す
const orderLinks = links.filter(l =>
  /カート|注文|発注|order|cart/i.test(l.text + l.href)
);
console.log('\n=== 注文/カート関連リンク ===');
orderLinks.forEach(l => console.log(`  [${l.text}] → ${l.href}`));

await browser.close();
