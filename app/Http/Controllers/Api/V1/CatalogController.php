<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCatalogRequest;
use App\Models\OrganizationalAxis;
use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $catalogs = Cache::remember('catalogs:v1', now()->addHour(), fn (): array => [
            'organizational_axes' => OrganizationalAxis::query()->where('is_active', true)->orderBy('name')->get(),
            'skill_categories' => SkillCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);

        return response()->json(['data' => $catalogs]);
    }

    public function store(StoreCatalogRequest $request, string $catalog): JsonResponse
    {
        $model = $this->model($catalog);
        $this->ensureCodeIsUnique($model, $request->string('code')->toString());
        $record = $model::query()->create($request->validated());
        Cache::forget('catalogs:v1');

        return response()->json(['data' => $record], 201);
    }

    public function update(StoreCatalogRequest $request, string $catalog, int $id): JsonResponse
    {
        $model = $this->model($catalog);
        $record = $model::query()->findOrFail($id);
        $this->ensureCodeIsUnique($model, $request->string('code')->toString(), $record->id);
        $record->update($request->validated());
        Cache::forget('catalogs:v1');

        return response()->json(['data' => $record->refresh()]);
    }

    /** @return class-string<Model> */
    private function model(string $catalog): string
    {
        return match ($catalog) {
            'organizational-axes' => OrganizationalAxis::class,
            'skill-categories' => SkillCategory::class,
            default => throw ValidationException::withMessages(['catalog' => 'Catálogo no válido.']),
        };
    }

    /** @param class-string<Model> $model */
    private function ensureCodeIsUnique(string $model, string $code, ?int $ignoreId = null): void
    {
        $exists = $model::query()->where('code', $code)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'El código ya existe en este catálogo.']);
        }
    }
}
