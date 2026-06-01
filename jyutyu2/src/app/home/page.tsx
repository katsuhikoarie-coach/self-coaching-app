'use client';

import { useEffect, useState, useRef } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { formatPrice, fetchProducts } from '@/lib/products';

interface OrderItem {
  code: string;
  name: string;
  qty: number;
  price: number;
}

interface OrderHistory {
  id: string;
  date: string;
  total: number;
  itemCount: number;
  fcName: string;
  contactName?: string;
  items: OrderItem[];
  note?: string;
  status?: string;
}

type YamanoStatus = 'idle' | 'running' | 'done' | 'error';

interface YamanoModal {
  order: OrderHistory;
  status: YamanoStatus;
  messages: string[];
}

const AUTOMATE_URL = 'http://localhost:3099';

/** id の重複を除去（先に出現したものを優先） */
function dedup(arr: OrderHistory[]): OrderHistory[] {
  const seen = new Set<string>();
  return arr.filter((o) => {
    if (seen.has(o.id)) return false;
    seen.add(o.id);
    return true;
  });
}

export default function HomePage() {
  const { user, isLoading, logout } = useAuth();
  const { clearCart, addItem } = useCart();
  const [orders, setOrders]               = useState<OrderHistory[]>([]);
  const [ordersLoading, setOrdersLoading] = useState(false);
  const [csvLoading, setCsvLoading]       = useState(false);
  const [expandedId, setExpandedId]       = useState<string | null>(null);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [yamanoModal, setYamanoModal]     = useState<YamanoModal | null>(null);
  const [cancellingId, setCancellingId]   = useState<string | null>(null);
  const [reorderingId, setReorderingId]   = useState<string | null>(null);
  const abortRef   = useRef<AbortController | null>(null);
  const historyRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!isLoading && !user) window.location.href = '/';
  }, [user, isLoading]);

  useEffect(() => {
    if (!user) return;
    let cancelled = false;

    const loadOrders = async () => {
      setOrdersLoading(true);
      try {
        const res = await fetch('/api/orders.php', { credentials: 'include' });
        if (res.ok && !cancelled) {
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          const data: any[] = await res.json();
          setOrders(dedup(data.map((o) => ({
            id:          o.id,
            date:        o.order_date ?? o.date ?? '',
            total:       Number(o.total),
            itemCount:   Array.isArray(o.items) ? o.items.length : (o.itemCount ?? 0),
            fcName:      o.fc_name ?? o.fcName ?? '',
            contactName: o.contact_name ?? o.contactName ?? '',
            items:     (o.items ?? []).map((it: { code: string; name: string; qty: number; price: number }) => ({
              code:  it.code,
              name:  it.name,
              qty:   it.qty,
              price: Number(it.price),
            })),
            note:   o.note ?? '',
            status: o.status ?? 'received',
          }))));
        }
      } catch { /* ignore */ }
      if (!cancelled) setOrdersLoading(false);
    };

    loadOrders();
    return () => { cancelled = true; };
  }, [user]);

  if (isLoading || !user) return null;

  const handleLogout = async () => {
    clearCart();
    await logout();
    window.location.href = '/';
  };

  const scrollToHistory = () => {
    historyRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const toggleExpand = (id: string) => {
    setExpandedId((prev) => (prev === id ? null : id));
  };

  const handleYamanoOrder = async (order: OrderHistory) => {
    if (!order.items || order.items.length === 0) {
      alert('この注文には商品データが含まれていません。');
      return;
    }
    abortRef.current = new AbortController();
    setYamanoModal({ order, status: 'running', messages: ['自動入力を開始します...'] });

    const addMessage = (msg: string, status?: YamanoStatus) => {
      setYamanoModal((prev) =>
        prev ? { ...prev, messages: [...prev.messages, msg], ...(status ? { status } : {}) } : null
      );
    };

    try {
      const res = await fetch(`${AUTOMATE_URL}/run`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        signal: abortRef.current.signal,
        body: JSON.stringify({
          orderId: order.id,
          fcName:  order.fcName,
          items:   order.items.map((i) => ({ code: i.code, qty: i.qty })),
        }),
      });
      if (!res.ok || !res.body) throw new Error(`サーバーエラー (${res.status})`);

      const reader  = res.body.getReader();
      const decoder = new TextDecoder();
      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        for (const line of decoder.decode(value).split('\n')) {
          if (!line.startsWith('data: ')) continue;
          try {
            const data = JSON.parse(line.slice(6)) as { type: string; message: string };
            if (data.message) {
              if (data.type === 'done')       addMessage(data.message, 'done');
              else if (data.type === 'error') addMessage(data.message, 'error');
              else                            addMessage(data.message);
            }
          } catch { /* ignore */ }
        }
      }
    } catch (err) {
      if ((err as Error).name === 'AbortError') return;
      const isNet = err instanceof TypeError && err.message.includes('fetch');
      addMessage(
        isNet
          ? '自動化サーバーが起動していません。\n「npm run automate」を実行してから再試行してください。'
          : `エラー: ${(err as Error).message}`,
        'error'
      );
    }
  };

  const closeModal = () => {
    abortRef.current?.abort();
    setYamanoModal(null);
  };

  const handleCancel = async (order: OrderHistory) => {
    if (!window.confirm('この注文をキャンセルしますか？')) return;
    setCancellingId(order.id);
    try {
      const res = await fetch('/api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'cancel', id: order.id }),
      });
      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error ?? `エラー (${res.status})`);
      }
      setOrders((prev) =>
        prev.map((o) => (o.id === order.id ? { ...o, status: 'cancelled' } : o))
      );
    } catch (err) {
      alert(`キャンセルに失敗しました: ${(err as Error).message}`);
    } finally {
      setCancellingId(null);
    }
  };

  const handleReorder = async (order: OrderHistory) => {
    if (!order.items || order.items.length === 0) {
      alert('この注文には商品データが含まれていません。');
      return;
    }
    setReorderingId(order.id);
    try {
      const prods = await fetchProducts(user.centerType);
      const productMap = new Map(prods.map((p) => [p.code, p]));
      for (const item of order.items) {
        if (item.code === 'S001') continue;
        const product = productMap.get(item.code);
        if (product) addItem(product, item.qty);
      }
      window.location.href = '/cart/';
    } catch (err) {
      alert(`再注文の準備に失敗しました: ${(err as Error).message}`);
    } finally {
      setReorderingId(null);
    }
  };

  const downloadCsv = async () => {
    if (orders.length === 0 || csvLoading) return;
    setCsvLoading(true);
    try {
      const headers = [
        '受注番号', '注文日', 'FC名', '担当者名',
        '商品コード', '商品名', '数量', '単価（税抜）', '小計（税抜）',
        '合計（税込）', '備考',
      ];
      const rows: string[][] = [headers];

      orders.forEach((o) => {
        const base = [o.id, o.date, o.fcName, o.contactName ?? ''];
        if (!o.items || o.items.length === 0) {
          rows.push([...base, '', '', '', '', '', String(o.total), o.note ?? '']);
        } else {
          o.items.forEach((item) => {
            rows.push([
              ...base,
              item.code,
              item.name,
              String(item.qty),
              String(item.price),
              String(item.price * item.qty),
              String(o.total),
              o.note ?? '',
            ]);
          });
        }
      });

      const csvStr = rows
        .map((row) =>
          row
            .map((v) => {
              const s = String(v ?? '');
              return s.includes(',') || s.includes('"') || s.includes('\n') || s.includes('\r')
                ? '"' + s.replace(/"/g, '""') + '"'
                : s;
            })
            .join(',')
        )
        .join('\r\n');

      // Shift-JIS 変換（Excel対応）
      const Encoding = (await import('encoding-japanese')).default;
      const sjis = Encoding.convert(Encoding.stringToCode(csvStr), { to: 'SJIS', from: 'UNICODE' });
      const blob = new Blob([new Uint8Array(sjis)], { type: 'text/csv; charset=shift-jis' });

      const today = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `orders_${today}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } finally {
      setCsvLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-stone-50">
      {/* ヘッダー */}
      <header className="bg-white border-b border-stone-200 sticky top-0 z-10">
        <div className="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-lg">🌿</span>
            <span className="font-bold text-stone-800 tracking-wide text-sm">朝霧ヤマノ</span>
          </div>
          <button
            onClick={() => setShowLogoutConfirm(true)}
            className="text-xs text-stone-400 hover:text-stone-600 transition-colors px-2 py-1"
          >
            ログアウト
          </button>
        </div>
      </header>

      <div className="max-w-lg mx-auto px-4 py-6 space-y-6">

        {/* ウェルカム */}
        <section className="bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-5 border border-amber-200">
          <p className="text-xs text-amber-700 mb-0.5">ようこそ</p>
          <h1 className="text-xl font-bold text-stone-800">{user.name}様</h1>
          <p className="text-sm text-stone-600 mt-0.5">{user.fcName}</p>
        </section>

        {/* メインアクション */}
        <section className="grid grid-cols-2 gap-3">
          <Link
            href="/products"
            className="bg-white rounded-2xl border border-stone-200 p-5 flex flex-col items-center gap-3 hover:border-amber-400 hover:shadow-sm active:bg-stone-50 transition-all"
          >
            <div className="w-12 h-12 bg-amber-50 rounded-full flex items-center justify-center text-2xl">📦</div>
            <span className="font-medium text-stone-800 text-sm">新規注文</span>
          </Link>
          <button
            onClick={scrollToHistory}
            className="bg-white rounded-2xl border border-stone-200 p-5 flex flex-col items-center gap-3 hover:border-amber-400 hover:shadow-sm active:bg-stone-50 transition-all cursor-pointer w-full"
          >
            <div className="w-12 h-12 bg-stone-50 rounded-full flex items-center justify-center text-2xl">📋</div>
            <span className="font-medium text-stone-800 text-sm">注文履歴</span>
          </button>
        </section>

        {/* 管理画面リンク（朝霧ヤマノのみ） */}
        {user.fcName === '朝霧ヤマノ' && (
          <Link
            href="/admin/"
            className="flex items-center justify-between bg-stone-800 hover:bg-stone-700 active:bg-stone-900 text-white rounded-2xl px-5 py-4 transition-colors"
          >
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 bg-stone-700 rounded-full flex items-center justify-center text-lg">⚙️</div>
              <div>
                <p className="font-semibold text-sm">管理画面</p>
                <p className="text-xs text-stone-400 mt-0.5">注文管理・センター管理</p>
              </div>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-stone-400">
              <path d="M9 18l6-6-6-6"/>
            </svg>
          </Link>
        )}

        {/* 注文履歴 */}
        <section ref={historyRef}>
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-sm font-semibold text-stone-500 tracking-wide">最近の注文</h2>
            {orders.length > 0 && (
              <button
                onClick={downloadCsv}
                disabled={csvLoading}
                className="flex items-center gap-1.5 border border-stone-200 rounded-lg px-3 py-1.5 text-xs text-stone-600 hover:bg-stone-50 active:bg-stone-100 transition-colors disabled:opacity-50"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                {csvLoading ? '生成中...' : 'CSV'}
              </button>
            )}
          </div>

          {ordersLoading ? (
            <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
              <p className="text-stone-400 text-sm">読み込み中...</p>
            </div>
          ) : orders.length === 0 ? (
            <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
              <p className="text-stone-400 text-sm">まだ注文履歴がありません</p>
              <Link href="/products" className="inline-block mt-3 text-amber-600 text-sm font-medium hover:text-amber-700">
                最初の注文をする →
              </Link>
            </div>
          ) : (
            <div className="space-y-2">
              {orders.slice(0, 20).map((o) => {
                const isOpen = expandedId === o.id;
                return (
                  <div key={o.id} className="bg-white rounded-xl border border-stone-200 overflow-hidden">

                    {/* ── 注文カードヘッダー（タップで展開） ── */}
                    <button
                      onClick={() => toggleExpand(o.id)}
                      className="w-full px-4 py-3 flex items-center justify-between gap-3 text-left active:bg-stone-50 transition-colors"
                    >
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-xs text-stone-400 font-mono">{o.id}</p>
                          {o.fcName && (
                            <span className="text-xs bg-stone-100 text-stone-500 px-1.5 py-0.5 rounded">
                              {o.fcName}
                            </span>
                          )}
                          {o.status === 'cancelled' && (
                            <span className="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">
                              キャンセル済
                            </span>
                          )}
                        </div>
                        <p className="text-xs text-stone-400 mt-0.5">{o.date}　{o.itemCount}品目</p>
                      </div>
                      <div className="flex items-center gap-2 shrink-0">
                        <div className="text-right">
                          <p className="font-semibold text-stone-800 text-sm">¥{formatPrice(o.total)}</p>
                          <p className="text-xs text-stone-400">税込</p>
                        </div>
                        {/* 開閉矢印 */}
                        <svg
                          width="16" height="16" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"
                          className={`text-stone-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                        >
                          <path d="M6 9l6 6 6-6" />
                        </svg>
                      </div>
                    </button>

                    {/* ── 展開：注文明細 ── */}
                    {isOpen && (
                      <div className="border-t border-stone-100">
                        {/* 商品明細リスト */}
                        {o.items && o.items.length > 0 ? (
                          <div className="divide-y divide-stone-100">
                            {o.items.map((item, idx) => (
                              <div key={idx} className="px-4 py-2.5 flex items-start justify-between gap-3">
                                <div className="flex-1 min-w-0">
                                  <p className="text-xs text-stone-400 font-mono">{item.code}</p>
                                  <p className="text-sm text-stone-700 leading-snug mt-0.5">{item.name}</p>
                                </div>
                                <div className="text-right shrink-0 text-sm">
                                  <p className="text-stone-500">× {item.qty}</p>
                                  <p className="font-medium text-stone-800">
                                    ¥{formatPrice(item.price * item.qty)}
                                  </p>
                                </div>
                              </div>
                            ))}
                          </div>
                        ) : (
                          <p className="px-4 py-3 text-xs text-stone-400">明細データがありません</p>
                        )}

                        {/* 備考 */}
                        {o.note && (
                          <div className="px-4 py-2 bg-stone-50 border-t border-stone-100">
                            <p className="text-xs text-stone-400">備考: {o.note}</p>
                          </div>
                        )}

                        {/* キャンセルボタン（朝霧ヤマノ以外 & received のみ） */}
                        {user.fcName !== '朝霧ヤマノ' && o.status === 'received' && (
                          <div className="px-4 py-3 bg-stone-50 border-t border-stone-100">
                            <button
                              onClick={() => handleCancel(o)}
                              disabled={cancellingId === o.id}
                              className="w-full bg-white border border-red-300 text-red-600 hover:bg-red-50 active:bg-red-100 rounded-lg py-2 text-sm font-semibold transition-colors disabled:opacity-50"
                            >
                              {cancellingId === o.id ? 'キャンセル中...' : 'この注文をキャンセル'}
                            </button>
                          </div>
                        )}

                        {/* ヤマノ発注ボタン（朝霧ヤマノのみ表示） */}
                        {user.fcName === '朝霧ヤマノ' && (
                          <div className="px-4 py-3 bg-stone-50 border-t border-stone-100">
                            <button
                              onClick={() => handleYamanoOrder(o)}
                              className="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg py-2 text-sm font-semibold transition-colors"
                            >
                              ヤマノ発注
                            </button>
                          </div>
                        )}

                        {/* 再注文ボタン */}
                        {o.items && o.items.length > 0 && (
                          <div className="px-4 py-3 bg-stone-50 border-t border-stone-100">
                            <button
                              onClick={() => handleReorder(o)}
                              disabled={reorderingId === o.id}
                              className="w-full bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg py-2 text-sm font-semibold transition-colors disabled:opacity-50"
                            >
                              {reorderingId === o.id ? '処理中...' : '再注文する'}
                            </button>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          )}
        </section>

        {/* お知らせ */}
        <section className="bg-white rounded-2xl border border-stone-200 p-4">
          <h2 className="text-xs font-semibold text-stone-500 mb-2 tracking-wide">お知らせ</h2>
          <p className="text-sm text-stone-600">
            センター受注システムへようこそ。ご不明点は担当者までお問い合わせください。
          </p>
        </section>
      </div>

      {/* ログアウト確認 */}
      {showLogoutConfirm && (
        <div
          className="fixed inset-0 bg-black/40 flex items-end justify-center p-4 z-50"
          onClick={() => setShowLogoutConfirm(false)}
        >
          <div
            className="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl mb-4"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="font-bold text-stone-800 text-center mb-1">ログアウト</h3>
            <p className="text-sm text-stone-500 text-center">よろしいですか？</p>
            <div className="flex gap-3 mt-5">
              <button
                onClick={() => setShowLogoutConfirm(false)}
                className="flex-1 border border-stone-200 text-stone-600 rounded-xl py-2.5 font-medium hover:bg-stone-50"
              >
                キャンセル
              </button>
              <button
                onClick={handleLogout}
                className="flex-1 bg-stone-800 text-white rounded-xl py-2.5 font-medium hover:bg-stone-700"
              >
                ログアウト
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ヤマノ発注 進捗モーダル */}
      {yamanoModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl w-full max-w-sm shadow-xl overflow-hidden">
            <div className="bg-blue-600 px-5 py-4">
              <p className="text-white font-bold text-base">ヤマノ発注 自動入力</p>
              <p className="text-blue-200 text-xs mt-0.5">
                受注番号: {yamanoModal.order.id}　/ {yamanoModal.order.fcName}
              </p>
            </div>
            <div className="px-5 py-4 max-h-64 overflow-y-auto space-y-1.5">
              {yamanoModal.messages.map((msg, i) => (
                <p
                  key={i}
                  className={`text-sm leading-snug whitespace-pre-wrap ${
                    msg.startsWith('✅')                                       ? 'text-green-700 font-medium'
                    : msg.startsWith('エラー') || msg.includes('見つかりません') ? 'text-red-600'
                    : 'text-stone-700'
                  }`}
                >
                  {msg}
                </p>
              ))}
              {yamanoModal.status === 'running' && (
                <p className="text-blue-500 text-sm animate-pulse">処理中...</p>
              )}
            </div>
            <div className="px-5 py-4 border-t border-stone-100">
              {yamanoModal.status === 'running' ? (
                <p className="text-xs text-stone-400 text-center">
                  Chromeが自動で操作されています。しばらくお待ちください。
                </p>
              ) : (
                <button
                  onClick={closeModal}
                  className="w-full bg-stone-800 hover:bg-stone-700 text-white rounded-xl py-2.5 font-semibold text-sm"
                >
                  閉じる
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </main>
  );
}
