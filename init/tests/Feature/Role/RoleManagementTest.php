<?php

namespace Tests\Feature\Role;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * Gestión de roles y permisos (solo Administrador).
 *
 * Cubre: autorización, CRUD de roles, protección de roles base,
 * listado de permisos, validación y asignación de rol a usuario.
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
    | Autorización
    |--------------------------------------------------------------------------
    */

    public function test_no_autenticado_no_accede_a_roles(): void
    {
        $this->getJson('/api/roles')
            ->assertStatus(401)
            ->assertJson(['error_code' => 'UNAUTHENTICATED']);
    }

    public function test_no_admin_no_accede_a_roles(): void
    {
        $this->getJson('/api/roles', $this->headersForRole('Medico'))
            ->assertStatus(403)
            ->assertJson(['error_code' => 'FORBIDDEN']);
    }

    /*
    |--------------------------------------------------------------------------
    | Listado y detalle
    |--------------------------------------------------------------------------
    */

    public function test_admin_lista_roles_con_permisos(): void
    {
        $this->getJson('/api/roles', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonCount(4) // Administrador, Medico, Enfermero, Paciente
            ->assertJsonStructure([['id', 'name', 'guard_name', 'permissions']]);
    }

    public function test_admin_lista_permisos_disponibles(): void
    {
        $this->getJson('/api/roles/permissions', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['patients.view']);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function test_admin_crea_rol_con_permisos(): void
    {
        $response = $this->postJson('/api/roles', [
            'name'        => 'Recepcion',
            'permissions' => ['patients.view', 'priorities.view'],
        ], $this->headersForRole('Administrador'));

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Recepcion')
            ->assertJsonFragment(['patients.view']);

        $this->assertDatabaseHas('roles', ['name' => 'Recepcion', 'guard_name' => 'web']);
        $this->assertTrue(Role::findByName('Recepcion', 'web')->hasPermissionTo('patients.view'));
    }

    public function test_no_crea_rol_con_nombre_duplicado(): void
    {
        $this->postJson('/api/roles', ['name' => 'Medico'], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_no_crea_rol_con_nombre_vacio(): void
    {
        $this->postJson('/api/roles', ['name' => '   '], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_no_crea_rol_con_permiso_inexistente(): void
    {
        $this->postJson('/api/roles', [
            'name'        => 'Otro',
            'permissions' => ['no.existe'],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('permissions.0');
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function test_admin_actualiza_permisos_de_rol(): void
    {
        $role = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);

        $this->putJson("/api/roles/{$role->id}", [
            'permissions' => ['drugs.view', 'drugs.create'],
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonFragment(['drugs.view'])
            ->assertJsonFragment(['drugs.create']);

        $this->assertTrue($role->fresh()->hasPermissionTo('drugs.create'));
    }

    public function test_admin_renombra_rol_no_protegido(): void
    {
        $role = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);

        $this->putJson("/api/roles/{$role->id}", ['name' => 'Renombrado'], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('name', 'Renombrado');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Renombrado']);
    }

    public function test_no_renombra_rol_base(): void
    {
        $role = Role::findByName('Administrador', 'web');

        $this->putJson("/api/roles/{$role->id}", ['name' => 'OtroNombre'], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJson(['error_code' => 'PROTECTED_ROLE']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Administrador']);
    }

    public function test_si_ajusta_permisos_de_rol_base(): void
    {
        $role = Role::findByName('Paciente', 'web');

        $this->putJson("/api/roles/{$role->id}", [
            'permissions' => ['diseases.view'],
        ], $this->headersForRole('Administrador'))
            ->assertOk();

        $this->assertTrue($role->fresh()->hasPermissionTo('diseases.view'));
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function test_admin_elimina_rol_no_protegido(): void
    {
        $role = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);

        $this->deleteJson("/api/roles/{$role->id}", [], $this->headersForRole('Administrador'))
            ->assertNoContent();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_no_elimina_rol_base(): void
    {
        $role = Role::findByName('Medico', 'web');

        $this->deleteJson("/api/roles/{$role->id}", [], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJson(['error_code' => 'PROTECTED_ROLE']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Medico']);
    }

    /*
    |--------------------------------------------------------------------------
    | Asignar rol a usuario
    |--------------------------------------------------------------------------
    */

    public function test_admin_asigna_rol_a_usuario(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Paciente');

        $this->putJson("/api/users/{$user->id}/role", [
            'role' => 'Enfermero',
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['Enfermero']);

        // syncRoles reemplaza: el usuario queda SOLO con el nuevo rol.
        $this->assertTrue($user->fresh()->hasRole('Enfermero'));
        $this->assertFalse($user->fresh()->hasRole('Paciente'));
    }

    public function test_no_asigna_rol_inexistente(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}/role", [
            'role' => 'NoExiste',
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_no_admin_no_asigna_rol(): void
    {
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}/role", [
            'role' => 'Enfermero',
        ], $this->headersForRole('Enfermero'))
            ->assertStatus(403);
    }
}
