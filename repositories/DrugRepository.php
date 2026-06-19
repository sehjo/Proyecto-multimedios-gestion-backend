<?php

class DrugRepository
{
    public static function findAll(): array
    {
        $stmt = db()->query('SELECT * FROM drugs ORDER BY id ASC');

        return array_map(fn ($row) => Drug::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?Drug
    {
        $stmt = db()->prepare('SELECT * FROM drugs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Drug::fromRow($row) : null;
    }

    public static function create(array $data): Drug
    {
        $stmt = db()->prepare(
            'INSERT INTO drugs (name, description, type, created_at, updated_at)
             VALUES (:name, :description, :type, NOW(), NOW())'
        );

        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
        ]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function update(int $id, array $data): ?Drug
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'description', 'type'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if (empty($fields)) {
            return self::findById($id);
        }

        $fields[] = 'updated_at = NOW()';

        $stmt = db()->prepare('UPDATE drugs SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM drugs WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
