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
        Schema::create('student_term_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->foreignUuid('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_arm_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('average_score', 8, 2)->nullable();
            
            $table->integer('class_arm_position')->nullable();
            $table->integer('overall_class_position')->nullable();
            
            $table->string('teacher_remark')->nullable();
            $table->string('principal_remark')->nullable();
            $table->string('status')->default('draft'); // draft, published

            $table->timestamps();

            // Prevent duplicate term results for the same student
            $table->unique(['academic_session_id', 'term', 'student_id'], 'stu_term_res_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_term_results');
    }
};
