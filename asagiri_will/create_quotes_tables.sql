-- ============================================================
-- quotes / quotes_items テーブル作成
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

--
-- テーブルの構造 `quotes`
--

CREATE TABLE `quotes` (
  `id` int(11) NOT NULL,
  `quote_no` varchar(20) NOT NULL,
  `cust_no` varchar(10) NOT NULL DEFAULT '',
  `cust_name` varchar(100) NOT NULL DEFAULT '',
  `quote_date` date NOT NULL,
  `rate` int(11) NOT NULL DEFAULT 100,
  `note` text DEFAULT NULL,
  `total_tax10` int(11) NOT NULL DEFAULT 0,
  `total_tax8` int(11) NOT NULL DEFAULT 0,
  `total_amount` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルの構造 `quotes_items`
--

CREATE TABLE `quotes_items` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `prod_code` varchar(20) NOT NULL DEFAULT '',
  `prod_name` varchar(200) NOT NULL DEFAULT '',
  `price` int(11) NOT NULL DEFAULT 0,
  `qty` int(11) NOT NULL DEFAULT 1,
  `rate` int(11) NOT NULL DEFAULT 100,
  `tax_rate` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- インデックス `quotes`
--

ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quote_no` (`quote_no`);

--
-- インデックス `quotes_items`
--

ALTER TABLE `quotes_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_id` (`quote_id`);

--
-- AUTO_INCREMENT `quotes`
--

ALTER TABLE `quotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT `quotes_items`
--

ALTER TABLE `quotes_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 外部キー制約 `quotes_items`
--

ALTER TABLE `quotes_items`
  ADD CONSTRAINT `quotes_items_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
