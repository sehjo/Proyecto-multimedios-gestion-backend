<?php

require_once __DIR__ . '/../config/connection.php';

class BlockDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function index(array $filters = []): array
    {
        $where = $this->buildWhere($filters);
        $sql   = "SELECT b.* FROM institution_blocks b"
               . $where['sql']
               . " ORDER BY b.date ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($where['params']);
        return array_map([$this, 'withRelations'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT b.* FROM institution_blocks b WHERE b.id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->withRelations($row) : null;
    }

    public function withRelations(array $block): array
    {
        if (!empty($block['created_by_id'])) {
            $s = $this->connection->prepare("SELECT id, name FROM users WHERE id = :id");
            $s->execute([':id' => $block['created_by_id']]);
            $block['created_by'] = $s->fetch() ?: null;
        } else {
            $block['created_by'] = null;
        }
        return $block;
    }

    public function dateExists(string $fecha, ?int $excluirId = null): bool
    {
        if ($excluirId) {
            $stmt = $this->connection->prepare("SELECT id FROM institution_blocks WHERE date = :date AND id != :id");
            $stmt->execute([':date' => $fecha, ':id' => $excluirId]);
        } else {
            $stmt = $this->connection->prepare("SELECT id FROM institution_blocks WHERE date = :date");
            $stmt->execute([':date' => $fecha]);
        }
        return (bool) $stmt->fetch();
    }

    public function store(array $datos, int $actorId): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO institution_blocks (date, reason, full_day, start_time, end_time, created_by_id, created_at, updated_at)
             VALUES (:date, :reason, :full_day, :start_time, :end_time, :created_by_id, :now, :now)"
        );
        $stmt->execute([
            ':date'          => $datos['date'],
            ':reason'        => $datos['reason']     ?? null,
            ':full_day'      => isset($datos['full_day']) ? (int) $datos['full_day'] : 1,
            ':start_time'    => $datos['start_time'] ?? null,
            ':end_time'      => $datos['end_time']   ?? null,
            ':created_by_id' => $actorId,
            ':now'           => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $datos): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE institution_blocks SET date = :date, reason = :reason, full_day = :full_day,
             start_time = :start_time, end_time = :end_time, updated_at = :now WHERE id = :id"
        );
        return $stmt->execute([
            ':date'       => $datos['date'],
            ':reason'     => $datos['reason']     ?? null,
            ':full_day'   => isset($datos['full_day']) ? (int) $datos['full_day'] : 1,
            ':start_time' => $datos['start_time'] ?? null,
            ':end_time'   => $datos['end_time']   ?? null,
            ':now'        => date('Y-m-d H:i:s'),
            ':id'         => $id,
        ]);
    }

    public function destroy(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM institution_blocks WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function logAction(?int $actorId, int $targetId, string $accion, ?string $changes): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO log_institution (performed_by_id, target_type, target_id, action, changes, timestamp)
             VALUES (:actor, 'InstitutionBlock', :target, :action, :changes, :ts)"
        );
        $stmt->execute([
            ':actor'   => $actorId,
            ':target'  => $targetId,
            ':action'  => $accion,
            ':changes' => $changes,
            ':ts'      => date('Y-m-d H:i:s'),
        ]);
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['date'])) {
            $conditions[] = "b.date = :date";
            $params[':date'] = $filters['date'];
        }
        if (isset($filters['full_day']) && $filters['full_day'] !== null) {
            $conditions[] = "b.full_day = :full_day";
            $params[':full_day'] = (int) $filters['full_day'];
        }
        if (!empty($filters['from'])) {
            $conditions[] = "b.date >= :from";
            $params[':from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $conditions[] = "b.date <= :to";
            $params[':to'] = $filters['to'];
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }
}
