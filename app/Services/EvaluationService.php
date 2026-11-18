<?php

namespace App\Services;

use App\Contracts\AssessmentAnalyzer;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Enums\QuestionType;
use App\Enums\ResultLevel;
use App\Models\Assessment;
use App\Models\CategoryResult;
use App\Models\Evaluation;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\EvaluationResult;
use App\Models\Question;
use App\Models\SkillResult;
use App\Models\User;
use App\Notifications\EvaluationAssignedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        private readonly AssessmentAnalyzer $analyzer,
        private readonly AssessmentScoringService $scoring,
    ) {}

    public function assign(User $actor, array $data, bool $notify = true): Evaluation
    {
        $collaborator = User::query()->findOrFail($data['user_id']);
        $companyId = $collaborator->company_id;

        if ($companyId === null || (! $actor->isSuperadmin() && $actor->company_id !== $companyId)) {
            throw ValidationException::withMessages(['user_id' => 'El colaborador no pertenece a su empresa.']);
        }

        $collaborator->load('position.skills');

        $assessment = null;
        $questionIds = collect($data['question_ids'] ?? []);

        if (($data['assessment_id'] ?? null) !== null) {
            $assessment = Assessment::query()
                ->whereKey($data['assessment_id'])
                ->where('is_active', true)
                ->first();

            if ($assessment === null) {
                throw ValidationException::withMessages(['assessment_id' => 'La prueba seleccionada no está disponible.']);
            }

            if ($collaborator->position === null
                || $collaborator->position->organizational_axis_id !== $assessment->organizational_axis_id) {
                throw ValidationException::withMessages([
                    'assessment_id' => 'El cargo del colaborador no corresponde al eje de la prueba seleccionada.',
                ]);
            }

            $questionIds = $assessment->questions()->where('is_active', true)->pluck('id');
        } elseif ($questionIds->isEmpty()) {
            if ($collaborator->position === null || $collaborator->position->skills->isEmpty()) {
                throw ValidationException::withMessages(['question_count' => 'El colaborador necesita un cargo con competencias configuradas.']);
            }

            $questionIds = Question::query()
                ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))
                ->where('is_active', true)
                ->whereIn('skill_id', $collaborator->position->skills->modelKeys())
                ->where(fn ($query) => $query->whereNull('organizational_axis_id')
                    ->orWhere('organizational_axis_id', $collaborator->position->organizational_axis_id))
                ->inRandomOrder()->limit($data['question_count'])->pluck('id');
        }

        $questions = Question::query()->whereIn('id', $questionIds)
            ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))
            ->where('is_active', true)->get();

        $expectedCount = $assessment !== null ? $questionIds->count() : ($data['question_count'] ?? $questionIds->count());
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

        return DB::transaction(function () use ($actor, $companyId, $collaborator, $data, $questionIds, $assessment, $notify): Evaluation {
            $evaluation = Evaluation::query()->create([
                'company_id' => $companyId,
                'user_id' => $collaborator->id,
                'assigned_by' => $actor->id,
                'parent_evaluation_id' => $data['parent_evaluation_id'] ?? null,
                'assessment_id' => $assessment?->id,
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

            if ($notify) {
                DB::afterCommit(fn () => $collaborator->notify(new EvaluationAssignedNotification($evaluation)));
            }

            return $evaluation->load('questions.question.options');
        });
    }

    /**
     * El colaborador inicia (o retoma) la prueba del eje de su cargo, sin esperar una asignación.
     */
    public function startOwn(User $user): Evaluation
    {
        $this->ensureConsent($user);

        $axisId = $user->position?->organizational_axis_id;
        $assessment = $axisId === null ? null : Assessment::query()
            ->where('organizational_axis_id', $axisId)->where('is_active', true)->first();

        if ($assessment === null) {
            throw ValidationException::withMessages(['assessment' => 'Tu cargo no tiene una prueba disponible. Consulta con Talento Humano.']);
        }

        // Bloquear la fila del colaborador hace idempotente el inicio: dos pestañas obtienen el mismo intento.
        return DB::transaction(function () use ($user, $assessment): Evaluation {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $ownEvaluations = Evaluation::query()->where('user_id', $user->id)->where('assessment_id', $assessment->id);

            $open = (clone $ownEvaluations)
                ->whereIn('status', [EvaluationStatus::Pending, EvaluationStatus::InProgress])
                ->where('due_at', '>', now())->latest('assigned_at')->first();

            if ($open !== null) {
                return $open;
            }

            if ((clone $ownEvaluations)->where('status', EvaluationStatus::Completed)->exists()) {
                throw ValidationException::withMessages(['assessment' => 'Ya completaste esta prueba. Talento Humano programará tu reevaluación.']);
            }

            return $this->assign($user, [
                'user_id' => $user->id,
                'type' => EvaluationType::Initial->value,
                'assessment_id' => $assessment->id,
                'due_at' => now()->addDays(30),
            ], notify: false);
        });
    }

    public function respond(Evaluation $evaluation, User $user, array $data): EvaluationResponse
    {
        $this->ensureConsent($user);

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
            // Mismo bloqueo de fila que complete(): un guardado no puede colarse después de calificar.
            $locked = Evaluation::query()->lockForUpdate()->findOrFail($evaluation->id);

            if ($locked->status === EvaluationStatus::Completed) {
                throw ValidationException::withMessages(['evaluation' => 'La evaluación ya fue completada y es inmutable.']);
            }

            if ($locked->status === EvaluationStatus::Pending) {
                $locked->update(['status' => EvaluationStatus::InProgress, 'starts_at' => now()]);
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

            $items = $responses->map(fn (EvaluationResponse $response): array => [
                'skill_id' => $response->evaluationQuestion->question->skill_id,
                'category_id' => $response->evaluationQuestion->question->skill->skill_category_id,
                'type' => $response->evaluationQuestion->question->type === QuestionType::SelfReport ? 'likert' : 'situational',
                'score' => (float) $response->score,
            ]);
            $weights = $evaluation->assessment?->skills
                ->mapWithKeys(fn ($skill): array => [$skill->id => (float) $skill->pivot->weight])->all() ?? [];
            $scores = $this->scoring->score($items->all(), $weights);

            // Punto de integración de la IA de planes de mejora: hoy responde NullAssessmentAnalyzer.
            $analysis = $this->analyzer->analyze([
                'evaluation_id' => $evaluation->id,
                'total_score' => $scores['total'],
                'skill_scores' => $scores['skills'],
                'category_scores' => $scores['categories'],
                'responses' => $items->all(),
            ]);
            $result = EvaluationResult::query()->create([
                'evaluation_id' => $evaluation->id,
                'total_score' => $scores['total'],
                'level' => ResultLevel::fromScore($scores['total'])->value,
                'ai_analysis' => $analysis['analysis'],
                'strengths' => $analysis['strengths'],
                'opportunities' => $analysis['opportunities'],
                'model_confidence' => $analysis['confidence'],
                'calculated_at' => now(),
                'immutable_at' => now(),
            ]);

            foreach ($scores['skills'] as $skillId => $score) {
                $explanation = $this->scoring->explain($items->where('skill_id', $skillId));
                SkillResult::query()->create(['evaluation_result_id' => $result->id, 'skill_id' => $skillId, 'score' => $score, 'level' => ResultLevel::fromScore($score)->value, ...$explanation]);
            }

            foreach ($scores['categories'] as $categoryId => $score) {
                CategoryResult::query()->create(['evaluation_result_id' => $result->id, 'skill_category_id' => $categoryId, 'score' => $score, 'level' => ResultLevel::fromScore($score)->value, 'strengths' => [], 'opportunities' => []]);
            }

            $evaluation->update(['status' => EvaluationStatus::Completed, 'completed_at' => now()]);

            return $result->load(['skillResults', 'categoryResults']);
        });
    }

    private function ensureConsent(User $user): void
    {
        if ($user->consent_accepted_at === null) {
            throw ValidationException::withMessages(['consent' => 'Debes aceptar el consentimiento informado antes de responder la prueba.']);
        }
    }
}
