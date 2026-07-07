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
    Schema::create('saved_jobs', function (Blueprint $table) {
    $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
    $table->timestamp('created_at')->useCurrent();
    $table->unique(['developer_id', 'job_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
    }
};
