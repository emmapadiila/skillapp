<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EvaluationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEvaluationRequest;
use App\Http\Requests\Api\V1\StoreEvaluationResponseRequest;
use App\Models\Evaluation;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Evaluation::class);
        $user = $request->user();
        $query = Evaluation::query()->with(['user:id,name,email', 'result']);

        if (! $user->isSuperadmin()) {
            $query->where('company_id', $user->company_id);
        }

        if ($user->role === UserRole::Collaborator) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->latest('assigned_at')->paginate(max(1, min($request->integer('per_page', 20), 100)))]);
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $this->authorize('create', Evaluation::class);
        $evaluation = $this->evaluations->assign($request->user(), $request->validated());

        return response()->json(['data' => $evaluation], 201);
    }

    public function show(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('view', $evaluation);
        $evaluation->load([
            'user:id,name,email', 'questions.question.skill.category',
            'questions.question.options', 'responses', 'result.skillResults', 'result.categoryResults',
        ]);

        if ($request->user()->role === UserRole::Collaborator) {
            $evaluation->questions->each(fn ($item) => $item->question->options->each->makeHidden('score'));
            $evaluation->responses->each->makeHidden('score');
        }

        return response()->json(['data' => $evaluation]);
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('delete', $evaluation);
        abort_unless($evaluation->status === EvaluationStatus::Pending, 409, 'Solo una evaluación pendiente puede reprogramarse.');
        $data = $request->validate(['due_at' => ['required', 'date', 'after:now']]);
        $evaluation->update($data);

        return response()->json(['data' => $evaluation->refresh()]);
    }

    public function destroy(Evaluation $evaluation): JsonResponse
    {
        $this->authorize('delete', $evaluation);
        abort_unless($evaluation->status === EvaluationStatus::Pending, 409, 'Solo una evaluación pendiente puede eliminarse.');
        $evaluation->delete();

        return response()->json(status: 204);
    }

    public function respond(StoreEvaluationResponseRequest $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('update', $evaluation);
        $response = $this->evaluations->respond($evaluation, $request->user(), $request->validated());

        return response()->json(['data' => $response->makeHidden('score')]);
    }

    public function complete(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorize('update', $evaluation);

        return response()->json(['data' => $this->evaluations->complete($evaluation)]);
    }
}
