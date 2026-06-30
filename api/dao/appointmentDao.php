<?php

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/appointment.php';

class AppointmentDao
{
    private $connection;

    private const AREAS_VALIDAS = [
        'general_medicine', 'pediatrics', 'cardiology', 'gynecology',
        'dermatology', 'neurology', 'orthopedics', 'ophthalmology',
        'dentistry', 'psychology',
    ];

    private const ESTADOS_VALIDOS = ['pending', 'approved', 'confirmed', 'canceled', 'rejected'];

    public function getValidAreas(): array  { return self::AREAS_VALIDAS; }
    public function getValidStatuses(): array { return self::ESTADOS_VALIDOS; }

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function countTotal(array $filters = []): int
    {
        $where = $this->buildWhere($filters);
        $stmt  = $this->connection->prepare("SELECT COUNT(*) AS total FROM appointments a" . $where['sql']);
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function index(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhere($filters);
        $sql   = "SELECT a.*, p.full_name AS patient_name FROM appointments a
                  LEFT JOIN patients p ON p.id = a.patient_id"
               . $where['sql']
               . " ORDER BY a.appointment_datetime DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->connection->prepare($sql);
        foreach ($where['params'] as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'stripConfirmationToken'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT a.*, p.full_name AS patient_name, p.email AS patient_email FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             WHERE a.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->stripConfirmationToken($row) : null;
    }

    private function stripConfirmationToken(array $row): array
    {
        unset($row['confirmation_token_hash'], $row['confirmation_token_expires_at']);
        return $row;
    }

    public function withRelations(array $appointment): array
    {
        if (!empty($appointment['patient_id'])) {
            $s = $this->connection->prepare(
                "SELECT id, full_name, identifier, email, birth_date, user_type, phone FROM patients WHERE id = :id"
            );
            $s->execute([':id' => $appointment['patient_id']]);
            $appointment['patient'] = $s->fetch() ?: null;
        } else {
            $appointment['patient'] = null;
        }

        if (!empty($appointment['companion_id'])) {
            $s = $this->connection->prepare("SELECT * FROM companions WHERE id = :id");
            $s->execute([':id' => $appointment['companion_id']]);
            $appointment['companion'] = $s->fetch() ?: null;
        } else {
            $appointment['companion'] = null;
        }

        if (!empty($appointment['accepted_by_id'])) {
            $s = $this->connection->prepare(
                "SELECT id, name, email FROM users WHERE id = :id"
            );
            $s->execute([':id' => $appointment['accepted_by_id']]);
            $appointment['accepted_by'] = $s->fetch() ?: null;
        } else {
            $appointment['accepted_by'] = null;
        }

        return $appointment;
    }

    public function patientExists(int $patientId): bool
    {
        $stmt = $this->connection->prepare("SELECT id FROM patients WHERE id = :id");
        $stmt->execute([':id' => $patientId]);
        return (bool) $stmt->fetch();
    }

    public function store(Cita $c): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO appointments
             (accepted_by_id, patient_id, attention_area, reason, appointment_datetime, status, created_at, updated_at)
             VALUES (:accepted_by_id, :patient_id, :attention_area, :reason, :appointment_datetime, :status, :created_at, :updated_at)"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':accepted_by_id'       => $c->getAcceptedById(),
            ':patient_id'           => $c->getPatientId(),
            ':attention_area'       => $c->getAttentionArea(),
            ':reason'               => $c->getReason(),
            ':appointment_datetime' => $c->getAppointmentDatetime(),
            ':status'               => $c->getStatus() ?? 'pending',
            ':created_at'           => $now,
            ':updated_at'           => $now,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, Cita $c): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE appointments SET
             patient_id = :patient_id, attention_area = :attention_area,
             reason = :reason, appointment_datetime = :appointment_datetime,
             updated_at = :updated_at
             WHERE id = :id"
        );
        return $stmt->execute([
            ':patient_id'           => $c->getPatientId(),
            ':attention_area'       => $c->getAttentionArea(),
            ':reason'               => $c->getReason(),
            ':appointment_datetime' => $c->getAppointmentDatetime(),
            ':updated_at'           => date('Y-m-d H:i:s'),
            ':id'                   => $id,
        ]);
    }

    public function changeStatus(int $id, string $status, ?string $cancelReason): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE appointments SET status = :status, cancellation_reason = :cancel, updated_at = :updated_at WHERE id = :id"
        );
        return $stmt->execute([
            ':status'     => $status,
            ':cancel'     => $cancelReason,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id'         => $id,
        ]);
    }

    public function calendar(string $fecha): array
    {
        $stmt = $this->connection->prepare(
            "SELECT a.*, p.full_name AS patient_name FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             WHERE DATE(a.appointment_datetime) = :fecha
             ORDER BY a.appointment_datetime ASC"
        );
        $stmt->execute([':fecha' => $fecha]);
        return array_map([$this, 'stripConfirmationToken'], $stmt->fetchAll());
    }

    public function logAction(?int $actorId, ?int $targetId, string $accion, ?string $changes): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO log_appointments (performed_by_id, target_appointment_id, action, changes, timestamp)
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

    public function confirm(int $id): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE appointments SET status = 'confirmed', updated_at = :now WHERE id = :id"
        );
        return $stmt->execute([':now' => date('Y-m-d H:i:s'), ':id' => $id]);
    }

    public function saveConfirmationToken(int $id, string $hashedToken, string $expiresAt): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE appointments
             SET confirmation_token_hash = :hash, confirmation_token_expires_at = :expires
             WHERE id = :id"
        );
        return $stmt->execute([':hash' => $hashedToken, ':expires' => $expiresAt, ':id' => $id]);
    }

    public function clearConfirmationToken(int $id): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE appointments
             SET confirmation_token_hash = NULL, confirmation_token_expires_at = NULL
             WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function findByConfirmationToken(string $hashedToken): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT a.*, p.full_name AS patient_name, p.email AS patient_email FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             WHERE a.confirmation_token_hash = :hash"
        );
        $stmt->execute([':hash' => $hashedToken]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function destroy(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM appointments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function listLogs(int $limit, int $offset, array $filters = []): array
    {
        $where = $this->buildWhereLogs($filters);
        $sql   = "SELECT * FROM log_appointments" . $where['sql'] . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt  = $this->connection->prepare($sql);
        foreach ($where['params'] as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countLogs(array $filters = []): int
    {
        $where = $this->buildWhereLogs($filters);
        $stmt  = $this->connection->prepare("SELECT COUNT(*) AS total FROM log_appointments" . $where['sql']);
        $stmt->execute($where['params']);
        return (int) $stmt->fetch()['total'];
    }

    public function findLogById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM log_appointments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function buildWhereLogs(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['appointment_id'])) {
            $conditions[] = "target_appointment_id = :appointment_id";
            $params[':appointment_id'] = (int) $filters['appointment_id'];
        }
        if (!empty($filters['performed_by_id'])) {
            $conditions[] = "performed_by_id = :performed_by_id";
            $params[':performed_by_id'] = (int) $filters['performed_by_id'];
        }
        if (!empty($filters['action'])) {
            $conditions[] = "action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(timestamp) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(timestamp) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params      = [];

        if (!empty($filters['status'])) {
            $conditions[] = "a.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['patient_id'])) {
            $conditions[] = "a.patient_id = :patient_id";
            $params[':patient_id'] = (int) $filters['patient_id'];
        }
        if (!empty($filters['attention_area'])) {
            $conditions[] = "a.attention_area = :attention_area";
            $params[':attention_area'] = $filters['attention_area'];
        }
        if (!empty($filters['date'])) {
            $conditions[] = "DATE(a.appointment_datetime) = :date";
            $params[':date'] = $filters['date'];
        }

        $sql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return ['sql' => $sql, 'params' => $params];
    }
}
