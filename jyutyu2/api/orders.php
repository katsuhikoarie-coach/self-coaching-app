<?php
// ============================================================
// 注文API
// GET  /api/orders.php        → 自分のFC注文一覧（明細付き）
// POST /api/orders.php        → 注文新規作成
//   body: { items:[{code,name,price,qty,tax}], note, subtotal, tax_total, total }
// POST /api/orders.php action=delete → 注文削除（当日分のみ）
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session_check.php';

$fcUser = requireLogin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ────────────────────────────────────────
// GET: 注文一覧（明細付き）
// 朝霧ヤマノ → 全FC分を返す / それ以外 → 自分の注文のみ
// ────────────────────────────────────────
if ($method === 'GET') {
    try {
        $isAsagiri = ($fcUser['fc_name'] === '朝霧ヤマノ');

        if ($isAsagiri) {
            $stmt = $db->prepare("
                SELECT o.*, u.contact_name
                FROM orders o
                LEFT JOIN fc_users u ON u.id = o.fc_user_id
                ORDER BY o.created_at DESC
                LIMIT 500
            ");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("
                SELECT o.*, u.contact_name
                FROM orders o
                LEFT JOIN fc_users u ON u.id = o.fc_user_id
                WHERE o.fc_user_id = ?
                ORDER BY o.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$fcUser['id']]);
        }
        $orders = $stmt->fetchAll();

        if (empty($orders)) {
            jsonResponse([]);
            exit;
        }

        // 明細を一括取得して紐付け
        $ids       = array_column($orders, 'id');
        $ph        = implode(',', array_fill(0, count($ids), '?'));
        $itemStmt  = $db->prepare("SELECT * FROM order_items WHERE order_id IN ($ph) ORDER BY id");
        $itemStmt->execute($ids);
        $allItems  = $itemStmt->fetchAll();

        $itemsMap = [];
        foreach ($allItems as $it) {
            $itemsMap[$it['order_id']][] = $it;
        }

        $result = array_map(function($o) use ($itemsMap) {
            $items = array_map(function($it) {
                return [
                    'code'  => $it['product_code'],
                    'name'  => $it['product_name'],
                    'price' => (float)$it['price'],
                    'qty'   => (int)$it['quantity'],
                    'tax'   => (float)$it['tax_rate'],
                    'amount'=> (float)$it['amount'],
                ];
            }, $itemsMap[$o['id']] ?? []);

            return [
                'id'           => $o['id'],
                'order_date'   => $o['order_date'],
                'fc_name'      => $o['fc_name'],
                'contact_name' => $o['contact_name'] ?? '',
                'subtotal'     => (float)$o['subtotal'],
                'tax_total'    => (float)$o['tax_total'],
                'total'        => (float)$o['total'],
                'note'         => $o['note'] ?? '',
                'status'       => $o['status'],
                'created_at'   => $o['created_at'],
                'items'        => $items,
            ];
        }, $orders);

        jsonResponse($result);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ────────────────────────────────────────
// POST: 注文作成 / 削除
// ────────────────────────────────────────
if ($method === 'POST') {
    $body   = getRequestBody();
    $action = $body['action'] ?? 'create';

    // ── キャンセル ──
    if ($action === 'cancel') {
        $orderId = $body['id'] ?? '';
        if (!$orderId) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        // 自分のFC注文のみキャンセル可
        $check = $db->prepare("SELECT id, status FROM orders WHERE id = ? AND fc_user_id = ?");
        $check->execute([$orderId, $fcUser['id']]);
        $row = $check->fetch();
        if (!$row) {
            http_response_code(403);
            jsonResponse(['error' => 'キャンセル権限がありません']);
            exit;
        }
        if ($row['status'] !== 'received') {
            http_response_code(400);
            jsonResponse(['error' => 'この注文はキャンセルできません（受付前以外）']);
            exit;
        }
        try {
            $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'サーバーエラーが発生しました']);
        }
        exit;
    }

    // ── 削除 ──
    if ($action === 'delete') {
        $orderId = $body['id'] ?? '';
        if (!$orderId) {
            http_response_code(400);
            jsonResponse(['error' => 'id は必須']);
            exit;
        }
        // 自分のFC注文のみ削除可
        $check = $db->prepare("SELECT id FROM orders WHERE id = ? AND fc_user_id = ?");
        $check->execute([$orderId, $fcUser['id']]);
        if (!$check->fetch()) {
            http_response_code(403);
            jsonResponse(['error' => '削除権限がありません']);
            exit;
        }
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
            $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
            $db->commit();
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'サーバーエラーが発生しました']);
        }
        exit;
    }

    // ── 注文作成 ──
    $items    = $body['items']     ?? [];
    $note     = $body['note']      ?? '';
    $subtotal = (float)($body['subtotal']  ?? 0);
    $taxTotal = (float)($body['tax_total'] ?? 0);
    $total    = (float)($body['total']     ?? 0);

    if (empty($items)) {
        http_response_code(400);
        jsonResponse(['error' => '注文明細が空です']);
        exit;
    }

    $today   = date('Y-m-d');
    $orderId = generateOrderId($db, $today);

    try {
        $db->beginTransaction();

        $db->prepare("
            INSERT INTO orders
              (id, fc_user_id, fc_name, fc_email, order_date, subtotal, tax_total, total, note, status)
            VALUES (?,?,?,?,?,?,?,?,?,'received')
        ")->execute([
            $orderId,
            $fcUser['id'],
            $fcUser['fc_name'],
            $fcUser['email'],
            $today,
            $subtotal,
            $taxTotal,
            $total,
            $note,
        ]);

        $itemStmt = $db->prepare("
            INSERT INTO order_items (order_id, product_code, product_name, price, quantity, tax_rate, amount)
            VALUES (?,?,?,?,?,?,?)
        ");
        foreach ($items as $it) {
            $price  = (float)($it['price'] ?? 0);
            $qty    = (int)($it['qty']   ?? 1);
            $tax    = (float)($it['tax']  ?? 0.10);
            $amount = $price * $qty;
            $itemStmt->execute([
                $orderId,
                $it['code']  ?? '',
                $it['name']  ?? '',
                $price,
                $qty,
                $tax,
                $amount,
            ]);
        }

        $db->commit();

        // 在りさんへ通知（失敗しても注文は成功）
        try {
            sendOrderNotification($fcUser, $orderId, $today, $items, $subtotal, $taxTotal, $total, $note);
        } catch (\Throwable $e) {
            error_log('管理者メール送信失敗: ' . $e->getMessage());
        }

        // 注文者（FC）へ確認メール（失敗しても注文は成功）
        try {
            sendOrderConfirmation($fcUser, $orderId, $today, $items, $subtotal, $taxTotal, $total, $note);
        } catch (\Throwable $e) {
            error_log('注文者メール送信失敗: ' . $e->getMessage());
        }

        jsonResponse(['ok' => true, 'id' => $orderId]);
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);

// ────────────────────────────────────────
// メール通知
// ────────────────────────────────────────
function sendOrderNotification($fcUser, $orderId, $today, $items, $subtotal, $taxTotal, $total, $note) {
    $to      = 'katsuhiko.arie@gmail.com';
    $fcName  = $fcUser['fc_name']      ?? '';
    $contact = $fcUser['contact_name'] ?? '';
    $email   = $fcUser['email']        ?? '';

    $subject = "【センター受注】{$fcName}　{$orderId}";

    // 明細テキスト生成
    $itemLines = '';
    foreach ($items as $it) {
        $name   = $it['name']  ?? '';
        $code   = $it['code']  ?? '';
        $price  = number_format((float)($it['price'] ?? 0));
        $qty    = (int)($it['qty'] ?? 1);
        $amount = number_format((float)($it['price'] ?? 0) * $qty);
        $itemLines .= "  [{$code}] {$name}\n";
        $itemLines .= "        ¥{$price} × {$qty}個 = ¥{$amount}\n";
    }

    $subtotal_fmt = number_format($subtotal);
    $taxTotal_fmt = number_format($taxTotal);
    $total_fmt    = number_format($total);

    $body  = "センター受注システムに新しい注文が届きました。\n\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "【注文者情報】\n";
    $body .= "FC名　　：{$fcName}\n";
    $body .= "担当者　：{$contact}\n";
    $body .= "メール　：{$email}\n\n";
    $body .= "【受注情報】\n";
    $body .= "受注番号：{$orderId}\n";
    $body .= "注文日　：{$today}\n\n";
    $body .= "【商品明細】\n";
    $body .= $itemLines;
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "小計（税抜）：¥{$subtotal_fmt}\n";
    $body .= "消費税　　　：¥{$taxTotal_fmt}\n";
    $body .= "合計（税込）：¥{$total_fmt}\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($note !== '') {
        $body .= "\n【備考】\n{$note}\n";
    }

    $body .= "\n※ このメールはセンター受注システムから自動送信されています。\n";

    mb_language('japanese');
    mb_internal_encoding('UTF-8');
    mb_send_mail($to, $subject, $body, 'From: fc-order@asagiriyamano.jp');
}

// ────────────────────────────────────────
// 注文者（FC）への確認メール
// ────────────────────────────────────────
function sendOrderConfirmation($fcUser, $orderId, $today, $items, $subtotal, $taxTotal, $total, $note) {
    $to      = $fcUser['email']        ?? '';
    $fcName  = $fcUser['fc_name']      ?? '';
    $contact = $fcUser['contact_name'] ?? '';

    if ($to === '') return; // メールアドレスなければスキップ

    $subject = "【注文確認】{$orderId} 朝霧ヤマノ";

    // 明細テキスト生成
    $itemLines = '';
    foreach ($items as $it) {
        $name   = $it['name']  ?? '';
        $code   = $it['code']  ?? '';
        $price  = number_format((float)($it['price'] ?? 0));
        $qty    = (int)($it['qty'] ?? 1);
        $amount = number_format((float)($it['price'] ?? 0) * $qty);
        $itemLines .= "  [{$code}] {$name}\n";
        $itemLines .= "        ¥{$price} × {$qty}個 = ¥{$amount}\n";
    }

    $subtotal_fmt = number_format($subtotal);
    $taxTotal_fmt = number_format($taxTotal);
    $total_fmt    = number_format($total);

    $body  = "{$contact} 様\n\n";
    $body .= "このたびはご注文いただきありがとうございました。\n";
    $body .= "以下の内容で受け付けました。\n\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "【受注情報】\n";
    $body .= "受注番号：{$orderId}\n";
    $body .= "注文日　：{$today}\n";
    $body .= "FC名　　：{$fcName}\n\n";
    $body .= "【注文内容】\n";
    $body .= $itemLines;
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "小計（税抜）：¥{$subtotal_fmt}\n";
    $body .= "消費税　　　：¥{$taxTotal_fmt}\n";
    $body .= "合計（税込）：¥{$total_fmt}\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($note !== '') {
        $body .= "\n【備考】\n{$note}\n";
    }

    $body .= "\nご注文確認後、発注完了メールをお送りします。\n";
    $body .= "\n引き続きよろしくお願いいたします。\n\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "朝霧ヤマノ\n";
    $body .= "fc-order@asagiriyamano.jp\n";
    $body .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $body .= "※ このメールはセンター受注システムから自動送信されています。\n";

    mb_language('japanese');
    mb_internal_encoding('UTF-8');
    mb_send_mail($to, $subject, $body, 'From: fc-order@asagiriyamano.jp');
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
