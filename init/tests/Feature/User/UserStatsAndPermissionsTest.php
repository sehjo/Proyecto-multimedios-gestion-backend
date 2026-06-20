<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * User stats endpoint and the "everything by permission" model: a custom role
 * with a fine-grained permission can access management routes without being
 * the Administrador role.
 */
class UserStatsAndPermissionsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    /*
    |--------------------------------------------------------------------------
    | Stats by role
    |--------------------------------------------------------------------------
    */

    public function test_stats_by_role_returns_counts(): void
    {
        // Two users with the Medico role.
        User::factory()->create()->assignRole('Medico');
        User::factory()->create()->assignRole('Medico');

        $this->getJson('/api/stats/users/by-role', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Medico', 2);
    }

    public function test_stats_by_role_requires_users_read(): void
    {
        // Enfermero does not have users.read.
        $this->getJson('/api/stats/users/by-role', $this->headersForRole('Enfermero'))
            ->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Everything-by-permission: custom role, not the Administrador role
    |--------------------------------------------------------------------------
    */

    public function test_custom_role_with_users_read_can_list_users(): void
    {
        // A "Supervisor" role that only has users.read — not Administrador.
        $supervisor = Role::create(['name' => 'Supervisor', 'guard_name' => 'web']);
        $supervisor->givePermissionTo('users.read');

        $user = User::factory()->create();
        $user->assignRole('Supervisor');

        $this->getJson('/api/users', $this->authHeaders($user))->assertOk();
    }

    public function test_custom_role_without_users_create_cannot_create_users(): void
    {
        $supervisor = Role::create(['name' => 'Supervisor', 'guard_name' => 'web']);
        $supervisor->givePermissionTo('users.read'); // read only, no create

        $user = User::factory()->create();
        $user->assignRole('Supervisor');

        $this->postJson('/api/users', [], $this->authHeaders($user))->assertStatus(403);
    }

    public function test_custom_role_with_roles_read_can_list_roles(): void
    {
        // Managing roles is now permission-based, not tied to Administrador.
        $auditor = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
        $auditor->givePermissionTo('roles.read');

        $user = User::factory()->create();
        $user->assignRole('Auditor');

        $this->getJson('/api/roles', $this->authHeaders($user))->assertOk();
    }
}
