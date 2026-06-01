// お届け先ページ（配送先選択）の構造を確認するスクリプト
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false, slowMo: 300 });
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

// カートに商品を追加
await page.goto('https://yamano-order.shop/Form/Order/CartList.aspx', { waitUntil: 'networkidle' });
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductId', '0303');
await page.fill('#ctl00_ContentPlaceHolder1_rInputProduct_ctl00_tbProductQuantity', '1');
await Promise.all([
  page.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
  page.click('#ctl00_ContentPlaceHolder1_lbGetAllproduct'),
]);
await page.waitForTimeout(1000);
console.log('取得完了');

// ご注文手続きボタンをクリック（WebForm_DoPostBackWithOptions でバリデーション後にポストバック）
await page.click('a:has-text("ご注文手続き")');
// ポストバック完了を待つ（URLが変わるか、networkidleになるまで）
await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
await page.waitForTimeout(2000);
console.log('ご注文手続き後 URL:', page.url());
await page.waitForTimeout(1000);

// フルページSS
await page.screenshot({ path: 'automation/shipping-page.png', fullPage: true });
console.log('SS保存: automation/shipping-page.png');

// ページ内の選択肢（配送先リスト）を取得
const shippingOptions = await page.evaluate(() => {
  // ラジオボタン
  const radios = Array.from(document.querySelectorAll('input[type="radio"]')).map(el => {
    const label = document.querySelector(`label[for="${el.id}"]`);
    return {
      type: 'radio', id: el.id, name: el.name, value: el.value,
      labelText: label?.textContent?.trim().slice(0, 60) || '',
    };
  });
  // セレクトボックス
  const selects = Array.from(document.querySelectorAll('select')).map(sel => ({
    type: 'select', id: sel.id, name: sel.name,
    options: Array.from(sel.options).map(o => ({ value: o.value, text: o.text.trim().slice(0, 40) })),
  }));
  // テキスト中に「センター」「西明石」「藍魚住」「学園東町」「配送先」「お届け先」を含む要素
  const keywords = ['センター', '西明石', '藍魚住', '学園東町', '配送先', 'お届け先', '配達先'];
  const textElements = [];
  document.querySelectorAll('td, th, label, p, div, span').forEach(el => {
    const t = el.textContent?.trim().replace(/\s+/g, ' ');
    if (t && keywords.some(k => t.includes(k))) {
      textElements.push({ tag: el.tagName, id: el.id, text: t.slice(0, 80) });
    }
  });
  return { radios, selects, textElements: textElements.slice(0, 20) };
});

console.log('\n=== ラジオボタン（配送先候補） ===');
console.log(JSON.stringify(shippingOptions.radios, null, 2));
console.log('\n=== セレクトボックス ===');
console.log(JSON.stringify(shippingOptions.selects, null, 2));
console.log('\n=== 配送先関連テキスト要素 ===');
shippingOptions.textElements.forEach(t => console.log(`  <${t.tag} id="${t.id}"> ${t.text}`));

// 「次へ」「確認へ」ボタンを探す
const nextBtns = await page.evaluate(() =>
  Array.from(document.querySelectorAll('a, button, input[type="submit"]'))
    .map(el => ({
      tag: el.tagName, id: el.id,
      text: (el.textContent?.trim() || el.value || '').replace(/\s+/g, ' ').slice(0, 50),
      href: (el.href || '').slice(0, 80),
    }))
    .filter(b => b.text && (
      b.text.includes('次へ') || b.text.includes('確認') || b.text.includes('注文') ||
      b.id?.includes('Next') || b.id?.includes('Confirm') || b.id?.includes('Order')
    ))
);
console.log('\n=== 次ページへのボタン ===');
console.log(JSON.stringify(nextBtns, null, 2));

await browser.close();
