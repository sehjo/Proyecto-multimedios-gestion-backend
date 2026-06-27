<?php

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/role.php';

class RoleDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function index(): array
    {
        $stmt = $this->connection->query("SELECT * FROM roles ORDER BY id ASC");
        $roles = $stmt->fetchAll();

        foreach ($roles as &$role) {
            $role['permissions'] = $this->rolePermissions((int) $role['id']);
            $role['users_count'] = $this->countUsers((int) $role['id']);
        }

        return $roles;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['permissions'] = $this->rolePermissions($id);
        return $row;
    }

    public function findByName(string $nombre): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM roles WHERE name = :name");
        $stmt->execute([':name' => $nombre]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function nameExists(string $nombre, ?int $excluirId = null): bool
    {
        if ($excluirId) {
            $stmt = $this->connection->prepare("SELECT id FROM roles WHERE name = :name AND id != :id");
            $stmt->execute([':name' => $nombre, ':id' => $excluirId]);
        } else {
            $stmt = $this->connection->prepare("SELECT id FROM roles WHERE name = :name");
            $stmt->execute([':name' => $nombre]);
        }
        return (bool) $stmt->fetch();
    }

    public function store(string $nombre, array $permisosIds): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO roles (name, created_at, updated_at) VALUES (:name, :now, :now)"
        );
        $stmt->execute([':name' => $nombre, ':now' => date('Y-m-d H:i:s')]);
        $newId = (int) $this->connection->lastInsertId();

        $this->syncPermissions($newId, $permisosIds);

        return $newId;
    }

    public function update(int $id, array $datos, ?array $permisosIds): bool
    {
        $sets  = [];
        $param = [':id' => $id, ':updated_at' => date('Y-m-d H:i:s')];

        if (isset($datos['name'])) {
            $sets[]        = "name = :name";
            $param[':name'] = $datos['name'];
        }

        $sets[] = "updated_at = :updated_at";
        $stmt   = $this->connection->prepare("UPDATE roles SET " . implode(', ', $sets) . " WHERE id = :id");
        $ok     = $stmt->execute($param);

        if ($permisosIds !== null) {
            $this->syncPermissions($id, $permisosIds);
        }

        return $ok;
    }

    public function destroy(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM roles WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function rolePermissions(int $roleId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT p.* FROM permissions p
             INNER JOIN role_has_permissions rhp ON rhp.permission_id = p.id
             WHERE rhp.role_id = :role_id"
        );
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll();
    }

    public function countUsers(int $roleId): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) AS total FROM user_has_roles WHERE role_id = :role_id"
        );
        $stmt->execute([':role_id' => $roleId]);
        return (int) $stmt->fetch()['total'];
    }

    public function syncPermissions(int $roleId, array $permisosIds): void
    {
        $stmt = $this->connection->prepare("DELETE FROM role_has_permissions WHERE role_id = :role_id");
        $stmt->execute([':role_id' => $roleId]);

        if (empty($permisosIds)) return;

        $placeholders = implode(',', array_fill(0, count($permisosIds), '(?, ?)'));
        $valores      = [];
        foreach ($permisosIds as $pid) {
            $valores[] = $roleId;
            $valores[] = (int) $pid;
        }

        $stmt = $this->connection->prepare(
            "INSERT IGNORE INTO role_has_permissions (role_id, permission_id) VALUES {$placeholders}"
        );
        $stmt->execute($valores);
    }

    public function listLogs(int $limit, int $offset): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM log_roles ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countLogs(): int
    {
        $stmt = $this->connection->query("SELECT COUNT(*) AS total FROM log_roles");
        return (int) $stmt->fetch()['total'];
    }

    public function userRoleIds(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT role_id FROM user_has_roles WHERE user_id = :user_id"
        );
        $stmt->execute([':user_id' => $userId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'role_id'));
    }

    public function logAction(?int $actorId, ?int $targetId, string $accion, ?string $changes): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO log_roles (performed_by_id, target_role_id, action, changes, timestamp)
             VALUES (:actor, :target, :action, :changes, :ts)"
        );
        $stmt->execute([
            ':actor'   => $actorId,
            ':target'  => $targetId,
            ':action'  => $accion,
            ':changes' => $changes,
            ':ts'      => date('Y-m-d H:i:s'),
        ]);
    }
}
