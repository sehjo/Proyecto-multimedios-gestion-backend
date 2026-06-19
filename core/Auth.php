<?php

class Auth
{
    public static function attempt(string $email, string $password): ?array
    {
        $user = UserRepository::findByEmail($email);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return null;
        }

        return ['user' => $user, 'token' => self::issueToken($user)];
    }

    public static function issueToken(User $user): string
    {
        $plain = bin2hex(random_bytes(40));
        $hashed = hash('sha256', $plain);

        TokenRepository::create($user->getId(), 'User', $hashed, 'api-token');

        return $plain;
    }

    public static function user(Request $request): ?User
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        $record = TokenRepository::findByHash(hash('sha256', $token));

        if (!$record) {
            return null;
        }

        if ($record['expires_at'] !== null && strtotime($record['expires_at']) < time()) {
            return null;
        }

        TokenRepository::touchLastUsed((int) $record['id']);

        return UserRepository::findById((int) $record['tokenable_id']);
    }

    public static function logout(Request $request): bool
    {
        $token = $request->bearerToken();

        if (!$token) {
            return false;
        }

        return TokenRepository::deleteByHash(hash('sha256', $token));
    }

    public static function revokeAllTokens(int $userId): void
    {
        TokenRepository::deleteAllForUser($userId);
    }
}
