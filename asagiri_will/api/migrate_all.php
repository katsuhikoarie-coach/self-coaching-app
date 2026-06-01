<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=UTF-8');

$db = getDB();
$results = [];

$tables = [

// ══════════════════════════════════════════════
// ダンプから抽出（COMMENT句除去済み）
// ══════════════════════════════════════════════

'customers' => "CREATE TABLE IF NOT EXISTS `customers` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `kana` varchar(100) DEFAULT '',
  `zip` varchar(10) DEFAULT '',
  `address` varchar(200) DEFAULT '',
  `tel` varchar(30) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `birthday` date DEFAULT NULL,
  `gender` enum('M','F','') DEFAULT '',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'products' => "CREATE TABLE IF NOT EXISTS `products` (
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `genre` varchar(50) DEFAULT '',
  `supplier` varchar(100) DEFAULT '',
  `discontinued` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'sales' => "CREATE TABLE IF NOT EXISTS `sales` (
  `id` varchar(20) NOT NULL,
  `sale_date` date NOT NULL,
  `year_month` varchar(7) NOT NULL,
  `deliver_date` date DEFAULT NULL,
  `customer_id` varchar(20) DEFAULT '',
  `customer_name` varchar(100) DEFAULT '',
  `staff` varchar(50) DEFAULT '',
  `total` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `payment` varchar(30) DEFAULT '',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'sales_items' => "CREATE TABLE IF NOT EXISTS `sales_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` varchar(20) NOT NULL,
  `product_code` varchar(20) DEFAULT '',
  `product_name` varchar(100) DEFAULT '',
  `qty` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'stock' => "CREATE TABLE IF NOT EXISTS `stock` (
  `code` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT '',
  `price` decimal(10,2) DEFAULT 0.00,
  `qty` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'stock_inout' => "CREATE TABLE IF NOT EXISTS `stock_inout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `io_date` date NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT '',
  `in_qty` int(11) DEFAULT 0,
  `out_qty` int(11) DEFAULT 0,
  `stock_after` int(11) DEFAULT 0,
  `note` varchar(200) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'este_menus' => "CREATE TABLE IF NOT EXISTS `este_menus` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(11) DEFAULT 0,
  `tax_rate` int(11) DEFAULT 10,
  `category` varchar(50) DEFAULT '',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'este_sales' => "CREATE TABLE IF NOT EXISTS `este_sales` (
  `id` varchar(30) NOT NULL,
  `sale_date` date NOT NULL,
  `cust_no` varchar(20) DEFAULT '',
  `cust_name` varchar(100) DEFAULT '',
  `staff` varchar(50) DEFAULT '',
  `subtotal` int(11) DEFAULT 0,
  `tax` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  `payment` varchar(30) DEFAULT '',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'este_sales_items' => "CREATE TABLE IF NOT EXISTS `este_sales_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` varchar(30) NOT NULL,
  `menu_id` varchar(20) DEFAULT '',
  `menu_name` varchar(100) DEFAULT '',
  `item_type` enum('ticket','single','goods') DEFAULT 'single',
  `qty` int(11) DEFAULT 1,
  `price` int(11) DEFAULT 0,
  `subtotal` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'este_visits' => "CREATE TABLE IF NOT EXISTS `este_visits` (
  `id` varchar(30) NOT NULL,
  `visit_date` date NOT NULL,
  `customer_id` varchar(20) DEFAULT '',
  `customer_name` varchar(100) DEFAULT '',
  `staff` varchar(50) DEFAULT '',
  `menu_ids` text DEFAULT NULL,
  `menu_names` text DEFAULT NULL,
  `total_time` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'keep_items' => "CREATE TABLE IF NOT EXISTS `keep_items` (
  `id` varchar(30) NOT NULL,
  `item_date` date NOT NULL,
  `customer_id` varchar(20) DEFAULT '',
  `customer_name` varchar(100) DEFAULT '',
  `menu_id` varchar(20) DEFAULT '',
  `menu_name` varchar(100) DEFAULT '',
  `total_count` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `remain_count` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'quotes' => "CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(20) NOT NULL,
  `cust_no` varchar(10) NOT NULL DEFAULT '',
  `cust_name` varchar(100) NOT NULL DEFAULT '',
  `quote_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `total` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_no` (`quote_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'quotes_items' => "CREATE TABLE IF NOT EXISTS `quotes_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `prod_code` varchar(20) NOT NULL DEFAULT '',
  `prod_name` varchar(200) NOT NULL DEFAULT '',
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// ══════════════════════════════════════════════
// バックアップ未収録テーブル（APIから構造を再現）
// ══════════════════════════════════════════════

'suppliers' => "CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `kana` varchar(100) DEFAULT '',
  `tel` varchar(30) DEFAULT '',
  `fax` varchar(30) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `address` varchar(200) DEFAULT '',
  `contact_person` varchar(50) DEFAULT '',
  `payment_terms` varchar(100) DEFAULT '',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'purchase_orders' => "CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(30) NOT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT '',
  `notes` text DEFAULT NULL,
  `status` enum('draft','ordered','received') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'purchase_order_items' => "CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `product_code` varchar(20) DEFAULT '',
  `product_name` varchar(100) DEFAULT '',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'orderer' => "CREATE TABLE IF NOT EXISTS `orderer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT '',
  `address` varchar(200) DEFAULT '',
  `tel` varchar(30) DEFAULT '',
  `fax` varchar(30) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

foreach ($tables as $name => $sql) {
    try {
        $db->exec($sql);
        $results[] = "OK: {$name}";
    } catch (PDOException $e) {
        $results[] = "ERROR: {$name} - " . $e->getMessage();
    }
}

echo implode("\n", $results) . "\n";
echo "\nDone. " . count(array_filter($results, fn($r) => str_starts_with($r, 'OK'))) . "/" . count($tables) . " tables created.\n";

// 自己削除
@unlink(__FILE__);
echo "migrate_all.php deleted.\n";
