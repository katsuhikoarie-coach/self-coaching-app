'use client';

import { useEffect, useState, useRef } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { formatPrice, getPrice } from '@/lib/products';

export default function ConfirmPage() {
  const { user, isLoading } = useAuth();
  const { items, subtotal, totalTax, grandTotal } = useCart();
  const [note, setNote]           = useState('');
  const [agreed, setAgreed]       = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState('');
  const errorRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (!isLoading && !user) window.location.href = '/';
  }, [user, isLoading]);

  useEffect(() => {
    if (!isLoading && user && items.length === 0) window.location.href = '/products/';
  }, [items, isLoading, user]);

  useEffect(() => {
    if (submitError) errorRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, [submitError]);

  if (isLoading || !user || items.length === 0) return null;

  const tax8sub  = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.08 ? s + getPrice(p, user.centerType) * quantity : s, 0);
  const tax10sub = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.10 ? s + getPrice(p, user.centerType) * quantity : s, 0);
  const tax8amt  = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.08 ? s + Math.floor(getPrice(p, user.centerType) * quantity * 0.08) : s, 0);
  const tax10amt = items.reduce((s, { product: p, quantity }) => p.taxRate === 0.10 ? s + Math.floor(getPrice(p, user.centerType) * quantity * 0.10) : s, 0);
  const hasMixed     = tax8sub > 0 && tax10sub > 0;
  const freeShipping = subtotal >= 100000;
  const remaining    = 100000 - subtotal;

  const handleSubmit = async () => {
    if (!agreed) return;
    setSubmitting(true);
    setSubmitError('');

    // PHP API に送るペイロード
    const apiPayload = {
      items: items.map((i) => ({
        code:  i.product.code,
        name:  i.product.name,
        price: getPrice(i.product, user.centerType),
        qty:   i.quantity,
        tax:   i.product.taxRate,
      })),
      note,
      subtotal,
      tax_total: totalTax,
      total:     grandTotal,
    };

    try {
      const res = await fetch('/api/orders.php', {
        method:      'POST',
        headers:     { 'Content-Type': 'application/json' },
        credentials: 'include',
        body:        JSON.stringify(apiPayload),
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        throw new Error(data.error || `注文の送信に失敗しました (${res.status})`);
      }

      const orderRecord = {
        id:        data.id,
        date:      new Date().toLocaleDateString('ja-JP'),
        total:     grandTotal,
        itemCount: items.length,
        items:     apiPayload.items,
        note,
        fcName:    user.fcName,
      };
      try {
        localStorage.setItem('yamano_last_order', JSON.stringify(orderRecord));
      } catch { /* ignore */ }

      window.location.href = '/complete/';
    } catch (err) {
      setSubmitError((err as Error).message || '注文の送信に失敗しました。しばらく後に再試行してください。');
      setSubmitting(false);
    }
  };

  return (
    <main className="min-h-screen bg-stone-50 pb-36">
      {/* ヘッダー */}
      <header className="bg-white border-b border-stone-200 sticky top-0 z-10">
        <div className="max-w-lg mx-auto px-4 py-3 flex items-center gap-3">
          <Link href="/cart" className="text-stone-400 hover:text-stone-600 p-1">
            <ChevronLeft />
          </Link>
          <h1 className="font-semibold text-stone-800">注文確認</h1>
        </div>
      </header>

      <div className="max-w-lg mx-auto px-4 py-4 space-y-4">

        {/* FC情報 */}
        <section className="bg-white rounded-2xl border border-stone-200 p-4">
          <h2 className="text-xs font-semibold text-stone-500 mb-3 tracking-wide">注文者情報</h2>
          <div className="space-y-1.5">
            <Row label="FC名"  value={user.fcName} />
            <Row label="担当者" value={`${user.name}様`} />
            <Row label="メール" value={user.email} />
            {user.address && <Row label="住所" value={user.address} />}
          </div>
        </section>

        {/* 注文明細 */}
        <section className="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div className="px-4 py-3 bg-stone-50 border-b border-stone-100">
            <h2 className="text-xs font-semibold text-stone-500 tracking-wide">注文明細</h2>
          </div>
          <div className="divide-y divide-stone-100">
            {items.map(({ product: p, quantity }) => (
              <div key={p.code} className="px-4 py-3 flex items-center justify-between gap-3">
                <div className="flex-1 min-w-0">
                  <p className="text-xs text-stone-400 font-mono">{p.code}</p>
                  <p className="text-sm text-stone-800 leading-snug mt-0.5 line-clamp-2">{p.name}</p>
                </div>
                <div className="text-right shrink-0">
                  <p className="text-xs text-stone-400">× {quantity}</p>
                  <p className="text-sm font-semibold text-stone-800">
                    ¥{formatPrice(getPrice(p, user.centerType) * quantity)}
                  </p>
                </div>
              </div>
            ))}
          </div>
          <div className="px-4 py-3 bg-stone-50 border-t border-stone-100 space-y-1.5">
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
            <div className="flex justify-between font-bold text-stone-800 text-base pt-1 border-t border-stone-200">
              <span>合計（税込）</span>
              <span className="text-amber-700">¥{formatPrice(grandTotal)}</span>
            </div>
            {/* 送料案内 */}
            <div className="pt-0.5">
              {freeShipping ? (
                <div className="flex items-center gap-1.5 text-sm text-green-600 font-medium">
                  <span>🚚</span><span>送料無料</span>
                </div>
              ) : (
                <p className="text-xs text-stone-400">
                  あと <span className="font-semibold text-amber-600">¥{formatPrice(remaining)}</span> で送料無料（税抜10万円以上）
                </p>
              )}
            </div>
          </div>
        </section>

        {/* 備考 */}
        <section className="bg-white rounded-2xl border border-stone-200 p-4">
          <label className="block text-xs font-semibold text-stone-500 mb-2 tracking-wide">
            備考（任意）
          </label>
          <textarea
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="配送希望日、特記事項など"
            rows={3}
            className="w-full border border-stone-200 rounded-xl px-3 py-2.5 text-sm text-stone-800 placeholder-stone-300 resize-none focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
        </section>

        {/* エラーメッセージ */}
        {submitError && (
          <div ref={errorRef} className="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
            <p className="text-sm text-red-700 leading-relaxed">{submitError}</p>
          </div>
        )}

        {/* 確認チェック */}
        <label className="flex items-start gap-3 bg-white rounded-2xl border border-stone-200 p-4 cursor-pointer">
          <input
            type="checkbox"
            checked={agreed}
            onChange={(e) => setAgreed(e.target.checked)}
            className="mt-0.5 w-5 h-5 accent-amber-600 cursor-pointer"
          />
          <span className="text-sm text-stone-700 leading-relaxed">
            注文内容を確認しました。上記の内容で注文を確定します。
          </span>
        </label>
      </div>

      {/* 固定フッター */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-stone-200 p-4 z-20">
        <div className="max-w-lg mx-auto flex gap-3">
          <Link
            href="/cart"
            className="flex-1 border border-stone-200 text-stone-600 rounded-xl py-3 font-medium text-center hover:bg-stone-50 transition-colors"
          >
            戻る
          </Link>
          <button
            onClick={handleSubmit}
            disabled={!agreed || submitting}
            className={`flex-2 flex-1 rounded-xl py-3 font-semibold transition-all ${
              agreed && !submitting
                ? 'bg-amber-600 hover:bg-amber-700 text-white active:scale-95'
                : 'bg-stone-200 text-stone-400 cursor-not-allowed'
            }`}
          >
            {submitting ? '送信中…' : '注文を確定する'}
          </button>
        </div>
      </div>
    </main>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex gap-3">
      <span className="text-xs text-stone-400 w-16 shrink-0 pt-0.5">{label}</span>
      <span className="text-sm text-stone-700 flex-1">{value}</span>
    </div>
  );
}

function ChevronLeft() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M15 18l-6-6 6-6" />
    </svg>
  );
}
