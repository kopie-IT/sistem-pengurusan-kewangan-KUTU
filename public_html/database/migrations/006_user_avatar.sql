-- ============================================================================
-- Migration 006: User avatar
--
-- Adds an `avatar_path` column to `users` so each authenticated account can
-- upload a personal avatar (shown in the header dropdown). The avatar file is
-- stored in storage/uploads/avatars/<filename> and served via
-- /file/avatar/{userId} (owner-or-admin access).
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `users`
    ADD COLUMN `avatar_path` VARCHAR(150) NULL DEFAULT NULL
    AFTER `email_verified_at`;
