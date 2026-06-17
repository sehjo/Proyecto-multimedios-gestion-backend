<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * GET /api/users filters (name/role/status) and pagination (per_page).
 */
class UserIndexFilterTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_filters_by_name_lastname_or_email(): void
    {
        $admin = $this->userWithRole('Administrador');
        User::factory()->create(['name' => 'Laura', 'lastname' => 'Soto', 'email' => 'laura@ccss.cr'])->assignRole('Medico');
        User::factory()->create(['name' => 'Andres', 'lastname' => 'Mora', 'email' => 'andres@ccss.cr'])->assignRole('Medico');

        // Matches by name.
        $this->getJson('/api/users?name=Laura', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonFragment(['email' => 'laura@ccss.cr'])
            ->assertJsonMissing(['email' => 'andres@ccss.cr']);

        // Matches by email fragment.
        $this->getJson('/api/users?name=andres@', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonFragment(['email' => 'andres@ccss.cr']);
    }

    public function test_filters_by_role(): void
    {
        $admin = $this->userWithRole('Administrador');
        User::factory()->create()->assignRole('Medico');
        User::factory()->create()->assignRole('Enfermero');

        $response = $this->getJson('/api/users?role=Enfermero', $this->authHeaders($admin))->assertOk();
        $roles = collect($response->json('data'))->pluck('roles')->flatten();

        $this->assertTrue($roles->contains('Enfermero'));
        $this->assertFalse($roles->contains('Medico'));
    }

    public function test_filters_by_status(): void
    {
        $admin = $this->userWithRole('Administrador');
        User::factory()->create()->assignRole('Medico');
        User::factory()->inactive()->create()->assignRole('Medico');

        $statuses = collect(
            $this->getJson('/api/users?status=INACTIVE', $this->authHeaders($admin))->assertOk()->json('data')
        )->pluck('status')->unique();

        $this->assertSame(['INACTIVE'], $statuses->values()->all());
    }

    public function test_respects_per_page(): void
    {
        $admin = $this->userWithRole('Administrador');
        User::factory()->count(20)->create()->each(fn ($u) => $u->assignRole('Medico'));

        $this->getJson('/api/users?per_page=5', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_rejects_invalid_filters(): void
    {
        $admin = $this->userWithRole('Administrador');

        $this->getJson('/api/users?role=DoesNotExist', $this->authHeaders($admin))
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->getJson('/api/users?status=BOGUS', $this->authHeaders($admin))
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->getJson('/api/users?per_page=999', $this->authHeaders($admin))
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_no_filters_returns_all_paginated(): void
    {
        $admin = $this->userWithRole('Administrador');
        User::factory()->count(3)->create()->each(fn ($u) => $u->assignRole('Medico'));

        $this->getJson('/api/users', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }
}
