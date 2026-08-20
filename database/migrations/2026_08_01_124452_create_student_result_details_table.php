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
        Schema::create('student_result_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_result_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('result_configuration_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->timestamps();

            // Prevent duplicate scores for the same configuration under one result
            $table->unique(['student_result_id', 'result_configuration_id'], 'stu_res_det_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_result_details');
    }
};
