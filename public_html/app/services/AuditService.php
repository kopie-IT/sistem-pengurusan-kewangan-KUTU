<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Lightweight audit logger. Records security-relevant events.
 */
final class AuditService
{
    public static function log(
        string $action,
        ?int $userId = null,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $meta = null
    ): void {
        try {
            $sql = 'INSERT INTO audit_logs (user_id, action, entity, entity_id, meta, ip, user_agent)
                    VALUES (:user_id, :action, :entity, :entity_id, :meta, :ip, :ua)';
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute([
                ':user_id'   => $userId,
                ':action'    => $action,
                ':entity'    => $entity,
                ':entity_id' => $entityId,
                ':meta'      => $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                ':ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                ':ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the main flow.
            error_log('[AuditService] ' . $e->getMessage());
        }
    }
}
