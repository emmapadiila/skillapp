<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecoverPasswordRequest;
use App\Http\Requests\Api\V1\RefreshTokenRequest;
use App\Http\Requests\Api\V1\SignInRequest;
use App\Services\AuthenticationService;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly SupabaseAuthService $auth,
        private readonly AuthenticationService $authentication,
    ) {}

    public function signIn(SignInRequest $request): JsonResponse
    {
        try {
            $result = $this->authentication->attempt(
                $request->string('email')->lower()->toString(),
                $request->string('password')->toString(),
            );
        } catch (RequestException) {
            return response()->json(['message' => 'Las credenciales no son válidas.'], 401);
        }

        if ($result === null) {
            return response()->json(['message' => 'La cuenta no está habilitada en SoftSkills AI.'], 403);
        }

        return response()->json(['data' => [
            'session' => $result['session'],
            'user' => $result['user']->load('company', 'position'),
        ]]);
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
