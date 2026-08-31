-- Performance indexes for the hot read paths.
-- Idempotent: skip if the index already exists.

SET @db := DATABASE();

-- app_settings lookup by key (brand name, logo, tagline, captcha config, etc.)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'app_settings' AND INDEX_NAME = 'idx_app_settings_key') = 0,
  'CREATE INDEX idx_app_settings_key ON app_settings (`key`)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- users by email (login + forgot password)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_email') = 0,
  'CREATE INDEX idx_users_email ON users (email)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- users by role_id (used by admin user list join)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_role_id') = 0,
  'CREATE INDEX idx_users_role_id ON users (role_id)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- notifications by user (notifications list)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_user_created') = 0,
  'CREATE INDEX idx_notifications_user_created ON notifications (user_id, created_at)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- payments by member (member payments list)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'payments' AND INDEX_NAME = 'idx_payments_member_created') = 0,
  'CREATE INDEX idx_payments_member_created ON payments (member_id, created_at)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- password_resets by token (public reset link)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'password_resets' AND INDEX_NAME = 'idx_password_resets_token') = 0,
  'CREATE INDEX idx_password_resets_token ON password_resets (token)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- members by user_id (member resolution in many controllers)
SET @stmt := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'members' AND INDEX_NAME = 'idx_members_user_id') = 0,
  'CREATE INDEX idx_members_user_id ON members (user_id)',
  'SELECT 1'
));
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
