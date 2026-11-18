<?php

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\OrganizationalAxis;
use App\Models\Question;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Base de las tres pruebas del diagnóstico en 3 ejes.
 *
 * Cada habilidad tiene 2 ítems Likert y 1 situacional con 4 opciones (A–D, puntaje fijo 1–5).
 * Idempotente: prueba por código, preguntas por código y opciones por letra.
 */
abstract class PruebaSeeder extends Seeder
{
    private const INSTRUCTIONS = "Afirmaciones: Lee cada afirmación y marca qué tanto describe tu forma habitual de trabajar. No hay respuestas buenas ni malas; responde con sinceridad.\n"
        .'Situaciones: Lee la situación y elige la opción que mejor describe lo que harías en realidad.';

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     axis: string,
     *     description: string,
     *     axis_description: string,
     *     indicators: array<int, string>,
     *     skills: array<int, array{name: string, weight: float, likert: array{0: string, 1: string}, scenario: string, options: array<int, array{0: string, 1: int}>}>
     * }
     */
    abstract protected function definition(): array;

    public function run(): void
    {
        $definition = $this->definition();
        $axisId = OrganizationalAxis::query()->where('code', $definition['axis'])->value('id')
            ?? throw new RuntimeException("Falta el eje [{$definition['axis']}]; ejecuta primero DatabaseSeeder.");

        $assessment = Assessment::query()->updateOrCreate(['code' => $definition['code']], [
            'name' => $definition['name'],
            'description' => $definition['description'],
            'instructions' => self::INSTRUCTIONS,
            'axis_description' => $definition['axis_description'],
            'indicators' => $definition['indicators'],
            'organizational_axis_id' => $axisId,
            'is_active' => true,
        ]);

        $weights = [];
        $displayOrder = 0;

        foreach ($definition['skills'] as $skillIndex => $skillDefinition) {
            $skill = Skill::query()->whereNull('company_id')->where('name', $skillDefinition['name'])->first()
                ?? throw new RuntimeException("Falta la habilidad [{$skillDefinition['name']}]; ejecuta primero CategoriaHabilidadSeeder.");

            $weights[$skill->id] = ['weight' => $skillDefinition['weight']];

            $items = [
                [QuestionType::SelfReport, QuestionDifficulty::Basic, $skillDefinition['likert'][0], []],
                [QuestionType::SelfReport, QuestionDifficulty::Basic, $skillDefinition['likert'][1], []],
                [QuestionType::SituationalJudgment, QuestionDifficulty::Intermediate, $skillDefinition['scenario'], $skillDefinition['options']],
            ];

            foreach ($items as $itemIndex => [$type, $difficulty, $text, $options]) {
                $question = Question::withTrashed()->firstOrNew([
                    'code' => sprintf('%s-S%dQ%d', $definition['code'], $skillIndex + 1, $itemIndex + 1),
                ]);

                $question->fill([
                    'company_id' => null,
                    'assessment_id' => $assessment->id,
                    'skill_id' => $skill->id,
                    'organizational_axis_id' => $axisId,
                    'type' => $type,
                    'difficulty' => $difficulty,
                    'text' => $text,
                    'is_active' => true,
                    'display_order' => ++$displayOrder,
                ])->forceFill(['deleted_at' => null])->save();

                foreach ($options as $optionIndex => [$optionText, $score]) {
                    $question->options()->updateOrCreate(
                        ['label' => chr(65 + $optionIndex)],
                        ['text' => $optionText, 'score' => $score, 'display_order' => $optionIndex + 1],
                    );
                }
            }
        }

        $assessment->skills()->sync($weights);
    }
}
