// ヤマノ発注 自動化サーバー
// FCアプリの「ヤマノ発注」ボタンからのリクエストを受けてPlaywrightを起動する
// 起動方法: npm run automate
import { createServer } from 'http';
import { runYamanoOrder } from './yamano-order.mjs';

const PORT            = 3099;
const API_BASE        = 'https://fc-order.asagiriyamano.jp';
const DISPATCH_SECRET = 'YAMANO_DISPATCH_2026'; // api/dispatch-notify.php と一致

/**
 * 発注完了後にFCへ通知メールを送信する
 * エラーが起きても自動化の結果には影響しない
 */
async function notifyDispatch(orderId, send) {
  try {
    const r = await fetch(`${API_BASE}/api/dispatch-notify.php`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ orderId, secret: DISPATCH_SECRET }),
    });
    const data = await r.json().catch(() => ({}));
    if (r.ok) {
      send('progress', `📧 発注完了メールを送信しました（宛先: ${data.to ?? ''}）`);
    } else {
      send('progress', `⚠️ 発注完了メールの送信に失敗しました: ${data.error ?? r.status}`);
    }
  } catch (err) {
    send('progress', `⚠️ 発注完了メール送信エラー: ${err.message}`);
  }
}

const server = createServer(async (req, res) => {
  // CORS: FCアプリ（localhost:3000またはデプロイ先）からのアクセスを許可
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  // ヘルスチェック（ボタンが押せるか確認用）
  if (req.method === 'GET' && req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, version: '1.0' }));
    return;
  }

  // ヤマノ発注実行
  if (req.method === 'POST' && req.url === '/run') {
    // リクエストボディを読み込む
    const chunks = [];
    for await (const chunk of req) chunks.push(chunk);
    let orderData;
    try {
      orderData = JSON.parse(Buffer.concat(chunks).toString());
    } catch {
      res.writeHead(400, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: 'リクエストボディのJSON解析に失敗しました' }));
      return;
    }

    // SSE（Server-Sent Events）で進捗を送信
    res.writeHead(200, {
      'Content-Type': 'text/event-stream',
      'Cache-Control': 'no-cache',
      'Connection': 'keep-alive',
    });

    let dispatchSucceeded = false;
    const send = (type, message) => {
      if (!res.writableEnded) {
        res.write(`data: ${JSON.stringify({ type, message })}\n\n`);
      }
      // コンソールにも出力（デバッグ用）
      console.log(`[${type}] ${message}`);
      if (type === 'done') dispatchSucceeded = true;
    };

    try {
      await runYamanoOrder(orderData, send);
      // 自動入力が完了したらFCへ発注完了メールを送信
      if (dispatchSucceeded) {
        await notifyDispatch(orderData.orderId, send);
      }
    } catch (err) {
      send('error', `エラーが発生しました: ${err.message || String(err)}`);
    } finally {
      if (!res.writableEnded) res.end();
    }
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ error: 'Not Found' }));
});

server.listen(PORT, '127.0.0.1', () => {
  console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('  🤖 ヤマノ発注 自動化サーバー 起動中');
  console.log(`  http://localhost:${PORT}`);
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('FCアプリで「ヤマノ発注」ボタンを押すと');
  console.log('Chromeが自動で起動して入力が始まります。');
  console.log('終了するには Ctrl+C を押してください。\n');
});

server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(`エラー: ポート ${PORT} はすでに使用中です。`);
    console.error('別のターミナルで既に自動化サーバーが起動しているかもしれません。');
  } else {
    console.error('サーバーエラー:', err);
  }
  process.exit(1);
});
