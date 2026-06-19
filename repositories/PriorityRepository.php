<?php

class PriorityRepository
{
    public static function findAll(): array
    {
        $stmt = db()->query('SELECT * FROM priority ORDER BY id ASC');

        return array_map(fn ($row) => Priority::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?Priority
    {
        $stmt = db()->prepare('SELECT * FROM priority WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Priority::fromRow($row) : null;
    }

    public static function create(array $data): Priority
    {
        $stmt = db()->prepare('INSERT INTO priority (name, created_at, updated_at) VALUES (:name, NOW(), NOW())');
        $stmt->execute(['name' => $data['name']]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function update(int $id, array $data): ?Priority
    {
        $stmt = db()->prepare('UPDATE priority SET name = :name, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['name' => $data['name'], 'id' => $id]);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM priority WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
