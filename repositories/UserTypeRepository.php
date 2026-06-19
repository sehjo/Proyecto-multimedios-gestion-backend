<?php

class UserTypeRepository
{
    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users_types')->fetchColumn();
    }

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

    public static function create(array $data): UserType
    {
        $stmt = db()->prepare('INSERT INTO users_types (name, created_at, updated_at) VALUES (:name, NOW(), NOW())');
        $stmt->execute(['name' => $data['name']]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function update(int $id, array $data): ?UserType
    {
        $stmt = db()->prepare('UPDATE users_types SET name = :name, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['name' => $data['name'], 'id' => $id]);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM users_types WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
