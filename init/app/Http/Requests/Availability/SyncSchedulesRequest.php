<?php

namespace App\Http\Requests\Availability;

use App\Enums\DayOfWeek;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Replaces the institution's whole weekly schedule with the given intervals.
 * Each interval is { day_of_week, start_time, end_time }. A day with no
 * intervals means it is closed.
 */
class SyncSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'schedules'                 => ['present', 'array', 'max:50'],
            'schedules.*.day_of_week'   => ['required', 'string', Rule::enum(DayOfWeek::class)],
            // @example 08:00
            'schedules.*.start_time'    => ['required', 'date_format:H:i'],
            // @example 12:00
            'schedules.*.end_time'      => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * Cross-field rules: start < end (no midnight crossing) and no overlapping
     * intervals within the same day.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $schedules = $this->input('schedules', []);
            if (! is_array($schedules)) {
                return;
            }

            // 1) start < end per interval.
            foreach ($schedules as $i => $row) {
                $start = $row['start_time'] ?? null;
                $end   = $row['end_time'] ?? null;
                if ($start !== null && $end !== null && $start >= $end) {
                    $v->errors()->add("schedules.{$i}.end_time", 'La hora de fin debe ser mayor que la de inicio.');
                }
            }

            // 2) No overlap within the same day (string HH:MM compares lexically
            //    in chronological order; contiguous intervals like 12:00-12:00 ok).
            $byDay = [];
            foreach ($schedules as $i => $row) {
                $day = $row['day_of_week'] ?? null;
                if ($day === null) {
                    continue;
                }
                $byDay[$day][] = ['i' => $i, 'start' => $row['start_time'] ?? '', 'end' => $row['end_time'] ?? ''];
            }
            foreach ($byDay as $day => $intervals) {
                usort($intervals, fn ($a, $b) => $a['start'] <=> $b['start']);
                for ($k = 1; $k < count($intervals); $k++) {
                    if ($intervals[$k]['start'] < $intervals[$k - 1]['end']) {
                        $v->errors()->add(
                            "schedules.{$intervals[$k]['i']}.start_time",
                            'Los intervalos del mismo día no pueden solaparse.'
                        );
                    }
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'schedules.present'             => 'Debes enviar la lista de horarios.',
            'schedules.array'               => 'Los horarios deben enviarse como una lista.',
            'schedules.max'                 => 'Demasiados intervalos.',
            'schedules.*.day_of_week.required' => 'El día es obligatorio.',
            'schedules.*.day_of_week.enum'  => 'El día indicado no es válido.',
            'schedules.*.start_time.required' => 'La hora de inicio es obligatoria.',
            'schedules.*.start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'schedules.*.end_time.required' => 'La hora de fin es obligatoria.',
            'schedules.*.end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
        ];
    }
}
