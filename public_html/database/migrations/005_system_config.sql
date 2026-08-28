-- ============================================================================
-- Migration 005: System configuration (email blast, wap.net / WhatsApp
-- gateway, plus default seed values for notifier channels).
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- Default rows in `system_settings` for the new configurables.
-- Existing rows are left untouched; missing rows are inserted.
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
    -- Email blast ---------------------------------------------------
    ('email_blast_enabled',      '0',           'bool',   'Enable / disable the in-app email blast feature for admin broadcasts.'),
    ('email_blast_from_name',    'Sistem Kutu', 'string', 'Display name used as the From: header for outbound emails.'),
    ('email_blast_from_email',   '',            'string', 'From: address used by the mailer for broadcasts.'),
    ('email_blast_reply_to',     '',            'string', 'Optional Reply-To address that recipients can answer to.'),
    ('email_blast_footer',       '',            'string', 'Optional footer text appended to every blast message.'),
    ('email_blast_default_subject', 'Notis Penting daripada Sistem Kutu', 'string', 'Default subject line pre-filled when composing a new blast.'),

    -- wap.net / WhatsApp gateway ------------------------------------
    ('wapnet_enabled',           '0',           'bool',   'Enable / disable the wap.net WhatsApp notification gateway.'),
    ('wapnet_api_url',           'https://api.wap.net/v1/messages', 'string', 'Base URL for the wap.net REST endpoint.'),
    ('wapnet_api_key',           '',            'string', 'API key (token) supplied by wap.net for outbound messages.'),
    ('wapnet_sender_id',         '',            'string', 'Approved WhatsApp Business sender / phone number id.'),
    ('wapnet_default_template',  'general_notification', 'string', 'Default wap.net template code used for system notifications.'),

    -- General operation ---------------------------------------------
    ('system_contact_phone',     '',            'string', 'Helpdesk phone number shown on blast footers.'),
    ('system_contact_email',     '',            'string', 'Helpdesk email address shown on blast footers.');

-- ----------------------------------------------------------------------------
-- Email blast history (audit trail of every broadcast).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_blasts` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subject`       VARCHAR(200) NOT NULL,
    `message`       TEXT         NOT NULL,
    `target_role`   VARCHAR(30)  NOT NULL DEFAULT 'all' COMMENT 'all / admin / member / super_admin / staff',
    `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status`        ENUM('queued','sent','failed','partial') NOT NULL DEFAULT 'queued',
    `created_by`    INT UNSIGNED NOT NULL,
    `sent_at`       TIMESTAMP    NULL DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_blasts_creator` (`created_by`),
    KEY `idx_blasts_status`  (`status`),
    KEY `idx_blasts_created` (`created_at`),
    CONSTRAINT `fk_blasts_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
