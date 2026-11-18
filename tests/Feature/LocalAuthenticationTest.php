<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LocalTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.supabase.enabled', false);
        Http::preventStrayRequests();
    }

    public function test_a_user_signs_in_with_the_local_password_and_uses_the_api(): void
    {
        $user = User::factory()->create(['email' => 'operario@skillapp.test', 'password' => Hash::make('secreta-123')]);

        $this->post(route('login.store'), ['email' => 'operario@skillapp.test', 'password' => 'secreta-123'])
            ->assertRedirect(route('personal.workspace'));

        $this->assertAuthenticatedAs($user);
        $token = session('supabase.access_token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'operario@skillapp.test', 'password' => Hash::make('secreta-123')]);

        $this->post(route('login.store'), ['email' => 'operario@skillapp.test', 'password' => 'otra'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_tampered_expired_or_refresh_tokens_cannot_call_the_api(): void
    {
        $user = User::factory()->create();
        $tokens = app(LocalTokenService::class);
        $session = $tokens->issue($user);

        $this->withToken($session['access_token'].'x')->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken($session['refresh_token'])->getJson('/api/v1/me')->assertUnauthorized();

        $this->travel(2)->hours();
        $this->withToken($session['access_token'])->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_a_password_stored_without_hash_is_rejected_without_an_error(): void
    {
        User::factory()->create(['email' => 'operario@skillapp.test', 'password' => 'texto-plano']);

        $this->post(route('login.store'), ['email' => 'operario@skillapp.test', 'password' => 'texto-plano'])
            ->assertSessionHasErrors('email');
    }

    public function test_an_inactive_user_loses_api_access(): void
    {
        $user = User::factory()->create();
        $session = app(LocalTokenService::class)->issue($user);
        $user->update(['is_active' => false]);

        $this->withToken($session['access_token'])->getJson('/api/v1/me')->assertUnauthorized();
    }
}
