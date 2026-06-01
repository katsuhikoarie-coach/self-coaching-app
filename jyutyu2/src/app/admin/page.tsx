'use client';

import React, { useEffect, useState, useCallback } from 'react';
import { useAuth } from '@/context/AuthContext';

// ── 型定義 ──────────────────────────────────────────────────
type Tab = 'orders' | 'users' | 'products' | 'yamano' | 'invoice' | 'rates';
type OrderStatus = 'received' | 'confirmed' | 'shipped';
type BillingStatus = 'unbilled' | 'billed';

interface OrderItem {
  code: string; name: string; price: number; qty: number; amount: number;
}
interface AdminOrder {
  id: string;
  order_date: string;
  month_period: string;
  fc_name: string;
  contact_name: string;
  subtotal: number;
  tax_total: number;
  total: number;
  note: string;
  status: OrderStatus;
  billing_status: BillingStatus;
  items: OrderItem[];
}
interface AdminUser {
  id: number;
  email: string;
  fc_name: string;
  contact_name: string;
  center_type: string;
  active: number;
  created_at: string;
}
interface AdminProduct {
  id: number;
  code: string;
  name: string;
  category: string;
  price_hansha: number;
  price_bc: number;
  price_fc: number;
  tax_rate: number;
  active: number;
  note: string | null;
}
interface CenterRate {
  id: number;
  fc_user_id: number;
  rate_system: number;
  rate_general: number;
  valid_from: string;
  valid_to: string | null;
}
interface RateForm {
  rate_system: string;
  rate_general: string;
  valid_from: string;
}

interface Breakdown {
  system: number;
  general: number;
  bettaguchi: number;
  unknown: number;
  system_general: number;
}
interface YamanoCenter {
  fc_user_id: number;
  fc_name: string;
  order_count: number;
  total_pretax: number;
  breakdown: Breakdown;
}
interface YamanoUnclassified {
  yamano_order_id: string;
  order_date: string;
  shipping_name: string;
  total_pretax: number;
  status: string;
}
interface YamanoClassified {
  yamano_order_id: string;
  order_date: string;
  shipping_name: string;
  total_pretax: number;
  fc_user_id: number;
  fc_name: string;
  status: string;
}
interface YamanoFcUser {
  id: number;
  fc_name: string;
  center_type: string;
}
interface YamanoData {
  month_period: string;
  centers: YamanoCenter[];
  summary: { total: number; count: number; breakdown: Breakdown };
  unclassified: YamanoUnclassified[];
  classified: YamanoClassified[];
  fc_users: YamanoFcUser[];
}

interface ProdForm {
  code: string;
  name: string;
  category: string;
  price_hansha: string;
  price_bc: string;
  price_fc: string;
  tax_rate: string;
  note: string;
}

// ── 定数 ─────────────────────────────────────────────────────
const STATUS_LABEL: Record<OrderStatus, string> = {
  received:  '受付済',
  confirmed: '確認済',
  shipped:   '発送済',
};
const STATUS_COLOR: Record<OrderStatus, string> = {
  received:  'bg-amber-100 text-amber-800',
  confirmed: 'bg-blue-100 text-blue-800',
  shipped:   'bg-green-100 text-green-800',
};
const NEXT_STATUS: Partial<Record<OrderStatus, OrderStatus>> = {
  received:  'confirmed',
  confirmed: 'shipped',
};
const NEXT_LABEL: Partial<Record<OrderStatus, string>> = {
  received:  '確認する',
  confirmed: '発送する',
};
const NEXT_BTN_COLOR: Partial<Record<OrderStatus, string>> = {
  received:  'bg-blue-600 hover:bg-blue-700',
  confirmed: 'bg-green-600 hover:bg-green-700',
};
const PRODUCT_CATEGORIES = [
  'スキンケア', 'メイク', 'ボディ＆ヘアケア', 'エステキープ',
  '健康食品', 'キット', 'CS商品', '販促品', '一般',
] as const;
const EMPTY_PROD_FORM: ProdForm = {
  code: '', name: '', category: 'スキンケア',
  price_hansha: '', price_bc: '', price_fc: '',
  tax_rate: '0.10', note: '',
};

// ── 月度リスト生成 ───────────────────────────────────────────
function generatePeriods(): { value: string; label: string }[] {
  const now = new Date();
  const day = now.getDate();
  const base = day >= 20
    ? new Date(now.getFullYear(), now.getMonth() + 1, 1)
    : new Date(now.getFullYear(), now.getMonth(), 1);

  return Array.from({ length: 13 }, (_, i) => {
    const d = new Date(base.getFullYear(), base.getMonth() - i, 1);
    const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    return { value, label: `${d.getFullYear()}年${d.getMonth() + 1}月度` };
  });
}

function currentPeriod(): string {
  return generatePeriods()[0].value;
}

// ── 年度ユーティリティ（当年5月20日〜翌年5月19日） ───────────
function calcFiscalYear(): number {
  const now = new Date();
  const m   = now.getMonth() + 1;
  const d   = now.getDate();
  return (m > 5 || (m === 5 && d >= 20)) ? now.getFullYear() + 1 : now.getFullYear();
}

function fiscalYearList(): number[] {
  const fy = calcFiscalYear();
  return Array.from({ length: 5 }, (_, i) => fy - i);
}

// 年度N内の月度リスト（新→古順）: N-05, N-04, ..., (N-1)-06
function periodsInFY(fy: number): { value: string; label: string }[] {
  return Array.from({ length: 12 }, (_, i) => {
    let year = fy, month = 5 - i;
    if (month <= 0) { month += 12; year = fy - 1; }
    const value = `${year}-${String(month).padStart(2, '0')}`;
    return { value, label: `${year}年${month}月度` };
  });
}

// ── メインコンポーネント ─────────────────────────────────────
export default function AdminPage() {
  const { user, isLoading } = useAuth();
  const [tab, setTab]                   = useState<Tab>('orders');
  const [period, setPeriod]             = useState(currentPeriod);
  const [orders, setOrders]             = useState<AdminOrder[]>([]);
  const [users, setUsers]               = useState<AdminUser[]>([]);
  const [expandedId, setExpandedId]     = useState<string | null>(null);
  const [ordersLoading, setOrdersLoading] = useState(false);
  const [usersLoading, setUsersLoading]   = useState(false);
  const [csvLoading, setCsvLoading]       = useState(false);
  const [updatingId, setUpdatingId]       = useState<string | null>(null);
  const [deletingId, setDeletingId]       = useState<string | null>(null);
  const [showAddUser, setShowAddUser]     = useState(false);
  const [addingUser, setAddingUser]       = useState(false);
  const [addError, setAddError]           = useState('');
  const [newUser, setNewUser] = useState({ contact_name: '', email: '', fc_name: '', active: 1 });
  const [pwForm, setPwForm]   = useState<Record<number, string>>({});
  const [pwSaving, setPwSaving] = useState<number | null>(null);
  const [pwError, setPwError]   = useState<Record<number, string>>({});
  const [showPwMap, setShowPwMap] = useState<Record<number, boolean>>({});

  // ヤマノ実績タブのState
  const [yamanoData, setYamanoData]           = useState<YamanoData | null>(null);
  const [yamanoLoading, setYamanoLoading]     = useState(false);
  const [yamanoFiscalYear, setYamanoFiscalYear] = useState(calcFiscalYear);
  const [yamanoPeriod, setYamanoPeriod]       = useState(currentPeriod);
  const [mappingOrderId, setMappingOrderId]   = useState<string | null>(null);
  const [selectedFcUserId, setSelectedFcUserId] = useState('');
  const [mappingLoading, setMappingLoading]   = useState(false);
  const [yamanoCsvLoading, setYamanoCsvLoading]   = useState(false);
  const [deletingOrderId, setDeletingOrderId]     = useState<string | null>(null);
  const [expandedCenters, setExpandedCenters]     = useState<Set<number>>(new Set());

  // 掛率管理タブのState
  const [rateUsers, setRateUsers]         = useState<AdminUser[]>([]);
  const [rateMap, setRateMap]             = useState<Record<number, CenterRate | null>>({});
  const [rateUsersLoading, setRateUsersLoading] = useState(false);
  const [selectedRateUserId, setSelectedRateUserId] = useState<number | null>(null);
  const [currentRate, setCurrentRate]     = useState<CenterRate | null | undefined>(undefined);
  const [rateHistory, setRateHistory]     = useState<CenterRate[]>([]);
  const [rateHistoryLoading, setRateHistoryLoading] = useState(false);
  const [rateForm, setRateForm]           = useState<RateForm>({ rate_system: '', rate_general: '', valid_from: '' });
  const [rateSaving, setRateSaving]       = useState(false);
  const [rateError, setRateError]         = useState('');
  const [rateSuccess, setRateSuccess]     = useState('');

  // 請求書タブのState
  const [invoicePeriod, setInvoicePeriod]       = useState(currentPeriod);
  const [invoiceFcUserId, setInvoiceFcUserId]   = useState('');
  const [invoiceLoading, setInvoiceLoading]     = useState(false);
  const [invoiceUsers, setInvoiceUsers]         = useState<AdminUser[]>([]);

  // 商品管理タブのState
  const [prods, setProds]               = useState<AdminProduct[]>([]);
  const [prodsLoading, setProdsLoading] = useState(false);
  const [prodQuery, setProdQuery]       = useState('');
  const [prodCat, setProdCat]           = useState('');
  const [showProdModal, setShowProdModal] = useState(false);
  const [editingProd, setEditingProd]   = useState<AdminProduct | null>(null);
  const [prodForm, setProdForm]         = useState<ProdForm>(EMPTY_PROD_FORM);
  const [prodSaving, setProdSaving]     = useState(false);
  const [prodError, setProdError]       = useState('');

  const PERIODS = generatePeriods();

  // アクセス制御
  useEffect(() => {
    if (isLoading) return;
    if (!user) { window.location.href = '/'; return; }
    if (user.fcName !== '朝霧ヤマノ') { window.location.href = '/home/'; }
  }, [user, isLoading]);

  // ── 注文取得 ───────────────────────────────────────────────
  const loadOrders = useCallback(async (p: string) => {
    setOrdersLoading(true);
    setExpandedId(null);
    try {
      const res = await fetch(`/api/admin/orders.php?period=${p}`, {
        credentials: 'include',
      });
      if (!res.ok) throw new Error(`${res.status}`);
      setOrders(await res.json());
    } catch { setOrders([]); }
    setOrdersLoading(false);
  }, []);

  useEffect(() => {
    if (!user || user.fcName !== '朝霧ヤマノ') return;
    loadOrders(period);
  }, [user, period, loadOrders]);

  // ── ユーザー取得 ────────────────────────────────────────────
  const loadUsers = useCallback(async () => {
    if (tab !== 'users') return;
    setUsersLoading(true);
    try {
      const res = await fetch('/api/admin/users.php', {
        credentials: 'include',
      });
      if (!res.ok) throw new Error(`${res.status}`);
      setUsers(await res.json());
    } catch { setUsers([]); }
    setUsersLoading(false);
  }, [tab]);

  useEffect(() => {
    if (!user || user.fcName !== '朝霧ヤマノ') return;
    loadUsers();
  }, [user, tab, loadUsers]);

  // ── ヤマノ実績取得 ─────────────────────────────────────────
  const loadYamano = useCallback(async (p: string) => {
    if (tab !== 'yamano') return;
    setYamanoLoading(true);
    try {
      const res = await fetch(`/api/admin/yamano-orders.php?month_period=${p}`, {
        credentials: 'include',
      });
      if (!res.ok) throw new Error(`${res.status}`);
      setYamanoData(await res.json());
    } catch { setYamanoData(null); }
    setYamanoLoading(false);
  }, [tab]);

  // 年度変更 → 月度を選択年度内に収める
  useEffect(() => {
    const periods = periodsInFY(yamanoFiscalYear);
    if (!periods.some(p => p.value === yamanoPeriod)) {
      setYamanoPeriod(periods[0].value);
    }
  }, [yamanoFiscalYear]);

  useEffect(() => {
    if (!user || user.fcName !== '朝霧ヤマノ') return;
    loadYamano(yamanoPeriod);
  }, [user, tab, yamanoPeriod, loadYamano]);

  // ── ヤマノ キャンセル注文削除 ───────────────────────────
  const deleteYamanoOrder = async (yamano_order_id: string) => {
    if (!window.confirm(`この注文を削除しますか？\n${yamano_order_id}\n削除すると元に戻せません。`)) return;
    setDeletingOrderId(yamano_order_id);
    try {
      const res = await fetch('/api/admin/yamano-orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'delete_order', yamano_order_id }),
      });
      if (res.ok) await loadYamano(yamanoPeriod);
    } catch { /* ignore */ }
    setDeletingOrderId(null);
  };

  const toggleCenter = (id: number) => {
    setExpandedCenters(prev => {
      const next = new Set(prev);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  };

  // ── ヤマノ CSV ダウンロード ─────────────────────────────
  const downloadYamanoCsv = async () => {
    if (yamanoCsvLoading) return;
    setYamanoCsvLoading(true);
    try {
      const res = await fetch(
        `/api/admin/yamano-orders.php?month_period=${yamanoPeriod}&format=csv`,
        { credentials: 'include' }
      );
      if (!res.ok) throw new Error(`${res.status}`);
      const blob = await res.blob();
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href     = url;
      a.download = `yamano_${yamanoPeriod}_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch { /* ignore */ }
    setYamanoCsvLoading(false);
  };

  // ── ヤマノ未分類紐づけ ──────────────────────────────────
  const submitMapping = async () => {
    if (!mappingOrderId || !selectedFcUserId) return;
    setMappingLoading(true);
    try {
      const res = await fetch('/api/admin/yamano-map.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ yamano_order_id: mappingOrderId, fc_user_id: parseInt(selectedFcUserId) }),
      });
      if (res.ok) {
        setMappingOrderId(null);
        setSelectedFcUserId('');
        await loadYamano(yamanoPeriod);
      }
    } catch { /* ignore */ }
    setMappingLoading(false);
  };

  // ── 掛率管理：センター一覧取得 ─────────────────────────────
  const loadRateUsers = useCallback(async () => {
    if (tab !== 'rates') return;
    setRateUsersLoading(true);
    try {
      const res = await fetch('/api/admin/users.php', { credentials: 'include' });
      if (!res.ok) return;
      const all: AdminUser[] = await res.json();
      const targets = all.filter((u) => u.active && u.fc_name !== '朝霧ヤマノ');
      setRateUsers(targets);

      // 全センターの現在掛率を並列取得
      const entries = await Promise.all(
        targets.map(async (u) => {
          try {
            const r = await fetch(`/api/admin/rates.php?fc_user_id=${u.id}`, {
              credentials: 'include',
            });
            const data = r.ok ? await r.json() : null;
            return [u.id, data] as [number, CenterRate | null];
          } catch { return [u.id, null] as [number, null]; }
        })
      );
      setRateMap(Object.fromEntries(entries));
    } catch { /* ignore */ }
    setRateUsersLoading(false);
  }, [tab]);

  useEffect(() => {
    if (!user || user.fcName !== '朝霧ヤマノ') return;
    loadRateUsers();
  }, [user, tab, loadRateUsers]);

  // ── 掛率管理：センター選択時に現在掛率・履歴を取得 ──────────
  const loadRateForUser = useCallback(async (fcUserId: number) => {
    setCurrentRate(undefined);
    setRateHistory([]);
    setRateHistoryLoading(true);
    setRateError('');
    setRateSuccess('');
    try {
      const [curRes, histRes] = await Promise.all([
        fetch(`/api/admin/rates.php?fc_user_id=${fcUserId}`, { credentials: 'include' }),
        fetch(`/api/admin/rates.php?fc_user_id=${fcUserId}&history=1`, { credentials: 'include' }),
      ]);
      const cur  = curRes.ok  ? await curRes.json()  : null;
      const hist = histRes.ok ? await histRes.json() : [];
      setCurrentRate(cur);
      setRateMap((prev) => ({ ...prev, [fcUserId]: cur }));
      setRateHistory(Array.isArray(hist) ? hist : []);
      if (cur) {
        setRateForm({
          rate_system:  String(Math.round((cur.rate_system  ?? 0) * 100)),
          rate_general: String(Math.round((cur.rate_general ?? 0) * 100)),
          valid_from: '',
        });
      } else {
        setRateForm({ rate_system: '', rate_general: '', valid_from: '' });
      }
    } catch { /* ignore */ }
    setRateHistoryLoading(false);
  }, []);

  useEffect(() => {
    if (selectedRateUserId === null) return;
    loadRateForUser(selectedRateUserId);
  }, [selectedRateUserId, loadRateForUser]);

  // ── 掛率保存 ────────────────────────────────────────────────
  const saveRate = async () => {
    if (!selectedRateUserId) return;
    const sys = parseFloat(rateForm.rate_system);
    const gen = parseFloat(rateForm.rate_general);
    if (isNaN(sys) || isNaN(gen) || !rateForm.valid_from) {
      setRateError('掛率と適用開始日を入力してください'); return;
    }
    setRateSaving(true); setRateError(''); setRateSuccess('');
    try {
      const res = await fetch('/api/admin/rates.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          fc_user_id:   selectedRateUserId,
          rate_system:  sys / 100,
          rate_general: gen / 100,
          valid_from:   rateForm.valid_from,
        }),
      });
      const data = await res.json();
      if (!res.ok) { setRateError(data.error || '保存に失敗しました'); return; }
      setRateSuccess('保存しました');
      await loadRateForUser(selectedRateUserId);
    } catch { setRateError('通信エラーが発生しました'); }
    setRateSaving(false);
  };

  // ── 請求書用センター一覧取得 ────────────────────────────────
  const loadInvoiceUsers = useCallback(async () => {
    if (tab !== 'invoice') return;
    try {
      const res = await fetch('/api/admin/users.php', { credentials: 'include' });
      if (!res.ok) return;
      setInvoiceUsers(await res.json());
    } catch { /* ignore */ }
  }, [tab]);

  useEffect(() => {
    if (!user || user.fcName !== '朝霧ヤマノ') return;
    loadInvoiceUsers();
  }, [user, tab, loadInvoiceUsers]);

  // ── 請求書ダウンロード ──────────────────────────────────────
  const downloadInvoice = async () => {
    if (!invoiceFcUserId || invoiceLoading) return;
    setInvoiceLoading(true);
    try {
      const url = `/api/admin/invoice.php?period=${invoicePeriod}&fc_user_id=${invoiceFcUserId}`;
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        alert((err as { error?: string }).error || '請求書の生成に失敗しました');
        return;
      }
      const blob = await res.blob();
      const a    = document.createElement('a');
      a.href     = URL.createObjectURL(blob);
      const u    = invoiceUsers.find((u) => String(u.id) === invoiceFcUserId);
      a.download = `invoice_${invoicePeriod}_${u?.fc_name ?? invoiceFcUserId}.xlsx`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(a.href);
    } finally {
      setInvoiceLoading(false);
    }
  };

  // ── 商品取得 ────────────────────────────────────────────────
  const loadProds = useCallback(async () => {
    if (tab !== 'products') return;
    setProdsLoading(true);
    try {
      const res = await fetch('/api/admin/products.php?action=list', {
        credentials: 'include',
      });
      if (!res.ok) throw new Error(`${res.status}`);
      setProds(await res.json());
    } catch { setProds([]); }
    setProdsLoading(false);
  }, [tab]);

  useEffect(() => {
    if (!user || user.fcName !== '朝霧ヤマノ') return;
    loadProds();
  }, [user, tab, loadProds]);

  // ── ステータス変更 ─────────────────────────────────────────
  const updateStatus = async (order: AdminOrder, next: OrderStatus) => {
    setUpdatingId(order.id);
    try {
      const res = await fetch('/api/admin/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'update_status', id: order.id, status: next }),
      });
      if (res.ok) {
        setOrders((prev) =>
          prev.map((o) => (o.id === order.id ? { ...o, status: next } : o))
        );
      }
    } catch { /* ignore */ }
    setUpdatingId(null);
  };

  // ── 注文削除 ───────────────────────────────────────────────
  const deleteOrder = async (order: AdminOrder) => {
    if (!window.confirm('このデータを保存しましたか？削除すると元に戻せません。')) return;
    setDeletingId(order.id);
    try {
      const res = await fetch('/api/admin/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'delete', order_id: order.id }),
      });
      if (res.ok) {
        setOrders((prev) => prev.filter((o) => o.id !== order.id));
        setExpandedId(null);
      }
    } catch { /* ignore */ }
    setDeletingId(null);
  };

  // ── CSV ダウンロード ────────────────────────────────────────
  const downloadCsv = async () => {
    if (orders.length === 0 || csvLoading) return;
    setCsvLoading(true);
    try {
      const periodLabel = PERIODS.find((p) => p.value === period)?.label ?? period;
      const headers = [
        '受注番号', '月度', '注文日', 'FC名', '担当者名',
        '商品コード', '商品名', '数量', '単価（税抜）', '小計（税抜）',
        '合計（税込）', 'ステータス', '備考',
      ];
      const rows: string[][] = [headers];

      orders.forEach((o) => {
        const base = [
          o.id, periodLabel, o.order_date, o.fc_name, o.contact_name,
        ];
        if (!o.items?.length) {
          rows.push([...base, '', '', '', '', '', String(o.total), STATUS_LABEL[o.status], o.note]);
        } else {
          o.items.forEach((it) => {
            rows.push([
              ...base,
              it.code, it.name,
              String(it.qty), String(it.price), String(it.price * it.qty),
              String(o.total), STATUS_LABEL[o.status], o.note,
            ]);
          });
        }
      });

      const csvStr = rows
        .map((r) => r.map((v) => {
          const s = String(v ?? '');
          return s.includes(',') || s.includes('"') || s.includes('\n')
            ? '"' + s.replace(/"/g, '""') + '"' : s;
        }).join(','))
        .join('\r\n');

      const Encoding = (await import('encoding-japanese')).default;
      const sjis = Encoding.convert(Encoding.stringToCode(csvStr), { to: 'SJIS', from: 'UNICODE' });
      const blob = new Blob([new Uint8Array(sjis)], { type: 'text/csv; charset=shift-jis' });
      const fname = `orders_${period}_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.csv`;
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = fname;
      document.body.appendChild(a); a.click();
      document.body.removeChild(a); URL.revokeObjectURL(url);
    } finally { setCsvLoading(false); }
  };

  // ── ユーザー追加 ────────────────────────────────────────────
  const addUser = async () => {
    if (!newUser.email || !newUser.fc_name) {
      setAddError('メールアドレスとFC名は必須です'); return;
    }
    setAddingUser(true); setAddError('');
    try {
      const res = await fetch('/api/admin/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'create', ...newUser }),
      });
      const data = await res.json();
      if (!res.ok) { setAddError(data.error || '追加に失敗しました'); return; }
      setNewUser({ contact_name: '', email: '', fc_name: '', active: 1 });
      setShowAddUser(false);
      await loadUsers();
    } catch { setAddError('通信エラーが発生しました'); }
    setAddingUser(false);
  };

  // ── パスワード設定 ──────────────────────────────────────────
  const savePassword = async (id: number) => {
    const pw = pwForm[id] ?? '';
    if (pw.length < 6) {
      setPwError((prev) => ({ ...prev, [id]: '6文字以上で入力してください' }));
      return;
    }
    setPwSaving(id);
    setPwError((prev) => ({ ...prev, [id]: '' }));
    try {
      const res = await fetch('/api/admin/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'set_password', id, password: pw }),
      });
      if (res.ok) {
        setPwForm((prev) => { const n = { ...prev }; delete n[id]; return n; });
      } else {
        const data = await res.json().catch(() => ({}));
        setPwError((prev) => ({ ...prev, [id]: (data as { error?: string }).error || '更新失敗' }));
      }
    } catch {
      setPwError((prev) => ({ ...prev, [id]: '通信エラー' }));
    }
    setPwSaving(null);
  };

  // ── 有効/無効切替（ユーザー） ───────────────────────────────
  const toggleUser = async (id: number) => {
    try {
      const res = await fetch('/api/admin/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'toggle', id }),
      });
      if (res.ok) {
        const { active } = await res.json();
        setUsers((prev) => prev.map((u) => (u.id === id ? { ...u, active } : u)));
      }
    } catch { /* ignore */ }
  };

  // ── 商品モーダル操作 ────────────────────────────────────────
  const openAddProd = () => {
    setEditingProd(null);
    setProdForm(EMPTY_PROD_FORM);
    setProdError('');
    setShowProdModal(true);
  };

  const openEditProd = (p: AdminProduct) => {
    setEditingProd(p);
    setProdForm({
      code:         p.code,
      name:         p.name,
      category:     p.category,
      price_hansha: String(p.price_hansha),
      price_bc:     String(p.price_bc),
      price_fc:     String(p.price_fc),
      tax_rate:     String(p.tax_rate),
      note:         p.note ?? '',
    });
    setProdError('');
    setShowProdModal(true);
  };

  const saveProd = async () => {
    if (!prodForm.code || !prodForm.name || !prodForm.category) {
      setProdError('コード・商品名・カテゴリは必須です'); return;
    }
    setProdSaving(true); setProdError('');
    try {
      const isEdit = !!editingProd;
      const body: Record<string, unknown> = {
        action:       isEdit ? 'update' : 'add',
        code:         prodForm.code,
        name:         prodForm.name,
        category:     prodForm.category,
        price_hansha: parseInt(prodForm.price_hansha)  || 0,
        price_bc:     parseInt(prodForm.price_bc)      || 0,
        price_fc:     parseInt(prodForm.price_fc)      || 0,
        tax_rate:     parseFloat(prodForm.tax_rate),
        note:         prodForm.note,
      };
      if (isEdit) body.id = editingProd.id;

      const res = await fetch('/api/admin/products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) { setProdError(data.error || '保存に失敗しました'); return; }
      setShowProdModal(false);
      await loadProds();
    } catch { setProdError('通信エラーが発生しました'); }
    setProdSaving(false);
  };

  const deleteProd = async (p: AdminProduct) => {
    if (!window.confirm(`「${p.name}」を無効にしますか？`)) return;
    try {
      const res = await fetch('/api/admin/products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'delete', id: p.id }),
      });
      if (res.ok) await loadProds();
    } catch { /* ignore */ }
  };

  const activateProd = async (p: AdminProduct) => {
    try {
      const res = await fetch('/api/admin/products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          action: 'update', id: p.id,
          name: p.name, category: p.category,
          price_hansha: p.price_hansha, price_bc: p.price_bc, price_fc: p.price_fc,
          tax_rate: p.tax_rate, note: p.note ?? '',
          active: 1,
        }),
      });
      if (res.ok) await loadProds();
    } catch { /* ignore */ }
  };

  // クライアントサイドフィルタリング
  const filteredProds = prods.filter((p) => {
    if (prodCat && p.category !== prodCat) return false;
    if (!prodQuery) return true;
    const q = prodQuery.toLowerCase();
    return p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q);
  });

  if (isLoading || !user) return null;
  if (user.fcName !== '朝霧ヤマノ') return null;

  return (
    <main className="min-h-screen bg-stone-50">
      {/* ヘッダー */}
      <header className="bg-stone-800 text-white sticky top-0 z-10">
        <div className="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-base">🌿</span>
            <span className="font-bold text-sm tracking-wide">管理画面</span>
            <span className="text-stone-400 text-xs ml-1">朝霧ヤマノ</span>
          </div>
          <a href="/home/" className="text-xs text-stone-400 hover:text-white transition-colors">← ホームへ</a>
        </div>

        {/* タブ */}
        <div className="max-w-2xl mx-auto px-4 flex gap-1 pb-0">
          {([
            ['orders',   '注文管理'],
            ['users',    'センター管理'],
            ['products', '商品管理'],
            ['yamano',   'ヤマノ実績'],
            ['rates',    '掛率管理'],
            ['invoice',  '請求書'],
          ] as [Tab, string][]).map(([key, label]) => (
            <button
              key={key}
              onClick={() => setTab(key)}
              className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                tab === key
                  ? 'border-amber-400 text-amber-400'
                  : 'border-transparent text-stone-400 hover:text-white'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </header>

      <div className="max-w-2xl mx-auto px-4 py-5 space-y-4">

        {/* ══════════════════════════════════════
            注文管理タブ
        ══════════════════════════════════════ */}
        {tab === 'orders' && (
          <>
            {/* 月度選択 + CSV */}
            <div className="flex items-center gap-3">
              <select
                value={period}
                onChange={(e) => setPeriod(e.target.value)}
                className="flex-1 border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                {PERIODS.map((p) => (
                  <option key={p.value} value={p.value}>{p.label}</option>
                ))}
              </select>
              <button
                onClick={downloadCsv}
                disabled={orders.length === 0 || csvLoading}
                className="flex items-center gap-1.5 border border-stone-200 bg-white rounded-xl px-3 py-2 text-sm text-stone-600 hover:bg-stone-50 disabled:opacity-40"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                {csvLoading ? '生成中...' : 'CSV'}
              </button>
            </div>

            {/* 件数サマリー */}
            {!ordersLoading && (
              <p className="text-xs text-stone-400">
                {orders.length} 件 ／ 合計 ¥{orders.reduce((s, o) => s + o.total, 0).toLocaleString()} （税込）
              </p>
            )}

            {/* 注文リスト */}
            {ordersLoading ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">読み込み中...</p>
              </div>
            ) : orders.length === 0 ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">この月度の注文はありません</p>
              </div>
            ) : (
              <div className="space-y-2">
                {orders.map((o) => {
                  const isOpen = expandedId === o.id;
                  const nextStatus = NEXT_STATUS[o.status];
                  return (
                    <div key={o.id} className="bg-white rounded-xl border border-stone-200 overflow-hidden">
                      <button
                        onClick={() => setExpandedId(isOpen ? null : o.id)}
                        className="w-full px-4 py-3 flex items-start justify-between gap-3 text-left active:bg-stone-50"
                      >
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 flex-wrap">
                            <span className="text-xs font-mono text-stone-500">{o.id}</span>
                            <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${STATUS_COLOR[o.status]}`}>
                              {STATUS_LABEL[o.status]}
                            </span>
                          </div>
                          <p className="text-xs text-stone-500 mt-0.5">
                            {o.order_date}　{o.fc_name}
                            {o.contact_name && ` / ${o.contact_name}`}
                          </p>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                          <div className="text-right">
                            <p className="text-sm font-semibold text-stone-800">¥{o.total.toLocaleString()}</p>
                            <p className="text-xs text-stone-400">{o.items.length}品目</p>
                          </div>
                          <svg
                            width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"
                            className={`text-stone-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                          >
                            <path d="M6 9l6 6 6-6"/>
                          </svg>
                        </div>
                      </button>

                      {isOpen && (
                        <div className="border-t border-stone-100">
                          <div className="divide-y divide-stone-100">
                            {o.items.map((it, idx) => (
                              <div key={idx} className="px-4 py-2.5 flex items-start justify-between gap-3">
                                <div className="flex-1 min-w-0">
                                  <p className="text-xs text-stone-400 font-mono">{it.code}</p>
                                  <p className="text-sm text-stone-700 leading-snug">{it.name}</p>
                                </div>
                                <div className="text-right shrink-0 text-sm">
                                  <p className="text-stone-500">× {it.qty}</p>
                                  <p className="font-medium text-stone-800">¥{(it.price * it.qty).toLocaleString()}</p>
                                </div>
                              </div>
                            ))}
                          </div>
                          <div className="px-4 py-2.5 bg-stone-50 border-t border-stone-100 flex justify-between text-sm">
                            <span className="text-stone-500">合計（税込）</span>
                            <span className="font-bold text-amber-700">¥{o.total.toLocaleString()}</span>
                          </div>
                          {o.note && (
                            <div className="px-4 py-2 bg-stone-50 border-t border-stone-100">
                              <p className="text-xs text-stone-400">備考: {o.note}</p>
                            </div>
                          )}
                          {nextStatus && (
                            <div className="px-4 py-3 border-t border-stone-100">
                              <button
                                onClick={() => updateStatus(o, nextStatus)}
                                disabled={updatingId === o.id}
                                className={`w-full text-white rounded-lg py-2 text-sm font-semibold transition-colors disabled:opacity-50 ${NEXT_BTN_COLOR[o.status]}`}
                              >
                                {updatingId === o.id ? '更新中...' : NEXT_LABEL[o.status]}
                              </button>
                            </div>
                          )}
                          <div className="px-4 py-3 border-t border-stone-100">
                            <button
                              onClick={() => deleteOrder(o)}
                              disabled={deletingId === o.id}
                              className="w-full bg-red-600 hover:bg-red-700 text-white rounded-lg py-2 text-sm font-semibold transition-colors disabled:opacity-50"
                            >
                              {deletingId === o.id ? '削除中...' : '削除'}
                            </button>
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </>
        )}

        {/* ══════════════════════════════════════
            センター管理タブ
        ══════════════════════════════════════ */}
        {tab === 'users' && (
          <>
            <div className="flex justify-end">
              <button
                onClick={() => { setShowAddUser(!showAddUser); setAddError(''); }}
                className="bg-amber-600 hover:bg-amber-700 text-white rounded-xl px-4 py-2 text-sm font-semibold transition-colors"
              >
                {showAddUser ? 'キャンセル' : '＋ 新規追加'}
              </button>
            </div>

            {showAddUser && (
              <div className="bg-white rounded-2xl border border-amber-200 p-4 space-y-3">
                <h3 className="text-sm font-semibold text-stone-700">新規ユーザー追加</h3>
                {[
                  { label: 'FC名 *', key: 'fc_name', placeholder: '例: 西明石白神FC' },
                  { label: '担当者名', key: 'contact_name', placeholder: '例: 白神' },
                  { label: 'Gmailアドレス *', key: 'email', placeholder: 'xxx@gmail.com' },
                ].map(({ label, key, placeholder }) => (
                  <div key={key}>
                    <label className="block text-xs text-stone-500 mb-1">{label}</label>
                    <input
                      type={key === 'email' ? 'email' : 'text'}
                      value={(newUser as Record<string, string | number>)[key] as string}
                      onChange={(e) => setNewUser((prev) => ({ ...prev, [key]: e.target.value }))}
                      placeholder={placeholder}
                      className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                ))}
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="active-chk"
                    checked={newUser.active === 1}
                    onChange={(e) => setNewUser((prev) => ({ ...prev, active: e.target.checked ? 1 : 0 }))}
                    className="accent-amber-600"
                  />
                  <label htmlFor="active-chk" className="text-sm text-stone-700">有効にする</label>
                </div>
                {addError && <p className="text-xs text-red-600">{addError}</p>}
                <button
                  onClick={addUser}
                  disabled={addingUser}
                  className="w-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-2.5 text-sm font-semibold disabled:opacity-50"
                >
                  {addingUser ? '追加中...' : '追加する'}
                </button>
              </div>
            )}

            {usersLoading ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">読み込み中...</p>
              </div>
            ) : (
              <div className="space-y-2">
                {users.map((u) => (
                  <div key={u.id} className="bg-white rounded-xl border border-stone-200 px-4 py-3">
                    <div className="flex items-center gap-3">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-sm font-medium text-stone-800">{u.fc_name}</p>
                          <span className={`text-xs px-1.5 py-0.5 rounded-full ${u.active ? 'bg-green-100 text-green-700' : 'bg-stone-100 text-stone-500'}`}>
                            {u.active ? '有効' : '無効'}
                          </span>
                        </div>
                        <p className="text-xs text-stone-500 mt-0.5">
                          {u.contact_name && `${u.contact_name} ／ `}{u.email}
                        </p>
                      </div>
                      <button
                        onClick={() => toggleUser(u.id)}
                        className={`shrink-0 text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors ${
                          u.active
                            ? 'border-stone-200 text-stone-600 hover:bg-stone-50'
                            : 'border-green-200 text-green-700 hover:bg-green-50'
                        }`}
                      >
                        {u.active ? '無効にする' : '有効にする'}
                      </button>
                    </div>

                  {/* パスワード設定 */}
                  {pwForm[u.id] !== undefined ? (
                    <div className="mt-2 space-y-1">
                      <div className="flex gap-2">
                        <div style={{ position: 'relative', flex: 1 }}>
                          <input
                            type={showPwMap[u.id] ? 'text' : 'password'}
                            value={pwForm[u.id]}
                            onChange={(e) => setPwForm((prev) => ({ ...prev, [u.id]: e.target.value }))}
                            placeholder="新パスワード（6文字以上）"
                            className="w-full border border-stone-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-amber-500"
                            style={{ paddingRight: '2.8rem' }}
                          />
                          <button
                            type="button"
                            onClick={() => setShowPwMap((prev) => ({ ...prev, [u.id]: !prev[u.id] }))}
                            style={{
                              position: 'absolute',
                              right: '0.6rem',
                              top: '50%',
                              transform: 'translateY(-50%)',
                              background: 'none',
                              border: 'none',
                              cursor: 'pointer',
                              color: '#999',
                              fontSize: '1.2rem',
                              lineHeight: 1,
                              padding: '0',
                            }}
                            aria-label={showPwMap[u.id] ? 'パスワードを隠す' : 'パスワードを表示'}
                          >
                            {showPwMap[u.id] ? '🙈' : '👁️'}
                          </button>
                        </div>
                        <button
                          onClick={() => savePassword(u.id)}
                          disabled={pwSaving === u.id}
                          className="text-xs px-3 py-1.5 rounded-lg bg-amber-600 text-white font-medium disabled:opacity-50"
                        >
                          {pwSaving === u.id ? '...' : '保存'}
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            setPwForm((prev) => { const n = { ...prev }; delete n[u.id]; return n; });
                            setPwError((prev) => { const n = { ...prev }; delete n[u.id]; return n; });
                            setShowPwMap((prev) => { const n = { ...prev }; delete n[u.id]; return n; });
                          }}
                          className="text-xs px-2 py-1.5 rounded-lg border border-stone-200 text-stone-500 hover:bg-stone-50"
                        >
                          ✕
                        </button>
                      </div>
                      {pwError[u.id] && <p className="text-xs text-red-600">{pwError[u.id]}</p>}
                    </div>
                  ) : (
                    <button
                      onClick={() => setPwForm((prev) => ({ ...prev, [u.id]: '' }))}
                      className="mt-2 text-xs px-3 py-1 rounded-lg border border-stone-200 text-stone-500 hover:bg-stone-50 font-medium transition-colors"
                    >
                      PW設定
                    </button>
                  )}
                </div>
              ))}
            </div>
            )}
          </>
        )}

        {/* ══════════════════════════════════════
            商品管理タブ
        ══════════════════════════════════════ */}
        {tab === 'products' && (
          <>
            {/* 検索 + カテゴリフィルター + 追加ボタン */}
            <div className="flex gap-2">
              <input
                type="search"
                value={prodQuery}
                onChange={(e) => setProdQuery(e.target.value)}
                placeholder="商品名・コードで検索"
                className="flex-1 border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
              <button
                onClick={openAddProd}
                className="shrink-0 bg-amber-600 hover:bg-amber-700 text-white rounded-xl px-4 py-2 text-sm font-semibold transition-colors"
              >
                ＋ 追加
              </button>
            </div>

            {/* カテゴリフィルター */}
            <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
              <button
                onClick={() => setProdCat('')}
                className={`shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors whitespace-nowrap ${
                  !prodCat ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'
                }`}
              >
                すべて
              </button>
              {PRODUCT_CATEGORIES.map((c) => (
                <button
                  key={c}
                  onClick={() => setProdCat(prodCat === c ? '' : c)}
                  className={`shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors whitespace-nowrap ${
                    prodCat === c ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'
                  }`}
                >
                  {c}
                </button>
              ))}
            </div>

            {/* 件数 */}
            {!prodsLoading && (
              <p className="text-xs text-stone-400">
                {filteredProds.length}件（有効: {filteredProds.filter(p => p.active).length} ／ 無効: {filteredProds.filter(p => !p.active).length}）
              </p>
            )}

            {/* 商品リスト */}
            {prodsLoading ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">読み込み中...</p>
              </div>
            ) : filteredProds.length === 0 ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">該当する商品がありません</p>
              </div>
            ) : (
              <div className="space-y-2">
                {filteredProds.map((p) => (
                  <div
                    key={p.id}
                    className={`bg-white rounded-xl border px-4 py-3 ${p.active ? 'border-stone-200' : 'border-stone-100 opacity-60'}`}
                  >
                    {/* 1行目: コード / カテゴリ / 税率 / 有効フラグ */}
                    <div className="flex items-center gap-2 flex-wrap mb-0.5">
                      <span className="text-xs font-mono text-stone-400">{p.code}</span>
                      <span className="text-xs bg-stone-100 text-stone-500 px-1.5 rounded">{p.category}</span>
                      <span className={`text-xs px-1.5 rounded ${p.tax_rate === 0.08 ? 'bg-blue-50 text-blue-600' : 'bg-stone-50 text-stone-500'}`}>
                        {p.tax_rate === 0.08 ? '軽減8%' : '10%'}
                      </span>
                      <span className={`text-xs px-1.5 py-0.5 rounded-full ml-auto ${p.active ? 'bg-green-100 text-green-700' : 'bg-stone-100 text-stone-500'}`}>
                        {p.active ? '有効' : '無効'}
                      </span>
                    </div>

                    {/* 2行目: 商品名 */}
                    <p className="text-sm font-medium text-stone-800 leading-snug line-clamp-2 mb-1">{p.name}</p>

                    {/* 3行目: 価格 */}
                    <p className="text-xs text-stone-500">
                      FC: <span className="font-medium text-stone-700">¥{p.price_fc.toLocaleString()}</span>
                      　BC: <span className="font-medium text-stone-700">¥{p.price_bc.toLocaleString()}</span>
                      　販社: <span className="font-medium text-stone-700">¥{p.price_hansha.toLocaleString()}</span>
                    </p>

                    {/* 4行目: 操作ボタン */}
                    <div className="flex gap-2 mt-2">
                      <button
                        onClick={() => openEditProd(p)}
                        className="text-xs px-3 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 font-medium transition-colors"
                      >
                        編集
                      </button>
                      {p.active ? (
                        <button
                          onClick={() => deleteProd(p)}
                          className="text-xs px-3 py-1.5 rounded-lg border border-red-100 text-red-600 hover:bg-red-50 font-medium transition-colors"
                        >
                          無効にする
                        </button>
                      ) : (
                        <button
                          onClick={() => activateProd(p)}
                          className="text-xs px-3 py-1.5 rounded-lg border border-green-200 text-green-700 hover:bg-green-50 font-medium transition-colors"
                        >
                          有効にする
                        </button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </>
        )}
        {/* ══════════════════════════════════════
            掛率管理タブ
        ══════════════════════════════════════ */}
        {tab === 'rates' && (
          <>
            {rateUsersLoading ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">読み込み中...</p>
              </div>
            ) : (
              <>
                {/* センター一覧テーブル */}
                <div className="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                  <div className="px-4 py-3 border-b border-stone-100 bg-stone-50">
                    <h3 className="text-sm font-semibold text-stone-700">センター別掛率</h3>
                    <p className="text-xs text-stone-400 mt-0.5">センターを選択して掛率を設定・変更します</p>
                  </div>
                  <table className="w-full text-sm">
                    <thead className="bg-stone-50 border-b border-stone-100">
                      <tr>
                        <th className="px-4 py-2 text-left text-xs text-stone-500 font-medium">センター名</th>
                        <th className="px-4 py-2 text-center text-xs text-stone-500 font-medium">種別</th>
                        <th className="px-4 py-2 text-right text-xs text-stone-500 font-medium">システム掛率</th>
                        <th className="px-4 py-2 text-right text-xs text-stone-500 font-medium">一般掛率</th>
                        <th className="px-4 py-2 text-right text-xs text-stone-500 font-medium">適用開始日</th>
                        <th className="px-4 py-2"></th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-stone-100">
                      {rateUsers.map((u) => {
                        const cr = rateMap[u.id];
                        return (
                        <tr key={u.id} className={selectedRateUserId === u.id ? 'bg-amber-50' : ''}>
                          <td className="px-4 py-2.5 font-medium text-stone-800">{u.fc_name}</td>
                          <td className="px-4 py-2.5 text-center">
                            <span className="text-xs px-2 py-0.5 bg-stone-100 text-stone-600 rounded-full">{u.center_type}</span>
                          </td>
                          <td className="px-4 py-2.5 text-right text-stone-600 text-xs">
                            {cr ? Math.round(cr.rate_system * 100) + '%' : '—'}
                          </td>
                          <td className="px-4 py-2.5 text-right text-stone-600 text-xs">
                            {cr ? Math.round(cr.rate_general * 100) + '%' : '—'}
                          </td>
                          <td className="px-4 py-2.5 text-right text-stone-500 text-xs">
                            {cr ? cr.valid_from : '—'}
                          </td>
                          <td className="px-4 py-2.5 text-right">
                            <button
                              onClick={() => {
                                setSelectedRateUserId(u.id);
                                setRateError('');
                                setRateSuccess('');
                              }}
                              className="text-xs px-3 py-1.5 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 font-medium transition-colors"
                            >
                              変更する
                            </button>
                          </td>
                        </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>

                {/* 変更フォーム */}
                {selectedRateUserId !== null && (() => {
                  const selUser = rateUsers.find((u) => u.id === selectedRateUserId);
                  return (
                    <div className="bg-white rounded-2xl border border-amber-200 p-5 space-y-4">
                      <div className="flex items-center justify-between">
                        <h3 className="text-sm font-semibold text-stone-700">
                          {selUser?.fc_name} の掛率設定
                        </h3>
                        {currentRate === undefined && (
                          <span className="text-xs text-stone-400">読み込み中...</span>
                        )}
                        {currentRate === null && (
                          <span className="text-xs text-amber-600 font-medium">未登録</span>
                        )}
                        {currentRate && (
                          <span className="text-xs text-green-600">
                            現在: システム {Math.round(currentRate.rate_system * 100)}%　一般 {Math.round(currentRate.rate_general * 100)}%
                          </span>
                        )}
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block text-xs text-stone-500 mb-1">システム商品掛率（%）</label>
                          <input
                            type="number"
                            min={1} max={100}
                            value={rateForm.rate_system}
                            onChange={(e) => setRateForm((f) => ({ ...f, rate_system: e.target.value }))}
                            placeholder="例: 50"
                            className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                          />
                        </div>
                        <div>
                          <label className="block text-xs text-stone-500 mb-1">一般商品掛率（%）</label>
                          <input
                            type="number"
                            min={1} max={100}
                            value={rateForm.rate_general}
                            onChange={(e) => setRateForm((f) => ({ ...f, rate_general: e.target.value }))}
                            placeholder="例: 60"
                            className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                          />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs text-stone-500 mb-1">適用開始日</label>
                        <input
                          type="date"
                          value={rateForm.valid_from}
                          onChange={(e) => setRateForm((f) => ({ ...f, valid_from: e.target.value }))}
                          className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                        />
                      </div>

                      {rateError   && <p className="text-xs text-red-600">{rateError}</p>}
                      {rateSuccess && <p className="text-xs text-green-600">{rateSuccess}</p>}

                      <button
                        onClick={saveRate}
                        disabled={rateSaving}
                        className="w-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-2.5 text-sm font-semibold disabled:opacity-50 transition-colors"
                      >
                        {rateSaving ? '保存中...' : '保存する'}
                      </button>

                      {/* 履歴一覧 */}
                      {rateHistoryLoading ? (
                        <p className="text-xs text-stone-400 text-center pt-2">履歴読み込み中...</p>
                      ) : rateHistory.length > 0 && (
                        <div className="border-t border-stone-100 pt-4">
                          <p className="text-xs font-semibold text-stone-500 mb-2">掛率変更履歴</p>
                          <table className="w-full text-xs">
                            <thead>
                              <tr className="text-stone-400">
                                <th className="text-left py-1">適用開始日</th>
                                <th className="text-left py-1">適用終了日</th>
                                <th className="text-right py-1">システム</th>
                                <th className="text-right py-1">一般</th>
                              </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100">
                              {rateHistory.map((h) => (
                                <tr key={h.id} className={h.valid_to === null ? 'text-green-700 font-medium' : 'text-stone-500'}>
                                  <td className="py-1.5">{h.valid_from}</td>
                                  <td className="py-1.5">{h.valid_to ?? '（現在有効）'}</td>
                                  <td className="py-1.5 text-right">{Math.round(h.rate_system * 100)}%</td>
                                  <td className="py-1.5 text-right">{Math.round(h.rate_general * 100)}%</td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      )}
                    </div>
                  );
                })()}
              </>
            )}
          </>
        )}

        {/* ══════════════════════════════════════
            請求書タブ
        ══════════════════════════════════════ */}
        {tab === 'invoice' && (
          <div className="bg-white rounded-2xl border border-stone-200 p-5 space-y-4">
            <h3 className="text-sm font-semibold text-stone-700">請求書ダウンロード</h3>

            {/* 月度選択 */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">月度</label>
              <select
                value={invoicePeriod}
                onChange={(e) => setInvoicePeriod(e.target.value)}
                className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                {PERIODS.map((p) => (
                  <option key={p.value} value={p.value}>{p.label}</option>
                ))}
              </select>
            </div>

            {/* センター選択 */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">センター</label>
              <select
                value={invoiceFcUserId}
                onChange={(e) => setInvoiceFcUserId(e.target.value)}
                className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                <option value="">-- センターを選択 --</option>
                {invoiceUsers
                  .filter((u) => u.active && u.fc_name !== '朝霧ヤマノ')
                  .map((u) => (
                    <option key={u.id} value={String(u.id)}>
                      {u.fc_name}
                    </option>
                  ))}
              </select>
            </div>

            {/* ダウンロードボタン */}
            <button
              onClick={downloadInvoice}
              disabled={!invoiceFcUserId || invoiceLoading}
              className="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-3 text-sm font-semibold transition-colors disabled:opacity-40"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              {invoiceLoading ? 'Excel生成中...' : 'Excelダウンロード'}
            </button>

            <p className="text-xs text-stone-400">
              FC：センター受注システムの注文データ（FC価格）<br/>
              BC：ヤマノ注文履歴（BC価格。商品コード突き合わせ）
            </p>
          </div>
        )}

        {/* ══════════════════════════════════════
            ヤマノ実績タブ
        ══════════════════════════════════════ */}
        {tab === 'yamano' && (
          <>
            {/* 年度フィルター */}
            <div className="flex items-center gap-2">
              <label className="text-xs text-stone-500 shrink-0">年度</label>
              <select
                value={yamanoFiscalYear}
                onChange={(e) => setYamanoFiscalYear(Number(e.target.value))}
                className="flex-1 border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                {fiscalYearList().map((fy) => (
                  <option key={fy} value={fy}>{fy}年度</option>
                ))}
              </select>
            </div>

            {/* 月度フィルター（選択年度内のみ）+ CSV */}
            <div className="flex items-center gap-2">
              <select
                value={yamanoPeriod}
                onChange={(e) => setYamanoPeriod(e.target.value)}
                className="flex-1 border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                {periodsInFY(yamanoFiscalYear).map((p) => (
                  <option key={p.value} value={p.value}>{p.label}</option>
                ))}
              </select>
              <button
                onClick={() => loadYamano(yamanoPeriod)}
                disabled={yamanoLoading}
                className="shrink-0 border border-stone-200 bg-white rounded-xl px-3 py-2 text-sm text-stone-600 hover:bg-stone-50 disabled:opacity-40"
              >
                {yamanoLoading ? '読込中...' : '更新'}
              </button>
              <button
                onClick={downloadYamanoCsv}
                disabled={yamanoCsvLoading || !yamanoData}
                className="shrink-0 flex items-center gap-1.5 border border-stone-200 bg-white rounded-xl px-3 py-2 text-sm text-stone-600 hover:bg-stone-50 disabled:opacity-40"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                {yamanoCsvLoading ? '生成中...' : 'CSV'}
              </button>
            </div>

            {yamanoLoading ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">読み込み中...</p>
              </div>
            ) : !yamanoData ? (
              <div className="bg-white rounded-2xl border border-stone-200 p-6 text-center">
                <p className="text-stone-400 text-sm">データがありません</p>
              </div>
            ) : (
              <>
                {/* 月度サマリーボックス */}
                {yamanoData.summary && yamanoData.centers.length > 0 && (
                  <div className="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                    <div className="px-4 py-3 border-b border-stone-100 bg-stone-50">
                      <h3 className="text-sm font-semibold text-stone-700">月度サマリー（全センター合計）</h3>
                    </div>
                    <table className="w-full text-sm">
                      <tbody className="divide-y divide-stone-100">
                        <tr>
                          <td className="px-4 py-2.5 text-stone-600">システム商品</td>
                          <td className="px-4 py-2.5 text-right text-stone-800 font-medium">
                            ¥{yamanoData.summary.breakdown.system.toLocaleString()}
                          </td>
                        </tr>
                        <tr>
                          <td className="px-4 py-2.5 text-stone-600">一般商品</td>
                          <td className="px-4 py-2.5 text-right text-stone-800 font-medium">
                            ¥{yamanoData.summary.breakdown.general.toLocaleString()}
                          </td>
                        </tr>
                        <tr>
                          <td className="px-4 py-2.5 text-stone-600">別口（CS・販促・備品）</td>
                          <td className="px-4 py-2.5 text-right text-stone-800 font-medium">
                            ¥{yamanoData.summary.breakdown.bettaguchi.toLocaleString()}
                          </td>
                        </tr>
                        <tr className="bg-amber-50 border-t border-amber-100">
                          <td className="px-4 py-2.5 text-amber-800 font-semibold">システム＋一般 計</td>
                          <td className="px-4 py-2.5 text-right text-amber-800 font-bold">
                            ¥{yamanoData.summary.breakdown.system_general.toLocaleString()}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                )}

                {/* センター別集計テーブル */}
                <div className="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                  <div className="px-4 py-3 border-b border-stone-100">
                    <h3 className="text-sm font-semibold text-stone-700">センター別集計</h3>
                  </div>
                  {yamanoData.centers.length === 0 ? (
                    <p className="px-4 py-3 text-sm text-stone-400">この月度の実績はありません</p>
                  ) : (
                    <table className="w-full text-sm">
                      <thead className="bg-stone-50 border-b border-stone-100">
                        <tr>
                          <th className="px-4 py-2 text-left text-xs text-stone-500 font-medium">センター名</th>
                          <th className="px-4 py-2 text-right text-xs text-stone-500 font-medium">件数</th>
                          <th className="px-4 py-2 text-right text-xs text-stone-500 font-medium">合計（税別）</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-stone-100">
                        {yamanoData.centers.map((c) => {
                          const isExpanded = expandedCenters.has(c.fc_user_id);
                          return (
                            <React.Fragment key={c.fc_user_id}>
                              <tr
                                onClick={() => toggleCenter(c.fc_user_id)}
                                style={{ cursor: 'pointer' }}
                                className="hover:bg-stone-50 active:bg-stone-100"
                              >
                                <td className="px-4 py-2.5 text-stone-800 font-medium">
                                  <span className="mr-1.5 text-stone-400 text-xs">{isExpanded ? '▼' : '▶'}</span>
                                  {c.fc_name}
                                </td>
                                <td className="px-4 py-2.5 text-right text-stone-600">{c.order_count}件</td>
                                <td className="px-4 py-2.5 text-right text-stone-800 font-semibold">
                                  ¥{c.total_pretax.toLocaleString()}
                                </td>
                              </tr>
                              {isExpanded && (
                                <>
                                  <tr className="bg-stone-50/60">
                                    <td className="pl-8 pr-4 py-1.5 text-xs text-stone-500">└ システム商品</td>
                                    <td></td>
                                    <td className="px-4 py-1.5 text-right text-xs text-stone-600">¥{c.breakdown.system.toLocaleString()}</td>
                                  </tr>
                                  <tr className="bg-stone-50/60">
                                    <td className="pl-8 pr-4 py-1.5 text-xs text-stone-500">└ 一般商品</td>
                                    <td></td>
                                    <td className="px-4 py-1.5 text-right text-xs text-stone-600">¥{c.breakdown.general.toLocaleString()}</td>
                                  </tr>
                                  <tr className="bg-stone-50/60">
                                    <td className="pl-8 pr-4 py-1.5 text-xs text-stone-500">└ 別口（CS・販促）</td>
                                    <td></td>
                                    <td className="px-4 py-1.5 text-right text-xs text-stone-600">¥{c.breakdown.bettaguchi.toLocaleString()}</td>
                                  </tr>
                                  <tr className="bg-amber-50/60 border-b border-amber-100">
                                    <td className="pl-8 pr-4 py-1.5 text-xs text-amber-700 font-medium">└ システム＋一般 計</td>
                                    <td></td>
                                    <td className="px-4 py-1.5 text-right text-xs text-amber-700 font-semibold">¥{c.breakdown.system_general.toLocaleString()}</td>
                                  </tr>
                                </>
                              )}
                            </React.Fragment>
                          );
                        })}
                      </tbody>
                      <tfoot className="bg-amber-50 border-t-2 border-amber-200">
                        <tr>
                          <td className="px-4 py-2.5 text-amber-800 font-bold">朝霧ヤマノ合計</td>
                          <td className="px-4 py-2.5 text-right text-amber-700 font-semibold">
                            {yamanoData.summary.count}件
                          </td>
                          <td className="px-4 py-2.5 text-right text-amber-800 font-bold">
                            ¥{yamanoData.summary.total.toLocaleString()}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  )}
                </div>

                {/* インライン紐づけ/修正UI（共通） */}
                {(() => {
                  const MappingPanel = () => (
                    <div className="mt-3 p-3 bg-amber-50 rounded-xl space-y-2 border border-amber-200">
                      <p className="text-xs text-amber-700 font-medium">センターを選択してください</p>
                      <select
                        value={selectedFcUserId}
                        onChange={(e) => setSelectedFcUserId(e.target.value)}
                        className="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                      >
                        <option value="">-- センターを選択 --</option>
                        {(yamanoData.fc_users || []).map((fu) => (
                          <option key={fu.id} value={String(fu.id)}>
                            {fu.fc_name}（{fu.center_type}）
                          </option>
                        ))}
                      </select>
                      <div className="flex gap-2">
                        <button
                          onClick={() => { setMappingOrderId(null); setSelectedFcUserId(''); }}
                          className="flex-1 border border-stone-200 text-stone-600 rounded-lg py-2 text-xs font-medium hover:bg-stone-50 transition-colors"
                        >
                          キャンセル
                        </button>
                        <button
                          onClick={submitMapping}
                          disabled={!selectedFcUserId || mappingLoading}
                          className="flex-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg py-2 text-xs font-semibold disabled:opacity-50 transition-colors"
                        >
                          {mappingLoading ? '登録中...' : '確定'}
                        </button>
                      </div>
                    </div>
                  );

                  return (
                    <>
                      {/* 未分類一覧 */}
                      {yamanoData.unclassified.length > 0 && (
                        <div className="bg-white rounded-2xl border border-orange-200 overflow-hidden">
                          <div className="px-4 py-3 border-b border-orange-100 bg-orange-50">
                            <h3 className="text-sm font-semibold text-orange-700">
                              未分類 {yamanoData.unclassified.length}件
                            </h3>
                            <p className="text-xs text-orange-500 mt-0.5">センターに紐づけてください</p>
                          </div>
                          <div className="divide-y divide-stone-100">
                            {yamanoData.unclassified.map((u) => {
                              const isCancelled = u.status.includes('キャンセル');
                              return (
                              <div key={u.yamano_order_id} className="px-4 py-3">
                                <div className="flex items-start justify-between gap-3">
                                  <div className="flex-1 min-w-0">
                                    <p className="text-xs font-mono text-stone-400">{u.yamano_order_id}</p>
                                    <p className="text-sm font-medium text-stone-800 mt-0.5">{u.shipping_name}</p>
                                    <p className="text-xs text-stone-400 mt-0.5">
                                      {u.order_date}　¥{u.total_pretax.toLocaleString()}（税別）
                                    </p>
                                    {isCancelled && (
                                      <span className="inline-block mt-1 text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded-full font-medium">
                                        キャンセル
                                      </span>
                                    )}
                                  </div>
                                  {mappingOrderId !== u.yamano_order_id && (
                                    <div className="flex gap-1.5 shrink-0">
                                      {isCancelled ? (
                                        <button
                                          onClick={() => deleteYamanoOrder(u.yamano_order_id)}
                                          disabled={deletingOrderId === u.yamano_order_id}
                                          className="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors disabled:opacity-50"
                                        >
                                          {deletingOrderId === u.yamano_order_id ? '削除中...' : '削除'}
                                        </button>
                                      ) : (
                                        <button
                                          onClick={() => { setMappingOrderId(u.yamano_order_id); setSelectedFcUserId(''); }}
                                          className="text-xs px-3 py-1.5 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 font-medium transition-colors"
                                        >
                                          紐づける
                                        </button>
                                      )}
                                    </div>
                                  )}
                                </div>
                                {mappingOrderId === u.yamano_order_id && <MappingPanel />}
                              </div>
                              );
                            })}
                          </div>
                        </div>
                      )}

                      {yamanoData.unclassified.length === 0 && yamanoData.centers.length > 0 && (
                        <div className="bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                          <p className="text-sm text-green-700 font-medium">未分類の注文はありません</p>
                        </div>
                      )}

                      {/* 分類済み一覧（修正ボタン） */}
                      {(yamanoData.classified || []).length > 0 && (
                        <div className="bg-white rounded-2xl border border-stone-200 overflow-hidden">
                          <div className="px-4 py-3 border-b border-stone-100 bg-stone-50">
                            <h3 className="text-sm font-semibold text-stone-700">
                              分類済み {yamanoData.classified.length}件
                            </h3>
                          </div>
                          <div className="divide-y divide-stone-100">
                            {yamanoData.classified.map((c) => {
                              const isCancelled = c.status.includes('キャンセル');
                              return (
                              <div key={c.yamano_order_id} className="px-4 py-3">
                                <div className="flex items-start justify-between gap-3">
                                  <div className="flex-1 min-w-0">
                                    <p className="text-xs font-mono text-stone-400">{c.yamano_order_id}</p>
                                    <p className="text-sm font-medium text-stone-800 mt-0.5">{c.shipping_name}</p>
                                    <p className="text-xs text-stone-400 mt-0.5">
                                      {c.order_date}　¥{c.total_pretax.toLocaleString()}（税別）
                                    </p>
                                    <div className="flex gap-1.5 mt-1 flex-wrap">
                                      <span className="text-xs px-2 py-0.5 bg-stone-100 text-stone-600 rounded-full">
                                        {c.fc_name}
                                      </span>
                                      {isCancelled && (
                                        <span className="text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded-full font-medium">
                                          キャンセル
                                        </span>
                                      )}
                                    </div>
                                  </div>
                                  {mappingOrderId !== c.yamano_order_id && (
                                    <div className="flex gap-1.5 shrink-0">
                                      {isCancelled && (
                                        <button
                                          onClick={() => deleteYamanoOrder(c.yamano_order_id)}
                                          disabled={deletingOrderId === c.yamano_order_id}
                                          className="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors disabled:opacity-50"
                                        >
                                          {deletingOrderId === c.yamano_order_id ? '削除中...' : '削除'}
                                        </button>
                                      )}
                                      <button
                                        onClick={() => { setMappingOrderId(c.yamano_order_id); setSelectedFcUserId(String(c.fc_user_id)); }}
                                        className="text-xs px-3 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 font-medium transition-colors"
                                      >
                                        修正
                                      </button>
                                    </div>
                                  )}
                                </div>
                                {mappingOrderId === c.yamano_order_id && <MappingPanel />}
                              </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </>
                  );
                })()}
              </>
            )}
          </>
        )}

      </div>

      {/* ══════════════════════════════════════
          商品追加・編集モーダル
      ══════════════════════════════════════ */}
      {showProdModal && (
        <div
          className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          onClick={(e) => { if (e.target === e.currentTarget) setShowProdModal(false); }}
        >
          <div className="bg-white rounded-2xl w-full max-w-md p-5 space-y-3 max-h-[90vh] overflow-y-auto">
            {/* タイトル */}
            <div className="flex items-center justify-between">
              <h3 className="font-semibold text-stone-800">
                {editingProd ? '商品編集' : '商品追加'}
              </h3>
              <button
                onClick={() => setShowProdModal(false)}
                className="text-stone-400 hover:text-stone-600 text-xl leading-none"
              >
                ✕
              </button>
            </div>

            {/* コード */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">コード *</label>
              <input
                type="text"
                value={prodForm.code}
                onChange={(e) => { if (!editingProd) setProdForm((f) => ({ ...f, code: e.target.value })); }}
                readOnly={!!editingProd}
                placeholder="例: 0038"
                className={`w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 ${editingProd ? 'bg-stone-50 text-stone-500' : ''}`}
              />
            </div>

            {/* 商品名 */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">商品名 *</label>
              <input
                type="text"
                value={prodForm.name}
                onChange={(e) => setProdForm((f) => ({ ...f, name: e.target.value }))}
                placeholder="例: ヤマノ肌 クレンジングミルク"
                className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            {/* カテゴリ */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">カテゴリ *</label>
              <select
                value={prodForm.category}
                onChange={(e) => setProdForm((f) => ({ ...f, category: e.target.value }))}
                className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                {PRODUCT_CATEGORIES.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
            </div>

            {/* 価格3種 */}
            {([
              ['price_hansha', '販社価格（price_hansha）'],
              ['price_bc',     'BC価格（price_bc）'],
              ['price_fc',     'FC価格（price_fc）'],
            ] as [keyof ProdForm, string][]).map(([key, label]) => (
              <div key={key}>
                <label className="block text-xs text-stone-500 mb-1">{label}</label>
                <input
                  type="number"
                  min={0}
                  value={prodForm[key]}
                  onChange={(e) => setProdForm((f) => ({ ...f, [key]: e.target.value }))}
                  placeholder="0"
                  className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                />
              </div>
            ))}

            {/* 消費税率 */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">消費税率</label>
              <select
                value={prodForm.tax_rate}
                onChange={(e) => setProdForm((f) => ({ ...f, tax_rate: e.target.value }))}
                className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                <option value="0.10">10%（標準税率）</option>
                <option value="0.08">8%（軽減税率）</option>
              </select>
            </div>

            {/* 備考 */}
            <div>
              <label className="block text-xs text-stone-500 mb-1">備考（任意）</label>
              <input
                type="text"
                value={prodForm.note}
                onChange={(e) => setProdForm((f) => ({ ...f, note: e.target.value }))}
                placeholder="任意"
                className="w-full border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            {prodError && <p className="text-xs text-red-600">{prodError}</p>}

            <div className="flex gap-2 pt-1">
              <button
                onClick={() => setShowProdModal(false)}
                className="flex-1 border border-stone-200 text-stone-600 rounded-xl py-2.5 text-sm font-medium hover:bg-stone-50 transition-colors"
              >
                キャンセル
              </button>
              <button
                onClick={saveProd}
                disabled={prodSaving}
                className="flex-1 bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-2.5 text-sm font-semibold disabled:opacity-50 transition-colors"
              >
                {prodSaving ? '保存中...' : '保存する'}
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}
