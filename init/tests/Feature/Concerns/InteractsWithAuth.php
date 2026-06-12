<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Helpers para los tests que necesitan usuarios autenticados con un rol.
 * Siembra los roles/permisos de Spatie y emite tokens Sanctum.
 */
trait InteractsWithAuth
{
    /**
     * Siembra los roles y permisos de la aplicación (RolePermissionSeeder).
     * Llamar en setUp() o al inicio del test antes de crear usuarios con rol.
     */
    protected function seedRolesAndPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Crea un usuario con el rol indicado.
     */
    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Devuelve los headers de autenticación (Bearer token Sanctum) para un usuario.
     *
     * @return array<string, string>
     */
    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Atajo: crea un usuario con rol y devuelve sus headers autenticados.
     *
     * @return array<string, string>
     */
    protected function headersForRole(string $role): array
    {
        return $this->authHeaders($this->userWithRole($role));
    }
}
