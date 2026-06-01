'use client';

import React, { createContext, useContext, useReducer, useEffect, useCallback } from 'react';
import { Product, calcTax, getPrice } from '@/lib/products';
import { useAuth } from '@/context/AuthContext';

export interface CartItem {
  product: Product;
  quantity: number;
}

interface CartState {
  items: CartItem[];
}

type CartAction =
  | { type: 'ADD'; product: Product; qty?: number }
  | { type: 'SET_QTY'; code: string; qty: number }
  | { type: 'REMOVE'; code: string }
  | { type: 'CLEAR' }
  | { type: 'LOAD'; items: CartItem[] };

const STORAGE_KEY = 'yamano_cart';

function reducer(state: CartState, action: CartAction): CartState {
  switch (action.type) {
    case 'ADD': {
      const exists = state.items.find((i) => i.product.code === action.product.code);
      if (exists) {
        return {
          items: state.items.map((i) =>
            i.product.code === action.product.code
              ? { ...i, quantity: i.quantity + (action.qty ?? 1) }
              : i
          ),
        };
      }
      return { items: [...state.items, { product: action.product, quantity: action.qty ?? 1 }] };
    }
    case 'SET_QTY': {
      if (action.qty <= 0)
        return { items: state.items.filter((i) => i.product.code !== action.code) };
      return {
        items: state.items.map((i) =>
          i.product.code === action.code ? { ...i, quantity: action.qty } : i
        ),
      };
    }
    case 'REMOVE':
      return { items: state.items.filter((i) => i.product.code !== action.code) };
    case 'CLEAR':
      return { items: [] };
    case 'LOAD':
      return { items: action.items };
    default:
      return state;
  }
}

interface CartContextType extends CartState {
  addItem: (product: Product, qty?: number) => void;
  setQty: (code: string, qty: number) => void;
  removeItem: (code: string) => void;
  clearCart: () => void;
  totalCount: number;
  subtotal: number;
  totalTax: number;
  grandTotal: number;
}

const CartContext = createContext<CartContextType | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [state, dispatch] = useReducer(reducer, { items: [] });
  const { user } = useAuth();
  const centerType = user?.centerType ?? 'FC';

  useEffect(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) dispatch({ type: 'LOAD', items: JSON.parse(saved) as CartItem[] });
    } catch { /* ignore */ }
  }, []);

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state.items));
  }, [state.items]);

  const subtotal = state.items.reduce((s, i) => s + getPrice(i.product, centerType) * i.quantity, 0);
  const totalTax = state.items.reduce(
    (s, i) => s + calcTax(getPrice(i.product, centerType), i.quantity, i.product.taxRate),
    0
  );
  const grandTotal = subtotal + totalTax;
  const totalCount = state.items.reduce((s, i) => s + i.quantity, 0);

  const addItem = useCallback((product: Product, qty?: number) => {
    dispatch({ type: 'ADD', product, qty });
  }, []);
  const setQty = useCallback((code: string, qty: number) => {
    dispatch({ type: 'SET_QTY', code, qty });
  }, []);
  const removeItem = useCallback((code: string) => {
    dispatch({ type: 'REMOVE', code });
  }, []);
  const clearCart = useCallback(() => dispatch({ type: 'CLEAR' }), []);

  return (
    <CartContext.Provider
      value={{
        ...state,
        addItem,
        setQty,
        removeItem,
        clearCart,
        totalCount,
        subtotal,
        totalTax,
        grandTotal,
      }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error('useCart must be used inside CartProvider');
  return ctx;
}
