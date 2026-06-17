<?php

use App\Enums\DayOfWeek;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Institution opening hours. One row per time interval, so a day can
        // have several intervals (e.g. morning and afternoon). A day with no
        // rows means the institution is closed that day.
        Schema::create('institution_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('day_of_week', array_column(DayOfWeek::cases(), 'value'));
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_schedules');
    }
};
