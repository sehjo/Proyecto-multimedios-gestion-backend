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
        $role = Role::findByName('Administrador', 'web');

        $this->putJson("/api/roles/{$role->id}", ['name' => 'OtherName'], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJson(['error_code' => 'PROTECTED_ROLE']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Administrador']);
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
    | Assign a role to a user
    |--------------------------------------------------------------------------
    */

    public function test_admin_assigns_role_to_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Paciente');

        $this->putJson("/api/users/{$user->id}/role", [
            'role' => 'Enfermero',
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['Enfermero']);

        // syncRoles replaces: the user ends up with ONLY the new role.
        $this->assertTrue($user->fresh()->hasRole('Enfermero'));
        $this->assertFalse($user->fresh()->hasRole('Paciente'));
    }

    public function test_does_not_assign_nonexistent_role(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}/role", [
            'role' => 'DoesNotExist',
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_non_admin_cannot_assign_role(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}/role", [
            'role' => 'Enfermero',
        ], $this->headersForRole('Enfermero'))
            ->assertStatus(403);
    }
}
