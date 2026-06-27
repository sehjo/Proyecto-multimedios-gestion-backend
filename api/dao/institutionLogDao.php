<?php

require_once __DIR__ . '/../config/connection.php';

class InstitutionLogDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function index(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhere($filters);
        $sql   = "SELECT * FROM log_institution" . $where['sql'] . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt  = $this->connection->prepare($sql);
        foreach ($where['params'] as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countTotal(array $filters = []): int
    {
        $where = $this->buildWhere($filters);
        $stmt  = $this->connection->prepare("SELECT COUNT(*) AS total FROM log_institution" . $where['sql']);
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM log_institution WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['action'])) {
            $conditions[] = "action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['target_type'])) {
            $conditions[] = "target_type = :target_type";
            $params[':target_type'] = $filters['target_type'];
        }
        if (!empty($filters['performed_by'])) {
            $conditions[] = "performed_by_id = :performed_by";
            $params[':performed_by'] = (int) $filters['performed_by'];
        }
        if (!empty($filters['from'])) {
            $conditions[] = "DATE(timestamp) >= :from";
            $params[':from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $conditions[] = "DATE(timestamp) <= :to";
            $params[':to'] = $filters['to'];
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }
}
