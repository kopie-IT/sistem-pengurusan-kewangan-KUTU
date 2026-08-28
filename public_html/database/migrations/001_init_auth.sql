-- ============================================================================
-- Migration 001: Authentication Foundation
-- Tables: roles, users, password_resets, sessions (optional)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Roles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(50)  NOT NULL,
    `slug`       VARCHAR(50)  NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Users
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(100)   NOT NULL,
    `email`               VARCHAR(150)   NOT NULL,
    `email_verified_at`   TIMESTAMP      NULL DEFAULT NULL,
    `password`            VARCHAR(255)   NOT NULL,
    `role_id`             INT UNSIGNED   NOT NULL,
    `status`              ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `must_reset_password` TINYINT(1)     NOT NULL DEFAULT 0,
    `last_login_at`       TIMESTAMP      NULL DEFAULT NULL,
    `last_login_ip`       VARCHAR(45)    NULL DEFAULT NULL,
    `failed_login_count`  INT UNSIGNED   NOT NULL DEFAULT 0,
    `locked_until`        TIMESTAMP      NULL DEFAULT NULL,
    `remember_token`      VARCHAR(100)   NULL DEFAULT NULL,
    `created_at`          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Password Resets (single-use tokens)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED  NOT NULL,
    `token_hash` VARCHAR(255)  NOT NULL,
    `expires_at` TIMESTAMP     NOT NULL,
    `used_at`    TIMESTAMP     NULL DEFAULT NULL,
    `created_by` INT UNSIGNED  NULL DEFAULT NULL COMMENT 'admin who triggered the reset',
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_password_resets_user` (`user_id`),
    KEY `idx_password_resets_token` (`token_hash`),
    CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Audit Logs (for authentication events)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NULL DEFAULT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `entity`     VARCHAR(100) NULL DEFAULT NULL,
    `entity_id`  INT UNSIGNED NULL DEFAULT NULL,
    `meta`       JSON         NULL,
    `ip`         VARCHAR(45)  NULL DEFAULT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_entity` (`entity`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
