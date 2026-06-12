<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Helpers for tests that need authenticated users with a role.
 * Seeds Spatie's roles/permissions and issues Sanctum tokens.
 */
trait InteractsWithAuth
{
    /**
     * Seeds the application's roles and permissions (RolePermissionSeeder).
     * Call it in setUp() or at the start of the test before creating users with a role.
     */
    protected function seedRolesAndPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Creates a user with the given role.
     */
    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Returns the authentication headers (Sanctum Bearer token) for a user.
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
     * Shortcut: creates a user with a role and returns its authenticated headers.
     *
     * @return array<string, string>
     */
    protected function headersForRole(string $role): array
    {
        return $this->authHeaders($this->userWithRole($role));
    }
}
