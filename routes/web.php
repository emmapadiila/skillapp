<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Web\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:web')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
});

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:auth')
    ->name('login.store');
