import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
await page.setViewportSize({ width: 390, height: 844 }); // iPhone サイズで確認

// デモユーザーをlocalStorageにセット
await page.goto('http://localhost:3000');
await page.evaluate(() => {
  localStorage.setItem('yamano_auth_user', JSON.stringify({
    id: 'fc1', name: '白神', email: 'shiragami@example.com',
    fcName: '西明石白神FC', address: '兵庫県明石市', isDemo: true,
  }));
});

// 商品ページへ（trailingSlash対応）
await page.goto('http://localhost:3000/products/', { waitUntil: 'load' });
await page.waitForTimeout(1000);

// 「0303」で検索
await page.fill('input[type="search"]', '0303');
await page.waitForTimeout(600);
await page.screenshot({ path: 'automation/search-0303.png', fullPage: false });

// 件数とヒット商品を取得
const resultCount = await page.locator('p.text-xs.text-stone-400').first().textContent();
const items = await page.locator('.space-y-2 > div').allTextContents();
console.log('検索結果:', resultCount?.trim());
items.slice(0, 5).forEach((t, i) => {
  const lines = t.trim().split('\n').map(l => l.trim()).filter(Boolean);
  console.log(`  [${i+1}]`, lines.slice(0, 3).join(' / '));
});

await browser.close();
