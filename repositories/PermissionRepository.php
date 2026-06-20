<?php

class PermissionRepository
{
    /**
     * All permission names in the catalog, ordered by name.
     *
     * @return array<int, string>
     */
    public static function allNames(): array
    {
        $rows = db()->query('SELECT name FROM permissions ORDER BY name ASC')->fetchAll();

        return array_map(fn ($row) => $row['name'], $rows);
    }

    /**
     * All permissions (id + name), ordered by name.
     *
     * @return array<int, array{id:int, name:string}>
     */
    public static function all(): array
    {
        $rows = db()->query('SELECT id, name FROM permissions ORDER BY name ASC')->fetchAll();

        return array_map(fn ($row) => ['id' => (int) $row['id'], 'name' => $row['name']], $rows);
    }

    /**
     * Permission names granted to a given role (users_type).
     *
     * @return array<int, string>
     */
    public static function namesForUserType(int $userTypeId): array
    {
        $stmt = db()->prepare(
            'SELECT p.name
             FROM permissions p
             INNER JOIN usertype_has_permissions uhp ON uhp.permission_id = p.id
             WHERE uhp.user_type_id = :user_type_id
             ORDER BY p.name ASC'
        );
        $stmt->execute(['user_type_id' => $userTypeId]);

        return array_map(fn ($row) => $row['name'], $stmt->fetchAll());
    }

    /**
     * Whether a role (users_type) has a specific permission.
     */
    public static function userTypeHas(int $userTypeId, string $permission): bool
    {
        $stmt = db()->prepare(
            'SELECT 1
             FROM usertype_has_permissions uhp
             INNER JOIN permissions p ON p.id = uhp.permission_id
             WHERE uhp.user_type_id = :user_type_id AND p.name = :name
             LIMIT 1'
        );
        $stmt->execute(['user_type_id' => $userTypeId, 'name' => $permission]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Replaces the whole permission set of a role with the given names.
     * Expands the read/write cascade so a write permission always implies read.
     *
     * @param  array<int, string>  $permissionNames
     */
    public static function syncForUserType(int $userTypeId, array $permissionNames): void
    {
        $names = self::expandCascade($permissionNames);

        $db = db();
        $db->beginTransaction();

        try {
            $del = $db->prepare('DELETE FROM usertype_has_permissions WHERE user_type_id = :user_type_id');
            $del->execute(['user_type_id' => $userTypeId]);

            if ($names !== []) {
                $placeholders = implode(',', array_fill(0, count($names), '?'));
                $ins = $db->prepare(
                    "INSERT INTO usertype_has_permissions (user_type_id, permission_id)
                     SELECT :user_type_id, id FROM permissions WHERE name IN ($placeholders)"
                );
                $ins->bindValue(':user_type_id', $userTypeId, PDO::PARAM_INT);
                foreach ($names as $i => $name) {
                    $ins->bindValue($i + 1, $name);
                }
                $ins->execute();
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Applies linked permissions: every "<module>.<create|update|delete>" adds
     * the matching "<module>.read". Mirrors PermissionCatalog::expand().
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function expandCascade(array $names): array
    {
        $writeActions = ['create', 'update', 'delete'];
        $expanded = [];

        foreach ($names as $name) {
            $expanded[$name] = true;

            $parts = explode('.', $name, 2);
            if (count($parts) === 2 && in_array($parts[1], $writeActions, true)) {
                $expanded["{$parts[0]}.read"] = true;
            }
        }

        return array_keys($expanded);
    }
}
