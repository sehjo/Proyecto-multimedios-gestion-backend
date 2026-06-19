<?php

class DiseaseTreatmentRepository
{
    public static function findAll(): array
    {
        $stmt = db()->query('SELECT * FROM disease_has_treatments ORDER BY id ASC');

        return array_map(fn ($row) => DiseaseTreatment::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?DiseaseTreatment
    {
        $stmt = db()->prepare('SELECT * FROM disease_has_treatments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? DiseaseTreatment::fromRow($row) : null;
    }

    public static function create(array $data): DiseaseTreatment
    {
        $stmt = db()->prepare(
            'INSERT INTO disease_has_treatments (disease_id, drugs, descriptions, created_at, updated_at)
             VALUES (:disease_id, :drugs, :descriptions, NOW(), NOW())'
        );

        $stmt->execute([
            'disease_id' => $data['disease_id'],
            'drugs' => $data['drugs'],
            'descriptions' => $data['descriptions'] ?? null,
        ]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM disease_has_treatments WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
