<?php

namespace App\Http\Controllers\Api;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\SyncSchedulesRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\InstitutionSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstitutionScheduleController extends Controller
{
    /**
     * Get the weekly schedule.
     *
     * Returns the institution's opening intervals grouped by day of week. A day
     * with an empty array is closed. Requires the `availability.read` permission.
     */
    public function index(): JsonResponse
    {
        $schedules = InstitutionSchedule::orderBy('start_time')->get()
            ->groupBy(fn (InstitutionSchedule $s) => $s->day_of_week->value);

        // Always return every day (empty = closed) for a stable UI shape.
        $data = [];
        foreach (DayOfWeek::cases() as $day) {
            $data[$day->value] = ScheduleResource::collection($schedules->get($day->value, collect()));
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Replace the whole weekly schedule (atomic).
     *
     * Replaces all intervals with the provided list in a single transaction, so
     * the schedule never ends up in a half-updated state. Existing appointments
     * are NOT affected (HU-038.3). Requires the `availability.update` permission.
     */
    public function sync(SyncSchedulesRequest $request): JsonResponse
    {
        $schedules = $request->validated()['schedules'];

        DB::transaction(function () use ($schedules) {
            InstitutionSchedule::query()->delete();
            foreach ($schedules as $row) {
                InstitutionSchedule::create([
                    'day_of_week' => $row['day_of_week'],
                    'start_time'  => $row['start_time'],
                    'end_time'    => $row['end_time'],
                ]);
            }
        });

        return $this->index();
    }
}
