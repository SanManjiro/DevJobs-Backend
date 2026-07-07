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
    Schema::create('applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
    $table->text('cover_letter')->nullable();
    $table->enum('status', ['pending', 'viewed', 'accepted', 'rejected'])
          ->default('pending');
    $table->timestamps();
    $table->unique(['developer_id', 'job_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
