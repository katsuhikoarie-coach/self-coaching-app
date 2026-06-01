-- ============================================================
-- 朝霧店 売上顧客管理システム MariaDB セットアップ
-- 文字コード: UTF-8mb4
-- 実行方法: mysql -u asagiri -p asagiri < setup.sql
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET time_zone = '+09:00';

-- ────────────────────────────────────────
-- 顧客マスタ
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customers (
  id          VARCHAR(20)  NOT NULL COMMENT '顧客番号',
  name        VARCHAR(100) NOT NULL COMMENT '氏名',
  kana        VARCHAR(100) DEFAULT '' COMMENT 'フリガナ',
  zip         VARCHAR(10)  DEFAULT '' COMMENT '郵便番号',
  addr        VARCHAR(200) DEFAULT '' COMMENT '住所',
  tel         VARCHAR(20)  DEFAULT '' COMMENT '電話番号',
  mobile      VARCHAR(20)  DEFAULT '' COMMENT '携帯電話',
  email       VARCHAR(100) DEFAULT '' COMMENT 'メールアドレス',
  bday        VARCHAR(20)  DEFAULT '' COMMENT '誕生日 (YYYY-MM-DD)',
  rank_id     VARCHAR(5)   DEFAULT '' COMMENT 'ランクID',
  rank_name   VARCHAR(50)  DEFAULT '一般' COMMENT 'ランク名',
  staff       VARCHAR(50)  DEFAULT '' COMMENT '担当者',
  note        TEXT COMMENT '備考',
  homecare    TINYINT      DEFAULT 0 COMMENT 'ホームケアフラグ',
  keep_item   TINYINT      DEFAULT 0 COMMENT 'キープ商品フラグ',
  active      TINYINT      DEFAULT 1 COMMENT '有効フラグ (0=退会)',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- 商品マスタ
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  code         VARCHAR(20)   NOT NULL COMMENT '商品コード',
  name         VARCHAR(100)  NOT NULL COMMENT '商品名',
  price        DECIMAL(10,2) DEFAULT 0 COMMENT '上代（定価）',
  genre        VARCHAR(50)   DEFAULT 'その他' COMMENT 'ジャンル',
  supplier     VARCHAR(100)  DEFAULT '' COMMENT '仕入先',
  discontinued TINYINT       DEFAULT 0 COMMENT '終息フラグ (1=終息)',
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- 在庫
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS stock (
  code       VARCHAR(20)   NOT NULL COMMENT '商品コード',
  name       VARCHAR(100)  DEFAULT '' COMMENT '商品名',
  price      DECIMAL(10,2) DEFAULT 0 COMMENT '定価',
  qty        INT           DEFAULT 0 COMMENT '在庫数',
  updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- 入出庫履歴（inout は予約語のため stock_inout を使用）
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS stock_inout (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  io_date     DATE         NOT NULL COMMENT '日付',
  code        VARCHAR(20)  NOT NULL COMMENT '商品コード',
  name        VARCHAR(100) DEFAULT '' COMMENT '商品名',
  in_qty      INT          DEFAULT 0 COMMENT '入庫数',
  out_qty     INT          DEFAULT 0 COMMENT '出庫数',
  stock_after INT          DEFAULT 0 COMMENT '入出庫後の在庫数',
  note        VARCHAR(200) DEFAULT '' COMMENT '備考',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_io_date (io_date),
  INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- 売上伝票
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sales (
  id            VARCHAR(20)   NOT NULL COMMENT '伝票番号',
  sale_date     DATE          NOT NULL COMMENT '売上日（受付日）',
  year_month    VARCHAR(7)    NOT NULL COMMENT '年月 (YYYY-MM)',
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
  INDEX idx_year_month (year_month),
  INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- 売上明細
-- ────────────────────────────────────────
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

-- ────────────────────────────────────────
-- エステコースマスタ
-- ────────────────────────────────────────
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

-- ────────────────────────────────────────
-- エステ来店履歴
-- ────────────────────────────────────────
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

-- ────────────────────────────────────────
-- キープ商品・回数券
-- ────────────────────────────────────────
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
