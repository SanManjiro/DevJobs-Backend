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
    Schema::create('job_listings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();
    $table->string('title', 150);
    $table->text('description');
    $table->string('location', 100)->nullable();
    $table->enum('type', ['full_time', 'part_time', 'freelance', 'stage']);
    $table->enum('remote', ['on_site', 'remote', 'hybrid'])->default('on_site');
    $table->unsignedInteger('salary_min')->nullable();
    $table->unsignedInteger('salary_max')->nullable();
    $table->enum('experience_level', ['junior', 'intermediaire', 'senior']);
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
