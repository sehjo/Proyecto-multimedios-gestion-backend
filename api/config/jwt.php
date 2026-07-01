<?php

require_once __DIR__ . '/env.php';

/**
 * Minimal HS256 JWT implementation (no external dependencies).
 * Used to issue short-lived guest tokens for the guest appointment flow.
 * The frontend reads the `sub` claim client-side (without verifying the
 * signature) and sends the token back as a Bearer credential, which the
 * backend verifies here.
 */
class Jwt
{
    private static function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function secret(): string
    {
        return Env::get('JWT_SECRET', '');
    }

    /**
     * Signs a set of claims and returns a compact JWT string.
     * `iat` and `exp` are added automatically.
     */
    public static function encode(array $claims, int $ttlSeconds): string
    {
        $header  = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now     = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $segments = [
            self::b64UrlEncode(json_encode($header)),
            self::b64UrlEncode(json_encode($payload)),
        ];
        $signingInput = implode('.', $segments);
        $signature    = hash_hmac('sha256', $signingInput, self::secret(), true);
        $segments[]   = self::b64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Verifies signature and expiry. Returns the claims array on success,
     * or null if the token is malformed, tampered with, or expired.
     */
    public static function decode(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$headB64, $payloadB64, $sigB64] = $parts;

        $expectedSig = hash_hmac('sha256', "$headB64.$payloadB64", self::secret(), true);
        $providedSig = self::b64UrlDecode($sigB64);
        if (!hash_equals($expectedSig, $providedSig)) {
            return null;
        }

        $payload = json_decode(self::b64UrlDecode($payloadB64), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }
}
