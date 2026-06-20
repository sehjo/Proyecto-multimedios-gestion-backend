<?php

/**
 * Roles are the users_types rows; their permissions live in
 * usertype_has_permissions. This repository wraps both so controllers can treat
 * "role" as a first-class concept (CRUD + permission sync).
 */
class RoleRepository
{
    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users_types')->fetchColumn();
    }

    /**
     * @return array<int, UserType>
     */
    public static function all(): array
    {
        $rows = db()->query('SELECT * FROM users_types ORDER BY name ASC')->fetchAll();

        return array_map(fn ($row) => UserType::fromRow($row), $rows);
    }

    /**
     * @return array<int, UserType>
     */
    public static function paginate(int $offset, int $limit): array
    {
        $stmt = db()->prepare('SELECT * FROM users_types ORDER BY id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn ($row) => UserType::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?UserType
    {
        $stmt = db()->prepare('SELECT * FROM users_types WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? UserType::fromRow($row) : null;
    }

    public static function findByName(string $name): ?UserType
    {
        $stmt = db()->prepare('SELECT * FROM users_types WHERE name = :name');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row ? UserType::fromRow($row) : null;
    }

    public static function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users_types WHERE name = :name';
        $params = ['name' => $name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Creates a role and (optionally) syncs its permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public static function create(string $name, array $permissions = []): UserType
    {
        $stmt = db()->prepare('INSERT INTO users_types (name, created_at, updated_at) VALUES (:name, NOW(), NOW())');
        $stmt->execute(['name' => $name]);

        $id = (int) db()->lastInsertId();
        PermissionRepository::syncForUserType($id, $permissions);

        return self::findById($id);
    }

    public static function update(int $id, string $name): ?UserType
    {
        $stmt = db()->prepare('UPDATE users_types SET name = :name, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        // usertype_has_permissions rows cascade via FK ON DELETE CASCADE.
        $stmt = db()->prepare('DELETE FROM users_types WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Number of users currently assigned to this role.
     */
    public static function usersCount(int $id): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE user_type_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Permission names granted to the role.
     *
     * @return array<int, string>
     */
    public static function permissions(int $id): array
    {
        return PermissionRepository::namesForUserType($id);
    }

    /**
     * Replaces the role's permission set (with read/write cascade).
     *
     * @param  array<int, string>  $permissions
     */
    public static function syncPermissions(int $id, array $permissions): void
    {
        PermissionRepository::syncForUserType($id, $permissions);
    }
}
