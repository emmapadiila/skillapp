<?php

use App\Enums\UserRole;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Web\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:web')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::middleware('auth')->group(function (): void {
        // Página de entrada: el admin llega al tablero estratégico (vista general) y el colaborador a su espacio.
        Route::get('/dashboard', fn (Request $request) => redirect()->route(
            $request->user()->role === UserRole::Collaborator ? 'personal.workspace' : 'web.tablero-estrategico'
        ))->name('workspace');
        Route::view('/mi-espacio', 'workspace', ['screen' => 'inicio'])->name('personal.workspace');
        Route::view('/notificaciones', 'workspace', ['screen' => 'notificaciones'])->name('web.notifications');
        Route::view('/mi-perfil', 'workspace', ['screen' => 'perfil'])->name('web.profile');

        Route::middleware('role:superadmin,human_resources')->group(function (): void {
            Route::view('/diagnostico-organizacional', 'workspace', ['screen' => 'diagnostico'])->name('diagnostics.organizational');
            Route::view('/colaboradores', 'workspace', ['screen' => 'colaboradores'])->name('collaborators.index');
            foreach (['tablero-operativo', 'tablero-misional', 'tablero-estrategico', 'evaluaciones', 'habilidades', 'preguntas', 'cargos', 'brechas', 'recomendaciones', 'planes', 'seguimiento', 'reportes', 'areas'] as $screen) {
                Route::view('/'.$screen, 'workspace', ['screen' => $screen])->name('web.'.$screen);
            }
            Route::view('/empresas', 'workspace', ['screen' => 'empresas'])->middleware('role:superadmin')->name('web.empresas');
        });

        foreach (['evaluacion', 'resultados', 'plan', 'progreso'] as $screen) {
            Route::view('/mi-'.$screen, 'workspace', ['screen' => $screen])->name('personal.'.$screen);
        }
    });
});

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:auth')
    ->name('login.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/session/refresh', [AuthenticatedSessionController::class, 'refresh'])->middleware(['auth', 'throttle:auth'])->name('session.refresh');
