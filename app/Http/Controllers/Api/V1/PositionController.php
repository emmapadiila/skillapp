<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePositionRequest;
use App\Models\Area;
use App\Models\Position;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Position::query()->with(['area:id,name', 'organizationalAxis:id,name', 'skills:id,name']);
        if ($request->user()->isSuperadmin()) {
            $query->when($request->integer('company_id'), fn ($query, int $id) => $query->where('company_id', $id));
        } else {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json(['data' => $query->orderBy('name')->paginate(max(1, min($request->integer('per_page', 50), 100)))]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = DB::transaction(fn (): Position => $this->persist($request));

        return response()->json(['data' => $position->load(['area', 'organizationalAxis', 'skills'])], 201);
    }

    public function show(Request $request, Position $position): JsonResponse
    {
        $this->visible($request, $position);

        return response()->json(['data' => $position->load(['area', 'organizationalAxis', 'skills'])]);
    }

    public function update(StorePositionRequest $request, Position $position): JsonResponse
    {
        $this->visible($request, $position);
        DB::transaction(fn () => $this->persist($request, $position));

        return response()->json(['data' => $position->refresh()->load(['area', 'organizationalAxis', 'skills'])]);
    }

    public function destroy(Request $request, Position $position): JsonResponse
    {
        $this->visible($request, $position);
        abort_if($position->users()->exists(), 409, 'El cargo tiene colaboradores asociados.');
        $position->delete();

        return response()->json(status: 204);
    }

    private function persist(StorePositionRequest $request, ?Position $position = null): Position
    {
        $data = $request->validated();
        $skills = Arr::pull($data, 'skills', []);
        $companyId = $request->user()->isSuperadmin() ? ($data['company_id'] ?? $position?->company_id) : $request->user()->company_id;

        if ($companyId === null || ! Area::query()->whereKey($data['area_id'])->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['area_id' => 'El área debe pertenecer a la empresa.']);
        }

        $skillIds = collect($skills)->pluck('id');
        $validSkills = Skill::query()->whereIn('id', $skillIds)
            ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))->count();
        if ($validSkills !== $skillIds->count()) {
            throw ValidationException::withMessages(['skills' => 'Todas las competencias deben estar disponibles para la empresa.']);
        }

        $data['company_id'] = $companyId;
        $position ??= new Position;
        $position->fill($data)->save();
        if ($request->has('skills')) {
            $position->skills()->sync(collect($skills)->mapWithKeys(fn (array $item): array => [
                $item['id'] => ['weight' => $item['weight'], 'required_level' => $item['required_level']],
            ])->all());
        }

        return $position;
    }

    private function visible(Request $request, Position $position): void
    {
        abort_unless($request->user()->isSuperadmin() || $position->company_id === $request->user()->company_id, 404);
    }
}
