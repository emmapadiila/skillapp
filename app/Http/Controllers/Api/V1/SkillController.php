<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSkillRequest;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Skill::query()->with(['category', 'axes']);
        if ($request->user()->isSuperadmin()) {
            $query->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')));
        } else {
            $query->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $request->user()->company_id));
        }

        return response()->json(['data' => $query->orderBy('name')->paginate(max(1, min($request->integer('per_page', 50), 100)))]);
    }

    public function store(StoreSkillRequest $request): JsonResponse
    {
        $skill = DB::transaction(fn (): Skill => $this->persist($request));

        return response()->json(['data' => $skill->load(['category', 'axes'])], 201);
    }

    public function show(Request $request, Skill $skill): JsonResponse
    {
        $this->visible($request, $skill);

        return response()->json(['data' => $skill->load(['category', 'axes'])]);
    }

    public function update(StoreSkillRequest $request, Skill $skill): JsonResponse
    {
        $this->visible($request, $skill, write: true);
        DB::transaction(fn () => $this->persist($request, $skill));

        return response()->json(['data' => $skill->refresh()->load(['category', 'axes'])]);
    }

    public function destroy(Request $request, Skill $skill): JsonResponse
    {
        $this->visible($request, $skill, write: true);
        abort_if($skill->questions()->exists(), 409, 'La competencia tiene preguntas asociadas.');
        $skill->delete();

        return response()->json(status: 204);
    }

    private function persist(StoreSkillRequest $request, ?Skill $skill = null): Skill
    {
        $data = $request->validated();
        $axisIds = Arr::pull($data, 'axis_ids');
        $data['company_id'] = $request->user()->isSuperadmin() ? ($data['company_id'] ?? $skill?->company_id) : $request->user()->company_id;
        $skill ??= new Skill;
        $skill->fill($data)->save();
        $skill->axes()->sync($axisIds);

        return $skill;
    }

    private function visible(Request $request, Skill $skill, bool $write = false): void
    {
        $allowed = $request->user()->isSuperadmin()
            || (! $write && $skill->company_id === null)
            || $skill->company_id === $request->user()->company_id;
        abort_unless($allowed, 404);
        abort_if($write && $skill->company_id === null && ! $request->user()->isSuperadmin(), 403);
    }
}
