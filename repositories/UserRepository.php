<?php

class UserRepository
{
    /** SELECT list that also resolves the role name via users_types. */
    private const SELECT_WITH_ROLE =
        'SELECT u.*, ut.name AS role_name FROM users u
         LEFT JOIN users_types ut ON ut.id = u.user_type_id';

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function paginate(int $offset, int $limit): array
    {
        return self::paginateFiltered([], $offset, $limit);
    }

    /**
     * Builds the WHERE clause shared by paginateFiltered() and countFiltered().
     * Filters: name (matches name/lastname/email), role (users_types.name), status.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0:string, 1:array<string, mixed>}
     */
    private static function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = '(u.name LIKE :term OR u.lastname LIKE :term OR u.email LIKE :term)';
            $params['term'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['role'])) {
            $where[] = 'ut.name = :role';
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }

        $clause = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        return [$clause, $params];
    }

    /**
     * Paginated users with optional filters, including the role name.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, User>
     */
    public static function paginateFiltered(array $filters, int $offset, int $limit): array
    {
        [$where, $params] = self::buildFilters($filters);

        $stmt = db()->prepare(
            self::SELECT_WITH_ROLE . $where . ' ORDER BY u.id ASC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn ($row) => User::fromRow($row), $stmt->fetchAll());
    }

    /**
     * Total users matching the given filters (for pagination meta).
     *
     * @param  array<string, mixed>  $filters
     */
    public static function countFiltered(array $filters): int
    {
        [$where, $params] = self::buildFilters($filters);

        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM users u LEFT JOIN users_types ut ON ut.id = u.user_type_id' . $where
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public static function findById(int $id): ?User
    {
        $stmt = db()->prepare(self::SELECT_WITH_ROLE . ' WHERE u.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    public static function findByEmail(string $email): ?User
    {
        $stmt = db()->prepare(self::SELECT_WITH_ROLE . ' WHERE u.email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Whether an email is already taken, optionally excluding one user id
     * (used by update validation).
     */
    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data): User
    {
        $stmt = db()->prepare(
            'INSERT INTO users (name, lastname, email, password, user_type_id, status, created_at, updated_at)
             VALUES (:name, :lastname, :email, :password, :user_type_id, :status, NOW(), NOW())'
        );

        $stmt->execute([
            'name' => $data['name'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'user_type_id' => $data['user_type_id'],
            // New users start active unless told otherwise.
            'status' => $data['status'] ?? 'ACTIVE',
        ]);

        $id = (int) db()->lastInsertId();

        // Keep the role pivot in sync: the creation role is the user's first role.
        $pivot = db()->prepare('INSERT INTO user_has_roles (user_id, user_type_id) VALUES (:user_id, :role_id)');
        $pivot->execute(['user_id' => $id, 'role_id' => $data['user_type_id']]);

        return self::findById($id);
    }

    public static function updateStatus(int $id, string $status): ?User
    {
        $stmt = db()->prepare('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        return self::findById($id);
    }

    public static function update(int $id, array $data): ?User
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'lastname', 'email', 'user_type_id'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if (array_key_exists('password', $data) && $data['password'] !== null && $data['password'] !== '') {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return self::findById($id);
        }

        $fields[] = 'updated_at = NOW()';

        $stmt = db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        return self::findById($id);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        $stmt = db()->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'password' => password_hash($plainPassword, PASSWORD_BCRYPT),
            'id' => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Role ids the user holds (from user_has_roles).
     *
     * @return array<int, int>
     */
    public static function roleIds(int $userId): array
    {
        $stmt = db()->prepare('SELECT user_type_id FROM user_has_roles WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'user_type_id'));
    }

    /**
     * Role names the user holds (from user_has_roles), ordered by name.
     *
     * @return array<int, string>
     */
    public static function roleNames(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT ut.name
             FROM user_has_roles uhr
             INNER JOIN users_types ut ON ut.id = uhr.user_type_id
             WHERE uhr.user_id = :id
             ORDER BY ut.name ASC'
        );
        $stmt->execute(['id' => $userId]);

        return array_map(fn ($row) => $row['name'], $stmt->fetchAll());
    }

    /**
     * Replaces a user's whole role set. The FIRST role becomes the primary one
     * (users.user_type_id) so the modules that read it keep working. Runs in a
     * transaction so the pivot and the primary stay consistent.
     *
     * @param  array<int, int>  $roleIds  ordered; first = primary role
     */
    public static function syncRoles(int $userId, array $roleIds): ?User
    {
        // Deduplicate while preserving order (first occurrence wins as primary).
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        if ($roleIds === []) {
            return self::findById($userId);
        }

        $db = db();
        $db->beginTransaction();

        try {
            $del = $db->prepare('DELETE FROM user_has_roles WHERE user_id = :id');
            $del->execute(['id' => $userId]);

            $ins = $db->prepare('INSERT INTO user_has_roles (user_id, user_type_id) VALUES (:user_id, :role_id)');
            foreach ($roleIds as $roleId) {
                $ins->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }

            // Primary role = first in the list.
            $upd = $db->prepare('UPDATE users SET user_type_id = :role_id, updated_at = NOW() WHERE id = :id');
            $upd->execute(['role_id' => $roleIds[0], 'id' => $userId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return self::findById($userId);
    }

    /**
     * User count per role (users_types). Every role is included (0 if unused).
     *
     * @return array<string, int>
     */
    public static function countByRole(): array
    {
        $rows = db()->query(
            'SELECT ut.name AS role_name, COUNT(u.id) AS total
             FROM users_types ut
             LEFT JOIN users u ON u.user_type_id = ut.id
             GROUP BY ut.id, ut.name
             ORDER BY ut.name ASC'
        )->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['role_name']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * User count per status (ACTIVE / INACTIVE).
     *
     * @return array<string, int>
     */
    public static function countByStatus(): array
    {
        $counts = ['ACTIVE' => 0, 'INACTIVE' => 0];

        $rows = db()->query('SELECT status, COUNT(*) AS total FROM users GROUP BY status')->fetchAll();
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Number of active administrators (used by the anti-lockout guard).
     * `$adminTypeId` is the users_types id of the Administrador role.
     */
    public static function activeAdminCount(int $adminTypeId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM users WHERE user_type_id = :type AND status = 'ACTIVE'"
        );
        $stmt->execute(['type' => $adminTypeId]);

        return (int) $stmt->fetchColumn();
    }
}
