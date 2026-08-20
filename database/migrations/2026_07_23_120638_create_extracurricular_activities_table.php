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
        Schema::create('extracurricular_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category');
            $table->foreignUuid('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('schedule_days');
            $table->string('venue');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracurricular_activities');
    }
};
