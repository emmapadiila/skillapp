<?php

namespace App\Services;

use App\Models\User;

class AuthenticationService
{
    public function __construct(private readonly SupabaseAuthService $supabase) {}

    /**
     * @return array{session: array<string, mixed>, user: User}|null
     */
    public function attempt(string $email, string $password): ?array
    {
        $session = $this->supabase->signIn($email, $password);
        $identity = $session['user'] ?? null;

        if (! is_array($identity) || ! is_string($identity['id'] ?? null) || ! is_string($identity['email'] ?? null)) {
            return null;
        }

        $user = User::query()
            ->where('email', $identity['email'])
            ->where('is_active', true)
            ->first();

        if ($user === null || ($user->supabase_user_id !== null && $user->supabase_user_id !== $identity['id'])) {
            return null;
        }

        $user->forceFill([
            'supabase_user_id' => $identity['id'],
            'last_login_at' => now(),
        ])->save();

        return ['session' => $session, 'user' => $user];
    }
}
