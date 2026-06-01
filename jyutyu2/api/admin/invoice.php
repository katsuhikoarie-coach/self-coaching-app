<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
define('INVOICE_LOG', __DIR__ . '/invoice_error.log');

function invoiceLog(string $msg): void {
    file_put_contents(INVOICE_LOG, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}
// ============================================================
// 請求書発行API（掛率管理対応版）
// GET ?period=YYYY-MM&fc_user_id=N       → Excel(.xlsx)ダウンロード
// GET ?period=YYYY-MM&fc_user_id=N&preview=1 → JSON（プレビュー用）
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(['error' => 'Method Not Allowed']);
    exit;
}

$period     = trim($_GET['period']     ?? '');
$fc_user_id = (int)($_GET['fc_user_id'] ?? 0);
$preview    = ($_GET['preview'] ?? '') === '1';

if (!preg_match('/^\d{4}-\d{2}$/', $period) || $fc_user_id <= 0) {
    http_response_code(400);
    jsonResponse(['error' => 'period と fc_user_id は必須です']);
    exit;
}

$db = getDB();

// ── センター情報取得 ─────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM fc_users WHERE id = ? AND active = 1");
$stmt->execute([$fc_user_id]);
$fcUser = $stmt->fetch();
if (!$fcUser) {
    http_response_code(404);
    jsonResponse(['error' => 'センターが見つかりません']);
    exit;
}

$centerType  = $fcUser['center_type'] ?? 'FC';
$centerName  = $fcUser['fc_name'];
$contactName = $fcUser['contact_name'] ?? '';
$centerCode  = $fcUser['center_code'] ?? '';
$centerAddr  = $fcUser['address'] ?? '';

[$y, $m] = explode('-', $period);
$range        = periodToDateRange($period);
$periodLabel  = $y . '年' . ltrim($m, '0') . '月度';
$closingDate  = date('Y年n月j日', strtotime($range['end']));
$issueDate    = date('Y年n月j日');

// ── 適用掛率取得 ─────────────────────────────────────────────
// 基準日 = 請求月度の最終日
$baseDate = $range['end'];
$stmt = $db->prepare("
    SELECT * FROM center_rates
     WHERE fc_user_id = ?
       AND valid_from <= ?
       AND (valid_to IS NULL OR valid_to >= ?)
     ORDER BY valid_from DESC
     LIMIT 1
");
$stmt->execute([$fc_user_id, $baseDate, $baseDate]);
$rateRow = $stmt->fetch();

if (!$rateRow) {
    http_response_code(422);
    jsonResponse(['error' => 'この月度の掛率が登録されていません。掛率管理タブから登録してください。']);
    exit;
}

$rateSystem  = (float)$rateRow['rate_system'];
$rateGeneral = (float)$rateRow['rate_general'];

// ── 商品区分判定 ─────────────────────────────────────────────
// betsu=別口（CS商品・販促品・備品）/ ippan=一般 / system=システム
function itemGroup(?string $category): string {
    if ($category === null) return 'system';
    if (in_array($category, ['CS商品', '販促品', '備品', 'CS', '販促'], true)) return 'betsu';
    if ($category === '一般') return 'ippan';
    return 'system';
}

// ── 明細取得 ─────────────────────────────────────────────────
$items = [];

try {
    if ($centerType === 'BC') {
        invoiceLog('BC query: fc_user_id=' . $fc_user_id . ' period=' . $period);
        $stmt = $db->prepare("
            SELECT yoi.product_code  AS code,
                   yoi.product_name  AS name,
                   yoi.unit_price    AS yamano_price,
                   yoi.quantity      AS qty,
                   p.price_bc        AS price_bc,
                   p.tax_rate        AS tax_rate,
                   p.category        AS category,
                   yo.order_date     AS order_date
              FROM yamano_orders yo
              JOIN yamano_order_items yoi
                ON yoi.yamano_order_id COLLATE utf8mb4_unicode_ci = yo.yamano_order_id COLLATE utf8mb4_unicode_ci
              LEFT JOIN products p
                ON p.code COLLATE utf8mb4_unicode_ci = yoi.product_code COLLATE utf8mb4_unicode_ci
               AND p.active = 1
             WHERE yo.fc_user_id   = ?
               AND yo.month_period COLLATE utf8mb4_unicode_ci = ?
               AND yo.status NOT LIKE '%キャンセル%'
             ORDER BY yo.order_date, yoi.id
        ");
        $stmt->execute([$fc_user_id, $period]);
        $rows = $stmt->fetchAll();
        invoiceLog('BC rows: ' . count($rows));

        foreach ($rows as $r) {
            $matched = ($r['price_bc'] !== null);
            $price   = $matched ? (float)$r['price_bc'] : (float)$r['yamano_price'];
            $qty     = (int)$r['qty'];
            $taxRate = $matched ? (float)$r['tax_rate'] : 0.10;
            $group   = itemGroup($matched ? $r['category'] : null);
            $items[] = [
                'code'       => $r['code'],
                'name'       => $r['name'],
                'price'      => $price,
                'qty'        => $qty,
                'tax_rate'   => $taxRate,
                'group'      => $group,
                'flag'       => $matched ? '' : '※要確認',
                'order_date' => date('Y/m/d', strtotime($r['order_date'])),
            ];
        }
    } else {
        // FC / 販社
        $stmt = $db->prepare("
            SELECT oi.product_code AS code,
                   oi.product_name AS name,
                   oi.price,
                   oi.quantity     AS qty,
                   oi.tax_rate,
                   p.category      AS category,
                   o.order_date    AS order_date
              FROM orders o
              JOIN order_items oi ON oi.order_id = o.id
              LEFT JOIN products p
                ON p.code = oi.product_code AND p.active = 1
             WHERE o.fc_user_id  = ?
               AND o.month_period = ?
               AND o.status      != 'cancelled'
             ORDER BY o.created_at, oi.id
        ");
        $stmt->execute([$fc_user_id, $period]);
        foreach ($stmt->fetchAll() as $r) {
            $items[] = [
                'code'       => $r['code'],
                'name'       => $r['name'],
                'price'      => (float)$r['price'],
                'qty'        => (int)$r['qty'],
                'tax_rate'   => (float)$r['tax_rate'],
                'group'      => itemGroup($r['category']),
                'flag'       => '',
                'order_date' => date('Y/m/d', strtotime($r['order_date'])),
            ];
        }
    }
} catch (\Throwable $e) {
    invoiceLog('SQL error: ' . $e->getMessage());
    http_response_code(500);
    jsonResponse(['error' => 'データ取得に失敗しました: ' . $e->getMessage()]);
    exit;
}

// ── 上代・下代の計算 ─────────────────────────────────────────
foreach ($items as &$it) {
    $upper = round($it['price'] * $it['qty']);
    $it['upper'] = $upper;

    switch ($it['group']) {
        case 'system':
            $it['lower'] = (int)round($upper * $rateSystem);
            break;
        case 'ippan':
            $it['lower'] = (int)round($upper * $rateGeneral);
            break;
        default: // betsu
            $it['lower'] = $upper;
    }
}
unset($it);

// ── 集計 ─────────────────────────────────────────────────────
$sysUpper   = 0; $sysLower   = 0;
$sysUpper8  = 0; $sysLower8  = 0;
$sysUpper10 = 0; $sysLower10 = 0;
$ipUpper    = 0; $ipLower    = 0;
$betUpper   = 0; $betLower   = 0;

foreach ($items as $it) {
    switch ($it['group']) {
        case 'system':
            $sysUpper += $it['upper'];
            $sysLower += $it['lower'];
            if ($it['tax_rate'] < 0.10) {
                $sysUpper8 += $it['upper'];
                $sysLower8 += $it['lower'];
            } else {
                $sysUpper10 += $it['upper'];
                $sysLower10 += $it['lower'];
            }
            break;
        case 'ippan':
            $ipUpper += $it['upper'];
            $ipLower += $it['lower'];
            break;
        default:
            $betUpper += $it['upper'];
            $betLower += $it['lower'];
    }
}

$lower8pct      = $sysLower8;
$tax8           = (int)round($lower8pct * 0.08);
$lower10pct     = $sysLower10 + $ipLower + $betLower;
$tax10          = (int)round($lower10pct * 0.10);
$totalLower     = $sysLower + $ipLower + $betLower;  // 当月御買上額
$taxTotal       = $tax8 + $tax10;
$grandTotal     = $totalLower + $taxTotal;            // 繰越額=0前提

// ── プレビューJSON ───────────────────────────────────────────
if ($preview) {
    jsonResponse([
        'center_name'  => $centerName,
        'contact_name' => $contactName,
        'center_type'  => $centerType,
        'period_label' => $periodLabel,
        'closing_date' => $closingDate,
        'rate_system'  => $rateSystem,
        'rate_general' => $rateGeneral,
        'items'        => $items,
        'sys_upper'    => $sysUpper,   'sys_lower'   => $sysLower,
        'ip_upper'     => $ipUpper,    'ip_lower'    => $ipLower,
        'bet_upper'    => $betUpper,   'bet_lower'   => $betLower,
        'lower8pct'    => $lower8pct,  'tax8'        => $tax8,
        'lower10pct'   => $lower10pct, 'tax10'       => $tax10,
        'total_lower'  => $totalLower,
        'tax_total'    => $taxTotal,
        'grand_total'  => $grandTotal,
    ]);
    exit;
}

// ── Excel生成 ─────────────────────────────────────────────────
if (!extension_loaded('xlswriter')) {
    http_response_code(500);
    jsonResponse(['error' => 'xlswriter拡張が有効ではありません']);
    exit;
}

$tmpDir = sys_get_temp_dir();
$fname  = 'invoice_' . $period . '_' . $fc_user_id . '_' . time() . '.xlsx';

try {
    invoiceLog('Excel start: period=' . $period . ' fc_user_id=' . $fc_user_id
        . ' items=' . count($items) . ' grandTotal=' . $grandTotal
        . ' rateSystem=' . $rateSystem . ' rateGeneral=' . $rateGeneral);

    $excel  = new \Vtiful\Kernel\Excel(['path' => $tmpDir]);
    $sheet  = $excel->fileName($fname, '表紙');
    $handle = $sheet->getHandle();

    // ── フォーマット定義 ──────────────────────────────────────
    $fmtTitle    = (new \Vtiful\Kernel\Format($handle))->bold()->fontSize(20)->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_CENTER)->toResource();
    $fmtBold     = (new \Vtiful\Kernel\Format($handle))->bold()->toResource();
    $fmtRight    = (new \Vtiful\Kernel\Format($handle))->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_RIGHT)->toResource();
    $fmtBoldR    = (new \Vtiful\Kernel\Format($handle))->bold()->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_RIGHT)->toResource();
    $fmtCenter   = (new \Vtiful\Kernel\Format($handle))->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_CENTER)->toResource();
    $fmtBoldC    = (new \Vtiful\Kernel\Format($handle))->bold()->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_CENTER)->toResource();
    $fmtHdr      = (new \Vtiful\Kernel\Format($handle))->bold()->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_CENTER)->fontSize(9)->toResource();
    $fmtSmall    = (new \Vtiful\Kernel\Format($handle))->fontSize(9)->toResource();
    $fmtSmallR   = (new \Vtiful\Kernel\Format($handle))->fontSize(9)->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_RIGHT)->toResource();

    $yen = fn(int $v): string => '¥' . number_format($v);

    // ── 列幅（表紙） ──────────────────────────────────────────
    // A(0)=14, B(1)=14, C(2)=14, D(3)=14, E(4)=4, F(5)=18, G(6)=6, H(7)=6, I(8)=6
    $sheet->setColumn('A:A', 14);
    $sheet->setColumn('B:B', 14);
    $sheet->setColumn('C:C', 14);
    $sheet->setColumn('D:D', 14);
    $sheet->setColumn('E:E', 4);
    $sheet->setColumn('F:F', 20);
    $sheet->setColumn('G:G', 6);
    $sheet->setColumn('H:H', 6);
    $sheet->setColumn('I:I', 6);

    $r = 0;
    // ── タイトル ──────────────────────────────────────────────
    $sheet->insertText($r, 0, '御  請  求  書', null, $fmtTitle);
    $r += 2;

    // ── 発行元（右側） ────────────────────────────────────────
    $sheet->insertText($r, 5, '山野愛子どろんこ美容　朝霧ヤマノ', null, $fmtBold);
    $r++;
    $sheet->insertText($r, 5, '登録番号：T3140002005245', null, $fmtSmall);
    $r++;
    $sheet->insertText($r, 5, '兵庫県明石市朝霧町 3-15-15', null, $fmtSmall);
    $r++;
    $sheet->insertText($r, 5, 'TEL 078-918-0585', null, $fmtSmall);
    $r++;

    // ── 宛名（左側） ─────────────────────────────────────────
    $labelText = $centerName;
    if ($contactName) $labelText .= '　' . $contactName . ' 様';
    if ($centerCode)  $labelText .= '（' . $centerCode . '）';
    $sheet->insertText($r, 0, $labelText, null, $fmtBold);
    if ($centerAddr) {
        $r++;
        $sheet->insertText($r, 0, $centerAddr, null, $fmtSmall);
    }
    $r += 2;

    // ── 下記の通り御請求申し上げます ─────────────────────────
    $sheet->insertText($r, 0, '下記の通り御請求申し上げます。');
    $r += 2;

    // ── 繰越テーブル ──────────────────────────────────────────
    foreach (['前月御請求額', '当月御入金額', '繰 越 額'] as $ci => $label) {
        $sheet->insertText($r, $ci, $label, null, $fmtHdr);
    }
    $sheet->insertText($r, 5, $y . ' 年 ' . ltrim($m, '0') . ' 月分', null, $fmtBold);
    $r++;
    foreach ([0, 1, 2] as $ci) {
        $sheet->insertText($r, $ci, $yen(0), null, $fmtRight);
    }
    $sheet->insertText($r, 5, $closingDate . ' 締切り');
    $r += 2;

    // ── 買上・請求テーブル ────────────────────────────────────
    $buyHeaders = ['当月御買上額', '値引・その他', '合  計', '当月消費税額', '当月御請求金額'];
    foreach ($buyHeaders as $ci => $label) {
        $sheet->insertText($r, $ci, $label, null, $fmtHdr);
    }
    $r++;
    $sheet->insertText($r, 0, $yen($totalLower),  null, $fmtBoldR);
    $sheet->insertText($r, 1, $yen(0),             null, $fmtRight);
    $sheet->insertText($r, 2, $yen($totalLower),   null, $fmtBoldR);
    $sheet->insertText($r, 3, $yen($taxTotal),     null, $fmtBoldR);
    $sheet->insertText($r, 4, $yen($grandTotal),   null, $fmtBoldR);
    $r += 2;

    // ── 8%対象区分 ────────────────────────────────────────────
    $sheet->insertText($r, 1, '（8%対象商品）', null, $fmtCenter);
    $sheet->insertText($r, 3, '（8%対象消費税）', null, $fmtCenter);
    $r++;
    $sheet->insertText($r, 0, $yen($sysLower8), null, $fmtRight);
    $sheet->insertText($r, 1, $yen(0),           null, $fmtRight);
    $sheet->insertText($r, 2, $yen($lower8pct),  null, $fmtRight);
    $sheet->insertText($r, 3, $yen($tax8),        null, $fmtRight);
    $r += 2;

    // ── 10%対象区分 ───────────────────────────────────────────
    $sheet->insertText($r, 1, '（10%対象商品）', null, $fmtCenter);
    $sheet->insertText($r, 3, '（10%対象消費税）', null, $fmtCenter);
    $r++;
    $sheet->insertText($r, 2, $yen($lower10pct), null, $fmtRight);
    $sheet->insertText($r, 3, $yen($tax10),       null, $fmtRight);
    $r += 2;

    // ── 上代・下代テーブル ────────────────────────────────────
    $sheet->insertText($r, 1, 'システム',  null, $fmtBoldC);
    $sheet->insertText($r, 2, '一  般',   null, $fmtBoldC);
    $sheet->insertText($r, 3, '別  口',   null, $fmtBoldC);
    $sheet->insertText($r, 6, '検印', null, $fmtCenter);
    $sheet->insertText($r, 7, '検印', null, $fmtCenter);
    $sheet->insertText($r, 8, '検印', null, $fmtCenter);
    $r++;
    $sheet->insertText($r, 0, '上代', null, $fmtBold);
    $sheet->insertText($r, 1, $yen($sysUpper),  null, $fmtRight);
    $sheet->insertText($r, 2, $yen($ipUpper),   null, $fmtRight);
    $sheet->insertText($r, 3, $yen($betUpper),  null, $fmtRight);
    $r++;
    $sheet->insertText($r, 0, '　8%対象', null, $fmtSmall);
    $sheet->insertText($r, 1, $yen($sysUpper8),  null, $fmtSmallR);
    $r++;
    $sheet->insertText($r, 0, '　10%対象', null, $fmtSmall);
    $sheet->insertText($r, 1, $yen($sysUpper10), null, $fmtSmallR);
    $sheet->insertText($r, 2, $yen($ipUpper),    null, $fmtSmallR);
    $sheet->insertText($r, 3, $yen($betUpper),   null, $fmtSmallR);
    $r++;
    $sheet->insertText($r, 0, '下代', null, $fmtBold);
    $sheet->insertText($r, 1, $yen($sysLower),  null, $fmtRight);
    $sheet->insertText($r, 2, $yen($ipLower),   null, $fmtRight);
    $sheet->insertText($r, 3, $yen($betLower),  null, $fmtRight);
    $r++;
    $sheet->insertText($r, 0, '　8%対象', null, $fmtSmall);
    $sheet->insertText($r, 1, $yen($sysLower8),  null, $fmtSmallR);
    $r++;
    $sheet->insertText($r, 0, '　10%対象', null, $fmtSmall);
    $sheet->insertText($r, 1, $yen($sysLower10), null, $fmtSmallR);
    $sheet->insertText($r, 2, $yen($ipLower),    null, $fmtSmallR);
    $sheet->insertText($r, 3, $yen($betLower),   null, $fmtSmallR);
    $r += 2;

    // ── 振込先 ────────────────────────────────────────────────
    $sheet->insertText($r, 0, 'お振込み口座', null, $fmtBold); $r++;
    $sheet->insertText($r, 0, '三井住友銀行　明石支店　普通'); $r++;
    $sheet->insertText($r, 0, 'No.4810132'); $r++;
    $sheet->insertText($r, 0, '名義：有限会社まゆ企画', null, $fmtBold); $r++;
    $sheet->insertText($r, 0, '※振り込み手数料は、ご負担いただきますようよろしくお願いいたします。', null, $fmtSmall);

    // ╔══════════════════════════════════════════════════════════
    // ║  Sheet 2: 請求明細
    // ╚══════════════════════════════════════════════════════════
    $excel->addSheet('請求明細');

    $sheet->setColumn('A:A', 11);  // 注文日
    $sheet->setColumn('B:B', 12);  // 商品コード
    $sheet->setColumn('C:C', 36);  // 商品名
    $sheet->setColumn('D:D', 12);  // 単価
    $sheet->setColumn('E:E', 6);   // 個数
    $sheet->setColumn('F:F', 8);   // 区分
    $sheet->setColumn('G:G', 14);  // 金額(下代)
    $sheet->setColumn('H:H', 14);  // 上代システム
    $sheet->setColumn('I:I', 12);  // 上代一般
    $sheet->setColumn('J:J', 12);  // 上代販促

    $r = 0;
    $sheet->insertText($r, 0, '請  求  明  細  書', null, $fmtTitle);
    $r++;
    $sheet->insertText($r, 0, $periodLabel . '（' . $issueDate . '発行）');
    $sheet->insertText($r, 5, '8%☆　一般　販促別', null, $fmtSmall);
    $r += 2;

    $sheet->insertText($r, 0, 'お得意先名', null, $fmtSmall);
    $r++;
    $sheet->insertText($r, 0, $centerName, null, $fmtBold);
    $r++;
    if ($contactName) {
        $sheet->insertText($r, 0, $contactName . ' 様');
    }
    $sheet->insertText($r, 5,
        'システム掛率 ' . number_format($rateSystem * 100, 0) . '%　'
        . '一般掛率 '   . number_format($rateGeneral * 100, 0) . '%',
        null, $fmtBold
    );
    $r += 2;

    // ── 明細ヘッダー ──────────────────────────────────────────
    $detailHeaders = ['注文日', '商品コード', '商品名', '単価', '個数', '区分', '金額(下代)', '上代システム', '上代一般', '上代販促'];
    foreach ($detailHeaders as $ci => $h) {
        $sheet->insertText($r, $ci, $h, null, $fmtBoldC);
    }
    $r++;

    // ── 明細行 ───────────────────────────────────────────────
    $GROUP_LABEL = ['system' => 'システム', 'ippan' => '一般', 'betsu' => '別口'];

    foreach ($items as $it) {
        $sheet->insertText($r, 0, $it['order_date']);
        $sheet->insertText($r, 1, $it['code']);
        $sheet->insertText($r, 2, $it['name']);
        $sheet->insertText($r, 3, $yen((int)$it['price']),  null, $fmtRight);
        $sheet->insertText($r, 4, $it['qty'],                null, $fmtRight);

        $taxLabel = ($it['tax_rate'] < 0.10) ? '8%☆' : '10%';
        $grpLabel = ($GROUP_LABEL[$it['group']] ?? '');
        $sheet->insertText($r, 5, $grpLabel . ' ' . $taxLabel);

        $sheet->insertText($r, 6, $yen($it['lower']), null, $fmtRight);

        switch ($it['group']) {
            case 'system':
                $sheet->insertText($r, 7, $yen($it['upper']), null, $fmtRight);
                break;
            case 'ippan':
                $sheet->insertText($r, 8, $yen($it['upper']), null, $fmtRight);
                break;
            case 'betsu':
                $sheet->insertText($r, 9, $yen($it['upper']), null, $fmtRight);
                break;
        }

        if ($it['flag']) {
            $fmtRed = (new \Vtiful\Kernel\Format($handle))->fontColor(0xFF0000)->toResource();
            $sheet->insertText($r, 2, $it['name'] . '　' . $it['flag'], null, $fmtRed);
        }
        $r++;
    }

    if (empty($items)) {
        $sheet->insertText($r, 0, 'この月度の注文はありません');
        $r++;
    }

    $r++;  // 空行

    // ── フッター合計 ──────────────────────────────────────────
    $sheet->insertText($r, 5, '総合計', null, $fmtBold);
    $sheet->insertText($r, 6, $yen($totalLower), null, $fmtBoldR);
    $r++;

    $sheet->insertText($r, 5, '合計 8%☆', null, $fmtBold);
    $sheet->insertText($r, 6, $yen($lower8pct), null, $fmtRight);
    $r++;

    $sheet->insertText($r, 5, '合計 10%', null, $fmtBold);
    $sheet->insertText($r, 6, $yen($lower10pct), null, $fmtRight);

    // 10%上代合計（システム+一般+別口）
    $upper10Total = $sysUpper10 + $ipUpper + $betUpper;
    $sheet->insertText($r, 7, $yen($upper10Total), null, $fmtRight);
    $r += 2;

    $sheet->insertText($r, 5, '消費税（8%）', null, $fmtSmall);
    $sheet->insertText($r, 6, $yen($tax8), null, $fmtSmallR);
    $r++;
    $sheet->insertText($r, 5, '消費税（10%）', null, $fmtSmall);
    $sheet->insertText($r, 6, $yen($tax10), null, $fmtSmallR);
    $r++;
    $sheet->insertText($r, 5, '税込合計', null, $fmtBold);
    $sheet->insertText($r, 6, $yen($grandTotal), null, $fmtBoldR);

    // ── 出力 ─────────────────────────────────────────────────
    $outputPath  = $sheet->output();
    $fileContent = file_get_contents($outputPath);
    @unlink($outputPath);

    $safeName = preg_replace('/[^\w\-]/u', '_', $centerName);
    $dlName   = 'invoice_' . $period . '_' . $safeName . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . rawurlencode($dlName) . '"');
    header('Content-Length: ' . strlen($fileContent));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo $fileContent;

} catch (\Throwable $e) {
    invoiceLog('Excel error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    invoiceLog('trace: ' . $e->getTraceAsString());
    http_response_code(500);
    jsonResponse(['error' => 'Excel生成に失敗しました: ' . $e->getMessage()]);
}
