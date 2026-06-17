<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class InstitutionSchedule extends Model
{
    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
        ];
    }

    /**
     * Always expose times as HH:MM (DB stores HH:MM:SS).
     */
    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : substr($value, 0, 5),
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : substr($value, 0, 5),
        );
    }
}
