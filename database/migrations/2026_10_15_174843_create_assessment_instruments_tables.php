<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('organizational_axis_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('code', 80)->nullable()->unique()->after('id');
            $table->foreignId('assessment_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('display_order')->nullable()->after('is_active');
            $table->index(['assessment_id', 'display_order']);
        });

        // Las pruebas de plataforma son globales: sus preguntas no dependen de una empresa.
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('assessment_id')->nullable()->after('parent_evaluation_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_id');
        });

        if (Schema::hasTable('questions') && ! DB::table('questions')->whereNull('company_id')->exists()) {
            Schema::table('questions', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable(false)->change();
            });
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropConstrainedForeignId('assessment_id');
            $table->dropColumn('display_order');
        });

        Schema::dropIfExists('assessments');
    }
};
