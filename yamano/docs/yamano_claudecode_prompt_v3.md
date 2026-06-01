# ヤマノ AIカウンセラー アプリ 制作指示（最終版）

## リポジトリ情報

既存リポジトリをクローンして作業すること。

```bash
git clone https://github.com/katsuhikoarie-coach/yamanoAIv01.git
cd yamanoAIv01
```

---

## セットアップ

```bash
npx create-next-app@latest . --typescript --tailwind --eslint --app --src-dir --import-alias "@/*" --yes
npm install @google/generative-ai
```

---

## アーキテクチャ方針：トークン節約設計

毎ターンのAPIリクエストを最小化する。

**Layer 1 — システムプロンプト（常時送る・軽量・約500トークン）**
会話ルールとフローのみ。商品データは含めない。

**Layer 2 — 商品DB（`src/data/products.json`・静的ファイル）**
提案フェーズ（step7以降）に入ったとき、年代・予算・悩みでフィルタした結果だけを追加する。

**会話履歴のトリム**
- step1〜6（ヒアリング中）：直近3ターンのみ送る
- step7以降（提案フェーズ）：直近5ターン＋フィルタ済み商品リスト

---

## ファイル構成

```
yamanoAIv01/
├── src/
│   ├── app/
│   │   ├── api/chat/route.ts      ← Gemini APIルート（ストリーミング）
│   │   ├── page.tsx               ← チャット画面
│   │   ├── layout.tsx             ← フォント読み込み
│   │   └── globals.css
│   ├── components/
│   │   ├── ChatWindow.tsx         ← メッセージ一覧
│   │   ├── MessageBubble.tsx      ← 1件分のメッセージ
│   │   └── InputForm.tsx          ← 入力欄と送信ボタン
│   ├── data/
│   │   └── products.json          ← 商品DB（静的・APIに送らない）
│   └── lib/
│       ├── systemPrompt.ts        ← プロンプト定数
│       ├── filterProducts.ts      ← 商品フィルタリング
│       └── detectStep.ts          ← 会話ステップ推定
├── .env.local.example
├── .gitignore                     ← .env.local を必ず除外
└── README.md
```

---

## 環境変数

`.env.local` はすでに登録済み。コミットしないこと。

```
GEMINI_API_KEY=（登録済み）
```

`.env.local.example` にキー名だけ記載する：

```
GEMINI_API_KEY=your_api_key_here
```

`.gitignore` に以下が含まれているか確認し、なければ追加：

```
.env.local
```

---

## 実装詳細

### `src/data/products.json`

```json
[
  {"id":"c24_face","name":"クレオリ24 フェイシャルクリーム","price":4200,"step":"清潔","age":["20","30","40","50"],"concern":["乾燥","シワ","シミ","毛穴","リフト","敏感肌"]},
  {"id":"c24_cleanse","name":"クレオリ24 クレンジングクリーム","price":4200,"step":"清潔","age":["20","30","40","50"],"concern":["乾燥","毛穴","リフト","敏感肌","ニキビ"]},
  {"id":"c24k_lotion","name":"クレオリ24K スキンローション","price":4200,"step":"保護","age":["20","30"],"concern":["乾燥","毛穴","敏感肌"]},
  {"id":"c24k_milk","name":"クレオリ24K ミルクローション","price":4900,"step":"保護","age":["20","30"],"concern":["乾燥","シワ","毛穴","敏感肌"]},
  {"id":"c24k_gel","name":"クレオリ24K ナリッシングクリスタル","price":4900,"step":"保護","age":["20","30"],"concern":["乾燥","シワ","シミ","毛穴","敏感肌"]},
  {"id":"c24k_cream","name":"クレオリ24K クリーム","price":5200,"step":"保護","age":["20","30"],"concern":["乾燥","毛穴","敏感肌"]},
  {"id":"kohaku_oil","name":"コハクイミュアオイルセラム","price":5300,"step":"保護","age":["30","40","50"],"concern":["乾燥","シワ","敏感肌"]},
  {"id":"kbotanical","name":"Kボタニカルセラム","price":6400,"step":"活力","age":["20","30","40"],"concern":["シワ","シミ","ニキビ"]},
  {"id":"face_mask","name":"フェイスマスクF","price":3400,"step":"保護","age":["20","30","40","50"],"concern":["乾燥","シワ","シミ","毛穴","敏感肌","ニキビ"]},
  {"id":"eye_gel","name":"アイコントゥアジェル24N","price":7800,"step":"活力","age":["40","50"],"concern":["シワ","リフト"]},
  {"id":"bido_face","name":"薬用美道 フェイシャルクリーム","price":9600,"step":"清潔","age":["30","40","50"],"concern":["乾燥","毛穴"]},
  {"id":"bido_lotion","name":"薬用美道 スキンローション","price":9800,"step":"保護","age":["30","40"],"concern":["乾燥","シミ","ニキビ"]},
  {"id":"bido_cleanse","name":"薬用美道 クレンジングミルク","price":10000,"step":"清潔","age":["30","40","50"],"concern":["乾燥","シミ","毛穴","ニキビ"]},
  {"id":"bido_milk","name":"薬用美道 ミルクローション","price":10000,"step":"保護","age":["30","40","50"],"concern":["乾燥","シワ"]},
  {"id":"ep_serum","name":"EPセラムKLR","price":13500,"step":"活力","age":["40","50"],"concern":["乾燥","シワ","シミ","リフト"]},
  {"id":"zero_cleanse","name":"ゼロNK&Dクレンジングクリーム","price":14400,"step":"清潔","age":["40","50"],"concern":["乾燥","シワ","シミ","毛穴","リフト","敏感肌"]},
  {"id":"zero_face","name":"ゼロNK&Dフェイシャルクリーム","price":14400,"step":"清潔","age":["40","50"],"concern":["乾燥","シワ","毛穴","リフト","敏感肌"]},
  {"id":"zero_lotion","name":"ゼロNK Pローション","price":17400,"step":"保護","age":["40","50"],"concern":["乾燥","シワ","毛穴","敏感肌"]},
  {"id":"zero_serum","name":"ゼロNKセラムミルク","price":18700,"step":"活力","age":["50"],"concern":["乾燥","シワ","毛穴","敏感肌"]},
  {"id":"moist_gel","name":"モイストナノジェル","price":15000,"step":"保護","age":["40","50"],"concern":["乾燥","シワ","シミ","リフト","敏感肌"]},
  {"id":"placenta","name":"プラセンタコラーゲンドリンク","price":7000,"step":"内側","age":["40","50"],"concern":["シワ","リフト"]},
  {"id":"kohaku_jelly","name":"琥珀健寿イミュアゼリー","price":5500,"step":"内側","age":["40","50"],"concern":["疲れ","冷え","むくみ"]},
  {"id":"imua_oil","name":"イミュアオイル","price":6400,"step":"活力","age":["30","40","50"],"concern":["毛穴","シワ","乾燥"]}
]
```

### `src/lib/systemPrompt.ts`

```typescript
export const SYSTEM_PROMPT = `あなたはヤマノの美容AIカウンセラー「朝霧」です。

【会話ルール】
- 1回の返答で伝えることは1つ。質問も1つずつ
- 共感してから次へ進む
- 聞いた質問は二度聞かない
- 名前が分かれば「〇〇様」、不明なら「あなた」か「お客様」

【カウンセリング順序】
1→年代 2→肌悩み 3→一番気になる瞬間 4→現在のケア 5→予算 6→理想の肌 7→商品提案 8→選択肢

【提案ルール】
- 必ず清潔（洗顔/クレンジング）から始める
- 予算を1円も超えない
- 予算〜5000:1点 〜10000:2点 〜20000:3点 20000〜:4点
- 提案フォーマット：
  【あなたへのおすすめ】
  STEP1 清潔｜商品名 ¥価格｜理由1文
  STEP2 保護｜商品名 ¥価格｜理由1文（予算8000以上のみ）
  合計：¥〇〇〇（ご予算内✓）
  1点から始めるか2点セットか、どちらが合いそうですか？

【購入案内】
お客様が「欲しい」「試したい」と言ったら：
「〔商品名〕はヤマノ公式サイトまたはお近くのヤマノサロンでご購入いただけます。公式サイトをご確認ください」

【終了時サマリー】
「考えます」「今日は買わない」と言われたら、以下を1回だけ出力：
📋本日のカウンセリング｜提案商品：〔商品名〕¥〇〇〇 合計¥〇〇〇

【断り対応】
高い→1日あたり約〇〇円とお伝えする／効果不安→小さいサイズから提案／考えたい→「いつでもどうぞ」

【哲学】山野愛子先生：「美しさは女性への贈り物。大切に育てなさい」
素肌美3原則：清潔→活力→保護 ／ うなはだけつ：うるおい・なめらかさ・はり・弾力・血色・艶`;
```

### `src/lib/filterProducts.ts`

```typescript
import products from "@/data/products.json";

export type Product = (typeof products)[number];

export function filterProducts(
  age: string,
  budget: number,
  concerns: string[]
): Product[] {
  const ageKey = age.replace(/代$/, "");
  return products
    .filter(
      (p) =>
        p.age.includes(ageKey) &&
        p.price <= budget &&
        p.concern.some((c) => concerns.includes(c)) &&
        p.step !== "ヘア"
    )
    .sort((a, b) => a.price - b.price);
}
```

### `src/lib/detectStep.ts`

会話テキストからカウンセリングの進捗ステップを推定する。

```typescript
export type UserState = {
  step: number;
  age: string;
  budget: number;
  concerns: string[];
};

const AGE_PATTERN = /(10|20|30|40|50|60)代/;
const BUDGET_PATTERN = /(\d[\d,]*)\s*円|¥(\d[\d,]*)/;
const CONCERN_KEYWORDS = ["乾燥","シワ","シミ","毛穴","リフト","敏感肌","ニキビ","くすみ","ハリ","たるみ"];

export function detectStep(
  messages: { role: string; text: string }[],
  current: UserState
): UserState {
  const allText = messages.map((m) => m.text).join(" ");
  const next = { ...current };

  // 年代抽出
  if (!next.age) {
    const m = allText.match(AGE_PATTERN);
    if (m) {
      next.age = m[1];
      next.step = Math.max(next.step, 2);
    }
  }

  // 悩み抽出
  if (next.concerns.length === 0) {
    const found = CONCERN_KEYWORDS.filter((k) => allText.includes(k));
    if (found.length > 0) {
      next.concerns = found;
      next.step = Math.max(next.step, 3);
    }
  }

  // 予算抽出
  if (!next.budget) {
    const m = allText.match(BUDGET_PATTERN);
    if (m) {
      const raw = (m[1] || m[2]).replace(/,/g, "");
      next.budget = parseInt(raw, 10);
      next.step = Math.max(next.step, 6);
    }
  }

  // 提案フェーズ判定
  const lastAI = [...messages].reverse().find((m) => m.role === "model")?.text ?? "";
  if (lastAI.includes("手の届くところ") || lastAI.includes("おすすめ")) {
    next.step = Math.max(next.step, 7);
  }

  return next;
}
```

### `src/app/api/chat/route.ts`

```typescript
import { GoogleGenerativeAI } from "@google/generative-ai";
import { NextRequest, NextResponse } from "next/server";
import { SYSTEM_PROMPT } from "@/lib/systemPrompt";
import { filterProducts } from "@/lib/filterProducts";

const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY!);

type GeminiMessage = {
  role: "user" | "model";
  parts: [{ text: string }];
};

export async function POST(req: NextRequest) {
  const { messages, userState } = await req.json() as {
    messages: { role: string; text: string }[];
    userState: { step: number; age: string; budget: number; concerns: string[] };
  };

  const step = userState?.step ?? 1;

  // 履歴トリム：ヒアリング中は直近3ターン、提案以降は5ターン
  const maxTurns = step >= 7 ? 5 : 3;
  const trimmed = messages.slice(-(maxTurns * 2));

  // Gemini形式に変換
  const history: GeminiMessage[] = trimmed.slice(0, -1).map((m) => ({
    role: m.role === "user" ? "user" : "model",
    parts: [{ text: m.text }],
  }));
  const lastUserMessage = trimmed.at(-1)!.text;

  // 提案フェーズのみ商品リストを追加
  let systemPrompt = SYSTEM_PROMPT;
  if (step >= 7 && userState.age && userState.budget && userState.concerns.length > 0) {
    const filtered = filterProducts(userState.age, userState.budget, userState.concerns);
    const productList = filtered
      .map((p) => `${p.name}(${p.step})¥${p.price.toLocaleString()}`)
      .join(" / ");
    systemPrompt += `\n\n【今回提案可能な商品】\n${productList}`;
  }

  const model = genAI.getGenerativeModel({
    model: "gemini-2.0-flash",
    systemInstruction: systemPrompt,
  });

  const chat = model.startChat({ history });
  const result = await chat.sendMessageStream(lastUserMessage);

  const stream = new ReadableStream({
    async start(controller) {
      for await (const chunk of result.stream) {
        controller.enqueue(new TextEncoder().encode(chunk.text()));
      }
      controller.close();
    },
  });

  return new NextResponse(stream, {
    headers: { "Content-Type": "text/plain; charset=utf-8" },
  });
}
```

### `src/app/page.tsx` の設計方針

```typescript
"use client";
import { useState, useRef, useEffect } from "react";
import { detectStep, UserState } from "@/lib/detectStep";
import ChatWindow from "@/components/ChatWindow";
import InputForm from "@/components/InputForm";

type Message = { role: "user" | "model"; text: string };

const INITIAL_MESSAGE: Message = {
  role: "model",
  text: "こんにちは！朝霧ヤマノのAIカウンセラーです。\n泥と琥珀で、あなたのお肌を整えるお手伝いをします。\nまず、お客様の年代を教えていただけますか？",
};

export default function Home() {
  const [messages, setMessages] = useState<Message[]>([INITIAL_MESSAGE]);
  const [input, setInput] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [userState, setUserState] = useState<UserState>({
    step: 1, age: "", budget: 0, concerns: [],
  });

  const handleSend = async () => {
    if (!input.trim() || isLoading) return;
    const userMsg: Message = { role: "user", text: input };
    const next = detectStep([...messages, userMsg], userState);
    setUserState(next);
    const updated = [...messages, userMsg];
    setMessages(updated);
    setInput("");
    setIsLoading(true);

    const res = await fetch("/api/chat", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ messages: updated, userState: next }),
    });

    // ストリーミング受信
    const reader = res.body!.getReader();
    const decoder = new TextDecoder();
    let aiText = "";
    setMessages((prev) => [...prev, { role: "model", text: "" }]);

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      aiText += decoder.decode(value);
      setMessages((prev) => {
        const copy = [...prev];
        copy[copy.length - 1] = { role: "model", text: aiText };
        return copy;
      });
    }

    setIsLoading(false);
  };

  return (
    <main className="flex flex-col h-screen bg-[#F9F5EF]">
      <header className="py-4 px-6 border-b border-[#C9883A]/30 text-center">
        <h1 className="text-[#2D4A3E] font-serif text-xl tracking-widest">朝霧ヤマノ AIカウンセラー</h1>
      </header>
      <ChatWindow messages={messages} isLoading={isLoading} />
      <InputForm
        input={input}
        onChange={setInput}
        onSend={handleSend}
        isLoading={isLoading}
      />
    </main>
  );
}
```

---

## デザイン仕様

和モダン・ラグジュアリーミニマル。

- 背景：`#F9F5EF`（オフホワイト）
- アクセント：`#C9883A`（琥珀）と `#2D4A3E`（深緑）
- Google Fonts で Noto Serif JP + Cormorant Garamond を `layout.tsx` で読み込む
- AIメッセージ：左寄せ、緑の丸アイコン（「朝」の文字）付き
- ユーザーメッセージ：右寄せ、琥珀色バブル（`#C9883A`）
- メッセージ出現アニメーション：フェードイン 0.3s のみ
- 入力欄：下部固定、角丸、送信ボタンは琥珀色

---

## README.md に書くこと（日本語でOK）

- プロジェクト概要
- `npm run dev` でのローカル起動手順
- `.env.local` に `GEMINI_API_KEY` を設定する方法
- Vercelへのデプロイ手順（環境変数の設定を含む）

---

## 最後にやること

```bash
git add .
git commit -m "feat: yamano ai counselor with gemini 2.0 flash (token-efficient)"
git push origin main
```

プッシュ後、コンソールに表示されるGitHub URLとVercelのデプロイURLを教えてください。

---

## 絶対に守ること

- `.env.local` はコミットしない（`.gitignore` に含まれているか確認してから進める）
- `GEMINI_API_KEY` はコードに直接書かない
- `any` 型を使わない。型エラーはちゃんと解消する
- ストリーミングレスポンスを必ず使う（体感速度のため）
- 商品リストは提案フェーズ（step7）に入るまでAPIに送らない
