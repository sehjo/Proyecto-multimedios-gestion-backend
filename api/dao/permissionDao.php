<?php

require_once __DIR__ . '/../config/connection.php';

class PermissionDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function index(): array
    {
        $stmt = $this->connection->query("SELECT * FROM permissions ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM permissions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(string $nombre): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM permissions WHERE name = :name");
        $stmt->execute([':name' => $nombre]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function userDirectPermissions(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT p.* FROM permissions p
             INNER JOIN user_has_permissions uhp ON uhp.permission_id = p.id
             WHERE uhp.user_id = :user_id"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function userAllPermissions(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT DISTINCT p.* FROM permissions p
             INNER JOIN user_has_permissions uhp ON uhp.permission_id = p.id
             WHERE uhp.user_id = :user_id
             UNION
             SELECT DISTINCT p.* FROM permissions p
             INNER JOIN role_has_permissions rhp ON rhp.permission_id = p.id
             INNER JOIN user_has_roles uhr ON uhr.role_id = rhp.role_id
             WHERE uhr.user_id = :user_id_role"
        );
        $stmt->execute([':user_id' => $userId, ':user_id_role' => $userId]);
        return $stmt->fetchAll();
    }

    public function assignPermissionToUser(int $userId, int $permissionId): void
    {
        $stmt = $this->connection->prepare(
            "INSERT IGNORE INTO user_has_permissions (user_id, permission_id) VALUES (:user_id, :permission_id)"
        );
        $stmt->execute([':user_id' => $userId, ':permission_id' => $permissionId]);
    }

    public function revokePermissionFromUser(int $userId, int $permissionId): bool
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM user_has_permissions WHERE user_id = :user_id AND permission_id = :permission_id"
        );
        $stmt->execute([':user_id' => $userId, ':permission_id' => $permissionId]);
        return $stmt->rowCount() > 0;
    }

    public function userHasPermission(int $userId, string $permissionName): bool
    {
        // Direct permission
        $stmt = $this->connection->prepare(
            "SELECT p.id FROM permissions p
             INNER JOIN user_has_permissions uhp ON uhp.permission_id = p.id
             WHERE uhp.user_id = :user_id AND p.name = :name"
        );
        $stmt->execute([':user_id' => $userId, ':name' => $permissionName]);
        if ($stmt->fetch()) return true;

        // Permission via role
        $stmt = $this->connection->prepare(
            "SELECT p.id FROM permissions p
             INNER JOIN role_has_permissions rhp ON rhp.permission_id = p.id
             INNER JOIN user_has_roles uhr ON uhr.role_id = rhp.role_id
             WHERE uhr.user_id = :user_id AND p.name = :name"
        );
        $stmt->execute([':user_id' => $userId, ':name' => $permissionName]);
        return (bool) $stmt->fetch();
    }
}
