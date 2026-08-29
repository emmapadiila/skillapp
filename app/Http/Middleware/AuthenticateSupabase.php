<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SupabaseAuthService;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSupabase
{
    public function __construct(private readonly SupabaseAuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return $this->unauthorized();
        }

        try {
            $identity = $this->auth->user($token);
        } catch (RequestException) {
            return $this->unauthorized();
        } catch (ConnectionException) {
            return response()->json(['message' => 'El servicio de autenticación no está disponible.'], 503);
        }

        $user = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identity): void {
                $query->where('supabase_user_id', $identity['id'])
                    ->orWhere(function ($query) use ($identity): void {
                        $query->whereNull('supabase_user_id')->where('email', $identity['email']);
                    });
            })
            ->first();

        if ($user === null) {
            return response()->json(['message' => 'La cuenta no está habilitada en SoftSkills AI.'], 403);
        }

        if ($user->supabase_user_id === null) {
            $user->forceFill(['supabase_user_id' => $identity['id']])->save();
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        Auth::setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Token de acceso inválido o vencido.'], 401);
    }
}
