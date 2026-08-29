<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecoverPasswordRequest;
use App\Http\Requests\Api\V1\RefreshTokenRequest;
use App\Http\Requests\Api\V1\SignInRequest;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly SupabaseAuthService $auth) {}

    public function signIn(SignInRequest $request): JsonResponse
    {
        try {
            $session = $this->auth->signIn($request->string('email')->toString(), $request->string('password')->toString());
        } catch (RequestException) {
            return response()->json(['message' => 'Las credenciales no son válidas.'], 401);
        }

        $identity = $session['user'];
        $user = User::query()->where('email', $identity['email'])->where('is_active', true)->first();

        if ($user === null || ($user->supabase_user_id !== null && $user->supabase_user_id !== $identity['id'])) {
            return response()->json(['message' => 'La cuenta no está habilitada en SoftSkills AI.'], 403);
        }

        $user->forceFill(['supabase_user_id' => $identity['id'], 'last_login_at' => now()])->save();

        return response()->json(['data' => ['session' => $session, 'user' => $user->load('company', 'position')]]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            return response()->json(['data' => $this->auth->refresh($request->string('refresh_token')->toString())]);
        } catch (RequestException) {
            return response()->json(['message' => 'El token de renovación no es válido.'], 401);
        }
    }

    public function recover(RecoverPasswordRequest $request): JsonResponse
    {
        try {
            $this->auth->recover($request->string('email')->toString(), $request->validated('redirect_to'));
        } catch (RequestException) {
            // La respuesta es deliberadamente uniforme para evitar enumeración de cuentas.
        }

        return response()->json(['message' => 'Si la cuenta existe, recibirá instrucciones de recuperación.'], 202);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->load('company', 'position')]);
    }
}
