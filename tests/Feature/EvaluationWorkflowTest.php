<?php

namespace Tests\Feature;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Question;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\EvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EvaluationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_evaluation_can_be_assigned_answered_and_completed_once(): void
    {
        $company = Company::factory()->create();
        $category = SkillCategory::query()->create(['code' => 'communication', 'name' => 'Comunicativas']);
        $skill = Skill::query()->create(['company_id' => $company->id, 'skill_category_id' => $category->id, 'name' => 'Escucha activa']);
        $question = Question::query()->create([
            'company_id' => $company->id,
            'skill_id' => $skill->id,
            'type' => QuestionType::SelfReport,
            'difficulty' => QuestionDifficulty::Basic,
            'text' => 'Escucho con atención antes de responder.',
        ]);
        $hr = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::HumanResources]);
        $collaborator = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Collaborator]);
        $service = app(EvaluationService::class);

        $evaluation = $service->assign($hr, [
            'user_id' => $collaborator->id,
            'type' => EvaluationType::Initial->value,
            'question_ids' => [$question->id],
            'due_at' => now()->addDay(),
        ]);

        $service->respond($evaluation, $collaborator, [
            'evaluation_question_id' => $evaluation->questions->first()->id,
            'likert_value' => 5,
        ]);
        $result = $service->complete($evaluation);

        $this->assertSame('5.00', $result->total_score);
        $this->assertSame('outstanding', $result->level);
        $this->assertSame(EvaluationStatus::Completed, $evaluation->refresh()->status);
        $this->assertCount(1, $result->skillResults);
        $this->assertCount(1, $result->categoryResults);

        $this->expectException(LogicException::class);
        $result->update(['level' => 'tampered']);
    }
}
