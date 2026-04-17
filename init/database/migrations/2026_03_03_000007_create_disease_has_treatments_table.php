<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disease_has_treatments', function (Blueprint $table) {
            $table->id();
            $table->string('descriptions');
            $table->unsignedBigInteger('disease_id')->nullable();
            $table->foreign('disease_id')->references('id')->on('disease');
            $table->unsignedBigInteger('drugs')->nullable();
            $table->foreign('drugs')->references('id')->on('drugs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_has_treatments');
    }
};
