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
        Schema::table('assessments', function (Blueprint $table) {
            $table->text('instructions')->nullable()->after('description');
            $table->text('axis_description')->nullable()->after('instructions');
            $table->json('indicators')->nullable()->after('axis_description');
        });

        // El peso de cada habilidad depende de la prueba (eje), no del cargo.
        Schema::create('assessment_skill', function (Blueprint $table) {
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->decimal('weight', 4, 2)->default(1);
            $table->timestamps();
            $table->primary(['assessment_id', 'skill_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('consent_accepted_at')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('consent_accepted_at');
        });

        Schema::dropIfExists('assessment_skill');

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['instructions', 'axis_description', 'indicators']);
        });
    }
};
