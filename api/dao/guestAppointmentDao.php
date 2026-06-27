<?php

require_once __DIR__ . '/../config/connection.php';

class GuestAppointmentDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function findOrCreatePatient(array $datos): int
    {
        $stmt = $this->connection->prepare("SELECT id FROM patients WHERE identifier = :identifier");
        $stmt->execute([':identifier' => $datos['identifier']]);
        $existente = $stmt->fetch();

        if ($existente) {
            return (int) $existente['id'];
        }

        $stmt = $this->connection->prepare(
            "INSERT INTO patients (full_name, identifier, email, birth_date, user_type, phone, created_at)
             VALUES (:full_name, :identifier, :email, :birth_date, :user_type, :phone, :created_at)"
        );
        $stmt->execute([
            ':full_name'  => $datos['full_name'],
            ':identifier' => $datos['identifier'],
            ':email'      => $datos['email'] ?? '',
            ':birth_date' => $datos['birth_date'],
            ':user_type'  => $datos['user_type'],
            ':phone'      => $datos['phone'],
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function findOrCreateCompanion(?array $datos): ?int
    {
        if (!$datos) return null;

        $stmt = $this->connection->prepare("SELECT id FROM companions WHERE identifier = :identifier");
        $stmt->execute([':identifier' => $datos['identifier']]);
        $existente = $stmt->fetch();

        if ($existente) {
            return (int) $existente['id'];
        }

        $stmt = $this->connection->prepare(
            "INSERT INTO companions (full_name, identifier, phone, created_at)
             VALUES (:full_name, :identifier, :phone, :created_at)"
        );
        $stmt->execute([
            ':full_name'  => $datos['full_name'],
            ':identifier' => $datos['identifier'],
            ':phone'      => $datos['phone'],
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function createAppointment(int $patientId, ?int $companionId, array $datos): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO appointments
             (patient_id, companion_id, attention_area, reason, appointment_datetime, status, is_guest, created_at, updated_at)
             VALUES (:patient_id, :companion_id, :attention_area, :reason, :appointment_datetime, 'pending', 1, :now, :now)"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':patient_id'           => $patientId,
            ':companion_id'         => $companionId,
            ':attention_area'       => $datos['attention_area'],
            ':reason'               => $datos['reason'] ?? null,
            ':appointment_datetime' => $datos['appointment_datetime'],
            ':now'                  => $now,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function findAppointmentById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT a.*, p.full_name AS patient_name FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id WHERE a.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveVerificationToken(string $email, string $token): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO password_reset_tokens (email, token, created_at)
             VALUES (:email, :token, :now)
             ON DUPLICATE KEY UPDATE token = :token2, created_at = :now2"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':email' => $email, ':token' => $token, ':now' => $now,
            ':token2' => $token, ':now2' => $now,
        ]);
    }

    public function findVerificationToken(string $token): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM password_reset_tokens WHERE token = :token"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deleteVerificationToken(string $token): void
    {
        $stmt = $this->connection->prepare("DELETE FROM password_reset_tokens WHERE token = :token");
        $stmt->execute([':token' => $token]);
    }
}
