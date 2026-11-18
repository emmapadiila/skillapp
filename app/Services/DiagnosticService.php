<?php

namespace App\Services;

use App\Enums\EvaluationStatus;
use App\Enums\GapPriority;
use App\Enums\ResultLevel;
use App\Models\DiagnosticMatrixCell;
use App\Models\Evaluation;
use App\Models\OrganizationalDiagnostic;
use App\Models\OrganizationalGap;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiagnosticService
{
    public function __construct(private readonly AssessmentScoringService $scoring) {}

    public function generate(User $actor, array $data): OrganizationalDiagnostic
    {
        $companyId = $actor->isSuperadmin() ? ($data['company_id'] ?? null) : $actor->company_id;
        if ($companyId === null) {
            throw ValidationException::withMessages(['company_id' => 'La empresa es obligatoria.']);
        }

        $exists = OrganizationalDiagnostic::query()->where('company_id', $companyId)
            ->whereDate('period_start', $data['period_start'])->whereDate('period_end', $data['period_end'])->exists();
        if ($exists) {
            throw ValidationException::withMessages(['period_end' => 'Ya existe un diagnóstico cerrado para este período.']);
        }

        $evaluations = Evaluation::query()
            ->where('company_id', $companyId)->where('status', EvaluationStatus::Completed)
            ->whereBetween('completed_at', [$data['period_start'].' 00:00:00', $data['period_end'].' 23:59:59'])
            ->with([
                'assessment',
                'result.skillResults.skill',
                'result.categoryResults',
                'user.position.skills',
            ])->get();

        if ($evaluations->isEmpty()) {
            throw ValidationException::withMessages(['period_start' => 'No hay evaluaciones completadas en el período.']);
        }

        return DB::transaction(function () use ($actor, $companyId, $data, $evaluations): OrganizationalDiagnostic {
            $diagnostic = OrganizationalDiagnostic::query()->create([
                'company_id' => $companyId,
                'generated_by' => $actor->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_evaluated' => $evaluations->pluck('user_id')->unique()->count(),
                'snapshot' => [
                    'evaluation_ids' => $evaluations->pluck('id')->all(),
                    'average_score' => round((float) $evaluations->avg(fn (Evaluation $evaluation) => $evaluation->result?->total_score), 2),
                    'generated_at' => now()->toISOString(),
                ],
                'closed_at' => now(),
            ]);

            $this->createMatrix($diagnostic, $evaluations);
            $this->createGaps($diagnostic, $evaluations);

            return $diagnostic->load(['matrixCells', 'gaps']);
        });
    }

    /**
     * Cada celda es el promedio de los resultados de una categoría (0–100) en un eje.
     */
    private function createMatrix(OrganizationalDiagnostic $diagnostic, Collection $evaluations): void
    {
        $evaluations->flatMap(function (Evaluation $evaluation): array {
            $axisId = $evaluation->assessment?->organizational_axis_id ?? $evaluation->user?->position?->organizational_axis_id;

            if ($axisId === null || $evaluation->result === null) {
                return [];
            }

            return $evaluation->result->categoryResults->map(fn ($category): array => [
                'axis_id' => $axisId,
                'category_id' => $category->skill_category_id,
                'score' => (float) $category->score,
                'user_id' => $evaluation->user_id,
            ])->all();
        })->groupBy(fn (array $row): string => $row['axis_id'].'|'.$row['category_id'])
            ->each(function (Collection $items) use ($diagnostic): void {
                $score = round((float) $items->avg('score'), 2);
                DiagnosticMatrixCell::query()->create([
                    'organizational_diagnostic_id' => $diagnostic->id,
                    'organizational_axis_id' => $items->first()['axis_id'],
                    'skill_category_id' => $items->first()['category_id'],
                    'average_score' => $score,
                    'level' => ResultLevel::fromScore($score)->value,
                    'evaluated_count' => $items->pluck('user_id')->unique()->count(),
                ]);
            });
    }

    private function createGaps(OrganizationalDiagnostic $diagnostic, Collection $evaluations): void
    {
        $gaps = collect();
        foreach ($evaluations as $evaluation) {
            $position = $evaluation->user?->position;
            if ($position === null || $evaluation->result === null) {
                continue;
            }

            foreach ($evaluation->result->skillResults as $skillResult) {
                $required = $position->skills->firstWhere('id', $skillResult->skill_id)?->pivot?->required_level;
                // El nivel requerido (1–5) se lleva a 0–100 y la brecha se expresa en puntos de la escala 1–5 (25 puntos = 1).
                $gap = $required === null ? 0 : max(0, ($this->scoring->normalize((float) $required) - (float) $skillResult->score) / 25);
                if ($gap <= 0) {
                    continue;
                }

                $key = implode('|', [$skillResult->skill_id, $position->organizational_axis_id, $position->area_id]);
                $current = $gaps->get($key, ['skill_id' => $skillResult->skill_id, 'axis_id' => $position->organizational_axis_id, 'area_id' => $position->area_id, 'total' => 0, 'users' => []]);
                $current['total'] += $gap;
                $current['users'][$evaluation->user_id] = true;
                $gaps->put($key, $current);
            }
        }

        $gaps->each(function (array $gap) use ($diagnostic): void {
            $average = round($gap['total'] / count($gap['users']), 2);
            OrganizationalGap::query()->create([
                'organizational_diagnostic_id' => $diagnostic->id,
                'company_id' => $diagnostic->company_id,
                'skill_id' => $gap['skill_id'],
                'organizational_axis_id' => $gap['axis_id'],
                'area_id' => $gap['area_id'],
                'priority' => match (true) {
                    $average >= 2 => GapPriority::Critical,
                    $average >= 1.5 => GapPriority::High,
                    $average >= 1 => GapPriority::Medium,
                    default => GapPriority::Low,
                },
                'affected_count' => count($gap['users']),
                'score_gap' => $average,
            ]);
        });
    }
}
