# ヤマノ実績 カテゴリ別内訳表示 実装指示

対象ファイル：
- `api/admin/yamano-orders.php`
- `src/app/admin/` 内のヤマノ実績画面コンポーネント

---

## 完成イメージ

```
朝霧ヤマノ合計    10件    ¥1,594,090

┌─────────────────────────────────┐
│ 月度サマリー（全センター合計）       │
│  システム商品        ¥x,xxx,xxx   │
│  一般商品            ¥  xxx,xxx   │
│  別口（CS・販促・備品）¥  xxx,xxx  │
│  システム＋一般 計   ¥x,xxx,xxx   │
└─────────────────────────────────┘

センター別集計
西舞子ゆかり    6件    ¥1,098,490  ▼（クリックで展開）
  └ システム商品        ¥ 800,000
  └ 一般商品            ¥ 150,000
  └ 別口（CS・販促）    ¥ 148,490
  └ システム＋一般 計   ¥ 950,000

朝霧ヤマノ      2件    ¥  202,000  ▼（クリックで展開）
  └ ...
```

---

## 1. APIの修正（api/admin/yamano-orders.php）

### カテゴリ分類ルール

`products.category` の値で以下のように分類する：

| 区分 | categoryの値 |
|------|------------|
| システム | 'システム' |
| 一般 | '一般' |
| 別口 | 'CS'、'販促'、'備品' |
| 不明 | NULLまたは products にコードが存在しない場合 |

### センター別集計エンドポイントに内訳を追加

既存のセンター別集計クエリに加えて、カテゴリ別内訳を取得するSQLを追加する。

```sql
-- カテゴリ別内訳（センター単位）
SELECT
  yo.fc_user_id,
  CASE
    WHEN p.category = 'システム' THEN 'system'
    WHEN p.category = '一般'     THEN 'general'
    WHEN p.category IN ('CS','販促','備品') THEN 'bettaguchi'
    ELSE 'unknown'
  END AS category_group,
  SUM(oi.subtotal) AS subtotal
FROM yamano_orders yo
JOIN yamano_order_items oi ON oi.yamano_order_id = yo.id
LEFT JOIN products p ON p.code = oi.product_code
WHERE yo.month_period = :month_period
  AND yo.fc_user_id IS NOT NULL
GROUP BY yo.fc_user_id, category_group
```

### 月度サマリー（全センター合計）を追加

```sql
-- 月度全体のカテゴリ別合計
SELECT
  CASE
    WHEN p.category = 'システム' THEN 'system'
    WHEN p.category = '一般'     THEN 'general'
    WHEN p.category IN ('CS','販促','備品') THEN 'bettaguchi'
    ELSE 'unknown'
  END AS category_group,
  SUM(oi.subtotal) AS subtotal
FROM yamano_orders yo
JOIN yamano_order_items oi ON oi.yamano_order_id = yo.id
LEFT JOIN products p ON p.code = oi.product_code
WHERE yo.month_period = :month_period
  AND yo.fc_user_id IS NOT NULL
GROUP BY category_group
```

### レスポンスのJSON構造

```json
{
  "summary": {
    "total": 1594090,
    "count": 10,
    "breakdown": {
      "system":      800000,
      "general":     150000,
      "bettaguchi":  644090,
      "system_general": 950000
    }
  },
  "centers": [
    {
      "fc_user_id": 22,
      "fc_name": "西舞子ゆかり",
      "count": 6,
      "total": 1098490,
      "breakdown": {
        "system":      800000,
        "general":     150000,
        "bettaguchi":  148490,
        "system_general": 950000
      }
    }
  ],
  "unclassified": []
}
```

`system_general` はPHP側で `system + general` を計算してセットする。

---

## 2. フロントエンドの修正

### 月度サマリーボックスの追加

センター別集計テーブルの上に、月度全体のサマリーを表示する。

```tsx
{summary && (
  <div className="summary-box">
    <h3>月度サマリー（全センター合計）</h3>
    <table>
      <tbody>
        <tr><td>システム商品</td><td>¥{summary.breakdown.system.toLocaleString()}</td></tr>
        <tr><td>一般商品</td><td>¥{summary.breakdown.general.toLocaleString()}</td></tr>
        <tr><td>別口（CS・販促・備品）</td><td>¥{summary.breakdown.bettaguchi.toLocaleString()}</td></tr>
        <tr className="total-row"><td>システム＋一般 計</td><td>¥{summary.breakdown.system_general.toLocaleString()}</td></tr>
      </tbody>
    </table>
  </div>
)}
```

### センター行クリックで内訳展開

- 各センター行をクリックで `expanded` 状態をトグル
- 展開時に内訳4行（システム・一般・別口・システム＋一般計）を表示
- 展開インジケーター：▶ / ▼

```tsx
const [expandedCenters, setExpandedCenters] = useState<Set<number>>(new Set());

const toggleCenter = (id: number) => {
  setExpandedCenters(prev => {
    const next = new Set(prev);
    next.has(id) ? next.delete(id) : next.add(id);
    return next;
  });
};

// センター行
<tr onClick={() => toggleCenter(center.fc_user_id)} style={{cursor:'pointer'}}>
  <td>{expandedCenters.has(center.fc_user_id) ? '▼' : '▶'} {center.fc_name}</td>
  <td>{center.count}件</td>
  <td>¥{center.total.toLocaleString()}</td>
</tr>

// 展開行
{expandedCenters.has(center.fc_user_id) && (
  <>
    <tr className="breakdown-row"><td>　└ システム商品</td><td></td><td>¥{center.breakdown.system.toLocaleString()}</td></tr>
    <tr className="breakdown-row"><td>　└ 一般商品</td><td></td><td>¥{center.breakdown.general.toLocaleString()}</td></tr>
    <tr className="breakdown-row"><td>　└ 別口（CS・販促）</td><td></td><td>¥{center.breakdown.bettaguchi.toLocaleString()}</td></tr>
    <tr className="breakdown-row total"><td>　└ システム＋一般 計</td><td></td><td>¥{center.breakdown.system_general.toLocaleString()}</td></tr>
  </>
)}
```

---

## 3. 注意事項

- `yamano_order_items.product_code` が `products.code` に存在しない商品は `unknown` 扱いになる。unknown は別口に含めず別途表示するか、在りさんに確認すること。
- 未分類（fc_user_id IS NULL）の注文は月度サマリーに含めない。
- `system_general` の計算はPHP側で行い、フロントでは計算しない。

---

## 作業順序

1. `api/admin/yamano-orders.php` を修正してAPIレスポンスにbreakdownを追加
2. ローカルで `curl` またはブラウザでAPIレスポンスを確認
3. フロントエンド修正（サマリーボックス追加 → センター行展開UI）
4. `npm run build` → WinSCPでアップ
5. 動作確認
