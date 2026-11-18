<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AxisDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tableros por eje con filtros opcionales por área, cargo y periodo.
     */
    public function axes(Request $request, AxisDashboardService $dashboards): JsonResponse
    {
        $filters = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $user = $request->user();
        abort_if(! $user->isSuperadmin() && $user->company_id === null, 403);
        // Talento Humano solo ve su empresa; el superadministrador puede elegir una.
        $filters['company_id'] = $user->isSuperadmin() ? ($filters['company_id'] ?? null) : $user->company_id;

        return response()->json(['data' => $dashboards->build($filters)]);
    }
}
