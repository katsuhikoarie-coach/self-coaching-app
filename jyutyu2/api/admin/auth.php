<?php
// 管理者API共通認証ヘルパー
// 朝霧ヤマノユーザー（role=admin）のみ許可

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../session_check.php';

function requireAdmin(): array {
    $user = requireLogin();
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        jsonResponse(['error' => '管理者権限が必要です']);
        exit;
    }
    return $user;
}

// ── 月度ユーティリティ ────────────────────────────────────────
// 月度定義：当月20日〜翌月19日を1月度とする
// 例: 2026年6月度 = 5月20日〜6月19日

// 月度（YYYY-MM）から期間の開始日・終了日を計算
// 例: 2026-06 → start=2026-05-20, end=2026-06-19
function periodToDateRange(string $period): array {
    [$y, $m] = explode('-', $period);
    $y = (int)$y; $m = (int)$m;
    $prevM = $m - 1; $prevY = $y;
    if ($prevM === 0) { $prevM = 12; $prevY--; }
    return [
        'start' => sprintf('%04d-%02d-20', $prevY, $prevM),
        'end'   => sprintf('%04d-%02d-19', $y, $m),
    ];
}

// 日付から月度（YYYY-MM）を計算
// 例: 2026-05-20 以降 → 2026-06 / 2026-05-19 以前 → 2026-05
function dateToMonthPeriod(string $dateStr): string {
    $dt  = new DateTime($dateStr);
    $day = (int)$dt->format('d');
    if ($day >= 20) {
        $dt->modify('+1 month');
    }
    return $dt->format('Y-m');
}

// ── 年度ユーティリティ ────────────────────────────────────────
// 年度定義：当年5月20日〜翌年5月19日を1年度とする
// 例: 2027年度 = 2026年5月20日〜2027年5月19日

// 日付から年度（整数）を計算
// 例: 2026-05-20 以降 → 2027 / 2026-05-19 以前 → 2026
function dateToFiscalYear(string $dateStr): int {
    $dt    = new DateTime($dateStr);
    $month = (int)$dt->format('n');
    $day   = (int)$dt->format('j');
    $year  = (int)$dt->format('Y');
    if ($month > 5 || ($month === 5 && $day >= 20)) {
        return $year + 1;
    }
    return $year;
}

// 年度から期間の開始日・終了日を計算
// 例: 2027 → start=2026-05-20, end=2027-05-19
function fiscalYearToDateRange(int $fiscalYear): array {
    return [
        'start' => sprintf('%04d-05-20', $fiscalYear - 1),
        'end'   => sprintf('%04d-05-19', $fiscalYear),
    ];
}

// 当年度を返す
function currentFiscalYear(): int {
    return dateToFiscalYear(date('Y-m-d'));
}
