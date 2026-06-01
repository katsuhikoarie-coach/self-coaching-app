SET NAMES utf8mb4;
SET time_zone = '+09:00';

-- 売上伝票
CREATE TABLE IF NOT EXISTS sales (
  id            VARCHAR(20)   NOT NULL COMMENT '伝票番号',
  sale_date     DATE          NOT NULL COMMENT '売上日（受付日）',
  `year_month`  VARCHAR(7)    NOT NULL COMMENT '年月 (YYYY-MM)',
  deliver_date  DATE          COMMENT '納品日',
  customer_id   VARCHAR(20)   DEFAULT '' COMMENT '顧客番号',
  customer_name VARCHAR(100)  DEFAULT '' COMMENT '顧客名',
  customer_kana VARCHAR(100)  DEFAULT '' COMMENT '顧客フリガナ',
  sub10         DECIMAL(10,2) DEFAULT 0 COMMENT '10%課税小計（税抜）',
  sub8          DECIMAL(10,2) DEFAULT 0 COMMENT '8%課税小計（税抜）',
  tax10         DECIMAL(10,2) DEFAULT 0 COMMENT '消費税10%',
  tax8          DECIMAL(10,2) DEFAULT 0 COMMENT '消費税8%',
  total         DECIMAL(10,2) DEFAULT 0 COMMENT '合計（税込）',
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_sale_date (sale_date),
  INDEX idx_year_month (`year_month`),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 売上明細
CREATE TABLE IF NOT EXISTS sales_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  sale_id      VARCHAR(20)   NOT NULL COMMENT '伝票番号',
  product_code VARCHAR(20)   DEFAULT '' COMMENT '商品コード',
  product_name VARCHAR(100)  DEFAULT '' COMMENT '商品名',
  qty          INT           DEFAULT 1 COMMENT '数量',
  kake         DECIMAL(5,4)  DEFAULT 1.0000 COMMENT '掛率（0.6〜1.0）',
  tax_rate     DECIMAL(4,2)  DEFAULT 0.10 COMMENT '税率',
  amount       DECIMAL(10,2) DEFAULT 0 COMMENT '下代 × 数量（税抜）',
  INDEX idx_sale_id (sale_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- エステコースマスタ
CREATE TABLE IF NOT EXISTS este_menus (
  id         VARCHAR(20)  NOT NULL COMMENT 'コースID (M001 など)',
  name       VARCHAR(100) NOT NULL COMMENT 'コース名',
  price      INT          DEFAULT 0 COMMENT '定価（税抜）',
  tax_rate   INT          DEFAULT 10 COMMENT '税率（%）',
  active     TINYINT      DEFAULT 1 COMMENT '有効フラグ',
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- エステ来店履歴
CREATE TABLE IF NOT EXISTS este_visits (
  id            VARCHAR(30)  NOT NULL COMMENT '来店ID',
  visit_date    DATE         NOT NULL COMMENT '来店日',
  customer_id   VARCHAR(20)  DEFAULT '' COMMENT '顧客番号',
  customer_name VARCHAR(100) DEFAULT '' COMMENT '顧客名',
  menu_id       VARCHAR(20)  DEFAULT '' COMMENT 'コースID',
  menu_name     VARCHAR(100) DEFAULT '' COMMENT 'コース名',
  staff         VARCHAR(50)  DEFAULT '' COMMENT '担当者',
  price         INT          DEFAULT 0 COMMENT '単価（税抜）',
  tax_rate      INT          DEFAULT 10 COMMENT '税率（%）',
  tax           INT          DEFAULT 0 COMMENT '消費税',
  total         INT          DEFAULT 0 COMMENT '合計（税込）',
  next_visit    DATE         COMMENT '次回予約日',
  note          TEXT         COMMENT '備考',
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_visit_date (visit_date),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- キープ商品・回数券
CREATE TABLE IF NOT EXISTS keep_items (
  id            VARCHAR(30)  NOT NULL COMMENT 'キープID',
  item_date     DATE         NOT NULL COMMENT '日付',
  customer_id   VARCHAR(20)  DEFAULT '' COMMENT '顧客番号',
  customer_name VARCHAR(100) DEFAULT '' COMMENT '顧客名',
  menu_name     VARCHAR(200) DEFAULT '' COMMENT '商品名・回数券名',
  qty           INT          DEFAULT 1 COMMENT '数量',
  count_val     INT          DEFAULT 1 COMMENT '回数',
  price         INT          DEFAULT 0 COMMENT '金額',
  note          TEXT         COMMENT '備考',
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
