<?php

require_once __DIR__ . '/../config/connection.php';

class NotificationDao
{
    private \PDO $connection;

    public function __construct()
    {
        $this->connection = (new Connection())->connect();
    }

    public function index(int $userId, int $limit, int $offset): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countTotal(int $userId): int
    {
        $stmt = $this->connection->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetch()['total'];
    }

    public function listUnread(int $userId, int $limit, int $offset): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM notifications WHERE user_id = :user_id AND read_at IS NULL ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND read_at IS NULL"
        );
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetch()['total'];
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM notifications WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findOne(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM notifications WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE notifications SET ui_status = 'read', read_at = :read_at WHERE id = :id AND user_id = :user_id"
        );
        return $stmt->execute([':read_at' => date('Y-m-d H:i:s'), ':id' => $id, ':user_id' => $userId]);
    }

    public function markAsUnread(int $id, int $userId): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE notifications SET ui_status = 'unread', read_at = NULL WHERE id = :id AND user_id = :user_id"
        );
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }

    public function markAllAsRead(int $userId): int
    {
        $stmt = $this->connection->prepare(
            "UPDATE notifications SET ui_status = 'read', read_at = :read_at WHERE user_id = :user_id AND read_at IS NULL"
        );
        $stmt->execute([':read_at' => date('Y-m-d H:i:s'), ':user_id' => $userId]);
        return $stmt->rowCount();
    }

    public function destroy(int $id, int $userId): bool
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM notifications WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function crear(int $userId, ?int $appointmentId, string $mensaje): int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO notifications (user_id, appointment_id, message, ui_status, sent_at, created_at)
             VALUES (:user_id, :appointment_id, :message, 'unread', :sent_at, :created_at)"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':user_id'        => $userId,
            ':appointment_id' => $appointmentId,
            ':message'        => $mensaje,
            ':sent_at'        => $now,
            ':created_at'     => $now,
        ]);
        return (int) $this->connection->lastInsertId();
    }
}
