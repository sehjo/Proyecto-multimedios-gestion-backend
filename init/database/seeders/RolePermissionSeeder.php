<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie's permission cache before seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create every permission, derived from the catalog (no hardcoding).
        foreach (PermissionCatalog::allPermissionNames() as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => PermissionCatalog::GUARD,
            ]);
        }

        // 2. Define roles by derivation/filtering, never by manual lists.
        //    The cascade (create/update/delete imply read) is applied by the catalog.

        // Administrador: every permission.
        $this->makeRole('Administrador', PermissionCatalog::allPermissionNames());

        // Medico: everything except user/role management and audit logs.
        $medical = array_values(array_filter(
            PermissionCatalog::allPermissionNames(),
            fn (string $name) => ! str_starts_with($name, 'users.')
                && ! str_starts_with($name, 'roles.')
                && ! str_starts_with($name, 'logs_'),
        ));
        $this->makeRole('Medico', $medical);

        // Enfermero: view patients/diseases; view + create diagnoses.
        // The cascade guarantees diagnoses.read alongside diagnoses.create.
        $this->makeRole('Enfermero', PermissionCatalog::forModules([
            'patients'  => ['read'],
            'diagnoses' => ['read', 'create'],
            'diseases'  => ['read'],
        ]));

        // Paciente: read-only on their clinical data.
        // TODO(scoping): limit to "their own" once the patient<->user relation exists.
        $this->makeRole('Paciente', PermissionCatalog::forModules([
            'patients'  => ['read'],
            'diagnoses' => ['read'],
            'diseases'  => ['read'],
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
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => PermissionCatalog::GUARD]);
        $role->syncPermissions($permissions);
    }
}
