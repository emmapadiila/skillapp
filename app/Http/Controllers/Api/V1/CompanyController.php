<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        return response()->json(['data' => Company::query()
            ->withCount('users')
            ->latest()
            ->paginate(max(1, min($request->integer('per_page', 20), 100)))]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $this->authorize('create', Company::class);

        return response()->json(['data' => Company::query()->create($request->validated())], 201);
    }

    public function show(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json(['data' => $company->loadCount(['users', 'areas'])]);
    }

    public function update(StoreCompanyRequest $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);
        $company->update($request->validated());

        return response()->json(['data' => $company->refresh()]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->authorize('delete', $company);
        $company->update(['is_active' => false]);
        $company->delete();

        return response()->json(status: 204);
    }
}
