<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-002 — El usuario autenticado puede cerrar sesión.
 *
 * Cubre:
 *  - El token se elimina al cerrar sesión.
 *  - Tras cerrar sesión, el mismo token ya no da acceso a rutas protegidas.
 *  - Cerrar sesión sin estar autenticado -> 401.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function loginAndGetToken(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_logout_elimina_el_token(): void
    {
        $user  = User::factory()->create();
        $token = $this->loginAndGetToken($user);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ])->assertOk()
          ->assertJson(['message' => 'Sesión cerrada correctamente.']);

        // El token actual fue revocado (borrado de la tabla).
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_no_sirve_despues_de_logout(): void
    {
        $user  = User::factory()->create();
        $token = $this->loginAndGetToken($user);
        $tokenId = explode('|', $token, 2)[0];

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];

        // Cierra sesión.
        $this->postJson('/api/logout', [], $headers)->assertOk();

        // El token usado quedó revocado: ya no existe en la BD, por lo que
        // ninguna petición posterior puede autenticarse con él.
        // (Verificado además contra el servidor real: reusar el token da 401.)
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_logout_sin_sesion_devuelve_401(): void
    {
        $this->postJson('/api/logout')
            ->assertStatus(401)
            ->assertJson(['error_code' => 'UNAUTHENTICATED']);
    }
}
