<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * GET /api/auth/has-permission — lets the front-end ask whether the
 * authenticated user holds a given permission.
 */
class HasPermissionTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_returns_true_when_user_has_permission(): void
    {
        $this->getJson('/api/auth/has-permission?permission=patients.read', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJson(['permission' => 'patients.read', 'granted' => true]);
    }

    public function test_returns_false_when_user_lacks_permission(): void
    {
        // A patient does not have users.read.
        $this->getJson('/api/auth/has-permission?permission=users.read', $this->headersForRole('Paciente'))
            ->assertOk()
            ->assertJson(['permission' => 'users.read', 'granted' => false]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/auth/has-permission?permission=patients.read')
            ->assertStatus(401);
    }

    public function test_requires_permission_parameter(): void
    {
        $this->getJson('/api/auth/has-permission', $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('permission');
    }
}
