# 修正指示：yamano-orders.php のカテゴリ分類を実際のDB値に合わせる

## 背景

`products.category` の実際の値を確認したところ、以下の9種類だった。
実装済みのCASE文が想定値（'システム'/'CS'/'販促'/'備品'）と異なるため修正が必要。

| 実際のcategory値 | 分類 |
|----------------|------|
| スキンケア | system（システム商品） |
| エステキープ | system（システム商品） |
| キット | system（システム商品） |
| ボディ＆ヘアケア | system（システム商品） |
| メイク | system（システム商品） |
| 健康食品 | system（システム商品・税率8%） |
| 一般 | general（一般商品） |
| CS商品 | bettaguchi（別口） |
| 販促品 | bettaguchi（別口） |

---

## 修正ファイル

`api/admin/yamano-orders.php`

---

## 修正内容

ファイル内にCASE文が2箇所ある（センター別内訳クエリ・月度全体サマリークエリ）。
両方を以下のCASE文に差し替える。

### 修正前（現在）
```sql
CASE
  WHEN p.category = 'システム' THEN 'system'
  WHEN p.category = '一般'     THEN 'general'
  WHEN p.category IN ('CS','販促','備品') THEN 'bettaguchi'
  ELSE 'unknown'
END AS category_group
```

### 修正後
```sql
CASE
  WHEN p.category IN ('スキンケア','エステキープ','キット','ボディ＆ヘアケア','メイク','健康食品') THEN 'system'
  WHEN p.category = '一般' THEN 'general'
  WHEN p.category IN ('CS商品','販促品') THEN 'bettaguchi'
  ELSE 'unknown'
END AS category_group
```

---

## 修正後の確認

修正後、ブラウザの管理画面（ヤマノ実績タブ）で2026年6月度を選択して以下を確認する。

- 月度サマリーのシステム・一般・別口に金額が表示されること
- センター行をクリックして内訳4行が表示されること
- unknown が0、または極小であること（unknownが大きい場合は在りさんに報告）

---

## 作業順序

1. `api/admin/yamano-orders.php` のCASE文を2箇所修正
2. WinSCPでサーバーにアップ（`/public_html/fc-order.asagiriyamano.jp/api/admin/yamano-orders.php`）
3. ブラウザで動作確認
4. 完了報告（unknownの有無も一緒に報告）
