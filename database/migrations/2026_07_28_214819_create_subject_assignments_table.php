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
        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignUuid('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            
            $table->timestamps();

            // Prevent assigning the exact same subject to the exact same teacher in the exact same class/session twice
            $table->unique(
                ['user_id', 'subject_id', 'school_class_id', 'academic_session_id'], 
                'subj_assign_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_assignments');
    }
};
