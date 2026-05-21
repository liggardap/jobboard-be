<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('ID');
            $table->string('website')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index('user_id', 'idx_user_id');
            $table->index(['industry', 'country'], 'idx_industry_country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
