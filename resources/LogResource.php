<?php

/**
 * Serializes a raw log row (log_users / log_roles). `changes` is stored as JSON
 * and decoded back into an object for the response.
 */
class LogResource
{
    public static function toArray(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : null,
            'action' => $row['action'] ?? null,
            'actor_id' => isset($row['actor_id']) ? (int) $row['actor_id'] : null,
            'target_id' => isset($row['target_id']) ? (int) $row['target_id'] : null,
            'changes' => isset($row['changes']) ? json_decode($row['changes'], true) : null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function collection(array $rows): array
    {
        return array_map(fn (array $row) => self::toArray($row), $rows);
    }
}
