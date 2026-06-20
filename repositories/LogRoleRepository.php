<?php

class LogRoleRepository
{
    public static function create(string $action, ?int $actorId, ?int $targetId, array $changes): void
    {
        $stmt = db()->prepare(
            'INSERT INTO log_roles (action, actor_id, target_id, changes, created_at)
             VALUES (:action, :actor_id, :target_id, :changes, NOW())'
        );

        $stmt->execute([
            'action' => $action,
            'actor_id' => $actorId,
            'target_id' => $targetId,
            'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM log_roles')->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function paginate(int $offset, int $limit): array
    {
        $stmt = db()->prepare('SELECT * FROM log_roles ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
