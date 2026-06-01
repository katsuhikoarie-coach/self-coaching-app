-- ヤマノ FC受注システム DB スキーマ
-- DB名: asagiri_fcorder
-- 文字コード: utf8mb4
-- 現状はlocalStorage（デモモード）だが、将来的にこのDBへ移行する

CREATE DATABASE IF NOT EXISTS asagiri_fcorder
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE asagiri_fcorder;

-- 注文ヘッダー
CREATE TABLE IF NOT EXISTS orders (
  id          VARCHAR(20)  NOT NULL COMMENT '受注番号 例: 2026-05-16-0001',
  fc_name     VARCHAR(100) NOT NULL COMMENT 'FC名 例: 西明石白神FC',
  fc_email    VARCHAR(200)          COMMENT 'FC担当者メール',
  total_amount INT          NOT NULL COMMENT '合計金額（税込）',
  item_count   INT          NOT NULL COMMENT '品目数',
  note         TEXT                  COMMENT '備考',
  status       ENUM('confirmed','ordered','cancelled') NOT NULL DEFAULT 'confirmed'
                COMMENT 'confirmed=FC確定済, ordered=ヤマノ発注済, cancelled=キャンセル',
  yamano_ordered_at DATETIME          COMMENT 'ヤマノへの発注実行日時',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 注文明細
CREATE TABLE IF NOT EXISTS order_items (
  id           INT          NOT NULL AUTO_INCREMENT,
  order_id     VARCHAR(20)  NOT NULL COMMENT 'ordersのid',
  product_code VARCHAR(50)  NOT NULL COMMENT 'ヤマノ商品コード',
  product_name VARCHAR(200) NOT NULL COMMENT '商品名',
  quantity     INT          NOT NULL COMMENT '発注数量',
  unit_price   INT          NOT NULL COMMENT '単価（税抜）',
  PRIMARY KEY (id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 使用例:
-- SELECT o.*, GROUP_CONCAT(i.product_code, '×', i.quantity ORDER BY i.id SEPARATOR ', ') AS items
-- FROM orders o
-- JOIN order_items i ON i.order_id = o.id
-- WHERE o.status = 'confirmed'
-- ORDER BY o.created_at DESC
-- LIMIT 1;
