<?php

class DiagnosisRepository
{
    public static function findAll(): array
    {
        $stmt = db()->query('SELECT * FROM diagnoses ORDER BY id ASC');

        return array_map(fn ($row) => Diagnosis::fromRow($row), $stmt->fetchAll());
    }

    public static function findById(int $id): ?Diagnosis
    {
        $stmt = db()->prepare('SELECT * FROM diagnoses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Diagnosis::fromRow($row) : null;
    }

    public static function create(array $data): Diagnosis
    {
        $stmt = db()->prepare(
            'INSERT INTO diagnoses (name, disease_id, patient_id, diagnoses_by, created_at, updated_at)
             VALUES (:name, :disease_id, :patient_id, :diagnoses_by, NOW(), NOW())'
        );

        $stmt->execute([
            'name' => $data['name'],
            'disease_id' => $data['disease_id'],
            'patient_id' => $data['patient_id'],
            'diagnoses_by' => $data['diagnoses_by'],
        ]);

        return self::findById((int) db()->lastInsertId());
    }

    public static function update(int $id, array $data): ?Diagnosis
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'disease_id', 'patient_id', 'diagnoses_by'] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if (empty($fields)) {
            return self::findById($id);
        }

        $fields[] = 'updated_at = NOW()';

        $stmt = db()->prepare('UPDATE diagnoses SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM diagnoses WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
