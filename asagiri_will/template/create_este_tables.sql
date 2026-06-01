SET NAMES utf8mb4;

-- エステ売上伝票ヘッダー
CREATE TABLE IF NOT EXISTS este_sales (
  id         VARCHAR(30)  NOT NULL PRIMARY KEY,
  sale_date  DATE         NOT NULL,
  cust_no    VARCHAR(20)  DEFAULT '',
  cust_name  VARCHAR(100) DEFAULT '',
  staff      VARCHAR(50)  DEFAULT '',
  subtotal   INT          DEFAULT 0,
  tax_amt    INT          DEFAULT 0,
  total      INT          DEFAULT 0,
  note       TEXT,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sale_date (sale_date),
  INDEX idx_cust_no (cust_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- エステ売上明細
CREATE TABLE IF NOT EXISTS este_sales_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  sale_id      VARCHAR(30)               NOT NULL,
  menu_id      VARCHAR(20)               DEFAULT '',
  menu_name    VARCHAR(100)              DEFAULT '',
  item_type    ENUM('ticket','single')   DEFAULT 'single',
  ticket_count INT                       DEFAULT 1,
  qty          INT                       DEFAULT 1,
  unit_price   INT                       DEFAULT 0,
  subtotal     INT                       DEFAULT 0,
  INDEX idx_sale_id (sale_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- keep_items に menu_id と used_count を追加
ALTER TABLE keep_items
  ADD COLUMN IF NOT EXISTS menu_id    VARCHAR(20) DEFAULT '' AFTER customer_name,
  ADD COLUMN IF NOT EXISTS used_count INT         DEFAULT 0 AFTER count_val;

-- este_visits に回数券連携カラムを追加
ALTER TABLE este_visits
  ADD COLUMN IF NOT EXISTS keep_item_id VARCHAR(30) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS used_ticket  TINYINT(1)  DEFAULT 0;
