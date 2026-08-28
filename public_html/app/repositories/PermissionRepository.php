<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for `permissions` and `role_permissions` (RBAC foundation).
 * Rows are returned as arrays.
 */
final class PermissionRepository
{
    /**
     * @return array[]
     */
    public function all(): array
    {
        $sql = 'SELECT * FROM permissions ORDER BY `group` ASC, id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignToRole(int $roleId, int $permissionId): void
    {
        $sql = 'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':role_id'       => $roleId,
            ':permission_id' => $permissionId,
        ]);
    }

    /**
     * @return array[]
     */
    public function allForRole(int $roleId): array
    {
        $sql = 'SELECT p.* FROM permissions p
                INNER JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id
                ORDER BY p.id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Replace a role's permission set with the given list.
     *
     * @param int[] $permissionIds
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
            $del->execute([':role_id' => $roleId]);

            $ins = $pdo->prepare(
                'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
            );
            foreach ($permissionIds as $permissionId) {
                $ins->execute([
                    ':role_id'       => $roleId,
                    ':permission_id' => $permissionId,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function hasPermission(int $roleId, string $slug): bool
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM permissions p
                INNER JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id AND p.slug = :slug';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':role_id' => $roleId, ':slug' => $slug]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
