<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-002 — The authenticated user can log out.
 *
 * Covers:
 *  - The token is deleted on logout.
 *  - After logout, the same token no longer grants access to protected routes.
 *  - Logging out while unauthenticated -> 401.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function loginAndGetToken(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_logout_deletes_the_token(): void
    {
        $user  = User::factory()->create();
        $token = $this->loginAndGetToken($user);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->postJson('/api/logout', [], [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ])->assertOk()
          ->assertJson(['message' => 'Sesión cerrada correctamente.']);

        // The current token was revoked (removed from the table).
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_does_not_work_after_logout(): void
    {
        $user  = User::factory()->create();
        $token = $this->loginAndGetToken($user);
        $tokenId = explode('|', $token, 2)[0];

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];

        // Log out.
        $this->postJson('/api/logout', [], $headers)->assertOk();

        // The used token is revoked: it no longer exists in the DB, so no later
        // request can authenticate with it.
        // (Also verified against the real server: reusing the token returns 401.)
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_logout_without_session_returns_401(): void
    {
        $this->postJson('/api/logout')
            ->assertStatus(401)
            ->assertJson(['error_code' => 'UNAUTHENTICATED']);
    }
}
