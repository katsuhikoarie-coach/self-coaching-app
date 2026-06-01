-- =====================================================
-- コーチングトレーナー データベース設定
-- コアサーバーの phpMyAdmin で実行してください
-- =====================================================

CREATE TABLE IF NOT EXISTS `sessions` (
  `id`               INT AUTO_INCREMENT NOT NULL,
  `session_id`       VARCHAR(64)  NOT NULL,
  `theme`            VARCHAR(100) NOT NULL,
  `mode`             ENUM('roleplay','scenario') NOT NULL DEFAULT 'roleplay',
  `client_type`      VARCHAR(100)  DEFAULT NULL,
  `messages`         JSON          NOT NULL,
  `feedback`         JSON          DEFAULT NULL,
  `score`            INT           DEFAULT NULL,
  `duration_seconds` INT           DEFAULT NULL,
  `created_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feedback_details` (
  `id`             INT AUTO_INCREMENT NOT NULL,
  `session_id`     VARCHAR(64)  NOT NULL,
  `overall_score`  INT          DEFAULT NULL,
  `used_skills`    JSON         DEFAULT NULL,
  `missed_skills`  JSON         DEFAULT NULL,
  `model_response` TEXT,
  `summary`        TEXT,
  `created_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fd_session_id` (`session_id`),
  KEY `idx_fd_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
