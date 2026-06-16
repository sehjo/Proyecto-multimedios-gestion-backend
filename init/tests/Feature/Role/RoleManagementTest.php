<?php

namespace Tests\Feature\Role;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * Role and permission management (Administrador only).
 *
 * Covers: authorization, role CRUD, base-role protection, permission listing,
 * validation and assigning a role to a user.
 */
class RoleManagementTest extends TestCase
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
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_cannot_access_roles(): void
    {
        $this->getJson('/api/roles')
            ->assertStatus(401)
            ->assertJson(['error_code' => 'UNAUTHENTICATED']);
    }

    public function test_non_admin_cannot_access_roles(): void
    {
        $this->getJson('/api/roles', $this->headersForRole('Medico'))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'FORBIDDEN']);
    }

    /*
    |--------------------------------------------------------------------------
    | Listing and detail
    |--------------------------------------------------------------------------
    */

    public function test_admin_lists_roles_with_permissions(): void
    {
        $this->getJson('/api/roles', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonCount(4) // Administrador, Medico, Enfermero, Paciente
            ->assertJsonStructure([['id', 'name', 'guard_name', 'permissions']]);
    }

    public function test_admin_lists_available_permissions(): void
    {
        $this->getJson('/api/roles/permissions', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['patients.read']);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function test_admin_creates_role_with_permissions(): void
    {
        $response = $this->postJson('/api/roles', [
            'name'        => 'Recepcion',
            'permissions' => ['patients.read', 'priorities.read'],
        ], $this->headersForRole('Administrador'));

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Recepcion')
            ->assertJsonFragment(['patients.read']);

        $this->assertDatabaseHas('roles', ['name' => 'Recepcion', 'guard_name' => 'web']);
        $this->assertTrue(Role::findByName('Recepcion', 'web')->hasPermissionTo('patients.read'));
    }

    public function test_does_not_create_role_with_duplicate_name(): void
    {
        $this->postJson('/api/roles', ['name' => 'Medico'], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_does_not_create_role_with_blank_name(): void
    {
        $this->postJson('/api/roles', ['name' => '   '], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_does_not_create_role_with_nonexistent_permission(): void
    {
        $this->postJson('/api/roles', [
            'name'        => 'Other',
            'permissions' => ['does.not.exist'],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('permissions.0');
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function test_admin_updates_role_permissions(): void
    {
        $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

        $this->putJson("/api/roles/{$role->id}", [
            'permissions' => ['drugs.read', 'drugs.create'],
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonFragment(['drugs.read'])
            ->assertJsonFragment(['drugs.create']);

        $this->assertTrue($role->fresh()->hasPermissionTo('drugs.create'));
    }

    public function test_admin_renames_non_protected_role(): void
    {
        $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

        $this->putJson("/api/roles/{$role->id}", ['name' => 'Renamed'], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('name', 'Renamed');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Renamed']);
    }

    public function test_does_not_rename_base_role(): void
    {
        // Use a base role the acting admin does NOT hold (Paciente), so the
        // self-protection guard doesn't fire first and we test PROTECTED_ROLE.
        $role = Role::findByName('Paciente', 'web');

        $this->putJson("/api/roles/{$role->id}", ['name' => 'OtherName'], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJson(['error_code' => 'PROTECTED_ROLE']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Paciente']);
    }

    public function test_cannot_edit_permissions_of_own_role(): void
    {
        // The acting admin holds 'Administrador'; editing that role's permissions
        // is blocked to prevent self-lockout.
        $role = Role::findByName('Administrador', 'web');

        $this->putJson("/api/roles/{$role->id}", [
            'permissions' => ['users.read'], // would strip the rest from the admin
        ], $this->headersForRole('Administrador'))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'SELF_ACTION_FORBIDDEN']);

        // Permissions were not changed.
        $this->assertTrue($role->fresh()->hasPermissionTo('users.delete'));
    }

    public function test_can_adjust_permissions_of_base_role(): void
    {
        $role = Role::findByName('Paciente', 'web');

        $this->putJson("/api/roles/{$role->id}", [
            'permissions' => ['diseases.read'],
        ], $this->headersForRole('Administrador'))
            ->assertOk();

        $this->assertTrue($role->fresh()->hasPermissionTo('diseases.read'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function test_admin_deletes_non_protected_role(): void
    {
        $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

        $this->deleteJson("/api/roles/{$role->id}", [], $this->headersForRole('Administrador'))
            ->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_does_not_delete_base_role(): void
    {
        $role = Role::findByName('Medico', 'web');

        $this->deleteJson("/api/roles/{$role->id}", [], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJson(['error_code' => 'PROTECTED_ROLE']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Medico']);
    }

    /*
    |--------------------------------------------------------------------------
    | Manage a user's roles (add / remove — a user can hold several)
    |--------------------------------------------------------------------------
    */

    public function test_admin_adds_a_role_without_removing_others(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');

        $this->postJson("/api/users/{$user->id}/roles", [
            'role' => 'Enfermero',
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['Enfermero']);

        // The user now holds BOTH roles.
        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasRole('Medico'));
        $this->assertTrue($fresh->hasRole('Enfermero'));
    }

    public function test_adding_a_role_the_user_already_has_is_idempotent(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');

        $this->postJson("/api/users/{$user->id}/roles", [
            'role' => 'Medico',
        ], $this->headersForRole('Administrador'))->assertOk();

        $this->assertCount(1, $user->fresh()->getRoleNames());
    }

    public function test_does_not_add_nonexistent_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');

        $this->postJson("/api/users/{$user->id}/roles", [
            'role' => 'DoesNotExist',
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_admin_removes_a_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');
        $user->assignRole('Enfermero');
        $enfermero = Role::findByName('Enfermero', 'web');

        $this->deleteJson("/api/users/{$user->id}/roles/{$enfermero->id}", [], $this->headersForRole('Administrador'))
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasRole('Medico'));
        $this->assertFalse($fresh->hasRole('Enfermero'));
    }

    public function test_cannot_remove_the_last_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');
        $medico = Role::findByName('Medico', 'web');

        $this->deleteJson("/api/users/{$user->id}/roles/{$medico->id}", [], $this->headersForRole('Administrador'))
            ->assertStatus(409)
            ->assertJson(['error_code' => 'LAST_ROLE']);

        $this->assertTrue($user->fresh()->hasRole('Medico'));
    }

    public function test_non_admin_cannot_add_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Paciente');

        $this->postJson("/api/users/{$user->id}/roles", [
            'role' => 'Enfermero',
        ], $this->headersForRole('Enfermero'))
            ->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Sync a user's roles (atomic replace)
    |--------------------------------------------------------------------------
    */

    public function test_sync_replaces_the_whole_role_set_atomically(): void
    {
        // The reproducible case from the report: Enfermero -> Administrador
        // would fail with DELETE+POST (passes through 0 roles). PUT sync works.
        $user = User::factory()->create();
        $user->assignRole('Enfermero');

        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => ['Administrador'],
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['Administrador']);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasRole('Administrador'));
        $this->assertFalse($fresh->hasRole('Enfermero'));
    }

    public function test_sync_can_set_multiple_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Paciente');

        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => ['Medico', 'Enfermero'],
        ], $this->headersForRole('Administrador'))->assertOk();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasRole('Medico'));
        $this->assertTrue($fresh->hasRole('Enfermero'));
        $this->assertFalse($fresh->hasRole('Paciente'));
    }

    public function test_sync_rejects_empty_role_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');

        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => [],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('roles');
    }

    public function test_sync_rejects_nonexistent_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Medico');

        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => ['DoesNotExist'],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('roles.0');
    }

    public function test_sync_blocks_dropping_admin_from_last_active_admin(): void
    {
        // A single active admin; a non-admin manager with users.update tries to
        // sync that admin's roles to a set without Administrador.
        $lastAdmin = $this->userWithRole('Administrador');
        $this->assertSame(1, \App\Support\AdminGuard::activeAdminCount());

        $manager = User::factory()->create();
        Role::create(['name' => 'UserManager', 'guard_name' => 'web'])
            ->givePermissionTo(['users.read', 'users.update']);
        $manager->assignRole('UserManager');

        $this->putJson("/api/users/{$lastAdmin->id}/roles", [
            'roles' => ['Medico'],
        ], $this->authHeaders($manager))
            ->assertStatus(409)
            ->assertJson(['error_code' => 'LAST_ADMIN']);

        $this->assertTrue($lastAdmin->fresh()->hasRole('Administrador'));
    }

    public function test_sync_blocks_changing_own_roles(): void
    {
        $admin = $this->userWithRole('Administrador');

        $this->putJson("/api/users/{$admin->id}/roles", [
            'roles' => ['Medico'],
        ], $this->authHeaders($admin))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'SELF_ACTION_FORBIDDEN']);
    }
}
