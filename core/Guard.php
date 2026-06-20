<?php

/**
 * Authorization helpers for controllers.
 *
 * The Router has no middleware layer, so each protected action calls these at
 * the top. They emit the proper JSON error (401/403) and return null/false so
 * the caller can `return` early.
 */
class Guard
{
    /**
     * Resolves the authenticated user or emits 401 and returns null.
     * Usage:  if (! $user = Guard::user($request)) return;
     */
    public static function user(Request $request): ?User
    {
        $user = Auth::user($request);

        if (!$user) {
            Response::unauthenticated();

            return null;
        }

        return $user;
    }

    /**
     * Resolves the authenticated user AND checks a permission.
     * Emits 401 if unauthenticated, 403 if the permission is missing, then
     * returns null. Returns the User when authorized.
     *
     * Usage:  if (! $user = Guard::permission($request, 'users.read')) return;
     */
    public static function permission(Request $request, string $permission): ?User
    {
        $user = self::user($request);

        if (!$user) {
            return null;
        }

        if (!Auth::can($user, $permission)) {
            Response::forbidden();

            return null;
        }

        return $user;
    }
}
