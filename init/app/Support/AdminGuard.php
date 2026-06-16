<?php

namespace App\Support;

use App\Enums\UserStatus;
use App\Models\User;

/**
 * Guards against locking the system out of administrators.
 *
 * "Administrador" is the role that manages users/roles; the system must always
 * keep at least one active one.
 */
class AdminGuard
{
    public const ADMIN_ROLE = 'Administrador';

    /**
     * Number of active administrators, optionally excluding one user.
     */
    public static function activeAdminCount(?int $excludeUserId = null): int
    {
        return User::role(self::ADMIN_ROLE)
            ->where('status', UserStatus::Active->value)
            ->when($excludeUserId !== null, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->count();
    }

    /**
     * Whether the given user is the last active administrator. Removing or
     * deactivating them would leave the system with zero active admins.
     */
    public static function isLastActiveAdmin(User $user): bool
    {
        if (! $user->hasRole(self::ADMIN_ROLE) || $user->status !== UserStatus::Active) {
            return false;
        }

        return self::activeAdminCount(excludeUserId: $user->id) === 0;
    }
}
