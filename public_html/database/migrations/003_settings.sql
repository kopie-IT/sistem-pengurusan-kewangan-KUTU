-- ============================================================================
-- Migration 003: App-level settings (system name, logo, etc.)
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `app_settings` (
    `key`        VARCHAR(100) NOT NULL,
    `value`      TEXT NULL,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `app_settings` (`key`, `value`) VALUES
    ('app_name',  'Sistem Pengurusan Main Kutu'),
    ('brand_tagline', 'Platform pengurusan Main Kutu yang moden, telus dan selamat.'),
    ('logo_path', NULL);
