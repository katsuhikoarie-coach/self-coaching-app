<?php
// ============================================================
// 管理者用 商品API
// GET  ?action=list   → 全商品一覧（active=0含む、q/categoryで絞り込み可）
// POST action=add     → 新規追加
// POST action=update  → 編集（activeフィールドが含まれれば更新）
// POST action=delete  → active=0に更新（物理削除禁止）
// ============================================================
require_once __DIR__ . '/auth.php';
requireAdmin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: 商品一覧 ───────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action !== 'list') {
        http_response_code(400);
        jsonResponse(['error' => '不明なアクションです']);
        exit;
    }

    try {
        $q        = trim($_GET['q']        ?? '');
        $category = trim($_GET['category'] ?? '');

        $sql    = "SELECT id, code, name, category, price_hansha, price_bc, price_fc, tax_rate, active, note FROM products WHERE 1=1";
        $params = [];

        if ($q !== '') {
            $sql     .= " AND (name LIKE ? OR code LIKE ?)";
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }
        if ($category !== '') {
            $sql     .= " AND category = ?";
            $params[] = $category;
        }
        $sql .= " ORDER BY id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['id']           = (int)   $row['id'];
            $row['price_hansha'] = (int)   $row['price_hansha'];
            $row['price_bc']     = (int)   $row['price_bc'];
            $row['price_fc']     = (int)   $row['price_fc'];
            $row['tax_rate']     = (float) $row['tax_rate'];
            $row['active']       = (int)   $row['active'];
        }
        unset($row);

        jsonResponse($rows);
    } catch (\Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        jsonResponse(['error' => 'サーバーエラーが発生しました']);
    }
    exit;
}

// ── POST: 追加・更新・削除 ──────────────────────────────────
if ($method === 'POST') {
    $body   = getRequestBody();
    $action = $body['action'] ?? '';

    // ── 新規追加 ────────────────────────────────────────────
    if ($action === 'add') {
        $code         = trim($body['code']         ?? '');
        $name         = trim($body['name']         ?? '');
        $category     = trim($body['category']     ?? '');
        $price_hansha = (int)($body['price_hansha'] ?? 0);
        $price_bc     = (int)($body['price_bc']     ?? 0);
        $price_fc     = (int)($body['price_fc']     ?? 0);
        $tax_rate_raw = (float)($body['tax_rate']   ?? 0.10);
        $tax_rate     = in_array($tax_rate_raw, [0.08, 0.10], true) ? $tax_rate_raw : 0.10;
        $note         = trim($body['note'] ?? '') ?: null;

        if (!$code || !$name || !$category) {
            http_response_code(400);
            jsonResponse(['error' => 'コード・商品名・カテゴリは必須です']);
            exit;
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO products (code, name, category, price_hansha, price_bc, price_fc, tax_rate, note, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$code, $name, $category, $price_hansha, $price_bc, $price_fc, $tax_rate, $note]);
            jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                jsonResponse(['error' => 'このコードは既に登録されています']);
            } else {
                error_log($e->getMessage());
                http_response_code(500);
                jsonResponse(['error' => '追加に失敗しました']);
            }
        }
        exit;
    }

    // ── 編集 ────────────────────────────────────────────────
    if ($action === 'update') {
        $id           = (int)($body['id']           ?? 0);
        $name         = trim($body['name']         ?? '');
        $category     = trim($body['category']     ?? '');
        $price_hansha = (int)($body['price_hansha'] ?? 0);
        $price_bc     = (int)($body['price_bc']     ?? 0);
        $price_fc     = (int)($body['price_fc']     ?? 0);
        $tax_rate_raw = (float)($body['tax_rate']   ?? 0.10);
        $tax_rate     = in_array($tax_rate_raw, [0.08, 0.10], true) ? $tax_rate_raw : 0.10;
        $note         = trim($body['note'] ?? '') ?: null;

        if (!$id || !$name || !$category) {
            http_response_code(400);
            jsonResponse(['error' => 'ID・商品名・カテゴリは必須です']);
            exit;
        }

        $setClauses = "name=?, category=?, price_hansha=?, price_bc=?, price_fc=?, tax_rate=?, note=?";
        $params     = [$name, $category, $price_hansha, $price_bc, $price_fc, $tax_rate, $note];

        if (array_key_exists('active', $body)) {
            $setClauses .= ", active=?";
            $params[]    = (int)(bool)$body['active'];
        }
        $params[] = $id;

        try {
            $stmt = $db->prepare("UPDATE products SET {$setClauses} WHERE id=?");
            $stmt->execute($params);
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => '更新に失敗しました']);
        }
        exit;
    }

    // ── 削除（active=0） ────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            jsonResponse(['error' => 'IDは必須です']);
            exit;
        }
        try {
            $stmt = $db->prepare("UPDATE products SET active=0 WHERE id=?");
            $stmt->execute([$id]);
            jsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => '削除に失敗しました']);
        }
        exit;
    }

    http_response_code(400);
    jsonResponse(['error' => '不明なアクションです']);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
