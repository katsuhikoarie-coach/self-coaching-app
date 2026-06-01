CREATE TABLE center_rates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fc_user_id INT NOT NULL,
  rate_system DECIMAL(4,2) COMMENT 'システム商品掛率（例：0.50）',
  rate_general DECIMAL(4,2) COMMENT '一般商品掛率（例：0.60）',
  valid_from DATE NOT NULL COMMENT '適用開始日',
  valid_to DATE DEFAULT NULL COMMENT '適用終了日（NULLは現在有効）',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fc_user_id) REFERENCES fc_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
