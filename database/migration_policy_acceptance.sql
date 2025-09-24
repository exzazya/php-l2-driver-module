-- Policy acceptance table to record Terms & Conditions and Privacy Policy consent
-- Shared across admins, fleet managers, and drivers
CREATE TABLE IF NOT EXISTS policy_acceptance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_type ENUM('admin','fleet_manager','driver') NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  accepted_terms TINYINT(1) NOT NULL DEFAULT 1,
  accepted_privacy TINYINT(1) NOT NULL DEFAULT 1,
  accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  UNIQUE KEY uniq_subject (subject_type, subject_id),
  KEY idx_accepted_at (accepted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
