<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
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

        return redirect()->route('login')->with('status', 'Sesión iniciada correctamente. Tu espacio de trabajo estará disponible en la siguiente pantalla.');
    }

    private function invalidCredentials(LoginRequest $request): RedirectResponse
    {
        return back()
            ->withInput($request->safe()->only(['email', 'remember']))
            ->withErrors(['email' => 'No pudimos iniciar sesión con esos datos. Verifica tu correo y contraseña.']);
    }
}
