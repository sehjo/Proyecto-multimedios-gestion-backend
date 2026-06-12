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
 * HU-003 — Recuperación de contraseña por enlace al correo.
 *
 * Cubre forgot-password (envío de enlace, anti-enumeración) y reset-password
 * (token válido/ inválido/ expirado, actualización de contraseña y revocación
 * de sesiones).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | forgot-password
    |--------------------------------------------------------------------------
    */

    public function test_forgot_password_envia_correo_y_guarda_token(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'persona@ccss.cr']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'persona@ccss.cr'])
            ->assertOk();

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'persona@ccss.cr']);
        Mail::assertSent(PasswordResetMail::class);
    }

    public function test_forgot_password_no_revela_si_el_correo_existe(): void
    {
        Mail::fake();

        // Correo inexistente: misma respuesta genérica, sin enviar correo.
        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'noexiste@ccss.cr']);

        $response->assertOk();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'noexiste@ccss.cr']);
        Mail::assertNothingSent();
    }

    public function test_forgot_password_requiere_email(): void
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
     * Genera un token de reset válido para un usuario (replica lo que hace
     * forgot-password: guarda el hash del token plano).
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

    public function test_reset_password_actualiza_la_contrasena(): void
    {
        $user  = User::factory()->create(['password' => Hash::make('ViejaPass123!')]);
        $token = $this->createResetToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => 'NuevaPass123!',
        ])->assertOk();

        // La nueva contraseña es válida y la vieja ya no.
        $this->assertTrue(Hash::check('NuevaPass123!', $user->fresh()->password));

        // El token usado se elimina.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_revoca_las_sesiones_activas(): void
    {
        $user = User::factory()->create();
        $user->createToken('sesion-activa'); // sesión existente
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $token = $this->createResetToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => 'NuevaPass123!',
        ])->assertOk();

        // Todas las sesiones previas se invalidan tras cambiar la contraseña.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_password_token_invalido(): void
    {
        $this->postJson('/api/auth/reset-password', [
            'token'    => 'token-que-no-existe',
            'password' => 'NuevaPass123!',
        ])->assertStatus(422);
    }

    public function test_reset_password_token_expirado(): void
    {
        $user  = User::factory()->create();
        // Token creado hace 61 minutos (expira a los 60).
        $token = $this->createResetToken($user, now()->subMinutes(61));

        $this->postJson('/api/auth/reset-password', [
            'token'    => $token,
            'password' => 'NuevaPass123!',
        ])->assertStatus(422);

        // El token expirado se limpia.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_requiere_password_minimo(): void
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
