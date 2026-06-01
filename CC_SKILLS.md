# Claude Code Agent Skills
## クロコ（Claude Code）専用スキルズ

講座資料・ドキュメント自動生成  
在りさんのAIツール使いこなし講座プロジェクトから抽出した、実戦検証済みスキル集。

---

## 作業環境

| 項目 | 値 |
|------|-----|
| 作業ディレクトリ | `/home/claude/` |
| 出力先 | `/mnt/user-data/outputs/` |
| スキルファイル | `/mnt/skills/public/pptx/` および `/mnt/skills/public/docx/` |
| 使用ツール | Node.js（pptxgenJS・docx-js）、Python（LibreOffice・pdftoppm） |

---

## ブランド設定（在りさんのプロジェクト共通）

| 項目 | 値 |
|------|-----|
| ネイビー | `#1E2761` |
| ゴールド | `#D4A843` |
| フォント（スライド） | `Meiryo` |
| フォント（docx） | `Arial` |
| A4コンテンツ幅 | `9746 DXA`（マージン上下左右1080） |

---

## SKILL CC-01　pptxgenJS スライド自動生成

### セットアップ確認

```bash
# インストール確認
npm list -g pptxgenjs

# 入っていない場合
npm install -g pptxgenjs
```

### クロコへの指示テンプレート

```
slides.js というNode.jsスクリプトを /home/claude/ に作成してください。
pptxgenJS で以下のスライドを生成し、/mnt/user-data/outputs/ にコピーしてください。

【スライド仕様】
- レイアウト：LAYOUT_16x9
- デザインカラー：ネイビー #1E2761 × ゴールド #D4A843
- フォント：Meiryo（日本語）
- 各スライドに視覚要素（カード・矢印・フロー図）を必ず入れる
- テキストのみのスライドにしない

【枚数・内容】
（ここにスライド構成を書く）
```

### 必須ルール（pptxgenJS）

| NG | OK |
|----|-----|
| `color: '#FF0000'` | `color: 'FF0000'` （#を付けない） |
| `shadow: { color: '00000020' }` | `shadow: { color: '000000', opacity: 0.12 }` |
| shadowオブジェクトを使い回す | `makeShadow()` 関数で毎回新規生成 |
| `ROUNDED_RECTANGLE` にアクセント線を重ねる | `RECTANGLE` を使う |
| `'•' + text` | `bullet: true` を使う |

### shadowの正しい書き方

```javascript
// ✅ 毎回新しいオブジェクトを生成する関数にする
const ms = () => ({
  type: 'outer', blur: 6, offset: 2,
  color: '000000', opacity: 0.09, angle: 135
});

// 使う時は毎回 ms() を呼ぶ
s.addShape(pres.shapes.RECTANGLE, {
  x: 0.5, y: 1.0, w: 3, h: 1,
  fill: { color: 'FFFFFF' },
  shadow: ms()  // ← 毎回 ms() で新規生成
});
```

### QAフロー（スライド生成後）

```bash
# PPTX → PDF
python /mnt/skills/public/pptx/scripts/office/soffice.py \
  --headless --convert-to pdf 'ファイル名.pptx'

# PDF → JPG（古いファイルを先に削除）
rm -f slide-*.jpg
pdftoppm -jpeg -r 150 'ファイル名.pdf' slide

# 画像パスを確認
ls -1 "$PWD"/slide-*.jpg

# 問題なければ outputs にコピー
cp 'ファイル名.pptx' '/mnt/user-data/outputs/ファイル名.pptx'
```

> **注意：** 10枚以上のスライドは `slide-01.jpg`（ゼロパディング）になる

---

## SKILL CC-02　docx-js ドキュメント自動生成

### セットアップ確認

```bash
npm list -g docx
# docx@9.6.1 が出ればOK

# 入っていない場合
npm install -g docx
```

### クロコへの指示テンプレート

```
以下の内容で docx ファイルを作成してください。

【ファイル名】/home/claude/○○.docx
【ページ設定】A4・マージン上下左右 1080 DXA（0.75インチ）
【デザイン】ネイビー #1E2761 × ゴールド #D4A843・フォント Arial

【構成】
（ここに章立てと内容を書く）

生成後：
1. LibreOfficeでPDFに変換してQA確認
2. 問題なければ /mnt/user-data/outputs/ にコピー
3. present_files で共有
```

### 必須ルール（Google Docs互換）

| NG | OK | 理由 |
|----|-----|------|
| TextRun に `\n` を使う | 行ごとに別 `Paragraph` にする | Google Docsで縦書きになる |
| 単一列テーブル | 段落の `shading` + `border` で代替 | セル幅が崩れる |
| `WidthType.PERCENTAGE` | `WidthType.DXA` を使う | Google Docsで崩れる |
| `ShadingType.SOLID` | `ShadingType.CLEAR` を使う | 黒背景になる |
| columnWidths の合計 ≠ width.size | 必ず一致させる | テーブルが崩れる |

### コンテンツ幅の計算

```
A4用紙幅:   11906 DXA
マージン左右: 1080 × 2 = 2160 DXA
コンテンツ幅: 11906 - 2160 = 9746 DXA

2列均等: columnWidths: [4873, 4873]  // 合計 9746
3列(2:3:4): columnWidths: [2165, 3248, 4333]  // 合計 9746
```

### 台本ブロックの実装（テーブルを使わない）

```javascript
// ✅ 行ごとに別Paragraph・shadingとborderで枠を表現
function script(lines) {
  return lines.map((line, i) => new Paragraph({
    children: [new TextRun({
      text: line, size: 22, font: 'Arial', color: '1A1F36'
    })],
    shading: { type: ShadingType.CLEAR, fill: 'F3F4F6' },
    border: {
      left:   { style: BorderStyle.SINGLE, size: 12, color: '1E2761', space: 8 },
      top:    i === 0
                ? { style: BorderStyle.SINGLE, size: 1, color: 'DDDDDD' }
                : { style: BorderStyle.NONE },
      bottom: i === lines.length - 1
                ? { style: BorderStyle.SINGLE, size: 1, color: 'DDDDDD' }
                : { style: BorderStyle.NONE },
      right:  { style: BorderStyle.SINGLE, size: 1, color: 'DDDDDD' }
    },
    indent: { left: 160 },
    spacing: {
      before: i === 0 ? 4 : 0,
      after: i === lines.length - 1 ? 4 : 0
    }
  }));
}

// 使い方（スプレッド展開必須）
...script(['一行目', '二行目', '三行目']),
```

---

## SKILL CC-03　Google Docs 互換 docx 修正

### 崩れの診断チェックリスト

```bash
# TextRun に \n が含まれていないか
grep -n '\\n' script.js

# 単一列テーブルを使っていないか（columnWidths が1要素）
grep -n 'columnWidths:\[9' script.js

# ShadingType.SOLID を使っていないか
grep -n 'SOLID' script.js

# WidthType.PERCENTAGE を使っていないか
grep -n 'PERCENTAGE' script.js
```

### 縦書きバグの修正手順

```bash
# STEP 1: \n を含む TextRun を全て探す
grep -n '\\\\n' script.js

# STEP 2: 単一列テーブルを段落ベースに書き換える
# columnWidths:[9xxx] のテーブルを script() 関数に置き換える

# STEP 3: テーブル幅を全て確認・修正
grep -n 'columnWidths\|size.*9[0-9]\{2,\}' script.js

# STEP 4: 再生成してQA確認
node script.js
python /mnt/skills/public/pptx/scripts/office/soffice.py \
  --headless --convert-to pdf output.docx
rm -f page-*.jpg && pdftoppm -jpeg -r 130 output.pdf page
ls page-*.jpg
```

### テキスト一括置換（sed）

```bash
# 単純な文字列置換
sed -i 's/置換前/置換後/g' スクリプト名.js

# 複数の置換を一括実行
sed -i \
  's/くろちゃん/Claude/g; \
   s/クロコ/Claude Code/g' スクリプト名.js

# 置換後に確認
grep -n 'Claude' スクリプト名.js | head -20

# 再生成
node スクリプト名.js
```

---

## SKILL CC-04　LibreOffice PDF変換 ＆ QA確認

### PPTX の QAフロー

```bash
# 1. sofficeスクリプトのパスを確認
find /mnt/skills -name 'soffice.py' 2>/dev/null | head -3

# 2. PPTX → PDF
python /mnt/skills/public/pptx/scripts/office/soffice.py \
  --headless --convert-to pdf 'ファイル名.pptx'

# 3. PDF → JPG
rm -f slide-*.jpg
pdftoppm -jpeg -r 150 'ファイル名.pdf' slide

# 4. 画像パスを確認してviewツールで確認
ls -1 "$PWD"/slide-*.jpg
```

### docx の QAフロー

```bash
# 1. docx → PDF
python /mnt/skills/public/pptx/scripts/office/soffice.py \
  --headless --convert-to pdf 'ファイル名.docx'

# 2. PDF → JPG
rm -f page-*.jpg
pdftoppm -jpeg -r 130 'ファイル名.pdf' page
ls page-*.jpg | wc -l  # ページ数確認
```

### 確認ポイント

- テキストが横書きか（縦書きになっていないか）
- テーブルが崩れていないか
- プロンプト枠・台本枠が正しく表示されているか
- フォントが正しく表示されているか

### outputs への最終出力

```bash
# PPTX
cp 'ファイル名.pptx' '/mnt/user-data/outputs/ファイル名.pptx'

# docx
cp 'ファイル名.docx' '/mnt/user-data/outputs/ファイル名.docx'

# present_files でくろちゃん（Claude）に引き渡す
```

---

## SKILL CC-05　ファイル一括修正

### テキスト・色・サイズの一括置換

```bash
# 色を変更
sed -i 's/color: "3B82C4"/color: "2E8B57"/g' slides.js

# テーブル幅を一括変更（9360 → 9746）
sed -i 's/size:9360, type:WidthType\.DXA/size:9746, type:WidthType.DXA/g' script.js
sed -i 's/columnWidths:\[9360\]/columnWidths:[9746]/g' script.js

# action/warn の2列テーブル幅修正
sed -i 's/columnWidths:\[400, 8960\]/columnWidths:[400, 9346]/g' script.js
sed -i 's/size:8960, type:WidthType\.DXA/size:9346, type:WidthType.DXA/g' script.js

# 変更確認
grep -n '9746\|9346' script.js | head -10
```

### 作業前の安全確認

```bash
# ファイル一覧確認
ls -la /home/claude/*.js /home/claude/*.pptx /home/claude/*.docx 2>/dev/null

# 修正前にバックアップ
cp slides.js slides.js.bak

# 失敗した場合に元に戻す
cp slides.js.bak slides.js

# outputs のファイルを確認
ls /mnt/user-data/outputs/
```

---

## SKILL CC-06　マスタープロンプト

> Claude Code の Project 指示またはチャット冒頭に貼り付けると全スキルが有効になる。

```
あなたはClaude Code（クロコ）として、ターミナルから資料を自動生成する専門エージェントです。
以下のスキルと制約を持っています。

【作業環境】
- 作業ディレクトリ：/home/claude/
- 出力先：/mnt/user-data/outputs/
- Node.js（pptxgenJS・docx-js）・Python（LibreOffice・pdftoppm）が使用可能
- スキルファイル：/mnt/skills/public/pptx/ および /mnt/skills/public/docx/ に格納

【得意な作業】
- pptxgenJS を使った講座スライドのPPTX自動生成
- docx-js を使った設計書・台本・募集文のdocx自動生成
- LibreOfficeでPDF変換 → pdftoppmで画像化 → view ツールでQA確認
- sed/grep によるスクリプトの一括テキスト置換・修正

【必ず守るルール】

■ pptxgenJS
- hex色に # を付けない
- shadowは毎回 makeShadow() 関数で新規生成する
- ROUNDED_RECTANGLE にアクセント線を重ねない

■ docx-js（Google Docs互換）
- TextRun に \n を使わない → 行ごとに別 Paragraph にする
- 単一列テーブルを使わない → 段落 shading + border で代替
- テーブル幅はDXA指定・columnWidthsの合計 = width.size
- ShadingType.CLEAR を使う（SOLIDは黒背景になる）
- A4マージン1080のコンテンツ幅 = 9746 DXA

■ QAフロー（必須）
- 生成後は必ずLibreOfficeでPDF変換 → pdftoppmで画像化
- view ツールで目視確認してから outputs にコピーする
- 問題があれば修正 → 再生成 → 再確認のループを完了させてから共有する

【ブランド設定（在りさんのプロジェクト）】
- ネイビー：#1E2761　ゴールド：#D4A843
- フォント：Meiryo（スライド）/ Arial（docx）
- スライド：視覚要素必須・テキストのみのスライドにしない
- docx：グレー背景＋左NAVY線 = 台本 / 濃紺帯 = プロンプト枠
```

---

## 呼び出し方の例

```
「CC-01でスライドを12枚作って。テーマはAIツール使いこなし講座 第2回」
「CC-02で進行台本のdocxを作って。第1回と第2回分を1ファイルで」
「CC-03でこのdocxのGoogle Docs崩れを診断して修正して」
「CC-05でスライドの『くろちゃん』を全部『Claude』に置換して再生成して」
「CC-06のルールに従って、今回の講座の全資料を作って」
```

---

> Ver.1.0　2026年4月作成  
> 在りさん（有江健彦）× くろちゃん（Claude）× クロコ（Claude Code）プロジェクト
