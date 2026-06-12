<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Guard usado por la API. Sanctum resuelve el provider 'users', cuyo guard
     * por defecto es 'web', por eso los roles/permisos se crean con guard 'web'.
     */
    private const GUARD = 'web';

    /**
     * Módulos del sistema y las acciones disponibles por módulo.
     * Genera permisos con el formato "<modulo>.<accion>".
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
        // Limpiar la caché de permisos de Spatie antes de sembrar.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Crear todos los permisos.
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$module}.{$action}",
                    'guard_name' => self::GUARD,
                ]);
            }
        }

        // 2. Crear roles y asignar permisos según la matriz aprobada.
        $this->makeRole('Administrador', $this->allPermissions());

        $this->makeRole('Medico', $this->permissionsFor([
            // ver + gestionar (CRUD completo) de los módulos clínicos y catálogos
            'patients'   => ['view', 'create', 'update', 'delete'],
            'diagnoses'  => ['view', 'create', 'update', 'delete'],
            'diseases'   => ['view', 'create', 'update', 'delete'],
            'drugs'      => ['view', 'create', 'update', 'delete'],
            'priorities' => ['view', 'create', 'update', 'delete'],
            'treatments' => ['view', 'create', 'update', 'delete'],
        ]));

        $this->makeRole('Enfermero', $this->permissionsFor([
            // ver pacientes/enfermedades; ver + crear diagnósticos (sin eliminar)
            'patients'  => ['view'],
            'diagnoses' => ['view', 'create'],
            'diseases'  => ['view'],
        ]));

        $this->makeRole('Paciente', $this->permissionsFor([
            // solo lectura de sus datos clínicos
            // TODO(scoping): limitar a "solo lo suyo" cuando exista la relación patient<->user
            'patients'  => ['view'],
            'diagnoses' => ['view'],
            'diseases'  => ['view'],
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Crea (o recupera) un rol y le sincroniza el set de permisos dado.
     *
     * @param  array<int, string>  $permissions
     */
    private function makeRole(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        $role->syncPermissions($permissions);
    }

    /**
     * Devuelve los nombres de TODOS los permisos definidos.
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
     * Construye nombres de permisos a partir de un mapa modulo => [acciones].
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
