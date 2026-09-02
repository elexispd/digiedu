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
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->unsignedTinyInteger('term')->nullable()->default(1);
            $table->foreignUuid('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignUuid('class_arm_id')->constrained('class_arms')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('timetable_period_id')->constrained('timetable_periods')->cascadeOnDelete();
            $table->string('day_of_week');
            $table->string('session_type')->default('lecture');
            $table->string('room')->nullable();
            $table->timestamps();

            $table->index(['academic_session_id', 'term', 'day_of_week'], 'idx_tt_session_term_day');
            $table->index(['teacher_id', 'day_of_week', 'timetable_period_id'], 'idx_tt_teacher_day_period');
            $table->index(['class_arm_id', 'day_of_week', 'timetable_period_id'], 'idx_tt_class_day_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
