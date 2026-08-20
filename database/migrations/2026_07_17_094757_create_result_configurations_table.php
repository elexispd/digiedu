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
        Schema::create('result_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('class_category');
            $table->string('assessment_type'); // e.g. First CA, Second CA, Exam
            $table->integer('percentage'); // e.g. 20, 60
            $table->boolean('is_exam')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_configurations');
    }
};
