'use client';

import { useState, useEffect, useMemo } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { fetchProducts, CATEGORIES, formatPrice, getPrice, type Category, type Product } from '@/lib/products';

export default function ProductsPage() {
  const { user, isLoading } = useAuth();
  const { addItem, totalCount, items } = useCart();
  const [query, setQuery] = useState('');
  const [activeCategory, setActiveCategory] = useState<Category | undefined>(undefined);
  const [addedCodes, setAddedCodes] = useState<Set<string>>(new Set());
  const [qtyMap, setQtyMap] = useState<Record<string, number>>({});
  const [allProducts, setAllProducts] = useState<Product[]>([]);
  const [productsLoading, setProductsLoading] = useState(true);

  useEffect(() => {
    if (!isLoading && !user) window.location.href = '/';
  }, [user, isLoading]);

  useEffect(() => {
    if (!user) return;
    setProductsLoading(true);
    fetchProducts(user.centerType)
      .then(setAllProducts)
      .catch(console.error)
      .finally(() => setProductsLoading(false));
  }, [user]);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    return allProducts.filter((p) => {
      if (activeCategory && p.category !== activeCategory) return false;
      if (!q) return true;
      return p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q);
    });
  }, [allProducts, query, activeCategory]);

  if (isLoading || !user) return null;

  const handleAdd = (code: string) => {
    const p = allProducts.find((p) => p.code === code);
    if (!p) return;
    const qty = qtyMap[code] ?? 1;
    addItem(p, qty);
    setAddedCodes((prev) => new Set(prev).add(code));
    setTimeout(
      () => setAddedCodes((prev) => { const s = new Set(prev); s.delete(code); return s; }),
      1500
    );
  };

  const cartCount = (code: string) => items.find((i) => i.product.code === code)?.quantity ?? 0;

  return (
    <main className="min-h-screen bg-stone-50 pb-24">
      {/* ヘッダー */}
      <header className="bg-white border-b border-stone-200 sticky top-0 z-10">
        <div className="max-w-lg mx-auto px-4 py-3 flex items-center gap-3">
          <Link href="/home" className="text-stone-400 hover:text-stone-600 p-1">
            <ChevronLeft />
          </Link>
          <h1 className="font-semibold text-stone-800 flex-1">商品を選ぶ</h1>
          <Link href="/cart" className="relative p-1">
            <CartIcon />
            {totalCount > 0 && (
              <span className="absolute -top-0.5 -right-0.5 w-5 h-5 bg-amber-600 text-white text-xs rounded-full flex items-center justify-center font-bold leading-none">
                {totalCount > 99 ? '99+' : totalCount}
              </span>
            )}
          </Link>
        </div>

        {/* 検索欄 */}
        <div className="px-4 pb-3">
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-stone-400">
              <SearchIcon />
            </span>
            <input
              type="search"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="商品名・コードで検索"
              className="w-full pl-9 pr-4 py-2.5 bg-stone-100 rounded-xl text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
            {query && (
              <button
                onClick={() => setQuery('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400"
              >
                ✕
              </button>
            )}
          </div>
        </div>

        {/* カテゴリフィルター */}
        <div className="flex gap-2 px-4 pb-3 overflow-x-auto scrollbar-none">
          <CategoryPill
            label="すべて"
            active={!activeCategory}
            onClick={() => setActiveCategory(undefined)}
          />
          {CATEGORIES.map((c) => (
            <CategoryPill
              key={c}
              label={c}
              active={activeCategory === c}
              onClick={() => setActiveCategory(c === activeCategory ? undefined : c)}
            />
          ))}
        </div>
      </header>

      {/* 商品一覧 */}
      <div className="max-w-lg mx-auto px-4 pt-4">
        {productsLoading ? (
          <div className="text-center py-16 text-stone-400">
            <p className="text-sm">商品データを読み込み中...</p>
          </div>
        ) : (
        <>
        <p className="text-xs text-stone-400 mb-3">{filtered.length}件</p>

        {filtered.length === 0 ? (
          <div className="text-center py-16 text-stone-400">
            <p className="text-2xl mb-2">🔍</p>
            <p className="text-sm">該当する商品が見つかりません</p>
          </div>
        ) : (
          <div className="space-y-2">
            {filtered.map((p) => {
              const inCart = cartCount(p.code);
              const isAdded = addedCodes.has(p.code);
              const qty = qtyMap[p.code] ?? 1;
              return (
                <div
                  key={p.code}
                  className="bg-white rounded-2xl border border-stone-200 p-4 flex items-center gap-3"
                >
                  {/* 商品情報 */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-0.5">
                      <span className="text-xs text-stone-400 font-mono">{p.code}</span>
                      <span className="text-xs bg-stone-100 text-stone-500 px-1.5 rounded">
                        {p.taxRate === 0.08 ? '軽減税率8%' : '10%'}
                      </span>
                    </div>
                    <p className="text-sm font-medium text-stone-800 leading-snug line-clamp-2">{p.name}</p>
                    {p.volume && (
                      <p className="text-xs text-stone-400 mt-0.5">{p.volume}</p>
                    )}
                    <p className="text-base font-bold text-amber-700 mt-1">
                      ¥{formatPrice(getPrice(p, user.centerType))}<span className="text-xs font-normal text-stone-400">（税抜）</span>
                    </p>
                    {inCart > 0 && (
                      <p className="text-xs text-amber-600 mt-0.5">カート: {inCart}個</p>
                    )}
                  </div>

                  {/* 数量 + 追加 */}
                  <div className="flex flex-col items-end gap-2 shrink-0">
                    <div className="flex items-center gap-1">
                      <button
                        onClick={() => setQtyMap((m) => ({ ...m, [p.code]: Math.max(1, (m[p.code] ?? 1) - 1) }))}
                        className="w-7 h-7 rounded-full bg-stone-100 text-stone-600 flex items-center justify-center text-sm font-bold hover:bg-stone-200 transition-colors"
                      >
                        −
                      </button>
                      <span className="w-8 text-center text-sm font-medium text-stone-800">
                        {qty}
                      </span>
                      <button
                        onClick={() => setQtyMap((m) => ({ ...m, [p.code]: (m[p.code] ?? 1) + 1 }))}
                        className="w-7 h-7 rounded-full bg-stone-100 text-stone-600 flex items-center justify-center text-sm font-bold hover:bg-stone-200 transition-colors"
                      >
                        ＋
                      </button>
                    </div>
                    <button
                      onClick={() => handleAdd(p.code)}
                      className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition-all ${
                        isAdded
                          ? 'bg-green-500 text-white'
                          : 'bg-amber-600 hover:bg-amber-700 text-white active:scale-95'
                      }`}
                    >
                      {isAdded ? '✓ 追加' : 'カートへ'}
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
        </>
        )}
      </div>

      {/* 固定フッター：カートへ進む */}
      {totalCount > 0 && (
        <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-stone-200 p-4 z-20">
          <div className="max-w-lg mx-auto">
            <Link
              href="/cart"
              className="flex items-center justify-center gap-2 w-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-3.5 font-semibold transition-colors"
            >
              <CartIcon />
              カートを確認する（{totalCount}点）
            </Link>
          </div>
        </div>
      )}
    </main>
  );
}

function CategoryPill({ label, active, onClick }: { label: string; active: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={`shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors whitespace-nowrap ${
        active
          ? 'bg-amber-600 text-white'
          : 'bg-stone-100 text-stone-600 hover:bg-stone-200'
      }`}
    >
      {label}
    </button>
  );
}

function ChevronLeft() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="M15 18l-6-6 6-6" />
    </svg>
  );
}

function CartIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
    </svg>
  );
}

function SearchIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
    </svg>
  );
}
