<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * HU-004 — Todas las páginas internas son inaccesibles sin sesión activa.
 *
 * Cubre:
 *  - Criterio 1: ruta protegida sin sesión -> 401.
 *  - Criterio 2: rutas públicas accesibles sin autenticación.
 *  - Criterio 3: token inválido/expirado -> 401.
 *  - Autorización por rol/permiso (matriz aprobada).
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
    | Criterio 1 — Rutas internas requieren sesión (401 sin token)
    |--------------------------------------------------------------------------
    */

    /** @dataProvider protectedRoutesProvider */
    public function test_ruta_protegida_sin_token_devuelve_401(string $method, string $uri): void
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
            'listar usuarios'     => ['GET', '/api/users'],
            'listar pacientes'    => ['GET', '/api/patients'],
            'listar diagnósticos' => ['GET', '/api/diagnoses'],
            'listar enfermedades' => ['GET', '/api/diseases'],
            'listar fármacos'     => ['GET', '/api/drugs'],
            'listar prioridades'  => ['GET', '/api/priorities'],
            'usuario actual'      => ['GET', '/api/user'],
            'cerrar sesión'       => ['POST', '/api/logout'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Criterio 2 — Rutas públicas accesibles sin autenticación (no 401)
    |--------------------------------------------------------------------------
    */

    public function test_login_es_publico(): void
    {
        // Sin body válido responde 422 (validación), nunca 401.
        $this->postJson('/api/login')->assertStatus(422);
    }

    public function test_forgot_password_es_publico(): void
    {
        $this->postJson('/api/auth/forgot-password')->assertStatus(422);
    }

    public function test_reset_password_es_publico(): void
    {
        $this->postJson('/api/auth/reset-password')->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Criterio 3 — Token inválido -> 401
    |--------------------------------------------------------------------------
    */

    public function test_token_invalido_devuelve_401(): void
    {
        $this->getJson('/api/users', ['Authorization' => 'Bearer token-invalido-xyz'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'error_code' => 'UNAUTHENTICATED']);
    }

    /*
    |--------------------------------------------------------------------------
    | Autorización por rol — Administrador
    |--------------------------------------------------------------------------
    */

    public function test_administrador_accede_a_usuarios(): void
    {
        $this->getJson('/api/users', $this->headersForRole('Administrador'))
            ->assertOk();
    }

    public function test_administrador_accede_a_pacientes(): void
    {
        $this->getJson('/api/patients', $this->headersForRole('Administrador'))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Autorización por rol — Enfermero (según la matriz aprobada)
    |--------------------------------------------------------------------------
    */

    public function test_enfermero_no_accede_a_usuarios(): void
    {
        $this->getJson('/api/users', $this->headersForRole('Enfermero'))
            ->assertStatus(403)
            ->assertJson(['success' => false, 'error_code' => 'FORBIDDEN']);
    }

    public function test_enfermero_puede_ver_pacientes(): void
    {
        $this->getJson('/api/patients', $this->headersForRole('Enfermero'))
            ->assertOk();
    }

    public function test_enfermero_no_puede_crear_pacientes(): void
    {
        // No tiene patients.create -> 403 antes de validar el body.
        $this->postJson('/api/patients', [], $this->headersForRole('Enfermero'))
            ->assertStatus(403);
    }

    public function test_enfermero_puede_crear_diagnosticos(): void
    {
        // Tiene diagnoses.create -> pasa autorización; falla validación (422), no 403.
        $this->postJson('/api/diagnoses', [], $this->headersForRole('Enfermero'))
            ->assertStatus(422);
    }

    public function test_enfermero_no_puede_eliminar_diagnosticos(): void
    {
        $headers = $this->headersForRole('Enfermero');

        // Sin diagnoses.delete -> 403 (la autorización corre antes del binding del modelo).
        $this->deleteJson('/api/diagnoses/1', [], $headers)
            ->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Autorización por rol — Médico
    |--------------------------------------------------------------------------
    */

    public function test_medico_puede_crear_pacientes(): void
    {
        // Tiene patients.create -> pasa autorización; falla validación (422), no 403.
        $this->postJson('/api/patients', [], $this->headersForRole('Medico'))
            ->assertStatus(422);
    }

    public function test_medico_no_accede_a_usuarios(): void
    {
        $this->getJson('/api/users', $this->headersForRole('Medico'))
            ->assertStatus(403);
    }
}
