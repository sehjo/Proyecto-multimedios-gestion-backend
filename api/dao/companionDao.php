<?php

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/companion.php';

class CompanionDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function countTotal(array $filters = []): int
    {
        $where = $this->buildWhere($filters);
        $stmt  = $this->connection->prepare("SELECT COUNT(*) AS total FROM companions" . $where['sql']);
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function index(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhere($filters);
        $sql   = "SELECT * FROM companions" . $where['sql'] . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt  = $this->connection->prepare($sql);
        foreach ($where['params'] as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM companions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function identifierExists(string $identifier, ?int $excluirId = null): bool
    {
        if ($excluirId) {
            $stmt = $this->connection->prepare("SELECT id FROM companions WHERE identifier = :identifier AND id != :id");
            $stmt->execute([':identifier' => $identifier, ':id' => $excluirId]);
        } else {
            $stmt = $this->connection->prepare("SELECT id FROM companions WHERE identifier = :identifier");
            $stmt->execute([':identifier' => $identifier]);
        }
        return (bool) $stmt->fetch();
    }

    public function store(Companion $a): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO companions (full_name, identifier, phone, created_at)
             VALUES (:full_name, :identifier, :phone, :created_at)"
        );
        $stmt->execute([
            ':full_name'  => $a->getFullName(),
            ':identifier' => $a->getIdentifier(),
            ':phone'      => $a->getPhone(),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, Companion $a): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE companions SET full_name = :full_name, identifier = :identifier, phone = :phone WHERE id = :id"
        );
        return $stmt->execute([
            ':full_name'  => $a->getFullName(),
            ':identifier' => $a->getIdentifier(),
            ':phone'      => $a->getPhone(),
            ':id'         => $id,
        ]);
    }

    public function destroy(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM companions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['full_name'])) {
            $conditions[] = "full_name LIKE :full_name";
            $params[':full_name'] = '%' . $filters['full_name'] . '%';
        }
        if (!empty($filters['identifier'])) {
            $conditions[] = "identifier LIKE :identifier";
            $params[':identifier'] = '%' . $filters['identifier'] . '%';
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }
}
