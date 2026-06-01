# index.html 変更箇所チェックリスト

## テンプレート (sales.asagiriyamano.jp/index.html) からの差分

以下の3箇所を変更してください。
ファイルをエディタ（VSCode 等）で開き、Ctrl+H で置換すると確実です。

---

### 1. 店舗名の変更

**置換前**
```
朝霧店
```
**置換後**
```
クレスティサロンWiLL
```

ページタイトル（`<title>` タグ）、h1/h2 見出し、フッター等に複数箇所あります。
「全て置換」で対応してください。

---

### 2. ALLOWED_EMAILS の変更

**置換前**（例）
```js
const ALLOWED_EMAILS = [
  'xxxxx@gmail.com',
  'yyyyy@gmail.com',
  // ...
];
```
**置換後**
```js
const ALLOWED_EMAILS = [
  'katsuhiko.arie@gmail.com',
];
```

---

### 3. API エンドポイント URL の変更

**置換前**
```
https://sales.asagiriyamano.jp/api/
```
**置換後**
```
https://demo-will.asagiriyamano.jp/api/
```

fetch() 呼び出しや定数定義部分に複数箇所ある場合は全て変更してください。

---

## 変更後の確認ポイント

- [ ] ページタイトルが「クレスティサロンWiLL」になっている
- [ ] ALLOWED_EMAILS が katsuhiko.arie@gmail.com のみになっている
- [ ] API URLが demo-will.asagiriyamano.jp を向いている
- [ ] 朝霧店 / sales.asagiriyamano.jp の文字列が残っていない
  （Ctrl+F で検索して確認）
