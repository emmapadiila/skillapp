<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rejects_requests_without_a_supabase_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_human_resources_cannot_view_an_employee_from_another_company(): void
    {
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test-key');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $supabaseId = (string) Str::uuid();
        $hr = User::factory()->create([
            'supabase_user_id' => $supabaseId,
            'company_id' => $company->id,
            'role' => UserRole::HumanResources,
        ]);
        $foreignEmployee = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => UserRole::Collaborator,
        ]);

        Http::fake(['https://supabase.test/auth/v1/user' => Http::response([
            'id' => $supabaseId,
            'email' => $hr->email,
        ])]);

        $this->withToken('valid-supabase-token')
            ->getJson("/api/v1/employees/{$foreignEmployee->id}")
            ->assertNotFound();
    }
}
