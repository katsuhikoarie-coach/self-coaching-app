-- ============================================================
-- 入出庫履歴テーブル作成
-- （inout は MariaDB の予約語のため stock_inout を使用）
-- phpMyAdmin の「インポート」タブからこのファイルを実行してください
-- ============================================================

SET NAMES utf8mb4;

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
