<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'shortlisted', 'rejected'])->default('pending');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['job_id', 'user_id'], 'uq_job_user');
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['job_id', 'status'], 'idx_job_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
