<?php

namespace App\Services;

use App\Contracts\AssessmentAnalyzer;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Enums\QuestionType;
use App\Models\CategoryResult;
use App\Models\Evaluation;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\EvaluationResult;
use App\Models\Question;
use App\Models\SkillResult;
use App\Models\User;
use App\Notifications\EvaluationAssignedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(private readonly AssessmentAnalyzer $analyzer) {}

    public function assign(User $actor, array $data): Evaluation
    {
        $collaborator = User::query()->findOrFail($data['user_id']);
        $companyId = $collaborator->company_id;

        if ($companyId === null || (! $actor->isSuperadmin() && $actor->company_id !== $companyId)) {
            throw ValidationException::withMessages(['user_id' => 'El colaborador no pertenece a su empresa.']);
        }

        $questionIds = collect($data['question_ids'] ?? []);
        if ($questionIds->isEmpty()) {
            $collaborator->load('position.skills');
            if ($collaborator->position === null || $collaborator->position->skills->isEmpty()) {
                throw ValidationException::withMessages(['question_count' => 'El colaborador necesita un cargo con competencias configuradas.']);
            }

            $questionIds = Question::query()->where('company_id', $companyId)->where('is_active', true)
                ->whereIn('skill_id', $collaborator->position->skills->modelKeys())
                ->where(fn ($query) => $query->whereNull('organizational_axis_id')
                    ->orWhere('organizational_axis_id', $collaborator->position->organizational_axis_id))
                ->inRandomOrder()->limit($data['question_count'])->pluck('id');
        }

        $questions = Question::query()->whereIn('id', $questionIds)
            ->where('company_id', $companyId)->where('is_active', true)->get();

        $expectedCount = $data['question_count'] ?? $questionIds->count();
        if ($questions->count() !== $expectedCount) {
            throw ValidationException::withMessages(['question_ids' => 'Todas las preguntas deben estar activas y pertenecer a la empresa.']);
        }

        if (EvaluationType::from($data['type']) === EvaluationType::Reevaluation) {
            $parentIsValid = isset($data['parent_evaluation_id']) && Evaluation::query()
                ->whereKey($data['parent_evaluation_id'])->where('user_id', $collaborator->id)
                ->where('status', EvaluationStatus::Completed)->exists();

            if (! $parentIsValid) {
                throw ValidationException::withMessages(['parent_evaluation_id' => 'La reevaluación requiere una evaluación completada del mismo colaborador.']);
            }
        }

        return DB::transaction(function () use ($actor, $companyId, $collaborator, $data, $questionIds): Evaluation {
            $evaluation = Evaluation::query()->create([
                'company_id' => $companyId,
                'user_id' => $collaborator->id,
                'assigned_by' => $actor->id,
                'parent_evaluation_id' => $data['parent_evaluation_id'] ?? null,
                'type' => $data['type'],
                'status' => EvaluationStatus::Pending,
                'question_count' => $questionIds->count(),
                'assigned_at' => now(),
                'due_at' => $data['due_at'],
                'settings' => $data['settings'] ?? null,
            ]);

            $evaluation->questions()->createMany($questionIds
                ->values()->map(fn (int $id, int $index): array => [
                    'question_id' => $id,
                    'display_order' => $index + 1,
                ])->all());

            DB::afterCommit(fn () => $collaborator->notify(new EvaluationAssignedNotification($evaluation)));

            return $evaluation->load('questions.question.options');
        });
    }

    public function respond(Evaluation $evaluation, User $user, array $data): EvaluationResponse
    {
        if ($evaluation->status === EvaluationStatus::Completed) {
            throw ValidationException::withMessages(['evaluation' => 'La evaluación ya fue completada y es inmutable.']);
        }

        if ($evaluation->due_at->isPast()) {
            $evaluation->update(['status' => EvaluationStatus::Expired]);
            throw ValidationException::withMessages(['evaluation' => 'La evaluación está vencida.']);
        }

        $evaluationQuestion = EvaluationQuestion::query()->with('question.options')
            ->whereKey($data['evaluation_question_id'])->where('evaluation_id', $evaluation->id)->first();

        if ($evaluationQuestion === null) {
            throw ValidationException::withMessages(['evaluation_question_id' => 'La pregunta no pertenece a esta evaluación.']);
        }

        $question = $evaluationQuestion->question;
        $optionId = $data['question_option_id'] ?? null;
        $likert = $data['likert_value'] ?? null;

        if ($question->type === QuestionType::SelfReport) {
            if ($likert === null || $optionId !== null) {
                throw ValidationException::withMessages(['likert_value' => 'Esta pregunta requiere un valor Likert entre 1 y 5.']);
            }
            $score = $likert;
        } else {
            $option = $question->options->firstWhere('id', $optionId);
            if ($option === null || $likert !== null) {
                throw ValidationException::withMessages(['question_option_id' => 'Seleccione una opción válida para la pregunta.']);
            }
            $score = $option->score;
        }

        return DB::transaction(function () use ($evaluation, $evaluationQuestion, $user, $optionId, $likert, $score): EvaluationResponse {
            if ($evaluation->status === EvaluationStatus::Pending) {
                $evaluation->update(['status' => EvaluationStatus::InProgress, 'starts_at' => now()]);
            }

            return EvaluationResponse::query()->updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'evaluation_question_id' => $evaluationQuestion->id],
                ['user_id' => $user->id, 'question_option_id' => $optionId, 'likert_value' => $likert, 'score' => $score, 'answered_at' => now()],
            );
        });
    }

    public function complete(Evaluation $evaluation): EvaluationResult
    {
        return DB::transaction(function () use ($evaluation): EvaluationResult {
            $evaluation = Evaluation::query()->lockForUpdate()->findOrFail($evaluation->id);

            if ($evaluation->result()->exists()) {
                return $evaluation->result()->with(['skillResults', 'categoryResults'])->firstOrFail();
            }

            $responses = $evaluation->responses()->with('evaluationQuestion.question.skill.category')->get();
            if ($responses->count() !== $evaluation->question_count) {
                throw ValidationException::withMessages(['evaluation' => 'Debe responder todas las preguntas antes de completar la evaluación.']);
            }

            $totalScore = round((float) $responses->avg('score'), 2);
            $analysis = $this->analyzer->analyze([
                'evaluation_id' => $evaluation->id,
                'total_score' => $totalScore,
                'responses' => $responses->map(fn (EvaluationResponse $response): array => [
                    'skill_id' => $response->evaluationQuestion->question->skill_id,
                    'score' => (float) $response->score,
                ])->all(),
            ]);
            $result = EvaluationResult::query()->create([
                'evaluation_id' => $evaluation->id,
                'total_score' => $totalScore,
                'level' => $this->level($totalScore),
                'ai_analysis' => $analysis['analysis'],
                'strengths' => $analysis['strengths'],
                'opportunities' => $analysis['opportunities'],
                'model_confidence' => $analysis['confidence'],
                'calculated_at' => now(),
                'immutable_at' => now(),
            ]);

            $this->storeSkillResults($result, $responses);
            $this->storeCategoryResults($result, $responses);
            $evaluation->update(['status' => EvaluationStatus::Completed, 'completed_at' => now()]);

            return $result->load(['skillResults', 'categoryResults']);
        });
    }

    private function storeSkillResults(EvaluationResult $result, Collection $responses): void
    {
        $responses->groupBy(fn (EvaluationResponse $response) => $response->evaluationQuestion->question->skill_id)
            ->each(function (Collection $items, int $skillId) use ($result): void {
                $score = round((float) $items->avg('score'), 2);
                SkillResult::query()->create(['evaluation_result_id' => $result->id, 'skill_id' => $skillId, 'score' => $score, 'level' => $this->level($score), 'strengths' => [], 'opportunities' => []]);
            });
    }

    private function storeCategoryResults(EvaluationResult $result, Collection $responses): void
    {
        $responses->groupBy(fn (EvaluationResponse $response) => $response->evaluationQuestion->question->skill->skill_category_id)
            ->each(function (Collection $items, int $categoryId) use ($result): void {
                $score = round((float) $items->avg('score'), 2);
                CategoryResult::query()->create(['evaluation_result_id' => $result->id, 'skill_category_id' => $categoryId, 'score' => $score, 'level' => $this->level($score), 'strengths' => [], 'opportunities' => []]);
            });
    }

    private function level(float $score): string
    {
        return match (true) {
            $score >= 4.5 => 'outstanding',
            $score >= 3.5 => 'strong',
            $score >= 2.5 => 'developing',
            default => 'critical',
        };
    }
}
