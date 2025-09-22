-- Migration: Add email-based 2FA support for drivers

-- Create table to store driver email OTP codes
CREATE TABLE IF NOT EXISTS `twofactor_email_codes_driver` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `driver_id` INT NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `sent_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_driver` (`driver_id`),
  FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
