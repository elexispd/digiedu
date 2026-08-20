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
        Schema::create('student_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->string('term'); // App\Enums\TermEnum values
            $table->foreignUuid('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_arm_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('total_score', 8, 2)->nullable();
            $table->string('grade')->nullable();
            $table->string('remark')->nullable();
            $table->integer('subject_position')->nullable();
            $table->string('status')->default('draft'); // draft, published
            
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate records for the same student, subject, and term
            $table->unique(['academic_session_id', 'term', 'student_id', 'subject_id'], 'stu_res_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
