<?php

class DiseaseRepository
{
    public static function findAll(): array
    {
        $stmt = db()->query('SELECT * FROM disease ORDER BY id ASC');

        return array_map(fn ($row) => Disease::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?Disease
    {
        $stmt = db()->prepare('SELECT * FROM disease WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Disease::fromRow($row) : null;
    }

    public static function create(array $data): Disease
    {
        $stmt = db()->prepare(
            'INSERT INTO disease (name, technincal_name, description, priority_id, created_at, updated_at)
             VALUES (:name, :technincal_name, :description, :priority_id, NOW(), NOW())'
        );

        $stmt->execute([
            'name' => $data['name'],
            'technincal_name' => $data['technincal_name'] ?? null,
            'description' => $data['description'] ?? null,
            'priority_id' => $data['priority_id'],
        ]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function update(int $id, array $data): ?Disease
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'technincal_name', 'description', 'priority_id'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if (empty($fields)) {
            return self::findById($id);
        }

        $fields[] = 'updated_at = NOW()';

        $stmt = db()->prepare('UPDATE disease SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM disease WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
