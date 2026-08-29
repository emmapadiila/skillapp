<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ImprovementActivityStatus;
use App\Enums\ImprovementPlanStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreImprovementPlanRequest;
use App\Models\ImprovementActivity;
use App\Models\ImprovementPlan;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\ImprovementPlanAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImprovementPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImprovementPlan::class);
        $user = $request->user();
        $query = ImprovementPlan::query()->with(['user:id,name,email', 'skill:id,name']);

        if (! $user->isSuperadmin()) {
            $query->where('company_id', $user->company_id);
        }

        if ($user->role === UserRole::Collaborator) {
            $query->where('user_id', $user->id);
        }

        return response()->json(['data' => $query->latest()->paginate(max(1, min($request->integer('per_page', 20), 100)))]);
    }

    public function store(StoreImprovementPlanRequest $request): JsonResponse
    {
        $this->authorize('create', ImprovementPlan::class);
        $plan = DB::transaction(fn (): ImprovementPlan => $this->persist($request));
        $plan->user->notify(new ImprovementPlanAssignedNotification($plan));

        return response()->json(['data' => $plan->load(['activities', 'resources', 'skill'])], 201);
    }

    public function show(ImprovementPlan $improvementPlan): JsonResponse
    {
        $this->authorize('view', $improvementPlan);

        return response()->json(['data' => $improvementPlan->load(['activities', 'resources', 'skill'])]);
    }

    public function update(StoreImprovementPlanRequest $request, ImprovementPlan $improvementPlan): JsonResponse
    {
        $this->authorize('update', $improvementPlan);
        DB::transaction(fn () => $this->persist($request, $improvementPlan));

        return response()->json(['data' => $improvementPlan->refresh()->load(['activities', 'resources', 'skill'])]);
    }

    public function destroy(ImprovementPlan $improvementPlan): JsonResponse
    {
        $this->authorize('delete', $improvementPlan);
        $improvementPlan->delete();

        return response()->json(status: 204);
    }

    public function completeActivity(Request $request, ImprovementPlan $improvementPlan, ImprovementActivity $activity): JsonResponse
    {
        $this->authorize('view', $improvementPlan);
        abort_unless($improvementPlan->user_id === $request->user()->id, 403);
        abort_unless($activity->improvement_plan_id === $improvementPlan->id, 404);

        DB::transaction(function () use ($improvementPlan, $activity): void {
            $activity->update(['status' => ImprovementActivityStatus::Completed, 'completed_at' => now()]);
            $total = $improvementPlan->activities()->count();
            $completed = $improvementPlan->activities()->where('status', ImprovementActivityStatus::Completed)->count();
            $progress = $total === 0 ? 0 : round(($completed / $total) * 100, 2);
            $improvementPlan->update([
                'progress' => $progress,
                'status' => $total > 0 && $completed === $total
                    ? ImprovementPlanStatus::Completed
                    : ImprovementPlanStatus::InProgress,
            ]);
        });

        return response()->json(['data' => $improvementPlan->refresh()->load('activities')]);
    }

    private function persist(StoreImprovementPlanRequest $request, ?ImprovementPlan $plan = null): ImprovementPlan
    {
        $data = $request->validated();
        $target = User::query()->findOrFail($data['user_id']);
        $companyId = $request->user()->isSuperadmin() ? $target->company_id : $request->user()->company_id;
        $targetExists = $companyId !== null && $target->company_id === $companyId;
        $skillExists = Skill::query()->whereKey($data['skill_id'])
            ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))->exists();

        if (! $targetExists || ! $skillExists) {
            throw ValidationException::withMessages(['user_id' => 'El colaborador o la competencia no pertenece a su empresa.']);
        }

        $activities = Arr::pull($data, 'activities', []);
        $resources = Arr::pull($data, 'resources', []);
        $data['company_id'] = $companyId;
        if ($plan === null) {
            $plan = new ImprovementPlan;
            $data['created_by'] = $request->user()->id;
        }
        $plan->fill($data)->save();

        if ($request->has('activities')) {
            $plan->activities()->delete();
            $plan->activities()->createMany($activities);
        }

        if ($request->has('resources')) {
            $plan->resources()->delete();
            $plan->resources()->createMany($resources);
        }

        return $plan;
    }
}
