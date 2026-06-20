<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-003 — Password recovery via an email link.
 *
 * Covers forgot-password (sending the link, anti-enumeration) and
 * reset-password (valid/invalid/expired token, password update and session
 * revocation).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | forgot-password
    |--------------------------------------------------------------------------
    */

    public function test_forgot_password_sends_email_and_stores_token(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'person@ccss.cr']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'person@ccss.cr'])
            ->assertOk();

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'person@ccss.cr']);
        Mail::assertSent(PasswordResetMail::class);
    }

    public function test_forgot_password_does_not_reveal_if_email_exists(): void
    {
        Mail::fake();

        // Non-existent email: same generic response, no email sent.
        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'missing@ccss.cr']);

        $response->assertOk();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'missing@ccss.cr']);
        Mail::assertNothingSent();
    }

    public function test_forgot_password_requires_email(): void
    {
        $this->postJson('/api/auth/forgot-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /*
    |--------------------------------------------------------------------------
    | reset-password
    |--------------------------------------------------------------------------
    */

    /**
     * Generates a valid reset token for a user (mirrors what forgot-password
     * does: stores the hash of the plain token).
     */
    private function createResetToken(User $user, ?Carbon $createdAt = null): string
    {
        $plain = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => hash('sha256', $plain), 'created_at' => $createdAt ?? now()],
        );

        return $plain;
    }

    public function test_reset_password_updates_the_password(): void
    {
        $user  = User::factory()->create(['password' => Hash::make('OldPass123!')]);
        $token = $this->createResetToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => 'NewPass123!',
        ])->assertOk();

        // The new password is valid and the old one no longer is.
        $this->assertTrue(Hash::check('NewPass123!', $user->fresh()->password));

        // The used token is removed.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_revokes_active_sessions(): void
    {
        $user = User::factory()->create();
        $user->createToken('active-session'); // existing session
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $token = $this->createResetToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => 'NewPass123!',
        ])->assertOk();

        // All previous sessions are invalidated after changing the password.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_password_invalid_token(): void
    {
        $this->postJson('/api/auth/reset-password', [
            'token'    => 'token-that-does-not-exist',
            'password' => 'NewPass123!',
        ])->assertStatus(422);
    }

    public function test_reset_password_expired_token(): void
    {
        $user  = User::factory()->create();
        // Token created 61 minutes ago (expires after 60).
        $token = $this->createResetToken($user, now()->subMinutes(61));

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => 'NewPass123!',
        ])->assertStatus(422);

        // The expired token is cleaned up.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_requires_minimum_password(): void
    {
        $user  = User::factory()->create();
        $token = $this->createResetToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => '123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('password');
    }
}
