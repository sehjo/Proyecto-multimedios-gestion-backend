<?php

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/user.php';

class UserDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function countTotal(array $filters = []): int
    {
        $where = $this->buildWhere($filters);
        $stmt  = $this->connection->prepare(
            "SELECT COUNT(*) AS total FROM users WHERE deleted_at IS NULL" . $where['sql']
        );
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function index(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhere($filters);
        $sql   = "SELECT id, name, identifier, email, status, is_guest, created_at, updated_at
                  FROM users WHERE deleted_at IS NULL" . $where['sql'] . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt  = $this->connection->prepare($sql);
        foreach ($where['params'] as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT id, name, identifier, email, status, is_guest, created_at, updated_at
             FROM users WHERE id = :id AND deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM users WHERE email = :email AND deleted_at IS NULL"
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailExists(string $email, ?int $excluirId = null): bool
    {
        if ($excluirId) {
            $stmt = $this->connection->prepare(
                "SELECT id FROM users WHERE email = :email AND id != :id AND deleted_at IS NULL"
            );
            $stmt->execute([':email' => $email, ':id' => $excluirId]);
        } else {
            $stmt = $this->connection->prepare(
                "SELECT id FROM users WHERE email = :email AND deleted_at IS NULL"
            );
            $stmt->execute([':email' => $email]);
        }
        return (bool) $stmt->fetch();
    }

    public function store(array $datos): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO users (name, identifier, email, password, status, is_guest, created_at, updated_at)
             VALUES (:name, :identifier, :email, :password, :status, :is_guest, :created_at, :updated_at)"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':name'       => $datos['name'],
            ':identifier' => $datos['identifier'],
            ':email'      => $datos['email'],
            ':password'   => password_hash($datos['password'], PASSWORD_BCRYPT),
            ':status'     => $datos['status'] ?? 'active',
            ':is_guest'   => $datos['is_guest'] ?? 0,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE users SET status = :status, updated_at = :updated_at WHERE id = :id"
        );
        return $stmt->execute([
            ':status'     => $status,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id'         => $id,
        ]);
    }

    public function updateData(int $id, array $datos): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE users SET name = :name, email = :email, updated_at = :updated_at WHERE id = :id"
        );
        return $stmt->execute([
            ':name'       => $datos['name'],
            ':email'      => $datos['email'],
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id'         => $id,
        ]);
    }

    public function softDelete(int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt  = $this->connection->prepare(
            "UPDATE users SET deleted_at = :deleted_at, updated_at = :updated_at WHERE id = :id"
        );
        return $stmt->execute([
            ':deleted_at' => $now,
            ':updated_at' => $now,
            ':id'         => $id,
        ]);
    }

    // ----- Roles -----

    public function userRoles(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT r.* FROM roles r
             INNER JOIN user_has_roles uhr ON uhr.role_id = r.id
             WHERE uhr.user_id = :user_id"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $stmt = $this->connection->prepare(
            "INSERT IGNORE INTO user_has_roles (user_id, role_id) VALUES (:user_id, :role_id)"
        );
        $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
    }

    public function revokeRole(int $userId, int $roleId): bool
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM user_has_roles WHERE user_id = :user_id AND role_id = :role_id"
        );
        $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
        return $stmt->rowCount() > 0;
    }

    public function findRoleByName(string $nombre): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM roles WHERE name = :name");
        $stmt->execute([':name' => $nombre]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findRoleById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ----- Logs -----

    public function logAction(?int $actorId, ?int $targetId, string $accion, ?string $changes): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO log_users (performed_by_id, target_user_id, action, changes, timestamp)
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

    public function hasAdminRole(int $userId): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT uhr.user_id FROM user_has_roles uhr
             INNER JOIN roles r ON r.id = uhr.role_id
             WHERE uhr.user_id = :id
             AND r.name = 'admin'"
        );
        $stmt->execute([':id' => $userId]);
        return (bool) $stmt->fetch();
    }

    public function countActiveAdmins(): int
    {
        // Count only active users with the exact 'admin' role.
        // super-admin is a separate role and does not block deactivation of the last admin.
        $stmt = $this->connection->query(
            "SELECT COUNT(DISTINCT u.id) AS total FROM users u
             INNER JOIN user_has_roles uhr ON uhr.user_id = u.id
             INNER JOIN roles r ON r.id = uhr.role_id
             WHERE u.status = 'active'
             AND r.name = 'admin'
             AND u.deleted_at IS NULL"
        );
        return (int) $stmt->fetch()['total'];
    }

    public function countByStatus(): array
    {
        $stmt = $this->connection->query(
            "SELECT status, COUNT(*) AS total FROM users WHERE deleted_at IS NULL GROUP BY status"
        );
        return $stmt->fetchAll();
    }

    public function countByRole(): array
    {
        $stmt = $this->connection->query(
            "SELECT r.name AS role, COUNT(uhr.user_id) AS total
             FROM roles r
             LEFT JOIN user_has_roles uhr ON uhr.role_id = r.id
             GROUP BY r.id, r.name ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }

    public function listLogs(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhereLogs($filters);
        $sql   = "SELECT * FROM log_users" . $where['sql'] . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt  = $this->connection->prepare($sql);
        foreach ($where['params'] as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countLogs(array $filters = []): int
    {
        $where = $this->buildWhereLogs($filters);
        $stmt  = $this->connection->prepare("SELECT COUNT(*) AS total FROM log_users" . $where['sql']);
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function findLogById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM log_users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function buildWhereLogs(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = "target_user_id = :user_id";
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $conditions[] = "action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(timestamp) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(timestamp) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['name'])) {
            $conditions[] = "AND name LIKE :name";
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['status'])) {
            $conditions[] = "AND status = :status";
            $params[':status'] = $filters['status'];
        }

        return ['sql' => ' ' . implode(' ', $conditions), 'params' => $params];
    }
}
