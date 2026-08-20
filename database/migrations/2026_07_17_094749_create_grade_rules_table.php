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
        Schema::create('grade_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('class_category'); // e.g. Primary, Junior Secondary
            $table->string('grade'); // e.g. A, C, P, F
            $table->integer('min_score');
            $table->integer('max_score');
            $table->string('remark'); // e.g. Distinction, Credit, Fail
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_rules');
    }
};
