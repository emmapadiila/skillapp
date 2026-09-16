<?php

namespace Tests\Feature\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders_the_secure_sign_in_form(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Transforma el talento')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_valid_supabase_credentials_start_an_encrypted_web_session(): void
    {
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test-key');
        $supabaseUserId = (string) Str::uuid();
        $user = User::factory()->create([
            'email' => 'persona@empresa.com',
            'supabase_user_id' => null,
        ]);
        Http::fake([
            'https://supabase.test/auth/v1/token?grant_type=password' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'token_type' => 'bearer',
                'user' => ['id' => $supabaseUserId, 'email' => $user->email],
            ]),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'PERSONA@EMPRESA.COM',
            'password' => 'secret-password',
            'remember' => true,
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('supabase.access_token', 'access-token')
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($supabaseUserId, $user->refresh()->supabase_user_id);
        $this->assertNotNull($user->last_login_at);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://supabase.test/auth/v1/token?grant_type=password'
            && $request['email'] === 'persona@empresa.com');
    }

    public function test_invalid_credentials_return_a_generic_error_without_flashing_the_password(): void
    {
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test-key');
        Http::fake([
            'https://supabase.test/auth/v1/token?grant_type=password' => Http::response([], 400),
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'persona@empresa.com',
            'password' => 'incorrect-password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'No pudimos iniciar sesión con esos datos. Verifica tu correo y contraseña.'])
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_inactive_local_account_cannot_start_a_session(): void
    {
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test-key');
        $user = User::factory()->create([
            'email' => 'inactivo@empresa.com',
            'is_active' => false,
        ]);
        Http::fake([
            'https://supabase.test/auth/v1/token?grant_type=password' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'token_type' => 'bearer',
                'user' => ['id' => (string) Str::uuid(), 'email' => $user->email],
            ]),
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'No pudimos iniciar sesión con esos datos. Verifica tu correo y contraseña.']);
        $this->assertGuest();
        $this->assertNull($user->refresh()->last_login_at);
    }

    public function test_invalid_form_data_is_rejected_before_contacting_supabase(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'correo-no-valido',
            'password' => '',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Ingresa un correo electrónico válido.',
                'password' => 'Ingresa tu contraseña.',
            ]);
        $this->assertGuest();
        Http::assertNothingSent();
    }
}
