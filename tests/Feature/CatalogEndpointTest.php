<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\OrganizationalAxis;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.supabase.url' => 'https://supabase.test',
            'services.supabase.anon_key' => 'anon-test-key',
            'cache.default' => 'file',
            'cache.stores.file.path' => storage_path('framework/cache/catalog-endpoint-test'),
        ]);

        Cache::clear();
    }

    protected function tearDown(): void
    {
        Cache::clear();

        parent::tearDown();
    }

    public function test_catalogs_survive_a_cache_hit_without_corrupting_the_payload(): void
    {
        $company = Company::factory()->create();
        $supabaseId = (string) Str::uuid();
        $user = User::factory()->create([
            'supabase_user_id' => $supabaseId,
            'company_id' => $company->id,
            'role' => UserRole::HumanResources,
        ]);
        OrganizationalAxis::factory()->create(['code' => 'operational', 'name' => 'Operativo', 'is_active' => true]);
        SkillCategory::factory()->create(['code' => 'communication', 'name' => 'Comunicativas', 'is_active' => true]);

        Http::fake(['https://supabase.test/auth/v1/user' => Http::response([
            'id' => $supabaseId,
            'email' => $user->email,
        ])]);

        $first = $this->withToken('valid-supabase-token')->getJson('/api/v1/catalogs');
        $second = $this->withToken('valid-supabase-token')->getJson('/api/v1/catalogs');

        $first->assertOk()->assertJsonPath('data.organizational_axes.0.code', 'operational');

        $second
            ->assertOk()
            ->assertJsonPath('data.organizational_axes.0.code', 'operational')
            ->assertJsonPath('data.skill_categories.0.code', 'communication');

        $this->assertIsArray($second->json('data.organizational_axes'));
        $this->assertIsArray($second->json('data.skill_categories'));
    }
}
