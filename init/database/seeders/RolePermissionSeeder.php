<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Guard used by the API. Sanctum resolves the 'users' provider, whose
     * default guard is 'web', so roles/permissions are created with guard 'web'.
     */
    private const GUARD = 'web';

    /**
     * System modules and the actions available per module.
     * Generates permissions in the "<module>.<action>" format.
     */
    private const MODULES = [
        'users'      => ['view', 'create', 'update', 'delete'],
        'roles'      => ['view', 'create', 'update', 'delete'],
        'patients'   => ['view', 'create', 'update', 'delete'],
        'diagnoses'  => ['view', 'create', 'update', 'delete'],
        'diseases'   => ['view', 'create', 'update', 'delete'],
        'drugs'      => ['view', 'create', 'update', 'delete'],
        'priorities' => ['view', 'create', 'update', 'delete'],
        'treatments' => ['view', 'create', 'update', 'delete'],
    ];

    public function run(): void
    {
        // Clear Spatie's permission cache before seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create every permission.
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$module}.{$action}",
                    'guard_name' => self::GUARD,
                ]);
            }
        }

        // 2. Create roles and assign permissions per the approved matrix.
        $this->makeRole('Administrador', $this->allPermissions());

        $this->makeRole('Medico', $this->permissionsFor([
            // view + manage (full CRUD) of the clinical modules and catalogs
            'patients'   => ['view', 'create', 'update', 'delete'],
            'diagnoses'  => ['view', 'create', 'update', 'delete'],
            'diseases'   => ['view', 'create', 'update', 'delete'],
            'drugs'      => ['view', 'create', 'update', 'delete'],
            'priorities' => ['view', 'create', 'update', 'delete'],
            'treatments' => ['view', 'create', 'update', 'delete'],
        ]));

        $this->makeRole('Enfermero', $this->permissionsFor([
            // view patients/diseases; view + create diagnoses (no delete)
            'patients'  => ['view'],
            'diagnoses' => ['view', 'create'],
            'diseases'  => ['view'],
        ]));

        $this->makeRole('Paciente', $this->permissionsFor([
            // read-only access to their clinical data
            // TODO(scoping): limit to "their own" once the patient<->user relation exists
            'patients'  => ['view'],
            'diagnoses' => ['view'],
            'diseases'  => ['view'],
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Creates (or fetches) a role and syncs the given set of permissions to it.
     *
     * @param  array<int, string>  $permissions
     */
    private function makeRole(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        $role->syncPermissions($permissions);
    }

    /**
     * Returns the names of ALL defined permissions.
     *
     * @return array<int, string>
     */
    private function allPermissions(): array
    {
        $names = [];
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    /**
     * Builds permission names from a module => [actions] map.
     *
     * @param  array<string, array<int, string>>  $map
     * @return array<int, string>
     */
    private function permissionsFor(array $map): array
    {
        $names = [];
        foreach ($map as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }
}
