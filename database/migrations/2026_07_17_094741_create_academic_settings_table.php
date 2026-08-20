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
        Schema::create('academic_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->integer('terms_per_year')->default(3);
            $table->integer('pass_threshold')->default(50);
            $table->string('grading_standard')->default('Nigerian Standard'); // 'Nigerian Standard', 'International', 'Custom System'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_settings');
    }
};
