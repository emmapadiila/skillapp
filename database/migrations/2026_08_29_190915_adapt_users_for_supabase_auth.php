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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('supabase_user_id')->nullable()->unique()->after('id');
            $table->foreignId('company_id')->nullable()->after('supabase_user_id')->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->after('position_id')->constrained('users')->nullOnDelete();
            $table->string('first_name')->nullable()->after('manager_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('role', 40)->default('collaborator')->after('email');
            $table->date('hire_date')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('hire_date');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();

            $table->index(['company_id', 'role', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['position_id']);
            $table->dropForeign(['manager_id']);
            $table->dropIndex(['company_id', 'role', 'is_active']);
            $table->dropUnique(['supabase_user_id']);
            $table->dropColumn([
                'supabase_user_id', 'company_id', 'position_id', 'manager_id',
                'first_name', 'last_name', 'role', 'hire_date', 'is_active',
                'last_login_at', 'deleted_at',
            ]);
        });
    }
};
