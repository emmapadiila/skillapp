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
