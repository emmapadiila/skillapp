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
        Schema::create('improvement_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('initial_level', 50);
            $table->string('target_level', 50);
            $table->decimal('progress', 5, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->date('reevaluation_date');
            $table->text('ai_recommendation')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'status']);
        });

        Schema::create('improvement_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('improvement_plan_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('display_order');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['improvement_plan_id', 'display_order']);
        });

        Schema::create('recommended_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('improvement_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('improvement_activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->string('url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->boolean('recommended_by_ai')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommended_resources');
        Schema::dropIfExists('improvement_activities');
        Schema::dropIfExists('improvement_plans');
    }
};
