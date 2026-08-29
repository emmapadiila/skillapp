<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAreaRequest;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Area::query()->withCount('positions');
        if ($request->user()->isSuperadmin()) {
            $query->when($request->integer('company_id'), fn ($query, int $id) => $query->where('company_id', $id));
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json(['data' => $query->orderBy('name')->paginate(max(1, min($request->integer('per_page', 50), 100)))]);
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        $data = $this->data($request);
        $this->ensureUnique($data['company_id'], $data['name']);

        return response()->json(['data' => Area::query()->create($data)], 201);
    }

    public function show(Request $request, Area $area): JsonResponse
    {
        $this->visible($request, $area);

        return response()->json(['data' => $area->load('positions')]);
    }

    public function update(StoreAreaRequest $request, Area $area): JsonResponse
    {
        $this->visible($request, $area);
        $data = $this->data($request, $area);
        $this->ensureUnique($data['company_id'], $data['name'], $area->id);
        $area->update($data);

        return response()->json(['data' => $area->refresh()]);
    }

    public function destroy(Request $request, Area $area): JsonResponse
    {
        $this->visible($request, $area);
        abort_if($area->positions()->exists(), 409, 'El área tiene cargos asociados.');
        $area->delete();

        return response()->json(status: 204);
    }

    private function data(StoreAreaRequest $request, ?Area $area = null): array
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->isSuperadmin() ? ($data['company_id'] ?? $area?->company_id) : $request->user()->company_id;

        if ($data['company_id'] === null) {
            throw ValidationException::withMessages(['company_id' => 'La empresa es obligatoria.']);
        }

        return $data;
    }

    private function visible(Request $request, Area $area): void
    {
        abort_unless($request->user()->isSuperadmin() || $area->company_id === $request->user()->company_id, 404);
    }

    private function ensureUnique(int $companyId, string $name, ?int $ignore = null): void
    {
        $exists = Area::withTrashed()->where('company_id', $companyId)->where('name', $name)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'Ya existe un área con este nombre en la empresa.']);
        }
    }
}
