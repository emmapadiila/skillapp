<?php

namespace App\Services;

/**
 * Calcula el resultado de una prueba a partir de los puntajes (1 a 5) de cada respuesta.
 *
 * - Habilidad: promedio de sus ítems llevado a 0–100 con (promedio − 1) / 4 × 100.
 * - Categoría y total: promedio de las habilidades ponderado con el peso de `assessment_skill`.
 */
class AssessmentScoringService
{
    /**
     * @param  iterable<array{skill_id: int, category_id: int, score: float}>  $responses
     * @param  array<int, float>  $weights  Peso por id de habilidad; las que no aparecen pesan 1.
     * @return array{total: float, skills: array<int, float>, categories: array<int, float>}
     */
    public function score(iterable $responses, array $weights): array
    {
        $scoresBySkill = [];
        $categoryBySkill = [];

        foreach ($responses as $response) {
            $scoresBySkill[$response['skill_id']][] = $response['score'];
            $categoryBySkill[$response['skill_id']] = $response['category_id'];
        }

        $skills = array_map(
            fn (array $scores): float => $this->normalize(array_sum($scores) / count($scores)),
            $scoresBySkill,
        );

        $skillsByCategory = [];
        foreach ($skills as $skillId => $score) {
            $skillsByCategory[$categoryBySkill[$skillId]][$skillId] = $score;
        }

        return [
            'total' => round($this->weightedAverage($skills, $weights), 2),
            'skills' => array_map(fn (float $score): float => round($score, 2), $skills),
            'categories' => array_map(fn (array $group): float => round($this->weightedAverage($group, $weights), 2), $skillsByCategory),
        ];
    }

    /**
     * Lleva un promedio de la escala 1–5 a 0–100 (1 → 0, 3 → 50, 5 → 100).
     */
    public function normalize(float $average): float
    {
        return max(0.0, min(100.0, ($average - 1) / 4 * 100));
    }

    /**
     * @param  array<int, float>  $scores  Puntaje por id de habilidad.
     * @param  array<int, float>  $weights
     */
    private function weightedAverage(array $scores, array $weights): float
    {
        $weighted = 0.0;
        $totalWeight = 0.0;

        foreach ($scores as $skillId => $score) {
            $weight = $weights[$skillId] ?? 1.0;
            $weighted += $score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weighted / $totalWeight : 0.0;
    }

    /**
     * Explica en lenguaje sencillo qué subió o bajó el puntaje de una habilidad.
     *
     * @param  iterable<array{type: string, score: float}>  $items  Ítems de una sola habilidad.
     * @return array{strengths: list<string>, opportunities: list<string>}
     */
    public function explain(iterable $items): array
    {
        $likert = [];
        $situational = [];

        foreach ($items as $item) {
            if ($item['type'] === 'likert') {
                $likert[] = $item['score'];
            } else {
                $situational[] = $item['score'];
            }
        }

        $strengths = [];
        $opportunities = [];

        if ($likert !== []) {
            $average = array_sum($likert) / count($likert);
            $formatted = number_format($average, 1, ',', '');

            match (true) {
                $average >= 4 => $strengths[] = "En las afirmaciones te describiste con frecuencia de esta forma (promedio {$formatted} de 5).",
                $average >= 3 => $opportunities[] = "En las afirmaciones te ubicaste en un punto intermedio (promedio {$formatted} de 5).",
                default => $opportunities[] = "En las afirmaciones te ubicaste por debajo del punto medio (promedio {$formatted} de 5).",
            };
        }

        foreach ($situational as $score) {
            match (true) {
                $score >= 5 => $strengths[] = 'En la situación planteada elegiste la respuesta más efectiva.',
                $score >= 3 => $opportunities[] = 'En la situación elegiste una respuesta aceptable, pero había una más efectiva.',
                default => $opportunities[] = 'En la situación elegiste una respuesta poco efectiva para lo que pedía el caso.',
            };
        }

        return ['strengths' => $strengths, 'opportunities' => $opportunities];
    }
}
