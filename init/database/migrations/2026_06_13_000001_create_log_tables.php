<?php

use App\Enums\RoleLogAction;
use App\Enums\UserLogAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users audit log.
        Schema::create('log_users', function (Blueprint $table) {
            $table->id();
            // Who performed the action (nullable for system/self actions).
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete();
            // Who the action was applied to (nullable if the user was deleted).
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', array_column(UserLogAction::cases(), 'value'));
            $table->json('changes')->nullable();
            $table->timestamp('timestamp');
        });

        // Roles audit log.
        Schema::create('log_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete();
            // Roles live in Spatie's `roles` table; kept nullable (role may be deleted).
            $table->unsignedBigInteger('target_role_id')->nullable();
            $table->enum('action', array_column(RoleLogAction::cases(), 'value'));
            $table->json('changes')->nullable();
            $table->timestamp('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_roles');
        Schema::dropIfExists('log_users');
    }
};
