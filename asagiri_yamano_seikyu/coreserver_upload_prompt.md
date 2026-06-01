# 請求書作成ツール CORESERVERアップロード指示

## あなたの役割
FTPでファイルをCORESERVERにアップロードするエンジニアです。

---

## システム情報

- 本番URL（アップ後のアクセスURL）: https://sales.asagiriyamano.jp/invoice/
- サーバー: CORESERVER.JP（s168.coreserver.jp）
- FTPホスト: asagiri.s168.coreserver.jp
- FTPユーザー・パスワード: 在りさんから受け取ること
- アップロード先ディレクトリ: public_html/sales.asagiriyamano.jp/invoice/

---

## アップロードするファイル

以下のファイルを上記ディレクトリにアップロードしてください。

| ファイル名 | 説明 |
|---|---|
| `invoice_generator.html` | 請求書作成ツール本体 |

---

## 作業手順

1. FTP接続情報を在りさんから受け取る
2. FTPで `asagiri.s168.coreserver.jp` に接続する
3. `public_html/sales.asagiriyamano.jp/invoice/` ディレクトリを作成する（存在しない場合）
4. `invoice_generator.html` をアップロードする
5. https://sales.asagiriyamano.jp/invoice/invoice_generator.html にアクセスして表示を確認する
6. 正常に表示されたら完了を報告する

---

## 注意事項

- 既存の `public_html/sales.asagiriyamano.jp/` 以下のファイルは触らないこと
- アップロードするのは `invoice_generator.html` 1ファイルのみ
- PHPやデータベースは不要（完全スタンドアロンのHTMLファイル）
- Basic認証などのアクセス制限は在りさんの指示に従うこと
