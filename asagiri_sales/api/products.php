<?php
require_once __DIR__ . '/../config/db.php';
requireAuth();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();
$action = $_GET['action'] ?? $body['action'] ?? '';

// ────────────────────────────────────────
// GET: 商品マスタ一覧
// ────────────────────────────────────────
if ($method === 'GET') {
    $includeDisc = !empty($_GET['include_disc']);
    $sql  = $includeDisc
        ? "SELECT * FROM products ORDER BY discontinued, code"
        : "SELECT * FROM products WHERE discontinued = 0 ORDER BY code";
    $stmt = $db->query($sql);
    $rows   = $stmt->fetchAll();
    $result = array_map(function($p) {
        return [
            'code'       => $p['code'],
            'name'       => $p['name'],
            'price'      => (int)$p['price'],
            'genre'      => $p['genre'],
            'supplier'   => $p['supplier'],
            'active'     => !$p['discontinued'],
            'disc'       => (bool)$p['discontinued'],
        ];
    }, $rows);
    jsonResponse($result);
    exit;
}

// ────────────────────────────────────────
// POST: 商品の登録・更新
// ────────────────────────────────────────
if ($method === 'POST') {

    // action=replace_all : 全件一括保存
    if ($action === 'replace_all') {
        $products = $body['data'] ?? [];
        if (empty($products)) {
            jsonResponse(['ok' => true, 'count' => 0]);
            exit;
        }
        $stmt = $db->prepare("
            INSERT INTO products (code, name, price, genre, supplier, discontinued)
            VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name), price=VALUES(price), genre=VALUES(genre),
              supplier=VALUES(supplier), discontinued=VALUES(discontinued)
        ");
        $db->beginTransaction();
        foreach ($products as $p) {
            $code = $p['code'] ?? '';
            if (!$code) continue;
            $disc = (isset($p['active']) && $p['active'] === false) || !empty($p['disc']) ? 1 : 0;
            $stmt->execute([
                $code,
                $p['name'] ?? '',
                (float)($p['price'] ?? 0),
                $p['genre'] ?? 'その他',
                $p['supplier'] ?? '',
                $disc,
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'count' => count($products)]);
        exit;
    }

    // action=toggle : 終息/復活トグル
    if ($action === 'toggle') {
        $code = $body['code'] ?? '';
        if (!$code) {
            http_response_code(400);
            jsonResponse(['error' => 'code は必須']);
            exit;
        }
        $db->prepare("UPDATE products SET discontinued = 1 - discontinued WHERE code = ?")->execute([$code]);
        jsonResponse(['ok' => true]);
        exit;
    }

    // action=update : 1件更新
    if ($action === 'update') {
        $code = $body['code'] ?? '';
        $data = $body['data'] ?? [];
        if (!$code) {
            http_response_code(400);
            jsonResponse(['error' => 'code は必須']);
            exit;
        }
        try {
            $disc = isset($data['disc']) ? (int)$data['disc'] : 0;
            $db->prepare("UPDATE products SET name=?, genre=?, price=?, supplier=?, discontinued=? WHERE code=?")
               ->execute([
                   $data['name']     ?? '',
                   $data['genre']    ?? 'その他',
                   (float)($data['price']    ?? 0),
                   $data['supplier'] ?? '',
                   $disc,
                   $code,
               ]);
            jsonResponse(['ok' => true]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            jsonResponse(['error' => 'サーバーエラーが発生しました']);
        }
        exit;
    }

    // 新規商品1件登録
    $code = $body['code'] ?? '';
    $name = $body['name'] ?? '';
    if (!$code || !$name) {
        http_response_code(400);
        jsonResponse(['error' => 'code と name は必須']);
        exit;
    }
    $db->prepare("
        INSERT INTO products (code, name, price, genre, supplier, discontinued)
        VALUES (?,?,?,?,?,0)
        ON DUPLICATE KEY UPDATE
          name=VALUES(name), price=VALUES(price), genre=VALUES(genre), supplier=VALUES(supplier)
    ")->execute([
        $code, $name,
        (float)($body['price'] ?? 0),
        $body['genre'] ?? 'その他',
        $body['supplier'] ?? '',
    ]);
    jsonResponse(['ok' => true, 'code' => $code]);
    exit;
}

// ────────────────────────────────────────
// DELETE: 商品の物理削除
// ────────────────────────────────────────
if ($method === 'DELETE') {
    $code = $_GET['code'] ?? $body['code'] ?? '';
    if (!$code) {
        http_response_code(400);
        jsonResponse(['error' => 'code は必須']);
        exit;
    }

    // 在庫残あり → 削除禁止
    $stockRow = $db->prepare("SELECT qty FROM stock WHERE code = ?")->execute([$code])
        ? $db->prepare("SELECT qty FROM stock WHERE code = ?") : null;
    $stockStmt = $db->prepare("SELECT qty FROM stock WHERE code = ?");
    $stockStmt->execute([$code]);
    $stockQty = $stockStmt->fetchColumn();
    if ($stockQty !== false && (int)$stockQty > 0) {
        http_response_code(409);
        jsonResponse(['error' => '在庫が残っているため削除できません（在庫数: ' . (int)$stockQty . '）']);
        exit;
    }

    // 売上実績あり → 削除禁止
    $salesStmt = $db->prepare("SELECT COUNT(*) FROM sales_items WHERE product_code = ?");
    $salesStmt->execute([$code]);
    if ((int)$salesStmt->fetchColumn() > 0) {
        http_response_code(409);
        jsonResponse(['error' => '売上実績があるため削除できません（終息処理をご利用ください）']);
        exit;
    }

    $db->beginTransaction();
    $db->prepare("DELETE FROM stock WHERE code = ?")->execute([$code]);
    $db->prepare("DELETE FROM products WHERE code = ?")->execute([$code]);
    $db->commit();
    jsonResponse(['ok' => true]);
    exit;
}

http_response_code(405);
jsonResponse(['error' => 'Method Not Allowed']);
