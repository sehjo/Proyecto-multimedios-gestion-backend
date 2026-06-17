<?php

namespace Tests\Feature\Availability;

use App\Models\InstitutionSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\InteractsWithAuth;
use Tests\TestCase;

/**
 * HU-038 — Institution weekly schedule (multi-interval per day).
 */
class InstitutionScheduleTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    /** A user (not admin) with only availability permissions, for clean authz tests. */
    private function availabilityManager(array $permissions = ['availability.read', 'availability.update']): array
    {
        $role = Role::firstOrCreate(['name' => 'AvailabilityMgr', 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $user = User::factory()->create();
        $user->assignRole('AvailabilityMgr');

        return $this->authHeaders($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/institution/schedules')->assertStatus(401);
    }

    public function test_requires_permission(): void
    {
        $this->getJson('/api/institution/schedules', $this->headersForRole('Medico'))->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Happy path
    |--------------------------------------------------------------------------
    */

    public function test_admin_sets_multiple_intervals_per_day(): void
    {
        $this->putJson('/api/institution/schedules', [
            'schedules' => [
                ['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day_of_week' => 'MONDAY', 'start_time' => '14:00', 'end_time' => '18:00'],
                ['day_of_week' => 'TUESDAY', 'start_time' => '08:00', 'end_time' => '16:00'],
            ],
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.MONDAY')
            ->assertJsonCount(1, 'data.TUESDAY')
            ->assertJsonCount(0, 'data.SUNDAY');

        $this->assertDatabaseCount('institution_schedules', 3);
    }

    public function test_index_returns_all_days_even_empty(): void
    {
        InstitutionSchedule::create(['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00']);

        $this->getJson('/api/institution/schedules', $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']]);
    }

    public function test_sync_replaces_previous_schedule_atomically(): void
    {
        InstitutionSchedule::create(['day_of_week' => 'FRIDAY', 'start_time' => '08:00', 'end_time' => '12:00']);

        $this->putJson('/api/institution/schedules', [
            'schedules' => [
                ['day_of_week' => 'MONDAY', 'start_time' => '09:00', 'end_time' => '13:00'],
            ],
        ], $this->headersForRole('Administrador'))->assertOk();

        // Old Friday interval gone, only the new Monday remains.
        $this->assertDatabaseCount('institution_schedules', 1);
        $this->assertDatabaseHas('institution_schedules', ['day_of_week' => 'MONDAY']);
        $this->assertDatabaseMissing('institution_schedules', ['day_of_week' => 'FRIDAY']);
    }

    public function test_times_are_returned_as_hh_mm(): void
    {
        $this->putJson('/api/institution/schedules', [
            'schedules' => [['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00']],
        ], $this->headersForRole('Administrador'))
            ->assertOk()
            ->assertJsonPath('data.MONDAY.0.start_time', '08:00')
            ->assertJsonPath('data.MONDAY.0.end_time', '12:00');
    }

    /*
    |--------------------------------------------------------------------------
    | Validation / edge cases (QA)
    |--------------------------------------------------------------------------
    */

    public function test_rejects_invalid_day(): void
    {
        $this->putJson('/api/institution/schedules', [
            'schedules' => [['day_of_week' => 'lunes', 'start_time' => '08:00', 'end_time' => '12:00']],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedules.0.day_of_week');
    }

    public function test_rejects_bad_time_format(): void
    {
        $this->putJson('/api/institution/schedules', [
            'schedules' => [['day_of_week' => 'MONDAY', 'start_time' => '8am', 'end_time' => '25:99']],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.start_time', 'schedules.0.end_time']);
    }

    public function test_rejects_start_after_or_equal_end(): void
    {
        // start == end
        $this->putJson('/api/institution/schedules', [
            'schedules' => [['day_of_week' => 'MONDAY', 'start_time' => '10:00', 'end_time' => '10:00']],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedules.0.end_time');

        // start > end (would cross midnight)
        $this->putJson('/api/institution/schedules', [
            'schedules' => [['day_of_week' => 'MONDAY', 'start_time' => '22:00', 'end_time' => '02:00']],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedules.0.end_time');
    }

    public function test_rejects_overlapping_intervals_same_day(): void
    {
        $this->putJson('/api/institution/schedules', [
            'schedules' => [
                ['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day_of_week' => 'MONDAY', 'start_time' => '11:00', 'end_time' => '13:00'],
            ],
        ], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedules.1.start_time');
    }

    public function test_allows_contiguous_intervals_same_day(): void
    {
        // 12:00-12:00 boundary touch is allowed (no overlap).
        $this->putJson('/api/institution/schedules', [
            'schedules' => [
                ['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day_of_week' => 'MONDAY', 'start_time' => '12:00', 'end_time' => '16:00'],
            ],
        ], $this->headersForRole('Administrador'))->assertOk();
    }

    public function test_empty_schedules_clears_everything(): void
    {
        InstitutionSchedule::create(['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00']);

        $this->putJson('/api/institution/schedules', [
            'schedules' => [],
        ], $this->headersForRole('Administrador'))->assertOk();

        $this->assertDatabaseCount('institution_schedules', 0);
    }

    public function test_missing_schedules_key_is_rejected(): void
    {
        $this->putJson('/api/institution/schedules', [], $this->headersForRole('Administrador'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedules');
    }

    public function test_custom_role_with_availability_permission_can_manage(): void
    {
        $headers = $this->availabilityManager();

        $this->putJson('/api/institution/schedules', [
            'schedules' => [['day_of_week' => 'MONDAY', 'start_time' => '08:00', 'end_time' => '12:00']],
        ], $headers)->assertOk();

        $this->getJson('/api/institution/schedules', $headers)->assertOk();
    }
}
