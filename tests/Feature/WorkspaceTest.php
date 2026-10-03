<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_workspace_requires_a_web_session(): void
    {
        $this->get(route('workspace'))->assertRedirect(route('login'));
        $this->get(route('collaborators.index'))->assertRedirect(route('login'));
    }

    public function test_collaborator_sees_personal_navigation_and_cannot_open_admin_screens(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['supabase.access_token' => 'test-access'])
            ->get(route('personal.workspace'))
            ->assertOk()->assertViewIs('workspace')
            ->assertSee('Mi evaluación')->assertSee('Mi plan')
            ->assertDontSee('Banco de preguntas')->assertDontSee('data-screen-link="empresas"', false)
            ->assertSee('content="test-access"', false);
        $this->get(route('collaborators.index'))->assertForbidden();
        $this->get(route('web.empresas'))->assertForbidden();
    }

    public function test_human_resources_can_open_all_management_screens_except_companies(): void
    {
        $user = User::factory()->create(['role' => UserRole::HumanResources]);

        $this->actingAs($user);
        foreach (['workspace', 'collaborators.index', 'diagnostics.organizational', 'web.evaluaciones', 'web.habilidades', 'web.preguntas', 'web.cargos', 'web.mapa-calor', 'web.brechas', 'web.recomendaciones', 'web.planes', 'web.seguimiento', 'web.reportes', 'web.areas'] as $route) {
            $this->get(route($route))->assertOk()->assertViewIs('workspace');
        }
        $this->get(route('web.empresas'))->assertForbidden();
    }

    public function test_superadmin_can_manage_companies_and_user_names_are_escaped(): void
    {
        $user = User::factory()->create(['role' => UserRole::Superadmin, 'name' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get(route('web.empresas'))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_logout_invalidates_the_session_and_supabase_tokens(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['supabase' => ['access_token' => 'test', 'refresh_token' => 'refresh']])
            ->post(route('logout'))->assertRedirect(route('login'))->assertSessionMissing('supabase');
        $this->assertGuest();
    }

    public function test_expired_access_tokens_can_be_renewed_using_the_server_session(): void
    {
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test');
        $user = User::factory()->create();
        Http::preventStrayRequests();
        Http::fake(['https://supabase.test/auth/v1/token?grant_type=refresh_token' => Http::response([
            'access_token' => 'renewed-access', 'refresh_token' => 'renewed-refresh', 'expires_in' => 3600,
        ])]);

        $this->actingAs($user)->withSession(['supabase.refresh_token' => 'initial-refresh'])
            ->postJson(route('session.refresh'))->assertOk()->assertJsonPath('data.access_token', 'renewed-access')
            ->assertSessionHas('supabase.refresh_token', 'renewed-refresh');
        Http::assertSent(fn ($request): bool => $request['refresh_token'] === 'initial-refresh');
    }

    public function test_refresh_without_a_token_or_for_an_inactive_account_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Http::preventStrayRequests();

        $this->actingAs($user)->withSession(['supabase.refresh_token' => 'refresh'])
            ->postJson(route('session.refresh'))->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_supabase_refresh_rejection_preserves_a_useful_session_error(): void
    {
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test');
        $user = User::factory()->create();
        Http::preventStrayRequests();
        Http::fake(['https://supabase.test/auth/v1/token?grant_type=refresh_token' => Http::response([], 400)]);

        $this->actingAs($user)->withSession(['supabase.refresh_token' => 'expired-refresh'])
            ->postJson(route('session.refresh'))->assertUnauthorized()
            ->assertJsonPath('message', 'La sesión venció. Vuelve a iniciar sesión.');
        Http::assertSentCount(2);
    }
}
