<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Autenticación sin Supabase (SUPABASE_AUTH_ENABLED=false): valida la contraseña con el hash local
 * y emite tokens cifrados con APP_KEY, con la misma forma que la sesión de Supabase.
 */
class LocalTokenService
{
    private const ACCESS_TTL_SECONDS = 3600;

    private const REFRESH_TTL_SECONDS = 604800;

    public function enabled(): bool
    {
        return ! config()->boolean('services.supabase.enabled');
    }

    public function attempt(string $email, string $password): ?User
    {
        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        if ($user === null || ! is_string($user->password) || ! $this->passwordMatches($password, $user->password)) {
            return null;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, expires_at: int, token_type: string}
     */
    public function issue(User $user): array
    {
        return [
            'access_token' => $this->token($user, 'access', self::ACCESS_TTL_SECONDS),
            'refresh_token' => $this->token($user, 'refresh', self::REFRESH_TTL_SECONDS),
            'expires_in' => self::ACCESS_TTL_SECONDS,
            'expires_at' => now()->addSeconds(self::ACCESS_TTL_SECONDS)->getTimestamp(),
            'token_type' => 'bearer',
        ];
    }

    /**
     * Devuelve el usuario activo dueño del token, o null si el token es inválido, de otro tipo o venció.
     */
    public function user(string $token, string $type = 'access'): ?User
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (($payload['type'] ?? null) !== $type || ($payload['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        return User::query()->whereKey($payload['user_id'] ?? null)->where('is_active', true)->first();
    }

    private function passwordMatches(string $password, string $hash): bool
    {
        try {
            return Hash::check($password, $hash);
        } catch (RuntimeException) {
            // Contraseña guardada sin hash bcrypt (p. ej. cuentas creadas para Supabase): no se acepta.
            return false;
        }
    }

    private function token(User $user, string $type, int $ttl): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'type' => $type,
            'expires_at' => now()->addSeconds($ttl)->getTimestamp(),
        ], JSON_THROW_ON_ERROR));
    }
}
