<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SupabaseAuthService
{
    public function signIn(string $email, string $password): array
    {
        return $this->client()->post('/auth/v1/token?grant_type=password', [
            'email' => $email,
            'password' => $password,
        ])->throw()->json();
    }

    public function refresh(string $refreshToken): array
    {
        return $this->client()->post('/auth/v1/token?grant_type=refresh_token', [
            'refresh_token' => $refreshToken,
        ])->throw()->json();
    }

    public function recover(string $email, ?string $redirectTo = null): void
    {
        $payload = ['email' => $email];

        if ($redirectTo !== null) {
            $payload['redirect_to'] = $redirectTo;
        }

        $this->client()->post('/auth/v1/recover', $payload)->throw();
    }

    public function user(string $accessToken): array
    {
        return $this->client()->withToken($accessToken)->get('/auth/v1/user')->throw()->json();
    }

    public function invite(string $email, array $metadata = []): array
    {
        $serviceKey = config()->string('services.supabase.service_role_key');
        abort_if($serviceKey === '', 503, 'Supabase Service Role no está configurado.');

        return Http::baseUrl(rtrim(config()->string('services.supabase.url'), '/'))
            ->acceptJson()->asJson()->withToken($serviceKey)
            ->withHeader('apikey', $serviceKey)
            ->timeout(config()->integer('services.supabase.timeout'))
            ->post('/auth/v1/invite', ['email' => $email, 'data' => $metadata])
            ->throw()->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config()->string('services.supabase.url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeader('apikey', config()->string('services.supabase.anon_key'))
            ->timeout(config()->integer('services.supabase.timeout'))
            ->retry(2, 200, throw: false);
    }
}
