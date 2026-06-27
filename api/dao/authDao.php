<?php

require_once __DIR__ . '/../config/connection.php';

class AuthDao
{
    private $connection;

    public function __construct()
    {
        $db = new Connection();
        $this->connection = $db->connect();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM users WHERE email = :email AND deleted_at IS NULL"
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function issueToken(int $userId): string
    {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        $stmt = $this->connection->prepare(
            "INSERT INTO tokens (user_id, token, expires_at, revoked, created_at)
             VALUES (:user_id, :token, :expires_at, 0, :created_at)"
        );
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $token,
            ':expires_at' => $expiresAt,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function findUserByToken(string $token): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT u.* FROM users u
             INNER JOIN tokens t ON t.user_id = u.id
             WHERE t.token = :token
               AND t.revoked = 0
               AND t.expires_at > NOW()
               AND u.deleted_at IS NULL"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function revokeToken(string $token): void
    {
        $stmt = $this->connection->prepare(
            "UPDATE tokens SET revoked = 1 WHERE token = :token"
        );
        $stmt->execute([':token' => $token]);
    }

    public function revokeAllTokens(int $userId): void
    {
        $stmt = $this->connection->prepare(
            "UPDATE tokens SET revoked = 1 WHERE user_id = :user_id"
        );
        $stmt->execute([':user_id' => $userId]);
    }

    public function saveResetToken(string $email, string $hashedToken): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO password_reset_tokens (email, token, created_at)
             VALUES (:email, :token, :created_at)
             ON DUPLICATE KEY UPDATE token = :token2, created_at = :created_at2"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            ':email'       => $email,
            ':token'       => $hashedToken,
            ':created_at'  => $now,
            ':token2'      => $hashedToken,
            ':created_at2' => $now,
        ]);
    }

    public function findResetToken(string $hashedToken): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM password_reset_tokens WHERE token = :token"
        );
        $stmt->execute([':token' => $hashedToken]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deleteResetTokenByEmail(string $email): void
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM password_reset_tokens WHERE email = :email"
        );
        $stmt->execute([':email' => $email]);
    }

    public function deleteResetTokenByHash(string $hashedToken): void
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM password_reset_tokens WHERE token = :token"
        );
        $stmt->execute([':token' => $hashedToken]);
    }

    public function updatePassword(int $userId, string $newPassword): void
    {
        $stmt = $this->connection->prepare(
            "UPDATE users SET password = :password, updated_at = :updated_at WHERE id = :id"
        );
        $stmt->execute([
            ':password'   => password_hash($newPassword, PASSWORD_BCRYPT),
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id'         => $userId,
        ]);
    }

    public function refreshToken(string $oldToken): ?string
    {
        $user = $this->findUserByToken($oldToken);
        if (!$user) {
            return null;
        }
        $this->revokeToken($oldToken);
        return $this->issueToken($user['id']);
    }

    public function markEmailVerified(string $email): void
    {
        $stmt = $this->connection->prepare(
            "UPDATE users SET email_verified_at = :now, updated_at = :now WHERE email = :email"
        );
        $stmt->execute([':now' => date('Y-m-d H:i:s'), ':email' => $email]);
    }

}
