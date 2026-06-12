<?php

namespace Tests\Feature\Role;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * Linked permissions (cascade): create/update/delete imply read on the same
 * module. Verified both at the catalog level and through the role API.
 */
class PermissionCascadeTest extends TestCase
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
    | Catalog level
    |--------------------------------------------------------------------------
    */

    public function test_expand_adds_read_for_write_actions(): void
    {
        $expanded = PermissionCatalog::expand(['patients.create', 'diagnoses.delete']);

        $this->assertContains('patients.read', $expanded);
        $this->assertContains('diagnoses.read', $expanded);
        // The original write permissions are kept.
        $this->assertContains('patients.create', $expanded);
        $this->assertContains('diagnoses.delete', $expanded);
    }

    public function test_expand_does_not_add_write_for_read(): void
    {
        $expanded = PermissionCatalog::expand(['patients.read']);

        $this->assertSame(['patients.read'], $expanded);
    }

    public function test_expand_deduplicates(): void
    {
        $expanded = PermissionCatalog::expand(['patients.read', 'patients.create']);

        $this->assertCount(2, $expanded);
        $this->assertEqualsCanonicalizing(['patients.read', 'patients.create'], $expanded);
    }

    /*
    |--------------------------------------------------------------------------
    | API level (create / update a role)
    |--------------------------------------------------------------------------
    */

    public function test_creating_role_with_create_also_grants_read(): void
    {
        $this->postJson('/api/roles', [
            'name'        => 'Recepcion',
            'permissions' => ['patients.create'], // read NOT sent on purpose
        ], $this->headersForRole('Administrador'))->assertStatus(201);

        $role = Role::findByName('Recepcion', 'web');

        $this->assertTrue($role->hasPermissionTo('patients.create'));
        $this->assertTrue($role->hasPermissionTo('patients.read')); // cascaded
    }

    public function test_updating_role_permissions_cascades_read(): void
    {
        $role = Role::create(['name' => 'Temporary', 'guard_name' => 'web']);

        $this->putJson("/api/roles/{$role->id}", [
            'permissions' => ['drugs.update'], // read NOT sent on purpose
        ], $this->headersForRole('Administrador'))->assertOk();

        $this->assertTrue($role->fresh()->hasPermissionTo('drugs.update'));
        $this->assertTrue($role->fresh()->hasPermissionTo('drugs.read')); // cascaded
    }

    /*
    |--------------------------------------------------------------------------
    | Seeder level
    |--------------------------------------------------------------------------
    */

    public function test_seeded_nurse_has_diagnoses_read_via_cascade(): void
    {
        // Enfermero is seeded with diagnoses read+create; read must be present.
        $nurse = Role::findByName('Enfermero', 'web');

        $this->assertTrue($nurse->hasPermissionTo('diagnoses.create'));
        $this->assertTrue($nurse->hasPermissionTo('diagnoses.read'));
    }
}
