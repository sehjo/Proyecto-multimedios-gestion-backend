<?php

class DiagnosisTreatmentRepository
{
    public static function findAll(): array
    {
        $stmt = db()->query('SELECT * FROM diagnoses_has_treatments ORDER BY id ASC');

        return array_map(fn ($row) => DiagnosisTreatment::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?DiagnosisTreatment
    {
        $stmt = db()->prepare('SELECT * FROM diagnoses_has_treatments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? DiagnosisTreatment::fromRow($row) : null;
    }

    public static function create(array $data): DiagnosisTreatment
    {
        $stmt = db()->prepare(
            'INSERT INTO diagnoses_has_treatments (diagnoses_id, drugs, descriptions, created_at, updated_at)
             VALUES (:diagnoses_id, :drugs, :descriptions, NOW(), NOW())'
        );

        $stmt->execute([
            'diagnoses_id' => $data['diagnoses_id'],
            'drugs' => $data['drugs'],
            'descriptions' => $data['descriptions'] ?? null,
        ]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM diagnoses_has_treatments WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
