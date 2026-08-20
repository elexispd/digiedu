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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Basic Info
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('admission_number')->nullable()->unique();
            $table->string('photo')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();
            $table->string('nationality')->nullable();
            $table->string('religion')->nullable();

            // Academic Info
            $table->foreignUuid('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('term')->nullable(); // 1, 2, 3
            $table->date('admission_date')->nullable();
            $table->string('class_category')->nullable();
            $table->foreignUuid('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('class_arm_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_type')->nullable(); // day, boarding
            $table->string('house')->nullable();
            $table->string('roll_number')->nullable();

            // Status
            $table->string('current_status')->default('draft'); // draft, active, graduated, suspended

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
