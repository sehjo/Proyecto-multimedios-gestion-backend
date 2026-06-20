<?php

/**
 * Minimal CORS handling for the SPA frontend (token Bearer auth).
 *
 * The frontend runs on a different origin (e.g. http://localhost:5173) so the
 * browser sends a preflight OPTIONS request before POST/PUT/DELETE. This emits
 * the required Access-Control-* headers and short-circuits the preflight.
 *
 * Allowed origins come from config('app.frontend_url'); '*' is used as a dev
 * fallback. Credentials are NOT enabled (auth is via Authorization header, not
 * cookies), which keeps the policy simple and safe.
 */
class Cors
{
    public static function handle(Request $request): void
    {
        $allowedOrigin = self::allowedOrigin($request);

        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        // Preflight: answer and stop here (no routing needed).
        if ($request->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    private static function allowedOrigin(Request $request): string
    {
        $origin = $request->header('origin');
        $frontend = config('app.frontend_url');

        // Echo back the request origin when it matches the configured frontend;
        // otherwise fall back to the configured URL (or '*' as a last resort).
        if ($origin !== null && $frontend !== null && rtrim($origin, '/') === rtrim($frontend, '/')) {
            return $origin;
        }

        return $frontend ?? '*';
    }
}
