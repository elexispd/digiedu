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
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('category');
            $table->enum('urgency_level', ['low', 'medium', 'high', 'critical']);
            $table->json('audience_target');
            $table->dateTime('scheduled_date')->nullable();
            $table->text('message_content');
            $table->unsignedInteger('total_views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
