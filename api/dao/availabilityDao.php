<?php

require_once __DIR__ . '/../config/connection.php';

class AvailabilityDao
{
    private $connection;

    private const DIAS_VALIDOS = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

    public function getValidDays(): array { return self::DIAS_VALIDOS; }

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function index(?bool $enabled = null): array
    {
        $cols = "id, day_of_week, start_time, end_time, enabled";
        $order = "ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')";
        if ($enabled !== null) {
            $stmt = $this->connection->prepare("SELECT {$cols} FROM institution_availability WHERE enabled = :enabled {$order}");
            $stmt->execute([':enabled' => (int) $enabled]);
        } else {
            $stmt = $this->connection->query("SELECT {$cols} FROM institution_availability {$order}");
        }
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['enabled'] = (bool)(int)$row['enabled'];
        }
        return $rows;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT id, day_of_week, start_time, end_time, enabled FROM institution_availability WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['enabled'] = (bool)(int)$row['enabled'];
        return $row;
    }

    public function overlapExists(string $dia, string $inicio, string $fin, ?int $excluirId = null): bool
    {
        $sql = "SELECT id FROM institution_availability
                WHERE day_of_week = :dia
                AND start_time < :fin
                AND end_time > :inicio";
        if ($excluirId) {
            $sql .= " AND id != :excluir_id";
        }
        $stmt   = $this->connection->prepare($sql);
        $params = [':dia' => $dia, ':inicio' => $inicio, ':fin' => $fin];
        if ($excluirId) {
            $params[':excluir_id'] = $excluirId;
        }
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function store(array $datos): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO institution_availability (day_of_week, start_time, end_time, enabled, created_at, updated_at)
             VALUES (:day_of_week, :start_time, :end_time, :enabled, :now, :now)"
        );
        $stmt->execute([
            ':day_of_week' => $datos['day_of_week'],
            ':start_time'  => $datos['start_time']  ?? null,
            ':end_time'    => $datos['end_time']     ?? null,
            ':enabled'     => isset($datos['enabled']) ? (int) $datos['enabled'] : 1,
            ':now'         => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $datos): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE institution_availability SET day_of_week = :day_of_week, start_time = :start_time,
             end_time = :end_time, enabled = :enabled, updated_at = :now WHERE id = :id"
        );
        return $stmt->execute([
            ':day_of_week' => $datos['day_of_week'],
            ':start_time'  => $datos['start_time'] ?? null,
            ':end_time'    => $datos['end_time']    ?? null,
            ':enabled'     => isset($datos['enabled']) ? (int) $datos['enabled'] : 1,
            ':now'         => date('Y-m-d H:i:s'),
            ':id'          => $id,
        ]);
    }

    public function destroy(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM institution_availability WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function logAction(?int $actorId, string $targetType, int $targetId, string $accion, ?string $changes): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO log_institution (performed_by_id, target_type, target_id, action, changes, timestamp)
             VALUES (:actor, :type, :target, :action, :changes, :ts)"
        );
        $stmt->execute([
            ':actor'   => $actorId,
            ':type'    => $targetType,
            ':target'  => $targetId,
            ':action'  => $accion,
            ':changes' => $changes,
            ':ts'      => date('Y-m-d H:i:s'),
        ]);
    }
}
