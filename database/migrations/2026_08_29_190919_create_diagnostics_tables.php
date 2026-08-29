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
        Schema::create('organizational_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('total_evaluated');
            $table->jsonb('snapshot');
            $table->jsonb('ai_recommendations')->nullable();
            $table->timestamp('closed_at');
            $table->timestamps();
            $table->unique(['company_id', 'period_start', 'period_end']);
        });

        Schema::create('diagnostic_matrix_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_diagnostic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organizational_axis_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_category_id')->constrained()->restrictOnDelete();
            $table->decimal('average_score', 8, 2);
            $table->string('level', 50);
            $table->unsignedInteger('evaluated_count');
            $table->timestamps();
            $table->unique(['organizational_diagnostic_id', 'organizational_axis_id', 'skill_category_id']);
        });

        Schema::create('organizational_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizational_diagnostic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('organizational_axis_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('priority', 30);
            $table->unsignedInteger('affected_count');
            $table->decimal('score_gap', 8, 2);
            $table->text('ai_recommendation')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamps();
            $table->index(['company_id', 'priority', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizational_gaps');
        Schema::dropIfExists('diagnostic_matrix_cells');
        Schema::dropIfExists('organizational_diagnostics');
    }
};
