<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Services\AuthenticationService;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $result = $this->authentication->attempt(
                $request->string('email')->lower()->toString(),
                $request->string('password')->toString(),
            );
        } catch (RequestException) {
            return $this->invalidCredentials($request);
        } catch (ConnectionException) {
            return back()
                ->withInput($request->safe()->only(['email', 'remember']))
                ->withErrors(['email' => 'El servicio de autenticación no está disponible. Inténtalo nuevamente.']);
        }

        if ($result === null) {
            return $this->invalidCredentials($request);
        }

        Auth::login($result['user'], $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('supabase', Arr::only($result['session'], [
            'access_token',
            'refresh_token',
            'expires_at',
            'expires_in',
            'token_type',
        ]));

        return redirect()->route($result['user']->role === UserRole::Collaborator ? 'personal.workspace' : 'workspace');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function refresh(Request $request, SupabaseAuthService $supabase): JsonResponse
    {
        $refreshToken = $request->session()->get('supabase.refresh_token');
        if (! is_string($refreshToken) || ! $request->user()->is_active) {
            return response()->json(['message' => 'Vuelve a iniciar sesión.'], 401);
        }

        try {
            $session = $supabase->refresh($refreshToken);
        } catch (RequestException) {
            return response()->json(['message' => 'La sesión venció. Vuelve a iniciar sesión.'], 401);
        } catch (ConnectionException) {
            return response()->json(['message' => 'No fue posible renovar la sesión. Inténtalo nuevamente.'], 503);
        }

        $request->session()->put('supabase', Arr::only($session, [
            'access_token', 'refresh_token', 'expires_at', 'expires_in', 'token_type',
        ]));

        return response()->json(['data' => ['access_token' => $session['access_token']]]);
    }

    private function invalidCredentials(LoginRequest $request): RedirectResponse
    {
        return back()
            ->withInput($request->safe()->only(['email', 'remember']))
            ->withErrors(['email' => 'No pudimos iniciar sesión con esos datos. Verifica tu correo y contraseña.']);
    }
}
