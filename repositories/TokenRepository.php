<?php

class TokenRepository
{
    public static function create(int $tokenableId, string $tokenableType, string $hashedToken, string $name): void
    {
        $stmt = db()->prepare(
            'INSERT INTO personal_access_tokens (tokenable_type, tokenable_id, name, token, created_at, updated_at)
             VALUES (:tokenable_type, :tokenable_id, :name, :token, NOW(), NOW())'
        );

        $stmt->execute([
            'tokenable_type' => $tokenableType,
            'tokenable_id' => $tokenableId,
            'name' => $name,
            'token' => $hashedToken,
        ]);
    }

    public static function findByHash(string $hashedToken): ?array
    {
        $stmt = db()->prepare('SELECT * FROM personal_access_tokens WHERE token = :token');
        $stmt->execute(['token' => $hashedToken]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function touchLastUsed(int $id): void
    {
        $stmt = db()->prepare('UPDATE personal_access_tokens SET last_used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function deleteByHash(string $hashedToken): bool
    {
        $stmt = db()->prepare('DELETE FROM personal_access_tokens WHERE token = :token');
        $stmt->execute(['token' => $hashedToken]);

        return $stmt->rowCount() > 0;
    }

    public static function deleteAllForUser(int $userId, string $tokenableType = 'User'): void
    {
        $stmt = db()->prepare(
            'DELETE FROM personal_access_tokens WHERE tokenable_id = :tokenable_id AND tokenable_type = :tokenable_type'
        );

        $stmt->execute([
            'tokenable_id' => $userId,
            'tokenable_type' => $tokenableType,
        ]);
    }
}
