// FCアプリ「ヤマノ発注」ボタン テストスクリプト
// ローカルストレージに直接テストデータをセットしてヤマノ発注の動作を確認する
// 実行: node automation/test-fc-flow.mjs
// 前提: npm run dev (port 3000) と npm run automate (port 3099) が起動済みであること
import { chromium } from 'playwright';

const FC_APP = 'http://localhost:3000';

// ─── テストデータ ───────────────────────────────────────────
const TEST_USER = {
  id: 'fc1',
  name: '白神',
  email: 'shiragami@example.com',
  fcName: '西明石白神FC',
  address: '兵庫県明石市大蔵海岸通1-1',
  isDemo: true,
};

// ヤマノ発注サイトの商品コードを使用（FCアプリとヤマノで共通である必要あり）
const TEST_ORDER = {
  id: '2026-05-16-TEST',
  date: '2026/5/16',
  total: 4950,   // ¥4,500 * 1.1 = ¥4,950（税込）
  itemCount: 1,
  items: [
    { code: '0303', name: 'ドロンコ クレー24 オリジナル WH', qty: 1, price: 4500 },
  ],
  note: 'テスト注文',
  fcName: '西明石白神FC',
};
// ────────────────────────────────────────────────────────────

async function main() {
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log(' 「ヤマノ発注」ボタン 動作テスト');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log(`  FC: ${TEST_USER.fcName}`);
  console.log(`  受注番号: ${TEST_ORDER.id}`);
  console.log(`  商品: ${TEST_ORDER.items.map(i => `${i.code}×${i.qty}`).join(', ')}`);
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

  const browser = await chromium.launch({ headless: false, slowMo: 400 });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1200, height: 800 });

  try {
    // ── Step 1: localStorage にテストデータをセット ──
    console.log('[1/3] テストデータをlocalStorageにセット中...');
    await page.goto(FC_APP);
    await page.waitForLoadState('domcontentloaded');

    await page.evaluate(({ user, order }) => {
      // ユーザー情報
      localStorage.setItem('yamano_auth_user', JSON.stringify(user));
      // 注文履歴（ヤマノ発注ボタンに表示される）
      localStorage.setItem('yamano_orders', JSON.stringify([order]));
      // カートはクリア
      localStorage.removeItem('yamano_cart');
    }, { user: TEST_USER, order: TEST_ORDER });

    console.log('   ✓ ユーザー: 西明石白神FC');
    console.log(`   ✓ 注文履歴: ${TEST_ORDER.id}（${TEST_ORDER.itemCount}品目）`);

    // ── Step 2: ホームページへ移動して注文履歴と「ヤマノ発注」ボタンを確認 ──
    console.log('\n[2/3] ホームページで「ヤマノ発注」ボタンを確認...');
    await page.goto(`${FC_APP}/home`);
    await page.waitForLoadState('networkidle');

    // 注文カードが表示されるのを待つ
    const orderCard = page.locator('.rounded-xl.border').filter({ hasText: TEST_ORDER.id }).first();
    await orderCard.waitFor({ state: 'visible', timeout: 8000 });
    console.log(`   ✓ 注文カード表示: ${TEST_ORDER.id}`);

    // ヤマノ発注ボタンの存在確認
    const yamanoBtn = page.locator('button:has-text("ヤマノ発注")').first();
    await yamanoBtn.waitFor({ state: 'visible', timeout: 5000 });
    console.log('   ✓ 「ヤマノ発注」ボタンを確認');

    // ── Step 3: ヤマノ発注ボタンをクリック → 自動化サーバーへリクエスト ──
    console.log('\n[3/3] 「ヤマノ発注」ボタンをクリック...');
    await yamanoBtn.click();
    console.log('   ✓ クリック完了 → 進捗モーダルが表示されます');

    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(' 自動化サーバー（localhost:3099）が');
    console.log(' Chromeを別ウィンドウで起動して');
    console.log(' yamano-order.shop を操作します。');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

    // 進捗モーダルのメッセージを監視
    let lastMsg = '';
    let done = false;
    for (let i = 0; i < 180 && !done; i++) {
      await page.waitForTimeout(1000);
      try {
        const msgs = await page
          .locator('.max-h-64 p')
          .allTextContents();
        const newMsgs = msgs.filter(m => m.trim() && m.trim() !== lastMsg);
        for (const msg of newMsgs) {
          console.log(`   → ${msg.trim()}`);
          lastMsg = msg.trim();
          if (msg.includes('✅')) done = true;
          if (msg.includes('自動化サーバーが起動してい')) {
            done = true;
            console.log('\n⚠️  自動化サーバー (npm run automate) が');
            console.log('   起動していないか応答していません。');
          }
        }
      } catch { /* ignore */ }
    }

    if (done) {
      console.log('\n✅ テスト完了！');
      if (lastMsg.includes('✅')) {
        console.log('yamano-order.shop の注文内容確認ページで');
        console.log('「ご注文を確定する」ボタンを押してください。');
      }
    } else {
      console.log('\n⏱ タイムアウト（3分）。ブラウザで状況を確認してください。');
    }

  } catch (err) {
    console.error(`\n❌ エラー: ${err.message}`);
    await page.screenshot({ path: 'automation/error-screenshot.png', fullPage: true }).catch(() => {});
    console.error('スクリーンショット: automation/error-screenshot.png');
    throw err;
  }
}

main().catch(() => process.exit(1));
