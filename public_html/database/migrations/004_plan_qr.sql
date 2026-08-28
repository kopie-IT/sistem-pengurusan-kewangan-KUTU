-- ============================================================================
-- Migration 004: Plan-level QR upload for member payments
-- ============================================================================

SET NAMES utf8mb4;

-- Add a nullable column to plans so each plan can carry its own payment QR.
ALTER TABLE `plans`
    ADD COLUMN IF NOT EXISTS `payment_qr_path` VARCHAR(255) NULL DEFAULT NULL AFTER `admin_fee_percent`;

-- A system-wide payment QR (used as a fallback when a plan has no QR).
INSERT IGNORE INTO `app_settings` (`key`, `value`) VALUES
    ('payment_qr_path', NULL);
