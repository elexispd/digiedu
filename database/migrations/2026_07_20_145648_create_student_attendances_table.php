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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->date('date');
            
            // These might not strictly need constraints if they are soft relations, 
            // but let's add them for data integrity
            $table->foreignUuid('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignUuid('class_arm_id')->constrained('class_arms')->cascadeOnDelete();
            
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->string('remark')->nullable();

            $table->timestamps();

            // Unique constraint: A student can only have one attendance record per day
            $table->unique(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
