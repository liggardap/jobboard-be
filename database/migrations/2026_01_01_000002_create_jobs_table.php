<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category', 100);
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship']);
            $table->string('location_city', 100)->nullable();
            $table->string('location_country', 100)->default('ID');
            $table->boolean('is_remote')->default(false);
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'idx_company_status');
            $table->index(['status', 'published_at'], 'idx_status_published');
            $table->index(['category', 'status'], 'idx_category_status');
            $table->index(['location_country', 'location_city', 'status'], 'idx_location_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
