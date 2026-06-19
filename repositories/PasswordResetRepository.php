<?php

class PasswordResetRepository
{
    public static function deleteByEmail(string $email): void
    {
        $stmt = db()->prepare('DELETE FROM password_reset_tokens WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }

    public static function create(string $email, string $hashedToken): void
    {
        // created_at is supplied by PHP (not MySQL's NOW()) so that the expiry check in
        // AuthController::resetPassword, which uses PHP's time(), compares against the same clock
        // regardless of the database server's timezone setting.
        $stmt = db()->prepare(
            'INSERT INTO password_reset_tokens (email, token, created_at) VALUES (:email, :token, :created_at)'
        );

        $stmt->execute([
            'email' => $email,
            'token' => $hashedToken,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function findByHash(string $hashedToken): ?array
    {
        $stmt = db()->prepare('SELECT * FROM password_reset_tokens WHERE token = :token');
        $stmt->execute(['token' => $hashedToken]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function deleteByHash(string $hashedToken): void
    {
        $stmt = db()->prepare('DELETE FROM password_reset_tokens WHERE token = :token');
        $stmt->execute(['token' => $hashedToken]);
    }
}
