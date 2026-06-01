<?php
// ============================================================
// 管理者用 ヤマノ実績API
// GET ?month_period=YYYY-MM
//   レスポンス:
//     centers[]   センター別集計（fc_name・order_count・total_pretax）
//     grand_total 朝霧ヤマノ全体合計
//     unclassified[] 未分類一覧
//     fc_users[]  センター選択ドロップダウン用
// GET ?month_period=YYYY-MM&format=csv
//   → 明細CSVをShift-JISで返す
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();

// ── POST: キャンセル注文削除 ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db   = getDB();
    $body = getRequestBody();

    if (($body['action'] ?? '') !== 'delete_order') {
        http_response_code(400);
        jsonResponse(['error' => '不明なアクションです']);
        exit;
    }

    $yamano_order_id = trim($body['yamano_order_id'] ?? '');
    if (!$yamano_order_id) {
        http_response_code(400);
        jsonResponse(['error' => 'yamano_order_id は必須です']);
        exit;
    }

    // ステータス確認（キャンセルのみ削除可）
    $chk = $db->prepare("SELECT status FROM yamano_orders WHERE yamano_order_id = ?");
    $chk->execute([$yamano_order_id]);
    $row = $chk->fetch();
    if (!$row) {
        http_response_code(404);
        jsonResponse(['error' => '注文が見つかりません']);
        exit;
    }
    if (strpos($row['status'], 'キャンセル') === false) {
        http_response_code(400);
        jsonResponse(['error' => 'キャンセル注文のみ削除できます（現在のステータス: ' . $row['status'] . '）']);
        exit;
    }

    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM yamano_order_items WHERE yamano_order_id = ?")->execute([$yamano_order_id]);
        $db->prepare("DELETE FROM yamano_orders       WHERE yamano_order_id = ?")->execute([$yamano_order_id]);
        $db->commit();
        jsonResponse(['ok' => true, 'deleted' => $yamano_order_id]);
    } catch (\Throwable $e) {
        $db->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => '削除に失敗しました']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['error' => 'Method Not Allowed']);
    exit;
}

$db = getDB();
$month_period = $_GET['month_period'] ?? '';

// バリデーション
if (!$month_period || !preg_match('/^\d{4}-\d{2}$/', $month_period)) {
    $month_period = currentMonthPeriod();
}

// ── CSV ダウンロード ─────────────────────────────────────────
if (($_GET['format'] ?? '') === 'csv') {
    try {
        $stmt = $db->prepare("
            SELECT yo.yamano_order_id,
                   yo.order_date,
                   COALESCE(u.fc_name, '未分類') AS fc_name,
                   yo.shipping_name,
                   COALESCE(yoi.product_code,  '') AS product_code,
                   COALESCE(yoi.product_name,  '') AS product_name,
                   COALESCE(yoi.unit_price,    '') AS unit_price,
                   COALESCE(yoi.quantity,      '') AS quantity,
                   COALESCE(yoi.subtotal,      '') AS subtotal,
                   yo.total_pretax,
                   yo.status
              FROM yamano_orders yo
              LEFT JOIN yamano_order_items yoi ON yoi.yamano_order_id = yo.yamano_order_id
              LEFT JOIN fc_users u ON u.id = yo.fc_user_id
             WHERE yo.month_period = ?
             ORDER BY yo.order_date DESC, yo.yamano_order_id, yoi.id
        ");
        $stmt->execute([$month_period]);
        $rows = $stmt->fetchAll();

        $headers = ['注文番号', '注文日', 'センター名', '配送先氏名',
                    '商品コード', '商品名', '単価', '数量', '小計',
                    '注文合計（税別）', '注文状況'];

        $escape = fn($v) => '"' . str_replace('"', '""', (string)$v) . '"';

        $csv  = implode(',', array_map($escape, $headers)) . "\r\n";
        foreach ($rows as $r) {
            $csv .= implode(',', array_map($escape, [
                $r['yamano_order_id'],
                $r['order_date'],
                $r['fc_name'],
                $r['shipping_name'],
                $r['product_code'],
                $r['product_name'],
                $r['unit_price'],
                $r['quantity'],
                $r['subtotal'],
                $r['total_pretax'],
                $r['status'],
            ])) . "\r\n";
        }

        $sjis     = mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');
        $filename = 'yamano_' . $month_period . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=Shift-JIS');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sjis));
        echo $sjis;
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo 'CSV生成エラー';
    }
    exit;
}

try {
    // ── センター別集計 ─────────────────────────────────────
    $stmt = $db->prepare("
        SELECT MIN(u.id) AS fc_user_id,
               u.fc_name,
               COUNT(*)             AS order_count,
               SUM(yo.total_pretax) AS total_pretax
          FROM yamano_orders yo
          JOIN fc_users u ON u.id = yo.fc_user_id
         WHERE yo.month_period = ?
         GROUP BY u.fc_name
         ORDER BY total_pretax DESC
    ");
    $stmt->execute([$month_period]);
    $centers = $stmt->fetchAll();

    // ── カテゴリ別内訳（センター単位） ────────────────────
    $stmt = $db->prepare("
        SELECT
          u.fc_name,
          CASE
            WHEN p.category COLLATE utf8mb4_unicode_ci IN ('スキンケア','エステキープ','キット','ボディ＆ヘアケア','メイク','健康食品') THEN 'system'
            WHEN p.category COLLATE utf8mb4_unicode_ci = '一般'                THEN 'general'
            WHEN p.category COLLATE utf8mb4_unicode_ci IN ('CS商品','販促品')  THEN 'bettaguchi'
            ELSE 'unknown'
          END AS category_group,
          SUM(oi.subtotal) AS subtotal
        FROM yamano_orders yo
        JOIN fc_users u ON u.id = yo.fc_user_id
        JOIN yamano_order_items oi ON oi.yamano_order_id = yo.yamano_order_id
        LEFT JOIN products p ON p.code COLLATE utf8mb4_unicode_ci = oi.product_code COLLATE utf8mb4_unicode_ci
        WHERE yo.month_period = ?
          AND yo.fc_user_id IS NOT NULL
        GROUP BY u.fc_name, category_group
    ");
    $stmt->execute([$month_period]);
    $breakdownRows = $stmt->fetchAll();

    $breakdownMap = [];
    foreach ($breakdownRows as $row) {
        $name = $row['fc_name'];
        if (!isset($breakdownMap[$name])) {
            $breakdownMap[$name] = ['system' => 0, 'general' => 0, 'bettaguchi' => 0, 'unknown' => 0];
        }
        $breakdownMap[$name][$row['category_group']] = (int)$row['subtotal'];
    }
    foreach ($breakdownMap as $name => $bd) {
        $breakdownMap[$name]['system_general'] = $bd['system'] + $bd['general'];
    }
    $emptyBreakdown = ['system' => 0, 'general' => 0, 'bettaguchi' => 0, 'unknown' => 0, 'system_general' => 0];

    // intキャスト＋内訳マージ
    $centers = array_map(function ($c) use ($breakdownMap, $emptyBreakdown) {
        $name = $c['fc_name'];
        return [
            'fc_user_id'   => (int)$c['fc_user_id'],
            'fc_name'      => $name,
            'order_count'  => (int)$c['order_count'],
            'total_pretax' => (int)$c['total_pretax'],
            'breakdown'    => $breakdownMap[$name] ?? $emptyBreakdown,
        ];
    }, $centers);

    // ── 全体合計 ─────────────────────────────────────────
    $stmt = $db->prepare("
        SELECT COUNT(*) AS order_count, COALESCE(SUM(total_pretax), 0) AS total_pretax
          FROM yamano_orders
         WHERE month_period = ?
    ");
    $stmt->execute([$month_period]);
    $grand = $stmt->fetch();

    // ── 月度全体のカテゴリ別合計（分類済みのみ） ──────────
    $stmt = $db->prepare("
        SELECT
          CASE
            WHEN p.category COLLATE utf8mb4_unicode_ci IN ('スキンケア','エステキープ','キット','ボディ＆ヘアケア','メイク','健康食品') THEN 'system'
            WHEN p.category COLLATE utf8mb4_unicode_ci = '一般'                THEN 'general'
            WHEN p.category COLLATE utf8mb4_unicode_ci IN ('CS商品','販促品')  THEN 'bettaguchi'
            ELSE 'unknown'
          END AS category_group,
          SUM(oi.subtotal) AS subtotal
        FROM yamano_orders yo
        JOIN yamano_order_items oi ON oi.yamano_order_id = yo.yamano_order_id
        LEFT JOIN products p ON p.code COLLATE utf8mb4_unicode_ci = oi.product_code COLLATE utf8mb4_unicode_ci
        WHERE yo.month_period = ?
          AND yo.fc_user_id IS NOT NULL
        GROUP BY category_group
    ");
    $stmt->execute([$month_period]);
    $summaryBreakdownRows = $stmt->fetchAll();

    $summaryBreakdown = ['system' => 0, 'general' => 0, 'bettaguchi' => 0, 'unknown' => 0];
    foreach ($summaryBreakdownRows as $row) {
        $summaryBreakdown[$row['category_group']] = (int)$row['subtotal'];
    }
    $summaryBreakdown['system_general'] = $summaryBreakdown['system'] + $summaryBreakdown['general'];

    $summary = [
        'total'     => (int)$grand['total_pretax'],
        'count'     => (int)$grand['order_count'],
        'breakdown' => $summaryBreakdown,
    ];

    // ── 未分類一覧 ───────────────────────────────────────
    $stmt = $db->prepare("
        SELECT yamano_order_id, order_date, shipping_name, total_pretax, status
          FROM yamano_orders
         WHERE month_period = ? AND fc_user_id IS NULL
         ORDER BY order_date DESC
    ");
    $stmt->execute([$month_period]);
    $unclassified = $stmt->fetchAll();
    $unclassified = array_map(function ($r) {
        return [
            'yamano_order_id' => $r['yamano_order_id'],
            'order_date'      => $r['order_date'],
            'shipping_name'   => $r['shipping_name'],
            'total_pretax'    => (int)$r['total_pretax'],
            'status'          => $r['status'] ?? '',
        ];
    }, $unclassified);

    // ── 分類済み一覧（修正ボタン用） ─────────────────────
    $stmt = $db->prepare("
        SELECT yo.yamano_order_id, yo.order_date, yo.shipping_name,
               yo.total_pretax, yo.fc_user_id, yo.status, u.fc_name
          FROM yamano_orders yo
          LEFT JOIN fc_users u ON u.id = yo.fc_user_id
         WHERE yo.month_period = ? AND yo.fc_user_id IS NOT NULL
         ORDER BY yo.order_date DESC
    ");
    $stmt->execute([$month_period]);
    $classified = $stmt->fetchAll();
    $classified = array_map(function ($r) {
        return [
            'yamano_order_id' => $r['yamano_order_id'],
            'order_date'      => $r['order_date'],
            'shipping_name'   => $r['shipping_name'],
            'total_pretax'    => (int)$r['total_pretax'],
            'fc_user_id'      => (int)$r['fc_user_id'],
            'fc_name'         => $r['fc_name'] ?? '',
            'status'          => $r['status'] ?? '',
        ];
    }, $classified);

    // ── センター一覧（紐づけドロップダウン用） ────────────
    $stmt = $db->prepare("
        SELECT id, fc_name, center_type
          FROM fc_users
         WHERE active = 1
         ORDER BY id
    ");
    $stmt->execute();
    $fc_users = $stmt->fetchAll();
    $fc_users = array_map(function ($u) {
        return [
            'id'          => (int)$u['id'],
            'fc_name'     => $u['fc_name'],
            'center_type' => $u['center_type'] ?? '',
        ];
    }, $fc_users);

    jsonResponse([
        'month_period'  => $month_period,
        'centers'       => $centers,
        'summary'       => $summary,
        'unclassified'  => $unclassified,
        'classified'    => $classified,
        'fc_users'      => $fc_users,
    ]);

} catch (\Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'サーバーエラーが発生しました']);
}

// 当月度を返すヘルパー（月度定義: 20日〜翌月19日）
function currentMonthPeriod(): string {
    $now = new DateTime();
    $day = (int)$now->format('d');
    if ($day >= 20) {
        $now->modify('+1 month');
    }
    return $now->format('Y-m');
}
