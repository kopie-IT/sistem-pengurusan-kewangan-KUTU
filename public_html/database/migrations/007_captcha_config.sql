-- ============================================================================
-- Migration 007: CAPTCHA + AWS WAF configuration defaults
--
-- Seats default rows in `system_settings` for the new anti-spam feature.
-- Existing rows are left untouched; missing rows are inserted so the
-- setting page has values to render after upgrade.
-- ============================================================================

SET NAMES utf8mb4;

INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
    -- Master switch ----------------------------------------------------
    ('captcha_enabled',            '1',           'bool',   'Enable the in-app math CAPTCHA on sensitive pages.'),

    -- Per-form toggles -------------------------------------------------
    ('captcha_on_login',           '1',           'bool',   'Require CAPTCHA on the login form.'),
    ('captcha_on_register',        '1',           'bool',   'Require CAPTCHA on the registration form.'),
    ('captcha_on_forgot_password', '1',           'bool',   'Require CAPTCHA on the forgot-password form.'),
    ('captcha_on_reset_password',  '1',           'bool',   'Require CAPTCHA on the reset-password form.'),
    ('captcha_on_admin_blast',     '1',           'bool',   'Require CAPTCHA when admin sends an email blast.'),

    -- AWS WAF / Captcha reserved configuration -------------------------
    ('aws_waf_api_key',            '',            'string', 'Reserved: AWS WAF API key for future Captcha integration.'),
    ('aws_waf_secret_key',         '',            'string', 'Reserved: AWS WAF Secret key.'),
    ('aws_waf_captcha_api',        'https://captcha.dev.waf.amazonaws.com/', 'string', 'Reserved: AWS WAF Captcha API endpoint.'),
    ('aws_waf_captcha_js',         '',            'string', 'Reserved: AWS WAF Captcha JS bundle URL.');
