<?php

class PatientRepository
{
    private const SELECT_WITH_USER = '
        SELECT
            p.*,
            u.id AS user_id,
            u.name AS user_name,
            u.lastname AS user_lastname,
            u.email AS user_email,
            u.password AS user_password,
            u.user_type_id AS user_user_type_id,
            u.created_at AS user_created_at,
            u.updated_at AS user_updated_at
        FROM patient p
        LEFT JOIN users u ON u.id = p.register_by
    ';

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM patient')->fetchColumn();
    }

    public static function paginate(int $offset, int $limit): array
    {
        $stmt = db()->prepare(self::SELECT_WITH_USER . ' ORDER BY p.id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn ($row) => self::hydrate($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?Patient
    {
        $stmt = db()->prepare(self::SELECT_WITH_USER . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function create(array $data): Patient
    {
        $stmt = db()->prepare(
            'INSERT INTO patient (name, lastname, nick, suffering, register_by, created_at, updated_at)
             VALUES (:name, :lastname, :nick, :suffering, :register_by, NOW(), NOW())'
        );

        $stmt->execute([
            'name' => $data['name'],
            'lastname' => $data['lastname'],
            'nick' => $data['nick'],
            'suffering' => $data['suffering'] ?? null,
            'register_by' => $data['register_by'] ?? null,
        ]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function update(int $id, array $data): ?Patient
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'lastname', 'nick', 'suffering', 'register_by'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if (empty($fields)) {
            return self::findById($id);
        }

        $fields[] = 'updated_at = NOW()';

        $stmt = db()->prepare('UPDATE patient SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM patient WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private static function hydrate(array $row): Patient
    {
        $patient = Patient::fromRow($row);

        if (!empty($row['user_id'])) {
            $patient->setUser(User::fromRow([
                'id' => $row['user_id'],
                'name' => $row['user_name'],
                'lastname' => $row['user_lastname'],
                'email' => $row['user_email'],
                'password' => $row['user_password'],
                'user_type_id' => $row['user_user_type_id'],
                'created_at' => $row['user_created_at'],
                'updated_at' => $row['user_updated_at'],
            ]));
        }

        return $patient;
    }
}
