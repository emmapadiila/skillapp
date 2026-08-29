<?php

use App\Http\Controllers\Api\V1\AreaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DiagnosticController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Controllers\Api\V1\ImprovementPlanController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\SkillController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->middleware('throttle:auth')->group(function (): void {
        Route::post('sign-in', [AuthController::class, 'signIn']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('recover', [AuthController::class, 'recover']);
    });

    Route::middleware(['supabase.auth', 'throttle:api'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::get('catalogs', [CatalogController::class, 'index']);
        Route::post('catalogs/{catalog}', [CatalogController::class, 'store'])->middleware('role:superadmin');
        Route::put('catalogs/{catalog}/{id}', [CatalogController::class, 'update'])->middleware('role:superadmin');

        Route::apiResource('companies', CompanyController::class)
            ->middleware('role:superadmin');

        Route::apiResource('employees', EmployeeController::class)
            ->middleware('role:superadmin,human_resources');
        Route::post('employees/{employee}/invite', [EmployeeController::class, 'invite'])
            ->middleware('role:superadmin,human_resources');

        Route::apiResource('areas', AreaController::class)
            ->middleware('role:superadmin,human_resources');
        Route::apiResource('positions', PositionController::class)
            ->middleware('role:superadmin,human_resources');
        Route::apiResource('skills', SkillController::class)
            ->middleware('role:superadmin,human_resources');

        Route::apiResource('questions', QuestionController::class)
            ->middleware('role:superadmin,human_resources');

        Route::apiResource('evaluations', EvaluationController::class);
        Route::post('evaluations/{evaluation}/responses', [EvaluationController::class, 'respond']);
        Route::post('evaluations/{evaluation}/complete', [EvaluationController::class, 'complete']);

        Route::apiResource('improvement-plans', ImprovementPlanController::class)
            ->parameters(['improvement-plans' => 'improvementPlan']);
        Route::post('improvement-plans/{improvementPlan}/activities/{activity}/complete', [ImprovementPlanController::class, 'completeActivity']);

        Route::apiResource('diagnostics', DiagnosticController::class)->only(['index', 'store', 'show'])
            ->middleware('role:superadmin,human_resources');

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    });
});
