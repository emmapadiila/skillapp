<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackendSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_domain_schema_can_be_migrated(): void
    {
        foreach ([
            'companies', 'areas', 'positions', 'skills', 'questions', 'question_options',
            'evaluations', 'evaluation_responses', 'evaluation_results', 'skill_results',
            'improvement_plans', 'organizational_diagnostics', 'organizational_gaps', 'notifications',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('users', ['supabase_user_id', 'company_id', 'role', 'is_active']));
    }
}
