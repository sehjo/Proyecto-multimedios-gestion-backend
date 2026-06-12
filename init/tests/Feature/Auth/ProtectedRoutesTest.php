<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * HU-004 — All internal pages are inaccessible without an active session.
 *
 * Covers:
 *  - Criterion 1: protected route without a session -> 401.
 *  - Criterion 2: public routes accessible without authentication.
 *  - Criterion 3: invalid/expired token -> 401.
 *  - Role/permission authorization (approved matrix).
 */
class ProtectedRoutesTest extends TestCase
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
    | Criterion 1 — Internal routes require a session (401 without token)
    |--------------------------------------------------------------------------
    */

    /** @dataProvider protectedRoutesProvider */
    public function test_protected_route_without_token_returns_401(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'error_code' => 'UNAUTHENTICATED']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            'list users'       => ['GET', '/api/users'],
            'list patients'    => ['GET', '/api/patients'],
            'list diagnoses'   => ['GET', '/api/diagnoses'],
            'list diseases'    => ['GET', '/api/diseases'],
            'list drugs'       => ['GET', '/api/drugs'],
            'list priorities'  => ['GET', '/api/priorities'],
            'current user'     => ['GET', '/api/user'],
            'logout'           => ['POST', '/api/logout'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Criterion 2 — Public routes accessible without authentication (not 401)
    |--------------------------------------------------------------------------
    */

    public function test_login_is_public(): void
    {
        // Without a valid body it returns 422 (validation), never 401.
        $this->postJson('/api/login')->assertStatus(422);
    }

    public function test_forgot_password_is_public(): void
    {
        $this->postJson('/api/auth/forgot-password')->assertStatus(422);
    }

    public function test_reset_password_is_public(): void
    {
        $this->postJson('/api/auth/reset-password')->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Criterion 3 — Invalid token -> 401
    |--------------------------------------------------------------------------
    */

    public function test_invalid_token_returns_401(): void
    {
        $this->getJson('/api/users', ['Authorization' => 'Bearer invalid-token-xyz'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'error_code' => 'UNAUTHENTICATED']);
    }

    /*
    |--------------------------------------------------------------------------
    | Role authorization — Administrador
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_access_users(): void
    {
        $this->getJson('/api/users', $this->headersForRole('Administrador'))
            ->assertOk();
    }

    public function test_admin_can_access_patients(): void
    {
        $this->getJson('/api/patients', $this->headersForRole('Administrador'))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Role authorization — Enfermero (per the approved matrix)
    |--------------------------------------------------------------------------
    */

    public function test_nurse_cannot_access_users(): void
    {
        $this->getJson('/api/users', $this->headersForRole('Enfermero'))
            ->assertStatus(403)
            ->assertJson(['success' => false, 'error_code' => 'FORBIDDEN']);
    }

    public function test_nurse_can_view_patients(): void
    {
        $this->getJson('/api/patients', $this->headersForRole('Enfermero'))
            ->assertOk();
    }

    public function test_nurse_cannot_create_patients(): void
    {
        // Lacks patients.create -> 403 before validating the body.
        $this->postJson('/api/patients', [], $this->headersForRole('Enfermero'))
            ->assertStatus(403);
    }

    public function test_nurse_can_create_diagnoses(): void
    {
        // Has diagnoses.create -> passes authorization; fails validation (422), not 403.
        $this->postJson('/api/diagnoses', [], $this->headersForRole('Enfermero'))
            ->assertStatus(422);
    }

    public function test_nurse_cannot_delete_diagnoses(): void
    {
        $headers = $this->headersForRole('Enfermero');

        // Without diagnoses.delete -> 403 (authorization runs before model binding).
        $this->deleteJson('/api/diagnoses/1', [], $headers)
            ->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Role authorization — Medico
    |--------------------------------------------------------------------------
    */

    public function test_doctor_can_create_patients(): void
    {
        // Has patients.create -> passes authorization; fails validation (422), not 403.
        $this->postJson('/api/patients', [], $this->headersForRole('Medico'))
            ->assertStatus(422);
    }

    public function test_doctor_cannot_access_users(): void
    {
        $this->getJson('/api/users', $this->headersForRole('Medico'))
            ->assertStatus(403);
    }
}
