<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InstitutionSchedule
 */
class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'day_of_week' => $this->day_of_week->value,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
        ];
    }
}
