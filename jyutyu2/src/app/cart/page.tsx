'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { formatPrice, getPrice } from '@/lib/products';

export default function CartPage() {
  const { user, isLoading } = useAuth();
  const { items, setQty, removeItem, totalCount, subtotal, totalTax, grandTotal } = useCart();

  useEffect(() => {
    if (!isLoading && !user) window.location.href = '/';
  }, [user, isLoading]);

  if (isLoading || !user) return null;

  const tax8sub  = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.08 ? s + getPrice(p, user.centerType) * quantity : s, 0);
  const tax10sub = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.10 ? s + getPrice(p, user.centerType) * quantity : s, 0);
  const tax8amt  = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.08 ? s + Math.floor(getPrice(p, user.centerType) * quantity * 0.08) : s, 0);
  const tax10amt = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.10 ? s + Math.floor(getPrice(p, user.centerType) * quantity * 0.10) : s, 0);
  const hasMixed     = tax8sub > 0 && tax10sub > 0;
  const freeShipping = subtotal >= 100000;
  const remaining    = 100000 - subtotal;

  return (
    <main className="min-h-screen bg-stone-50 pb-36">
      {/* ヘッダー */}
      <header className="bg-white border-b border-stone-200 sticky top-0 z-10">
        <div className="max-w-lg mx-auto px-4 py-3 flex items-center gap-3">
          <Link href="/products" className="text-stone-400 hover:text-stone-600 p-1">
            <ChevronLeft />
          </Link>
          <h1 className="font-semibold text-stone-800 flex-1">カート</h1>
          {items.length > 0 && (
            <span className="text-sm text-stone-400">{totalCount}点</span>
          )}
        </div>
      </header>

      <div className="max-w-lg mx-auto px-4 py-4 space-y-4">
        {items.length === 0 ? (
          <div className="text-center py-20 text-stone-400">
            <p className="text-4xl mb-3">🛒</p>
            <p className="text-sm mb-4">カートは空です</p>
            <Link
              href="/products"
              className="inline-block bg-amber-600 text-white text-sm px-5 py-2.5 rounded-xl font-medium hover:bg-amber-700 transition-colors"
            >
              商品を選ぶ
            </Link>
          </div>
        ) : (
          <>
            {/* 商品リスト */}
            <div className="space-y-2">
              {items.map(({ product: p, quantity }) => {
                const lineTotal = getPrice(p, user.centerType) * quantity;
                return (
                  <div
                    key={p.code}
                    className="bg-white rounded-2xl border border-stone-200 p-4"
                  >
                    <div className="flex justify-between items-start gap-2">
                      <div className="flex-1 min-w-0">
                        <p className="text-xs text-stone-400 font-mono">{p.code}</p>
                        <p className="text-sm font-medium text-stone-800 leading-snug mt-0.5">{p.name}</p>
                        <p className="text-xs text-stone-400 mt-0.5">
                          ¥{formatPrice(getPrice(p, user.centerType))} × {quantity} = ¥{formatPrice(lineTotal)}
                        </p>
                      </div>
                      <button
                        onClick={() => removeItem(p.code)}
                        className="text-stone-300 hover:text-red-400 transition-colors p-1 mt-0.5"
                        aria-label="削除"
                      >
                        <TrashIcon />
                      </button>
                    </div>

                    {/* 数量コントロール */}
                    <div className="flex items-center justify-between mt-3">
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => setQty(p.code, quantity - 1)}
                          className="w-8 h-8 rounded-full bg-stone-100 text-stone-600 flex items-center justify-center font-bold hover:bg-stone-200 transition-colors"
                        >
                          −
                        </button>
                        <input
                          type="number"
                          min={1}
                          max={9999}
                          value={quantity}
                          onChange={(e) => {
                            const v = parseInt(e.target.value, 10);
                            if (!isNaN(v)) setQty(p.code, v);
                          }}
                          className="w-14 text-center border border-stone-200 rounded-lg py-1.5 text-sm font-medium text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        />
                        <button
                          onClick={() => setQty(p.code, quantity + 1)}
                          className="w-8 h-8 rounded-full bg-stone-100 text-stone-600 flex items-center justify-center font-bold hover:bg-stone-200 transition-colors"
                        >
                          ＋
                        </button>
                      </div>
                      <p className="font-bold text-amber-700">
                        ¥{formatPrice(lineTotal)}
                        <span className="text-xs font-normal text-stone-400">（税抜）</span>
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* 合計 */}
            <div className="bg-white rounded-2xl border border-stone-200 p-4 space-y-2">
              <div className="flex justify-between text-sm text-stone-600">
                <span>小計（税抜）</span>
                <span>¥{formatPrice(subtotal)}</span>
              </div>
              {hasMixed ? (
                <>
                  <div className="flex justify-between text-sm text-stone-500">
                    <span className="pl-2">消費税 10%</span>
                    <span>¥{formatPrice(tax10amt)}</span>
                  </div>
                  <div className="flex justify-between text-sm text-stone-500">
                    <span className="pl-2">消費税 8%（軽減）</span>
                    <span>¥{formatPrice(tax8amt)}</span>
                  </div>
                </>
              ) : (
                <div className="flex justify-between text-sm text-stone-600">
                  <span>消費税</span>
                  <span>¥{formatPrice(totalTax)}</span>
                </div>
              )}
              <div className="border-t border-stone-100 pt-2 flex justify-between font-bold text-stone-800">
                <span>合計（税込）</span>
                <span className="text-amber-700">¥{formatPrice(grandTotal)}</span>
              </div>
            </div>

            {/* 送料案内 */}
            {freeShipping ? (
              <div className="flex items-center justify-center gap-2 text-sm text-green-600 font-medium bg-green-50 rounded-xl py-2.5">
                <span>🚚</span><span>送料無料</span>
              </div>
            ) : (
              <p className="text-center text-xs text-stone-400 py-1">
                あと <span className="font-semibold text-amber-600">¥{formatPrice(remaining)}</span> で送料無料（税抜10万円以上）
              </p>
            )}

            {/* 商品追加リンク */}
            <Link
              href="/products"
              className="flex items-center justify-center gap-2 text-amber-600 text-sm font-medium hover:text-amber-700 transition-colors py-2"
            >
              ＋ 商品を追加する
            </Link>
          </>
        )}
      </div>

      {/* 固定フッター */}
      {items.length > 0 && (
        <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-stone-200 p-4 z-20">
          <div className="max-w-lg mx-auto space-y-2">
            <div className="flex items-end justify-between px-1">
              <div>
                <p className="text-xs text-stone-400 mb-0.5">小計（税抜）</p>
                <p className="text-lg font-bold text-stone-800">¥{formatPrice(subtotal)}</p>
                <p className="text-xs text-stone-400">税込 ¥{formatPrice(grandTotal)}</p>
              </div>
              {freeShipping ? (
                <span className="text-xs text-green-600 font-medium pb-1">🚚 送料無料</span>
              ) : (
                <span className="text-xs text-stone-400 text-right pb-1">あと ¥{formatPrice(remaining)}<br/>で送料無料</span>
              )}
            </div>
            <Link
              href="/confirm"
              className="flex items-center justify-center w-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-3.5 font-semibold transition-colors"
            >
              注文内容を確認する
            </Link>
          </div>
        </div>
      )}
    </main>
  );
}

function ChevronLeft() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M15 18l-6-6 6-6" />
    </svg>
  );
}

function TrashIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="3 6 5 6 21 6" />
      <path d="M19 6l-1 14H6L5 6" />
      <path d="M10 11v6M14 11v6" />
      <path d="M9 6V4h6v2" />
    </svg>
  );
}
