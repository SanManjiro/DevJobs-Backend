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
    Schema::create('job_skill', function (Blueprint $table) {
    $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
    $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
    $table->boolean('required')->default(true);
    $table->unique(['job_listing_id', 'skill_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_skill');
    }
};
