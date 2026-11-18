<?php

namespace App\Services;

use App\Enums\EvaluationStatus;
use App\Enums\ResultLevel;
use App\Models\Assessment;
use App\Models\Evaluation;
use App\Models\OrganizationalAxis;
use App\Models\SkillCategory;
use Illuminate\Support\Collection;

/**
 * Datos de los tres tableros por eje, calculados en vivo a partir de los resultados guardados.
 *
 * Se toma el resultado más reciente de cada persona dentro de los filtros, para no contar dos veces
 * a quien presentó la prueba más de una vez.
 */
class AxisDashboardService
{
    /** @var array<string, string> Para quién está pensado cada tablero. */
    private const AUDIENCES = [
        'operational' => 'Líderes de primera línea',
        'mission' => 'Responsables de procesos centrales',
        'strategic' => 'Alta dirección',
    ];

    /**
     * Lectura vertical del anexo: habilidades análogas de los ejes operativo → misional → estratégico.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const VERTICAL_PAIRS = [
        ['Resolución de problemas inmediatos', 'Compromiso con los estándares de calidad', 'Toma de decisiones basada en datos'],
        ['Comunicación operativa entre turnos y equipos', 'Comunicación asertiva con partes interesadas', 'Negociación'],
        ['Trabajo en equipo', 'Colaboración interfuncional entre áreas', 'Liderazgo'],
        ['Adaptabilidad ante cambios en la asignación de tareas', 'Empatía', 'Gestión del cambio'],
    ];

    /**
     * @param  array{company_id?: ?int, area_id?: ?int, position_id?: ?int, from?: ?string, to?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $evaluations = $this->latestEvaluations($filters);
        $assessments = Assessment::query()->with('skills')->where('is_active', true)->get()->keyBy('organizational_axis_id');
        $axes = OrganizationalAxis::query()->whereIn('code', array_keys(self::AUDIENCES))->get()
            ->sortBy(fn (OrganizationalAxis $axis): int => array_search($axis->code, array_keys(self::AUDIENCES), true))->values();
        $byAxis = $evaluations->groupBy(fn (Evaluation $evaluation): ?int => $this->axisId($evaluation));

        return [
            'axes' => $axes->map(fn (OrganizationalAxis $axis): array => $this->axis(
                $axis,
                $assessments->get($axis->id),
                $byAxis->get($axis->id, collect()),
            ))->all(),
            'matrix' => $this->matrix($axes, $assessments, $byAxis),
            'vertical' => $this->vertical($evaluations),
        ];
    }

    /**
     * @param  array{company_id?: ?int, area_id?: ?int, position_id?: ?int, from?: ?string, to?: ?string}  $filters
     * @return Collection<int, Evaluation>
     */
    private function latestEvaluations(array $filters): Collection
    {
        return Evaluation::query()
            ->where('status', EvaluationStatus::Completed)
            ->whereHas('result')
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('completed_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('completed_at', '<=', $to.' 23:59:59'))
            ->when($filters['position_id'] ?? null, fn ($query, $positionId) => $query->whereHas('user', fn ($user) => $user->where('position_id', $positionId)))
            ->when($filters['area_id'] ?? null, fn ($query, $areaId) => $query->whereHas('user.position', fn ($position) => $position->where('area_id', $areaId)))
            ->with([
                'user:id,name,position_id',
                'user.position:id,name,area_id,organizational_axis_id',
                'user.position.area:id,name',
                'assessment:id,organizational_axis_id',
                'result.skillResults.skill:id,name',
                'result.categoryResults',
            ])
            ->orderByDesc('completed_at')
            ->get()
            ->unique('user_id')
            ->values();
    }

    private function axisId(Evaluation $evaluation): ?int
    {
        return $evaluation->assessment?->organizational_axis_id ?? $evaluation->user?->position?->organizational_axis_id;
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     * @return array<string, mixed>
     */
    private function axis(OrganizationalAxis $axis, ?Assessment $assessment, Collection $evaluations): array
    {
        $totals = $evaluations->map(fn (Evaluation $evaluation): float => (float) $evaluation->result->total_score);
        $average = $totals->isEmpty() ? null : round($totals->avg(), 2);
        $skillScores = $evaluations->flatMap(fn (Evaluation $evaluation) => $evaluation->result->skillResults)->groupBy('skill_id');
        $categoryScores = $evaluations->flatMap(fn (Evaluation $evaluation) => $evaluation->result->categoryResults)->groupBy('skill_category_id');

        return [
            'id' => $axis->id,
            'code' => $axis->code,
            'name' => $axis->name,
            'audience' => self::AUDIENCES[$axis->code],
            'assessment' => $assessment?->only(['id', 'name', 'axis_description', 'indicators']),
            'evaluated' => $evaluations->count(),
            'average' => $average,
            'level' => $average === null ? null : ResultLevel::fromScore($average)->value,
            'distribution' => collect(ResultLevel::cases())->mapWithKeys(fn (ResultLevel $level): array => [
                $level->value => $totals->filter(fn (float $total): bool => ResultLevel::fromScore($total) === $level)->count(),
            ])->all(),
            'skills' => ($assessment?->skills ?? collect())->map(fn ($skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category_id' => $skill->skill_category_id,
                'weight' => (float) $skill->pivot->weight,
                'average' => $this->average($skillScores->get($skill->id)),
            ])->values()->all(),
            'categories' => ($assessment?->skills ?? collect())->pluck('skill_category_id')->unique()->map(fn (int $categoryId): array => [
                'id' => $categoryId,
                'average' => $this->average($categoryScores->get($categoryId)),
            ])->values()->all(),
            'people' => $evaluations->map(fn (Evaluation $evaluation): array => [
                'evaluation_id' => $evaluation->id,
                'name' => $evaluation->user?->name,
                'position' => $evaluation->user?->position?->name,
                'area' => $evaluation->user?->position?->area?->name,
                'total' => (float) $evaluation->result->total_score,
                'level' => $evaluation->result->level,
                'completed_at' => $evaluation->completed_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Mapa de calor 4 categorías × 3 ejes. "measured" distingue "no se mide en este eje" de "sin datos".
     *
     * @param  Collection<int, OrganizationalAxis>  $axes
     * @param  Collection<int, Assessment>  $assessments
     * @param  Collection<int, Collection<int, Evaluation>>  $byAxis
     * @return list<array<string, mixed>>
     */
    private function matrix(Collection $axes, Collection $assessments, Collection $byAxis): array
    {
        $cells = [];

        foreach (SkillCategory::query()->orderBy('id')->get() as $category) {
            foreach ($axes as $axis) {
                $scores = $byAxis->get($axis->id, collect())
                    ->flatMap(fn (Evaluation $evaluation) => $evaluation->result->categoryResults)
                    ->where('skill_category_id', $category->id);
                $average = $this->average($scores);

                $cells[] = [
                    'category_id' => $category->id,
                    'axis_code' => $axis->code,
                    'measured' => (bool) $assessments->get($axis->id)?->skills->contains('skill_category_id', $category->id),
                    'average' => $average,
                    'level' => $average === null ? null : ResultLevel::fromScore($average)->value,
                    'count' => $scores->count(),
                ];
            }
        }

        return $cells;
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     * @return list<list<array{name: string, average: ?float}>>
     */
    private function vertical(Collection $evaluations): array
    {
        $scores = $evaluations->flatMap(fn (Evaluation $evaluation) => $evaluation->result->skillResults)
            ->groupBy(fn ($skillResult) => $skillResult->skill?->name);

        return array_map(fn (array $names): array => array_map(fn (string $name): array => [
            'name' => $name,
            'average' => $this->average($scores->get($name)),
        ], $names), self::VERTICAL_PAIRS);
    }

    private function average(?Collection $results): ?float
    {
        return $results === null || $results->isEmpty() ? null : round((float) $results->avg('score'), 2);
    }
}
