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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_evaluation_id')->nullable()->constrained('evaluations')->nullOnDelete();
            $table->string('type', 30);
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('question_count')->default(0);
            $table->timestamp('assigned_at');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'due_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('display_order');
            $table->jsonb('adaptive_metadata')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_id', 'question_id']);
            $table->unique(['evaluation_id', 'display_order']);
        });

        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('likert_value')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();
            $table->unique(['evaluation_id', 'evaluation_question_id']);
        });

        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_score', 8, 2);
            $table->string('level', 50);
            $table->text('ai_analysis')->nullable();
            $table->jsonb('strengths')->nullable();
            $table->jsonb('opportunities')->nullable();
            $table->decimal('model_confidence', 5, 4)->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('immutable_at');
            $table->timestamps();
        });

        Schema::create('skill_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 8, 2);
            $table->string('level', 50);
            $table->jsonb('strengths')->nullable();
            $table->jsonb('opportunities')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_result_id', 'skill_id']);
        });

        Schema::create('category_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_category_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 8, 2);
            $table->string('level', 50);
            $table->jsonb('strengths')->nullable();
            $table->jsonb('opportunities')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_result_id', 'skill_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_results');
        Schema::dropIfExists('skill_results');
        Schema::dropIfExists('evaluation_results');
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluations');
    }
};
