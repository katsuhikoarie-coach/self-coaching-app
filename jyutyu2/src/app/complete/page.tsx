'use client';

import { useEffect, useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import { formatPrice } from '@/lib/products';

interface LastOrder {
  id: string;
  date: string;
  total: number;
  itemCount: number;
  items: { code: string; name: string; qty: number; price: number }[];
  note: string;
  fcName: string;
}

export default function CompletePage() {
  const { user, isLoading } = useAuth();
  const [order, setOrder] = useState<LastOrder | null>(null);
  const [notFound, setNotFound] = useState(false);

  // 認証チェック
  useEffect(() => {
    if (!isLoading && !user) window.location.href = '/';
  }, [user, isLoading]);

  // 注文データ読み込み＋カートクリア（マウント時1回のみ）
  useEffect(() => {
    // React Context に頼らず直接 localStorage を操作してカートを確実に削除する
    localStorage.removeItem('yamano_cart');
    try {
      const saved = localStorage.getItem('yamano_last_order');
      if (saved) {
        setOrder(JSON.parse(saved) as LastOrder);
      } else {
        setNotFound(true);
      }
    } catch {
      setNotFound(true);
    }
  }, []);

  if (isLoading || !user) return null;

  // 注文データが見つからない場合
  if (notFound && !order) {
    return (
      <main className="min-h-screen bg-stone-50 flex items-center justify-center p-4">
        <div className="text-center space-y-4">
          <p className="text-stone-400">注文情報が見つかりません</p>
          <div className="flex flex-col gap-3 max-w-xs mx-auto">
            <a
              href="/home/"
              className="block w-full bg-amber-600 text-white rounded-xl py-3 font-semibold text-center hover:bg-amber-700 transition-colors"
            >
              ホームへ戻る
            </a>
          </div>
        </div>
      </main>
    );
  }

  if (!order) return null;

  return (
    <main className="min-h-screen bg-stone-50 pb-10">
      <div className="max-w-lg mx-auto px-4 pt-10 pb-10 space-y-6">

        {/* 完了メッセージ */}
        <div className="text-center">
          <div className="w-20 h-20 mx-auto bg-green-50 border-2 border-green-300 rounded-full flex items-center justify-center mb-4">
            <span className="text-4xl text-green-600">✓</span>
          </div>
          <h1 className="text-2xl font-bold text-stone-800">注文が完了しました</h1>
          <p className="text-stone-500 text-sm mt-1">ご注文ありがとうございます</p>
        </div>

        {/* 受注番号（大きく強調表示） */}
        <div className="bg-white rounded-2xl border-2 border-amber-300 p-6 text-center shadow-sm">
          <p className="text-xs font-semibold text-amber-700 tracking-widest mb-2">受注番号</p>
          <p className="text-3xl font-bold text-stone-900 font-mono tracking-wider">{order.id}</p>
          <p className="text-sm text-stone-400 mt-2">{order.date} ／ {order.fcName}</p>
          <p className="text-lg font-semibold text-amber-700 mt-3">
            合計 ¥{formatPrice(order.total)}<span className="text-xs font-normal text-stone-400 ml-1">（税込）</span>
          </p>
        </div>

        {/* 注文明細 */}
        <div className="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div className="px-4 py-3 bg-stone-50 border-b border-stone-100">
            <h2 className="text-xs font-semibold text-stone-500 tracking-wide">注文内容</h2>
          </div>
          <div className="divide-y divide-stone-100">
            {order.items.map((item) => (
              <div key={item.code} className="px-4 py-3 flex justify-between items-center gap-3">
                <div className="flex-1 min-w-0">
                  <p className="text-xs text-stone-400 font-mono">{item.code}</p>
                  <p className="text-sm text-stone-700 leading-snug mt-0.5 line-clamp-2">{item.name}</p>
                </div>
                <div className="text-right shrink-0">
                  <p className="text-xs text-stone-400">× {item.qty}</p>
                  <p className="text-sm font-semibold text-stone-800">¥{formatPrice(item.price * item.qty)}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="px-4 py-3 border-t border-stone-200 bg-stone-50 flex justify-between font-bold text-stone-800">
            <span>合計（税込）</span>
            <span className="text-amber-700">¥{formatPrice(order.total)}</span>
          </div>
        </div>

        {order.note && (
          <div className="bg-white rounded-2xl border border-stone-200 p-4">
            <p className="text-xs font-semibold text-stone-400 mb-1">備考</p>
            <p className="text-sm text-stone-700">{order.note}</p>
          </div>
        )}

        {/* アクションボタン */}
        <div className="space-y-3 pt-2">
          <a
            href="/products/"
            className="flex items-center justify-center w-full bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-xl py-4 font-semibold transition-colors text-base"
          >
            新規注文
          </a>
          <a
            href="/home/"
            className="flex items-center justify-center w-full border-2 border-stone-200 text-stone-700 hover:bg-stone-50 rounded-xl py-3.5 font-semibold transition-colors text-base"
          >
            注文履歴を見る
          </a>
        </div>
      </div>
    </main>
  );
}
