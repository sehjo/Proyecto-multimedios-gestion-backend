<?php

namespace App\Support;

/**
 * Single source of truth for the permission catalog.
 *
 * Permissions are DERIVED from MODULES x ACTIONS (never hardcoded one by one),
 * and "linked permissions" (cascade) are applied here: any create/update/delete
 * implies the module's read. This guarantees a role can never end up with a
 * write permission but no read, regardless of where the set comes from
 * (seeder or the role management endpoint).
 */
class PermissionCatalog
{
    /**
     * The guard the roles/permissions are created under. Sanctum resolves the
     * 'users' provider, whose default guard is 'web'.
     */
    public const GUARD = 'web';

    /**
     * Available actions per module. 'read' is the base; the rest cascade to it.
     *
     * @var array<int, string>
     */
    public const ACTIONS = ['read', 'create', 'update', 'delete'];

    /**
     * Actions that imply 'read' on the same module (linked permissions).
     *
     * @var array<int, string>
     */
    private const WRITE_ACTIONS = ['create', 'update', 'delete'];

    /**
     * System modules that get the full set of actions.
     *
     * @var array<int, string>
     */
    public const MODULES = [
        'users',
        'roles',
        'patients',
        'diagnoses',
        'diseases',
        'drugs',
        'priorities',
        'treatments',
        'availability',
    ];

    /**
     * Read-only audit-log permissions. Logs are never created/updated/deleted,
     * so these stand alone (only the `.read` action) and are NOT cascaded.
     *
     * @var array<int, string>
     */
    public const LOG_PERMISSIONS = [
        'logs_users.read',
        'logs_roles.read',
    ];

    /**
     * Returns the names of EVERY permission, derived from MODULES x ACTIONS
     * plus the standalone read-only log permissions.
     *
     * @return array<int, string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return array_merge($names, self::LOG_PERMISSIONS);
    }

    /**
     * Expands a list of permission names applying the cascade: for every
     * "<module>.<create|update|delete>" the matching "<module>.read" is added.
     * The result is deduplicated.
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public static function expand(array $names): array
    {
        $expanded = [];

        foreach ($names as $name) {
            $expanded[$name] = true;

            [$module, $action] = array_pad(explode('.', $name, 2), 2, null);
            if ($action !== null && in_array($action, self::WRITE_ACTIONS, true)) {
                $expanded["{$module}.read"] = true;
            }
        }

        return array_keys($expanded);
    }

    /**
     * Builds permission names from a "module => [actions]" map and runs them
     * through the cascade (expand).
     *
     * @param  array<string, array<int, string>>  $map
     * @return array<int, string>
     */
    public static function forModules(array $map): array
    {
        $names = [];
        foreach ($map as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return self::expand($names);
    }
}
