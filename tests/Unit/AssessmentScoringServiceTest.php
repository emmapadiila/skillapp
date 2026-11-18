<?php

namespace Tests\Unit;

use App\Enums\ResultLevel;
use App\Services\AssessmentScoringService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AssessmentScoringServiceTest extends TestCase
{
    public function test_a_skill_is_the_average_of_its_items_normalized_to_0_100(): void
    {
        $scores = (new AssessmentScoringService)->score([
            ['skill_id' => 1, 'category_id' => 10, 'score' => 4],
            ['skill_id' => 1, 'category_id' => 10, 'score' => 5],
            ['skill_id' => 1, 'category_id' => 10, 'score' => 3],
        ], []);

        // Promedio 4 → (4 − 1) / 4 × 100 = 75.
        $this->assertSame([1 => 75.0], $scores['skills']);
        $this->assertSame([10 => 75.0], $scores['categories']);
        $this->assertSame(75.0, $scores['total']);
    }

    public function test_the_scale_ends_map_to_0_and_100(): void
    {
        $service = new AssessmentScoringService;

        $this->assertSame(0.0, $service->normalize(1));
        $this->assertSame(50.0, $service->normalize(3));
        $this->assertSame(100.0, $service->normalize(5));
    }

    public function test_category_and_total_use_the_skill_weights(): void
    {
        $scores = (new AssessmentScoringService)->score([
            ['skill_id' => 1, 'category_id' => 10, 'score' => 5],
            ['skill_id' => 2, 'category_id' => 10, 'score' => 1],
            ['skill_id' => 3, 'category_id' => 20, 'score' => 3],
        ], [1 => 1.5, 2 => 1.0, 3 => 1.5]);

        // Categoría 10: (100 × 1.5 + 0 × 1) / 2.5 = 60.
        $this->assertSame(60.0, $scores['categories'][10]);
        $this->assertSame(50.0, $scores['categories'][20]);
        // Total: (100 × 1.5 + 0 × 1 + 50 × 1.5) / 4 = 56.25.
        $this->assertSame(56.25, $scores['total']);
    }

    public function test_skills_without_a_configured_weight_count_as_one(): void
    {
        $scores = (new AssessmentScoringService)->score([
            ['skill_id' => 1, 'category_id' => 10, 'score' => 5],
            ['skill_id' => 2, 'category_id' => 10, 'score' => 1],
        ], []);

        $this->assertSame(50.0, $scores['total']);
    }

    public function test_the_explanation_says_what_raised_and_lowered_a_skill(): void
    {
        $service = new AssessmentScoringService;

        $high = $service->explain([['type' => 'likert', 'score' => 4], ['type' => 'likert', 'score' => 5], ['type' => 'situational', 'score' => 5]]);
        $this->assertSame([
            'En las afirmaciones te describiste con frecuencia de esta forma (promedio 4,5 de 5).',
            'En la situación planteada elegiste la respuesta más efectiva.',
        ], $high['strengths']);
        $this->assertSame([], $high['opportunities']);

        $low = $service->explain([['type' => 'likert', 'score' => 2], ['type' => 'likert', 'score' => 3], ['type' => 'situational', 'score' => 1]]);
        $this->assertSame([], $low['strengths']);
        $this->assertSame([
            'En las afirmaciones te ubicaste por debajo del punto medio (promedio 2,5 de 5).',
            'En la situación elegiste una respuesta poco efectiva para lo que pedía el caso.',
        ], $low['opportunities']);

        $middle = $service->explain([['type' => 'likert', 'score' => 3], ['type' => 'situational', 'score' => 3]]);
        $this->assertCount(2, $middle['opportunities']);
    }

    #[DataProvider('levelBoundaries')]
    public function test_levels_follow_the_agreed_thresholds(float $score, ResultLevel $expected): void
    {
        $this->assertSame($expected, ResultLevel::fromScore($score));
    }

    /**
     * @return array<string, array{0: float, 1: ResultLevel}>
     */
    public static function levelBoundaries(): array
    {
        return [
            'cero' => [0, ResultLevel::ToDevelop],
            'justo debajo de 50' => [49.99, ResultLevel::ToDevelop],
            '50' => [50, ResultLevel::Developing],
            '74.5 no queda en un hueco' => [74.5, ResultLevel::Developing],
            '75' => [75, ResultLevel::Strength],
            '100' => [100, ResultLevel::Strength],
        ];
    }
}
