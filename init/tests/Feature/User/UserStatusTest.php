<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * User status (ACTIVE/INACTIVE), anti-lockout and self-protection guards.
 */
class UserStatusTest extends TestCase
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
    | changeStatus
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_deactivate_a_user_and_revoke_sessions(): void
    {
        $target = User::factory()->create();
        $target->assignRole('Medico');
        $target->createToken('active'); // existing session
        $this->assertSame(1, $target->tokens()->count());

        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'INACTIVE'], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('status', 'INACTIVE');

        // The deactivated user's own tokens are revoked (the actor's token is untouched).
        $this->assertSame(0, $target->fresh()->tokens()->count());
    }

    public function test_admin_can_reactivate_a_user(): void
    {
        $target = User::factory()->inactive()->create();
        $target->assignRole('Medico');

        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'ACTIVE'], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('status', 'ACTIVE');
    }

    public function test_change_status_validates_the_value(): void
    {
        $target = User::factory()->create();

        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'BOGUS'], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    /*
    |--------------------------------------------------------------------------
    | Self-protection
    |--------------------------------------------------------------------------
    */

    public function test_admin_cannot_change_their_own_status(): void
    {
        $admin = $this->userWithRole('Administrador');

        $this->patchJson("/api/users/{$admin->id}/status", ['status' => 'INACTIVE'], $this->authHeaders($admin))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'SELF_ACTION_FORBIDDEN']);
    }

    public function test_admin_cannot_change_their_own_roles(): void
    {
        $admin = $this->userWithRole('Administrador');

        $this->postJson("/api/users/{$admin->id}/roles", ['role' => 'Medico'], $this->authHeaders($admin))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'SELF_ACTION_FORBIDDEN']);
    }

    public function test_admin_cannot_edit_their_own_user(): void
    {
        $admin = $this->userWithRole('Administrador');

        $this->putJson("/api/users/{$admin->id}", [
            'name'     => 'Changed',
            'lastname' => $admin->lastname,
            'email'    => $admin->email,
            'role'     => 'Administrador',
        ], $this->authHeaders($admin))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'SELF_ACTION_FORBIDDEN']);

        $this->assertSame($admin->name, $admin->fresh()->name); // unchanged
    }

    public function test_update_does_not_change_the_role(): void
    {
        $target = User::factory()->create();
        $target->assignRole('Medico');

        // Even if a 'role' field is sent, update must ignore it (role is managed
        // only via PUT /users/{id}/role).
        $this->putJson("/api/users/{$target->id}", [
            'name'     => 'NewName',
            'lastname' => $target->lastname,
            'email'    => $target->email,
            'role'     => 'Administrador',
        ], $this->headersForRole('Administrador'))->assertOk();

        $fresh = $target->fresh();
        $this->assertSame('NewName', $fresh->name);          // data updated
        $this->assertTrue($fresh->hasRole('Medico'));         // role unchanged
        $this->assertFalse($fresh->hasRole('Administrador')); // role NOT escalated
    }

    /*
    |--------------------------------------------------------------------------
    | Direction-based permission (deactivate vs. reactivate)
    |--------------------------------------------------------------------------
    */

    /**
     * A role with users.update (but NOT users.delete) can reactivate but not deactivate.
     */
    public function test_reactivator_can_activate_but_not_deactivate(): void
    {
        // Create a custom role that has update but NOT delete.
        $reactivatorRole = \Spatie\Permission\Models\Role::create(['name' => 'Reactivator', 'guard_name' => 'web']);
        $reactivatorRole->givePermissionTo(['users.read', 'users.update']);

        $reactivator = User::factory()->create();
        $reactivator->assignRole('Reactivator');
        $headers = $this->authHeaders($reactivator);

        // Target: inactive user → should be reactivatable.
        $target = User::factory()->inactive()->create();
        $target->assignRole('Medico');

        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'ACTIVE'], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'ACTIVE');

        // Now try to deactivate — should be rejected with 403 FORBIDDEN.
        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'INACTIVE'], $headers)
            ->assertStatus(403)
            ->assertJson(['error_code' => 'FORBIDDEN']);
    }

    /**
     * A role with users.delete (but NOT users.update) can deactivate but not reactivate.
     */
    public function test_deactivator_can_deactivate_but_not_reactivate(): void
    {
        // Create a custom role that has delete but NOT update.
        $deactivatorRole = \Spatie\Permission\Models\Role::create(['name' => 'Deactivator', 'guard_name' => 'web']);
        $deactivatorRole->givePermissionTo(['users.read', 'users.delete']);

        $deactivator = User::factory()->create();
        $deactivator->assignRole('Deactivator');
        $headers = $this->authHeaders($deactivator);

        // Target: active user → should be deactivatable.
        $target = User::factory()->create();
        $target->assignRole('Medico');

        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'INACTIVE'], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'INACTIVE');

        // Now try to reactivate — should be rejected with 403 FORBIDDEN.
        $this->patchJson("/api/users/{$target->id}/status", ['status' => 'ACTIVE'], $headers)
            ->assertStatus(403)
            ->assertJson(['error_code' => 'FORBIDDEN']);
    }

    /*
    |--------------------------------------------------------------------------
    | Anti-lockout (last active administrator)
    |--------------------------------------------------------------------------
    */

    public function test_cannot_deactivate_the_last_active_admin(): void
    {
        // A single active administrator (the test DB has no seeded users).
        $lastAdmin = $this->userWithRole('Administrador');
        $this->assertSame(1, \App\Support\AdminGuard::activeAdminCount());

        // A non-admin user with users.delete tries to deactivate the last admin.
        // Deactivating requires users.delete; the anti-lockout guard fires after
        // the permission check passes.
        $manager = User::factory()->create();
        \Spatie\Permission\Models\Role::create(['name' => 'UserManager', 'guard_name' => 'web'])
            ->givePermissionTo(['users.read', 'users.delete']);
        $manager->assignRole('UserManager');

        $this->patchJson("/api/users/{$lastAdmin->id}/status", ['status' => 'INACTIVE'], $this->authHeaders($manager))
            ->assertStatus(409)
            ->assertJson(['error_code' => 'LAST_ADMIN']);
    }

    /*
    |--------------------------------------------------------------------------
    | Login rejects inactive accounts
    |--------------------------------------------------------------------------
    */

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create([
            'email'    => 'inactive@ccss.cr',
            'password' => bcrypt('Secret123!'),
        ]);

        $this->postJson('/api/login', ['email' => 'inactive@ccss.cr', 'password' => 'Secret123!'])
            ->assertStatus(403)
            ->assertJson(['error_code' => 'INACTIVE_ACCOUNT']);
    }

    public function test_active_user_can_log_in(): void
    {
        User::factory()->create([
            'email'    => 'active@ccss.cr',
            'password' => bcrypt('Secret123!'),
        ]);

        $this->postJson('/api/login', ['email' => 'active@ccss.cr', 'password' => 'Secret123!'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }
}
