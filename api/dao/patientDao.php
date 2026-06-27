<?php

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/patient.php';

class PatientDao
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
        $stmt = $this->connection->prepare("SELECT COUNT(*) AS total FROM patients" . $where['sql']);
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function index(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhere($filters);
        $sql = "SELECT * FROM patients" . $where['sql'] . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->connection->prepare($sql);
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
        $stmt = $this->connection->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function identifierExists(string $identifier, ?int $excluirId = null): bool
    {
        if ($excluirId) {
            $stmt = $this->connection->prepare("SELECT id FROM patients WHERE identifier = :identifier AND id != :id");
            $stmt->execute([':identifier' => $identifier, ':id' => $excluirId]);
        } else {
            $stmt = $this->connection->prepare("SELECT id FROM patients WHERE identifier = :identifier");
            $stmt->execute([':identifier' => $identifier]);
        }
        return (bool) $stmt->fetch();
    }

    public function store(Paciente $p): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO patients (full_name, identifier, email, birth_date, user_type, phone, created_at)
             VALUES (:full_name, :identifier, :email, :birth_date, :user_type, :phone, :created_at)"
        );
        $stmt->execute([
            ':full_name'  => $p->getFullName(),
            ':identifier' => $p->getIdentifier(),
            ':email'      => $p->getEmail(),
            ':birth_date' => $p->getBirthDate(),
            ':user_type'  => $p->getUserType(),
            ':phone'      => $p->getPhone(),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, Paciente $p): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE patients SET full_name = :full_name, identifier = :identifier, email = :email,
             birth_date = :birth_date, user_type = :user_type, phone = :phone
             WHERE id = :id"
        );
        return $stmt->execute([
            ':full_name'  => $p->getFullName(),
            ':identifier' => $p->getIdentifier(),
            ':email'      => $p->getEmail(),
            ':birth_date' => $p->getBirthDate(),
            ':user_type'  => $p->getUserType(),
            ':phone'      => $p->getPhone(),
            ':id'         => $id,
        ]);
    }

    public function destroy(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM patients WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function logAction(?int $actorId, ?int $targetId, string $accion, ?string $changes): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO log_patients (performed_by_id, target_patient_id, action, changes, timestamp)
             VALUES (:actor, :target, :action, :changes, :ts)"
        );
        $stmt->execute([
            ':actor'   => $actorId,
            ':target'  => $targetId,
            ':action'  => $accion,
            ':changes' => $changes,
            ':ts'      => date('Y-m-d H:i:s'),
        ]);
    }

    public function listLogs(int $limit, int $offset): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM log_patients ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countLogs(): int
    {
        $stmt = $this->connection->query("SELECT COUNT(*) AS total FROM log_patients");
        return (int) $stmt->fetch()['total'];
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['full_name'])) {
            $conditions[] = "full_name LIKE :full_name";
            $params[':full_name'] = '%' . $filters['full_name'] . '%';
        }
        if (!empty($filters['identifier'])) {
            $conditions[] = "identifier LIKE :identifier";
            $params[':identifier'] = '%' . $filters['identifier'] . '%';
        }
        if (!empty($filters['user_type'])) {
            $conditions[] = "user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }
}
