<?php
require 'config.php';

$message = '';
$error   = '';
$preview = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    $file = $_FILES['csv'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'アップロードに失敗しました（エラーコード: ' . $file['error'] . '）';
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = 'CSVファイルを選択してください。';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $error = 'ファイルを開けませんでした。';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO products (code, name, price, tax_rate, volume)
                VALUES (:code, :name, :price, :tax_rate, :volume)
                ON DUPLICATE KEY UPDATE
                    name     = VALUES(name),
                    price    = VALUES(price),
                    tax_rate = VALUES(tax_rate),
                    volume   = VALUES(volume)
            ");

            $count   = 0;
            $skipped = 0;
            $lineNum = 0;
            $errors  = [];

            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;

                // BOM除去（1行目）
                if ($lineNum === 1 && isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                }

                // ヘッダー行スキップ（価格列がカンマ除去後も数値でない場合）
                if ($lineNum === 1 && !is_numeric(preg_replace('/,/', '', trim($row[2] ?? '')))) {
                    continue;
                }

                if (count($row) < 5) {
                    $skipped++;
                    $errors[] = "{$lineNum}行目: 列数不足";
                    continue;
                }

                // 列順: コード番号, 商品名, 価格, 内容量, 消費税率
                $code    = trim($row[0]);
                $name    = trim($row[1]);
                $price   = (int) preg_replace('/[^0-9]/', '', trim($row[2])); // カンマ除去
                $volume  = trim($row[3]);
                $taxRate = (int) preg_replace('/[^0-9]/', '', trim($row[4])); // %除去

                if ($code === '' || $name === '') {
                    $skipped++;
                    $errors[] = "{$lineNum}行目: 商品コードまたは商品名が空";
                    continue;
                }

                if (!in_array($taxRate, [8, 10], true)) {
                    $taxRate = 10;
                }

                try {
                    $stmt->execute([
                        ':code'     => $code,
                        ':name'     => $name,
                        ':price'    => $price,
                        ':tax_rate' => $taxRate,
                        ':volume'   => $volume,
                    ]);
                    $count++;
                } catch (Exception $e) {
                    $skipped++;
                    $errors[] = "{$lineNum}行目: " . $e->getMessage();
                }
            }
            fclose($handle);

            $message = "{$count}件をインポートしました。";
            if ($skipped > 0) {
                $message .= "（{$skipped}件スキップ）";
            }
        }
    }
}

// 現在の件数取得
$currentCount = 0;
try {
    $pdo = getDB();
    $currentCount = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (Exception $e) {
    // テーブルがまだない場合は無視
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>商品CSVインポート</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Hiragino Kaku Gothic Pro', 'Meiryo', sans-serif;
    background: #f5f5f5;
    padding: 40px 20px;
}
.container {
    max-width: 700px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    padding: 30px;
}
h1 { font-size: 1.4em; color: #333; margin-bottom: 20px; }
h2 { font-size: 1em; color: #555; margin: 20px 0 10px; }
.msg  { background: #e6f4ea; color: #2d7a36; border: 1px solid #a8d5ae; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; }
.err  { background: #fdecea; color: #c0392b; border: 1px solid #f5b7b1; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; }
.info { background: #e8f4fd; color: #2471a3; border: 1px solid #aed6f1; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.9em; }
.format-table { border-collapse: collapse; width: 100%; font-size: 0.85em; margin: 10px 0; }
.format-table th, .format-table td { border: 1px solid #ddd; padding: 6px 10px; }
.format-table th { background: #f0f0f0; }
.format-table td.ex { color: #666; font-style: italic; }
label { display: block; font-weight: bold; margin-bottom: 6px; }
input[type=file] { border: 2px dashed #ccc; padding: 20px; width: 100%; border-radius: 4px; cursor: pointer; }
.btn {
    display: inline-block;
    background: #2471a3;
    color: #fff;
    border: none;
    padding: 10px 24px;
    border-radius: 4px;
    font-size: 1em;
    cursor: pointer;
    margin-top: 14px;
}
.btn:hover { background: #1a5276; }
.btn-link { color: #2471a3; text-decoration: none; font-size: 0.9em; }
.btn-link:hover { text-decoration: underline; }
.footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee; }
.error-list { margin-top: 10px; font-size: 0.85em; color: #c0392b; max-height: 120px; overflow-y: auto; }
.current-count { color: #555; font-size: 0.9em; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="container">
    <h1>商品CSVインポート</h1>

    <p class="current-count">現在の商品件数: <strong><?= $currentCount ?>件</strong></p>

    <?php if ($message): ?>
        <div class="msg"><?= h($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="err"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="info">
        <strong>CSVフォーマット（UTF-8）</strong><br>
        列順: コード番号, 商品名, 価格, 内容量, 消費税率<br>
        価格はカンマ付き（例: 4,000）、消費税率は%付き（例: 10%）でも自動変換します。<br>
        1行目がヘッダーの場合は自動スキップします。同じ商品コードは上書き更新されます。
    </div>

    <table class="format-table">
        <tr>
            <th>コード番号</th>
            <th>商品名</th>
            <th>価格</th>
            <th>内容量</th>
            <th>消費税率</th>
        </tr>
        <tr>
            <td class="ex">A001</td>
            <td class="ex">りんごジュース</td>
            <td class="ex">500</td>
            <td class="ex">200ml</td>
            <td class="ex">8%</td>
        </tr>
        <tr>
            <td class="ex">B001</td>
            <td class="ex">ハンドソープ</td>
            <td class="ex">4,000</td>
            <td class="ex">300ml</td>
            <td class="ex">10%</td>
        </tr>
    </table>

    <form method="post" enctype="multipart/form-data" style="margin-top:20px;">
        <label for="csv">CSVファイルを選択</label>
        <input type="file" id="csv" name="csv" accept=".csv" required>
        <button type="submit" class="btn">インポート実行</button>
    </form>

    <?php if (!empty($errors)): ?>
        <div class="error-list">
            <strong>スキップされた行:</strong><br>
            <?php foreach ($errors as $e): ?>
                <?= h($e) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="footer">
        <a href="index.php" class="btn-link">← 見積書作成に戻る</a>
    </div>
</div>
</body>
</html>
