<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDiagnosticRequest;
use App\Models\OrganizationalDiagnostic;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    public function __construct(private readonly DiagnosticService $diagnostics) {}

    public function index(Request $request): JsonResponse
    {
        $query = OrganizationalDiagnostic::query()->withCount(['matrixCells', 'gaps']);
        if ($request->user()->isSuperadmin()) {
            $query->when($request->integer('company_id'), fn ($query, int $id) => $query->where('company_id', $id));
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json(['data' => $query->latest('period_end')->paginate(max(1, min($request->integer('per_page', 20), 100)))]);
    }

    public function store(StoreDiagnosticRequest $request): JsonResponse
    {
        $diagnostic = $this->diagnostics->generate($request->user(), $request->validated());

        return response()->json(['data' => $diagnostic], 201);
    }

    public function show(Request $request, OrganizationalDiagnostic $diagnostic): JsonResponse
    {
        abort_unless($request->user()->isSuperadmin() || $diagnostic->company_id === $request->user()->company_id, 404);

        return response()->json(['data' => $diagnostic->load(['matrixCells', 'gaps'])]);
    }
}
