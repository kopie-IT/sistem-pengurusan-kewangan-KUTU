-- ============================================================================
-- Migration 002: Business Domain Schema (PRD core entities)
-- Covers: members, plans, memberships, schedules, payments, payouts,
--         admin fees, credit scores, withdrawals, shortfalls, ledger,
--         notifications, system settings, permissions.
-- Money stored as DECIMAL(15,2) — never FLOAT.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Permissions + role_permissions (granular RBAC foundation)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(100) NOT NULL,
    `name`       VARCHAR(150) NOT NULL,
    `group`      VARCHAR(100) NULL DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Members (profile linked 1:1 to users)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `member_code`     VARCHAR(30)  NULL DEFAULT NULL,
    `phone`           VARCHAR(30)  NULL DEFAULT NULL,
    `ic_number`       VARCHAR(30)  NULL DEFAULT NULL,
    `address`         TEXT         NULL,
    `credit_score`    TINYINT UNSIGNED NOT NULL DEFAULT 100 COMMENT '0-100 reliability score',
    `status`          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_members_user`   (`user_id`),
    UNIQUE KEY `uk_members_code`   (`member_code`),
    KEY `idx_members_status` (`status`),
    CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Plans
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plans` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_code`           VARCHAR(30)  NOT NULL,
    `name`                VARCHAR(150) NOT NULL,
    `description`         TEXT         NULL,
    `number_of_members`   INT UNSIGNED NOT NULL DEFAULT 0,
    `contribution_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_frequency`   ENUM('weekly','biweekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
    `number_of_cycles`    INT UNSIGNED NOT NULL DEFAULT 1,
    `start_date`          DATE         NULL DEFAULT NULL,
    `end_date`            DATE         NULL DEFAULT NULL,
    `status`              ENUM('draft','open','full','active','suspended','completed','cancelled') NOT NULL DEFAULT 'draft',
    -- membership config
    `max_members`         INT UNSIGNED NOT NULL DEFAULT 0,
    `min_credit_score`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `approval_required`   TINYINT(1)   NOT NULL DEFAULT 0,
    `allow_multiple`      TINYINT(1)   NOT NULL DEFAULT 1,
    `withdrawal_allowed`  TINYINT(1)   NOT NULL DEFAULT 0,
    -- payout config
    `payout_mode`         ENUM('fixed','actual_collection') NOT NULL DEFAULT 'fixed',
    `fixed_payout_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payout_frequency`    ENUM('weekly','biweekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
    `payout_day`          TINYINT UNSIGNED NULL DEFAULT NULL,
    -- credit score config
    `min_score`           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_by`          INT UNSIGNED  NULL DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plans_code` (`plan_code`),
    KEY `idx_plans_status` (`status`),
    CONSTRAINT `fk_plans_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Plan Members (membership join table)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plan_members` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NOT NULL,
    `status`          ENUM('pending','approved','rejected','active','withdrawn','completed') NOT NULL DEFAULT 'pending',
    `joined_at`       TIMESTAMP    NULL DEFAULT NULL,
    `approved_by`     INT UNSIGNED NULL DEFAULT NULL,
    `withdrawal_at`   TIMESTAMP    NULL DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plan_member` (`plan_id`, `member_id`),
    KEY `idx_pm_member` (`member_id`),
    KEY `idx_pm_status` (`status`),
    CONSTRAINT `fk_pm_plan`   FOREIGN KEY (`plan_id`)   REFERENCES `plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pm_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Plan Cycles (generated per plan)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plan_cycles` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`    INT UNSIGNED NOT NULL,
    `cycle_no`   INT UNSIGNED NOT NULL,
    `start_date` DATE         NULL DEFAULT NULL,
    `end_date`   DATE         NULL DEFAULT NULL,
    `status`     ENUM('upcoming','active','completed') NOT NULL DEFAULT 'upcoming',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cycle_plan` (`plan_id`, `cycle_no`),
    KEY `idx_cycle_status` (`status`),
    CONSTRAINT `fk_cycle_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Contribution Schedules (per member/cycle)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contribution_schedules` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`     INT UNSIGNED NOT NULL,
    `plan_cycle_id` INT UNSIGNED NULL DEFAULT NULL,
    `member_id`   INT UNSIGNED NOT NULL,
    `due_date`    DATE         NOT NULL,
    `amount`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status`      ENUM('pending','paid','partial','overdue','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cs_plan`    (`plan_id`),
    KEY `idx_cs_member`  (`member_id`),
    KEY `idx_cs_status`  (`status`),
    KEY `idx_cs_due`     (`due_date`),
    CONSTRAINT `fk_cs_plan`   FOREIGN KEY (`plan_id`)     REFERENCES `plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cs_cycle`  FOREIGN KEY (`plan_cycle_id`) REFERENCES `plan_cycles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cs_member` FOREIGN KEY (`member_id`)   REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Payment Slips (uploaded files, stored outside public web root)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_slips` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id`    INT UNSIGNED NULL DEFAULT NULL,
    `stored_name`  VARCHAR(255) NOT NULL COMMENT 'random filename inside storage',
    `original_name` VARCHAR(255) NULL DEFAULT NULL,
    `mime_type`    VARCHAR(100) NULL DEFAULT NULL,
    `size_bytes`   INT UNSIGNED NOT NULL DEFAULT 0,
    `purpose`      ENUM('contribution','payout') NOT NULL DEFAULT 'contribution',
    `uploaded_by`  INT UNSIGNED  NULL DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_slips_member` (`member_id`),
    KEY `idx_slips_purpose` (`purpose`),
    CONSTRAINT `fk_slips_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Payment Batches (bulk payment parent)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_batches` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_no`      VARCHAR(30)  NOT NULL,
    `member_id`     INT UNSIGNED NOT NULL,
    `total_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_slip_id` INT UNSIGNED NULL DEFAULT NULL,
    `status`        ENUM('draft','submitted','pending_verification','approved','rejected','resubmission') NOT NULL DEFAULT 'submitted',
    `verified_by`   INT UNSIGNED  NULL DEFAULT NULL,
    `verified_at`   TIMESTAMP     NULL DEFAULT NULL,
    `note`          TEXT          NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_batch_no` (`batch_no`),
    KEY `idx_pb_member` (`member_id`),
    KEY `idx_pb_status` (`status`),
    CONSTRAINT `fk_pb_member`   FOREIGN KEY (`member_id`)       REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pb_slip`     FOREIGN KEY (`payment_slip_id`) REFERENCES `payment_slips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Payment Batch Items (allocation breakdown)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_batch_items` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id`     INT UNSIGNED NOT NULL,
    `plan_id`      INT UNSIGNED NOT NULL,
    `contribution_schedule_id` INT UNSIGNED NULL DEFAULT NULL,
    `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pbi_batch` (`batch_id`),
    KEY `idx_pbi_plan`  (`plan_id`),
    CONSTRAINT `fk_pbi_batch` FOREIGN KEY (`batch_id`) REFERENCES `payment_batches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pbi_plan`  FOREIGN KEY (`plan_id`)  REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Payments (individual contribution payments)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id`           INT UNSIGNED NOT NULL,
    `plan_id`             INT UNSIGNED NOT NULL,
    `contribution_schedule_id` INT UNSIGNED NULL DEFAULT NULL,
    `batch_id`            INT UNSIGNED NULL DEFAULT NULL,
    `amount`              DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status`              ENUM('draft','submitted','pending_verification','approved','rejected','resubmission','refunded') NOT NULL DEFAULT 'submitted',
    `payment_slip_id`     INT UNSIGNED NULL DEFAULT NULL,
    `verified_by`         INT UNSIGNED NULL DEFAULT NULL,
    `verified_at`         TIMESTAMP   NULL DEFAULT NULL,
    `note`                TEXT        NULL,
    `created_at`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pay_member` (`member_id`),
    KEY `idx_pay_plan`   (`plan_id`),
    KEY `idx_pay_status` (`status`),
    CONSTRAINT `fk_pay_member`   FOREIGN KEY (`member_id`)  REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_plan`     FOREIGN KEY (`plan_id`)    REFERENCES `plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_slip`     FOREIGN KEY (`payment_slip_id`) REFERENCES `payment_slips` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Payout Schedules (admin-defined recipient order per cycle)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payout_schedules` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`      INT UNSIGNED NOT NULL,
    `plan_cycle_id` INT UNSIGNED NULL DEFAULT NULL,
    `recipient_member_id` INT UNSIGNED NOT NULL,
    `payout_date`  DATE         NULL DEFAULT NULL,
    `expected_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status`       ENUM('scheduled','due','processing','paid','failed','delayed','cancelled','reversed') NOT NULL DEFAULT 'scheduled',
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pos_plan` (`plan_id`),
    KEY `idx_pos_recipient` (`recipient_member_id`),
    KEY `idx_pos_status` (`status`),
    KEY `idx_pos_date` (`payout_date`),
    CONSTRAINT `fk_pos_plan`    FOREIGN KEY (`plan_id`)        REFERENCES `plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pos_cycle`   FOREIGN KEY (`plan_cycle_id`)  REFERENCES `plan_cycles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_pos_recipient` FOREIGN KEY (`recipient_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Payouts (actual payout transaction)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payouts` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`             INT UNSIGNED NOT NULL,
    `plan_cycle_id`       INT UNSIGNED NULL DEFAULT NULL,
    `payout_schedule_id`  INT UNSIGNED NULL DEFAULT NULL,
    `recipient_member_id` INT UNSIGNED NOT NULL,
    `gross_payout`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `actual_collection`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `admin_fee`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `net_payout`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payout_mode`         ENUM('fixed','actual_collection') NOT NULL DEFAULT 'fixed',
    `admin_fee_version_id` INT UNSIGNED NULL DEFAULT NULL,
    `shortfall_amount`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `shortfall_id`        INT UNSIGNED NULL DEFAULT NULL,
    `status`              ENUM('scheduled','due','processing','paid','failed','delayed','cancelled','reversed') NOT NULL DEFAULT 'scheduled',
    `payment_reference`   VARCHAR(100) NULL DEFAULT NULL,
    `payment_slip_id`     INT UNSIGNED NULL DEFAULT NULL,
    `paid_date`           TIMESTAMP   NULL DEFAULT NULL,
    `paid_by`             INT UNSIGNED NULL DEFAULT NULL,
    `created_at`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_po_plan` (`plan_id`),
    KEY `idx_po_recipient` (`recipient_member_id`),
    KEY `idx_po_status` (`status`),
    KEY `idx_po_date` (`paid_date`),
    CONSTRAINT `fk_po_plan`      FOREIGN KEY (`plan_id`)             REFERENCES `plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_po_schedule`  FOREIGN KEY (`payout_schedule_id`)  REFERENCES `payout_schedules` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_po_slip`      FOREIGN KEY (`payment_slip_id`)     REFERENCES `payment_slips` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_po_shortfall` FOREIGN KEY (`shortfall_id`)        REFERENCES `shortfalls` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Admin Fee Config + Versions
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_fee_configs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`    INT UNSIGNED NOT NULL,
    `enabled`    TINYINT(1)   NOT NULL DEFAULT 1,
    `fee_type`   ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
    `fee_value`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_afc_plan` (`plan_id`),
    CONSTRAINT `fk_afc_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_fee_versions` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_fee_config_id` INT UNSIGNED NOT NULL,
    `fee_type`       ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
    `fee_value`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `effective_date` DATE         NOT NULL,
    `status`         ENUM('active','superseded') NOT NULL DEFAULT 'active',
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_afv_config` (`admin_fee_config_id`),
    CONSTRAINT `fk_afv_config` FOREIGN KEY (`admin_fee_config_id`) REFERENCES `admin_fee_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Credit Score History + Rules
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `credit_scores` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id`       INT UNSIGNED NOT NULL,
    `score`           TINYINT UNSIGNED NOT NULL DEFAULT 100,
    `level`           ENUM('excellent','good','fair','risk','high_risk') NOT NULL DEFAULT 'excellent',
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cs_member` (`member_id`),
    CONSTRAINT `fk_cs_member_score` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `credit_score_history` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id`       INT UNSIGNED NOT NULL,
    `event`           VARCHAR(100) NOT NULL,
    `reason_code`     VARCHAR(50)  NOT NULL,
    `previous_score`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `score_change`    SMALLINT     NOT NULL DEFAULT 0,
    `new_score`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `related_plan_id` INT UNSIGNED NULL DEFAULT NULL,
    `related_payment_id` INT UNSIGNED NULL DEFAULT NULL,
    `related_payout_id`  INT UNSIGNED NULL DEFAULT NULL,
    `actor_id`        INT UNSIGNED NULL DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_csh_member` (`member_id`),
    KEY `idx_csh_created` (`created_at`),
    CONSTRAINT `fk_csh_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `credit_score_rules` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reason_code`  VARCHAR(50)  NOT NULL,
    `description`  VARCHAR(150) NULL DEFAULT NULL,
    `score_change` SMALLINT     NOT NULL DEFAULT 0,
    `recovery_cap` TINYINT UNSIGNED NULL DEFAULT NULL,
    `is_recovery`  TINYINT(1)   NOT NULL DEFAULT 0,
    `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rule_code` (`reason_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Withdrawal Requests
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `withdrawal_requests` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id`       INT UNSIGNED NOT NULL,
    `plan_id`         INT UNSIGNED NOT NULL,
    `reason`          TEXT         NULL,
    `request_date`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `current_cycle`   INT UNSIGNED NULL DEFAULT NULL,
    `outstanding`    DECIMAL(15,2) NULL DEFAULT NULL,
    `score_impact`    TINYINT      NULL DEFAULT NULL,
    `status`          ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    `approved_by`     INT UNSIGNED NULL DEFAULT NULL,
    `decision_date`   TIMESTAMP    NULL DEFAULT NULL,
    `notes`           TEXT         NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wr_member` (`member_id`),
    KEY `idx_wr_plan`   (`plan_id`),
    KEY `idx_wr_status` (`status`),
    CONSTRAINT `fk_wr_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wr_plan`   FOREIGN KEY (`plan_id`)   REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Shortfalls
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shortfalls` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_id`           INT UNSIGNED NOT NULL,
    `plan_cycle_id`     INT UNSIGNED NULL DEFAULT NULL,
    `payout_id`         INT UNSIGNED NULL DEFAULT NULL,
    `expected_amount`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `actual_collection` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `shortfall_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status`            ENUM('open','under_review','resolved','written_off') NOT NULL DEFAULT 'open',
    `resolution`        VARCHAR(100) NULL DEFAULT NULL,
    `notes`             TEXT         NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at`       TIMESTAMP    NULL DEFAULT NULL,
    `approved_by`       INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sf_plan` (`plan_id`),
    KEY `idx_sf_status` (`status`),
    CONSTRAINT `fk_sf_plan`    FOREIGN KEY (`plan_id`)   REFERENCES `plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sf_payout`  FOREIGN KEY (`payout_id`) REFERENCES `payouts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Ledger Transactions (immutable financial ledger)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ledger_transactions` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_type` ENUM('contribution','payout','admin_fee','shortfall','refund','adjustment','recovery','penalty') NOT NULL,
    `member_id`       INT UNSIGNED NULL DEFAULT NULL,
    `plan_id`         INT UNSIGNED NULL DEFAULT NULL,
    `reference_id`    INT UNSIGNED NULL DEFAULT NULL COMMENT 'related payment/payout/etc',
    `amount`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `currency`        VARCHAR(3)   NOT NULL DEFAULT 'MYR',
    `description`     VARCHAR(255) NULL DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lt_type` (`transaction_type`),
    KEY `idx_lt_member` (`member_id`),
    KEY `idx_lt_plan` (`plan_id`),
    KEY `idx_lt_created` (`created_at`),
    CONSTRAINT `fk_lt_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lt_plan`   FOREIGN KEY (`plan_id`)   REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Notifications
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipient_id`    INT UNSIGNED NOT NULL,
    `type`            VARCHAR(50)  NOT NULL,
    `title`           VARCHAR(150) NOT NULL,
    `message`         TEXT         NULL,
    `reference_type`  VARCHAR(50)  NULL DEFAULT NULL,
    `reference_id`    INT UNSIGNED NULL DEFAULT NULL,
    `channel`         ENUM('in_app','email') NOT NULL DEFAULT 'in_app',
    `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_recipient` (`recipient_id`),
    KEY `idx_notif_read` (`is_read`),
    CONSTRAINT `fk_notif_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- System Settings
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT        NULL,
    `setting_type` VARCHAR(30)  NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sys_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Adjustments (non-destructive reversals/corrections)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `adjustments` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type`     VARCHAR(50)  NOT NULL,
    `entity_id`       INT UNSIGNED NOT NULL,
    `adjustment_type` ENUM('reversal','correction','manual') NOT NULL DEFAULT 'correction',
    `reason`          TEXT         NULL,
    `old_value`       JSON         NULL,
    `new_value`       JSON         NULL,
    `created_by`      INT UNSIGNED NULL DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adj_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_adj_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Migrations tracking
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `migrations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration`   VARCHAR(255) NOT NULL,
    `batch`       INT UNSIGNED NOT NULL DEFAULT 1,
    `executed_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
