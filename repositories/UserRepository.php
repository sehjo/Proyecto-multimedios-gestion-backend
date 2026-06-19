<?php

class UserRepository
{
    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function paginate(int $offset, int $limit): array
    {
        $stmt = db()->prepare('SELECT * FROM users ORDER BY id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn ($row) => User::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?User
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    public static function findByEmail(string $email): ?User
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    public static function create(array $data): User
    {
        $stmt = db()->prepare(
            'INSERT INTO users (name, lastname, email, password, user_type_id, created_at, updated_at)
             VALUES (:name, :lastname, :email, :password, :user_type_id, NOW(), NOW())'
        );

        $stmt->execute([
            'name' => $data['name'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'user_type_id' => $data['user_type_id'],
        ]);

        return self::findById((int) db()->lastInsertId());
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
}
