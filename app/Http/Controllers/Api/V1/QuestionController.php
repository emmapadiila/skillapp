<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreQuestionRequest;
use App\Models\Question;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Question::class);
        $query = Question::query()->with(['skill.category', 'options']);

        if (! $request->user()->isSuperadmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json(['data' => $query->latest()->paginate(max(1, min($request->integer('per_page', 20), 100)))]);
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $this->authorize('create', Question::class);
        $question = DB::transaction(fn (): Question => $this->persist($request));

        return response()->json(['data' => $question->load(['skill.category', 'options'])], 201);
    }

    public function show(Question $question): JsonResponse
    {
        $this->authorize('view', $question);

        return response()->json(['data' => $question->load(['skill.category', 'options'])]);
    }

    public function update(StoreQuestionRequest $request, Question $question): JsonResponse
    {
        $this->authorize('update', $question);
        abort_if($question->evaluationQuestions()->exists(), 409, 'Una pregunta utilizada debe versionarse, no modificarse.');
        DB::transaction(fn () => $this->persist($request, $question));

        return response()->json(['data' => $question->refresh()->load(['skill.category', 'options'])]);
    }

    public function destroy(Question $question): JsonResponse
    {
        $this->authorize('delete', $question);
        abort_if($question->evaluationQuestions()->exists(), 409, 'Una pregunta utilizada no puede eliminarse.');
        $question->delete();

        return response()->json(status: 204);
    }

    private function persist(StoreQuestionRequest $request, ?Question $question = null): Question
    {
        $data = $request->validated();
        $companyId = $request->user()->isSuperadmin() ? ($data['company_id'] ?? null) : $request->user()->company_id;

        if ($companyId === null) {
            throw ValidationException::withMessages(['company_id' => 'La empresa es obligatoria.']);
        }

        $skillIsValid = Skill::query()->whereKey($data['skill_id'])
            ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))->exists();

        if (! $skillIsValid) {
            throw ValidationException::withMessages(['skill_id' => 'La competencia no está disponible para la empresa.']);
        }

        $options = Arr::pull($data, 'options', []);
        unset($data['company_id']);
        $data['company_id'] = $companyId;
        if ($question === null) {
            $question = new Question;
            $data['created_by'] = $request->user()->id;
        }
        $question->fill($data)->save();

        if ($question->type === QuestionType::SelfReport && $options !== []) {
            throw ValidationException::withMessages(['options' => 'Las preguntas de autoinforme usan escala Likert y no opciones.']);
        }

        if ($question->type !== QuestionType::SelfReport && count($options) < 2) {
            throw ValidationException::withMessages(['options' => 'Las preguntas situacionales requieren al menos dos opciones.']);
        }

        if ($request->has('options')) {
            $question->options()->delete();
            $question->options()->createMany($options);
        }

        return $question;
    }
}
