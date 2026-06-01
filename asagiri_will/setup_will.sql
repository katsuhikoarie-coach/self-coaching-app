-- ============================================================
-- setup_will.sql
-- DB: asagiri_will
-- 用途: クレスティサロンWiLL 新規DB テーブル作成
-- 実行方法: phpMyAdmin で asagiri_will を選択 → SQLタブ → 貼り付けて実行
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

-- ------------------------------------------------------------
-- products（商品マスタ）
-- ※ データは import_products_will.sql で別途投入
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(20)  NOT NULL,
  `name`          VARCHAR(255) NOT NULL,
  `price`         DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tax_rate`      TINYINT      NOT NULL DEFAULT 10  COMMENT '消費税率 8 or 10',
  `category`      VARCHAR(100) DEFAULT NULL,
  `unit`          VARCHAR(20)  DEFAULT NULL,
  `discontinued`  TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- customers（顧客マスタ）
-- ※ 新規店舗のため初期データなし
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(20)  DEFAULT NULL,
  `name`          VARCHAR(100) NOT NULL,
  `name_kana`     VARCHAR(100) DEFAULT NULL,
  `phone`         VARCHAR(20)  DEFAULT NULL,
  `email`         VARCHAR(255) DEFAULT NULL,
  `birthday`      DATE         DEFAULT NULL,
  `gender`        TINYINT(1)  DEFAULT NULL COMMENT '0:女 1:男',
  `memo`          TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- sales（売上ヘッダ）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `sale_date`     DATE         NOT NULL,
  `customer_id`   INT          DEFAULT NULL,
  `total_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tax_8_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tax_10_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `memo`          TEXT         DEFAULT NULL,
  `created_by`    VARCHAR(255) DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sale_date` (`sale_date`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- sale_details（売上明細）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sale_details` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `sale_id`       INT          NOT NULL,
  `product_code`  VARCHAR(20)  NOT NULL,
  `product_name`  VARCHAR(255) NOT NULL,
  `unit_price`    DECIMAL(10,2) NOT NULL,
  `quantity`      INT          NOT NULL DEFAULT 1,
  `tax_rate`      TINYINT      NOT NULL DEFAULT 10,
  `subtotal`      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- sessions（ログインセッション管理）
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id`            VARCHAR(128) NOT NULL,
  `user_email`    VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'setup_will.sql 完了: テーブルが作成されました' AS status;
