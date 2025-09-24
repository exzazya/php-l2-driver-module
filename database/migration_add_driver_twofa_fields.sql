-- Migration: Add 2FA fields to drivers table
ALTER TABLE `drivers`
  ADD COLUMN `twofa_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password_hash`,
  ADD COLUMN `twofa_method` ENUM('email','totp') NOT NULL DEFAULT 'email' AFTER `twofa_enabled`,
  ADD COLUMN `twofa_secret` VARCHAR(64) NULL AFTER `twofa_method`;
