FC向けWEB受注アプリ開発プラン
Googleログイン（OAuth2.0）対応版
================================

【プロジェクト概要】

アプリ名：朝霧ヤマノ FC受注システム
URL：fc-order.asagiriyamano.jp（予定）
対象：小規模代理店（FC）4社
主要機能：商品検索・選択 → カート → 注文 → 請求書PDF
ログイン方式：Google OAuth2.0

【Googleログインのメリット】

✅ FCさんが新たにパスワード覚える必要なし
✅ セキュリティが高い（Googleが管理）
✅ 実装が簡単（OAuthライブラリで対応）
✅ FCさんの既存Gmailアドレスをそのまま使用可
✅ メール送信も Google API で一元化できる

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【ユーザーフロー】

【初回ログイン】
1. FC がアプリにアクセス
   https://fc-order.asagiriyamano.jp/
   ↓
2. 「Googleでログイン」ボタンをクリック
   ↓
3. Google ログイン画面が表示
   ↓
4. FC のGmailアドレスを入力
   ↓
5. Google が本人確認（パスワード入力等）
   ↓
6. 「朝霧ヤマノがアクセスすることを許可しますか？」と表示
   ↓
7. FC が「許可」をクリック
   ↓
8. アプリに自動ログイン
   ↓
9. FC情報（Gmailアドレス → FC名）を確認・登録
   ↓
10. ホーム画面に遷移

【2回目以降のログイン】
1. FC がアプリにアクセス
   ↓
2. 「Googleでログイン」ボタンをクリック
   ↓
3. Googleのセッションがあれば自動ログイン
   ↓
4. 商品検索画面に遷移

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【技術スタック】

【フロントエンド】
├─ React 18
├─ Next.js（サーバーサイドレンダリング対応）
├─ Tailwind CSS（スタイリング）
├─ Google Sign-In Library
├─ Responsive Design（スマートフォン対応）
└─ PWA（オフライン対応）

【バックエンド】
├─ Node.js + Express
├─ Firebase or Auth0（Googleログイン管理）
├─ PostgreSQL or MongoDB（データベース）
├─ REST API
└─ CORS設定

【認証・セキュリティ】
├─ Google OAuth 2.0（ログイン）
├─ JWT トークン（API認証）
├─ HTTPS（SSL証明書）
├─ CSRF対策
└─ Rate limiting（ブルートフォース対策）

【メール送信】
├─ Google Gmail API（メール送信）
├─ nodemailer（メールライブラリ）
├─ HTML メールテンプレート
└─ 送信ログ記録

【ホスティング】
├─ Heroku or AWS Lightsail or Vercel
├─ Cloud CDN（画像配信高速化）
└─ バックアップ・自動スケーリング

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【Google OAuth2.0 の実装】

【1. Google Cloud Console での設定】

準備：
├─ Google Cloud Account を作成
├─ プロジェクトを新規作成
├─ OAuth 2.0 認証情報を生成
│  ├─ クライアントID（前面向け）
│  ├─ クライアントシークレット（サーバー用・非公開）
│  └─ リダイレクトURI（fc-order.asagiriyamano.jp/auth/callback）
├─ 同意画面を設定
│  ├─ アプリ名：「朝霧ヤマノ FC受注システム」
│  ├─ ロゴ：朝霧ヤマノのロゴ
│  └─ スコープ：email, profile
└─ Gmail API を有効化（メール送信用）

【2. フロントエンド実装】

```javascript
import { GoogleLogin } from '@react-oauth/google';

export default function LoginPage() {
  const handleSuccess = (credentialResponse) => {
    // Google から受け取った credential をサーバーに送信
    fetch('/api/auth/google', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        token: credentialResponse.credential 
      })
    })
    .then(res => res.json())
    .then(data => {
      // サーバーから JWT トークンを取得
      localStorage.setItem('authToken', data.token);
      // ホーム画面にリダイレクト
      window.location.href = '/home';
    });
  };

  return (
    <div className="login-container">
      <h1>朝霧ヤマノ FC受注システム</h1>
      <GoogleLogin
        onSuccess={handleSuccess}
        onError={() => console.log('Login Failed')}
        text="signin_with"
      />
    </div>
  );
}
```

【3. バックエンド実装】

```javascript
const express = require('express');
const { OAuth2Client } = require('google-auth-library');
const jwt = require('jsonwebtoken');
const app = express();

const CLIENT_ID = process.env.GOOGLE_CLIENT_ID;
const CLIENT_SECRET = process.env.GOOGLE_CLIENT_SECRET;
const client = new OAuth2Client(CLIENT_ID, CLIENT_SECRET);

app.post('/api/auth/google', async (req, res) => {
  try {
    // Google から受け取った token を検証
    const ticket = await client.verifyIdToken({
      idToken: req.body.token,
      audience: CLIENT_ID,
    });

    const payload = ticket.getPayload();
    const email = payload['email'];
    const name = payload['name'];
    const googleId = payload['sub']; // Google User ID

    // ユーザー情報をデータベースに保存 or 更新
    let user = await User.findOne({ email });
    if (!user) {
      user = new User({
        googleId,
        email,
        name,
        role: 'fc', // FC ユーザー
      });
      await user.save();
    }

    // JWT トークンを生成
    const token = jwt.sign(
      { userId: user._id, email: user.email },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );

    res.json({ 
      success: true, 
      token,
      user: { id: user._id, name: user.name, email: user.email }
    });
  } catch (error) {
    res.status(401).json({ success: false, error: error.message });
  }
});
```

【4. Userテーブル設計】

```sql
CREATE TABLE users (
  id UUID PRIMARY KEY,
  google_id VARCHAR(255) UNIQUE,
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(255),
  fc_name VARCHAR(255), -- FC正式名（西明石白神など）
  phone VARCHAR(20),
  address TEXT,
  role ENUM('fc', 'admin', 'support'),
  status ENUM('active', 'inactive', 'pending'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  last_login_at TIMESTAMP
);
```

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【画面設計】

【画面1：ログイン画面】
┌─────────────────────────┐
│   朝霧ヤマノ             │
│  FC受注システム          │
│                         │
│  [Googleでログイン 🔐]   │
│                         │
│  ※Gmailアドレスで      │
│    ログインできます     │
└─────────────────────────┘

【画面2：ホーム画面（ログイン後）】
┌─────────────────────────┐
│ 朝霧ヤマノ FC受注システム │
├─────────────────────────┤
│ こんにちは、白神様 ⬇️     │
│ 西明石白神FC             │
├─────────────────────────┤
│ [新規注文]  [注文履歴]   │
│                         │
│ 最近の注文：             │
│ ・2025-05-15 36,080円   │
│ ・2025-05-10 42,370円   │
│                         │
│ [ログアウト]             │
└─────────────────────────┘

【画面3：商品検索画面】
┌─────────────────────────┐
│ < 新規注文               │
├─────────────────────────┤
│ 🔍 商品検索              │
│ [      商品を検索 ]      │
│                         │
│ カテゴリ：               │
│ □ スキンケア            │
│ □ メイク                │
│ □ 販促品                │
│                         │
│ 【おすすめ】            │
│ AW ドロンコパック       │
│ 13,500円 [カートに追加]  │
│                         │
│ セルセル 70             │
│ 270円 [カートに追加]     │
└─────────────────────────┘

【画面4：カート画面】
┌─────────────────────────┐
│ < 商品選択              │
├─────────────────────────┤
│ カート（2点）            │
│                         │
│ 商品名 | 数量 | 金額    │
│─────────────────────────┤
│ AW ドロ.. │ 2 │27,400  │
│ セルセル.. │20│ 5,400  │
│                         │
│ 小計     ¥32,800       │
│ 消費税   ¥3,280        │
│ 合計     ¥36,080       │
│                         │
│ [編集]  [確認画面へ]    │
└─────────────────────────┘

【画面5：確認画面】
┌─────────────────────────┐
│ < カート確認            │
├─────────────────────────┤
│ FC情報                  │
│ 西明石白神FC             │
│ 兵庫県明石市…            │
│                         │
│ 注文内容                │
│ AW ドロンコパック×2     │
│ セルセル 70 × 20        │
│                         │
│ 合計：¥36,080           │
│ 備考：[月末までに]      │
│                         │
│ □ 内容を確認しました   │
│                         │
│ [キャンセル] [確定]     │
└─────────────────────────┘

【画面6：完了画面】
┌─────────────────────────┐
│ ✓ 注文が完了しました    │
├─────────────────────────┤
│ 受注番号                │
│ 2025-05-15-0001         │
│                         │
│ 内容確認メールを        │
│ 送信しました            │
│                         │
│ [PDFダウンロード]       │
│ [新規注文へ]            │
└─────────────────────────┘

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【実装スケジュール】

【フェーズ1：基本実装】期間 4-5週間

週1-2：
├─ Google Cloud Console 設定
├─ プロジェクト構築（フロントエンド・バックエンド）
├─ ログイン画面実装
└─ OAuth 2.0 実装完了

週3：
├─ 商品検索・カート機能実装
├─ 注文入力・確認画面実装
└─ データベース構築完了

週4：
├─ メール送信機能
├─ PDF 生成機能
├─ 単体テスト
└─ 統合テスト

週5：
├─ バグ修正
├─ パフォーマンス調整
├─ セキュリティテスト
└─ 本番環境デプロイ準備

【フェーズ2：ベータテスト】期間 1-2週間

├─ 1社（西明石白神FC）で試用
├─ フィードバック収集
├─ バグ修正・改善
└─ 全社展開準備

【フェーズ3：本番運用】期間 1週間

├─ 全 FC への展開
├─ 使用方法説明（オンライン）
├─ 初期サポート体制
└─ 定期メンテナンス開始

【全体：6-8週間で本番運用開始】

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【セキュリティチェックリスト】

認証・認可：
☐ Google OAuth 2.0 が正しく実装されているか
☐ JWT トークンに有効期限が設定されているか
☐ リフレッシュトークンの仕組みがあるか
☐ FC は自分の注文データのみ閲覧可か
☐ 在りさん・有江啓子のみ全注文データ閲覧可か
☐ セッションタイムアウトが設定されているか（例：30分）

データ保護：
☐ HTTPS が有効か（SSL証明書）
☐ パスワードがハッシュ化されているか
☐ 個人情報が暗号化されているか
☐ データベースバックアップが自動化されているか
☐ ログインID・パスワードが平文で保存されていないか

API セキュリティ：
☐ CORS が正しく設定されているか
☐ Rate limiting が有効か（ブルートフォース対策）
☐ CSRF トークンが実装されているか
☐ SQL インジェクション対策が講じられているか
☐ XSS（クロスサイトスクリプティング）対策があるか

ログ・監視：
☐ ログイン・ログアウトのログが記録されているか
☐ データ更新・削除のログが記録されているか
☐ エラーログが監視されているか
☐ 異常なアクセスパターンが検出できるか

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【くろちゃんへの確認事項】

1. Google Cloud Platform の設定
   ├─ GCP アカウントの作成・設定
   ├─ OAuth 2.0 認証情報の生成
   └─ リダイレクト URI の設定

2. ホスティング環境
   ├─ Heroku, Vercel, AWS どれを使う？
   ├─ ドメイン設定（fc-order.asagiriyamano.jp）
   ├─ SSL証明書の設定
   └─ バックアップ体制

3. データベース
   ├─ PostgreSQL or MongoDB どちらか？
   ├─ DB ホスティング（AWS RDS, Google Cloud SQL等）
   └─ バックアップ・復旧手順

4. Gmail API 設定
   ├─ メール送信用 Gmail アカウントの作成
   ├─ App Password の生成（Gmail送信用）
   └─ メールテンプレートの作成

5. 既存システムとの連携
   ├─ 現在のスプレッドシートとの併用期間
   ├─ データ移行方法
   └─ 運用切り替えのタイミング

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【今からやること】

1. 在りさんが確認事項 5項目 をくろちゃんに伝える
2. くろちゃんが GCP 設定・環境構築（1-2日）
3. くろちゃんが開発開始（4-5週間）
4. ベータテスト・フィードバック（1-2週間）
5. 本番運用開始（6-8週間後）

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【期待効果】

有江啓子さん：
├─ スプレッド入力：100%削減
├─ 対面業務に集中：可能
└─ 時間削減：月間 30時間

在りさん：
├─ 受注データが自動で届く
├─ 発注業務がスムーズ
├─ データ整理の手間：削減
└─ 時間削減：月間 20時間

FC（代理店）：
├─ いつでもどこでも注文可能
├─ Gmailでログイン → 簡単
├─ 注文内容がすぐに確認できる
└─ ユーザー体験向上

全体効果：
└─ 月間 50時間削減 = ¥150,000 相当の効率化
