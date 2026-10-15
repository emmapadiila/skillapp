<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Assessment;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\OrganizationalAxis;
use App\Models\Position;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\AssessmentInstrumentSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SoftSkillsAssessmentTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private const AXIS_BY_CODE = [
        'OPERATIVE_SKILLS_V1' => 'operational',
        'MISSIONAL_SKILLS_V1' => 'mission',
        'STRATEGIC_SKILLS_V1' => 'strategic',
    ];

    /** @var array<string, User> */
    private array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test-key');

        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_seeder_installs_three_instruments_with_nine_questions_each(): void
    {
        $this->assertSame(3, Assessment::count());
        $this->assertSame(9, Skill::count());
        $this->assertSame(27, Question::count());
        $this->assertSame(72, QuestionOption::count());

        foreach (Assessment::with('questions')->get() as $assessment) {
            $this->assertCount(9, $assessment->questions, "La prueba {$assessment->code} debe tener 9 preguntas.");
        }
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(AssessmentInstrumentSeeder::class);
        $this->seed(AssessmentInstrumentSeeder::class);

        $this->assertSame(3, Assessment::count());
        $this->assertSame(27, Question::count());
        $this->assertSame(9, Skill::count());
        $this->assertSame(72, QuestionOption::count());
    }

    public function test_each_instrument_covers_its_axis_three_skills_and_all_question_types(): void
    {
        $response = $this->actingAsApi($this->humanResources())->getJson('/api/v1/assessments');
        $response->assertOk()->assertJsonCount(3, 'data.data');

        foreach (Assessment::with('questions')->get() as $assessment) {
            $axisCode = OrganizationalAxis::findOrFail($assessment->organizational_axis_id)->code;

            $this->assertSame(self::AXIS_BY_CODE[$assessment->code], $axisCode);
            $this->assertCount(3, $assessment->questions->pluck('skill_id')->unique());

            foreach (QuestionType::cases() as $type) {
                $this->assertCount(
                    3,
                    $assessment->questions->where('type', $type),
                    "La prueba {$assessment->code} debe tener 3 preguntas de tipo {$type->value}.",
                );
            }

            $difficulties = $assessment->questions->pluck('difficulty')->map->value->unique();
            $this->assertEqualsCanonicalizing(['basic', 'intermediate', 'advanced'], $difficulties->all());
        }
    }

    public function test_an_human_resources_user_assigns_an_instrument_and_the_collaborator_completes_it(): void
    {
        $company = Company::factory()->create();
        $axis = OrganizationalAxis::where('code', 'operational')->firstOrFail();
        $hr = $this->user($company, UserRole::HumanResources);
        $collaborator = $this->user($company, UserRole::Collaborator);
        $assessment = Assessment::where('code', 'OPERATIVE_SKILLS_V1')->firstOrFail();

        $this->positionFor($collaborator, $axis, $assessment, requiredLevel: 4);

        $created = $this->actingAsApi($hr)->postJson('/api/v1/evaluations', [
            'user_id' => $collaborator->id,
            'type' => 'initial',
            'assessment_id' => $assessment->id,
            'due_at' => now()->addWeek()->toIso8601String(),
        ]);

        $created->assertCreated();

        $evaluation = Evaluation::findOrFail($created->json('data.id'));

        $this->assertSame($assessment->id, $evaluation->assessment_id);
        $this->assertSame(9, $evaluation->question_count);
        $this->assertSame($assessment->organizational_axis_id, $collaborator->refresh()->position->organizational_axis_id);

        $this->answerAll($collaborator, $evaluation, likert: 5, optionScore: 5);

        $completed = $this->actingAsApi($collaborator)
            ->postJson("/api/v1/evaluations/{$evaluation->id}/complete");

        $completed->assertOk();
        $this->assertSame(5.0, (float) $completed->json('data.total_score'));
        $this->assertSame('outstanding', $completed->json('data.level'));
        $this->assertCount(3, $completed->json('data.skill_results'));
        $this->assertCount(3, $completed->json('data.category_results'));
        $this->assertSame('completed', $evaluation->refresh()->status->value);
    }

    public function test_an_instrument_cannot_be_assigned_to_a_collaborator_of_a_different_axis(): void
    {
        $company = Company::factory()->create();
        $axis = OrganizationalAxis::where('code', 'strategic')->firstOrFail();
        $hr = $this->user($company, UserRole::HumanResources);
        $collaborator = $this->user($company, UserRole::Collaborator);
        $assessment = Assessment::where('code', 'OPERATIVE_SKILLS_V1')->firstOrFail();

        $this->positionFor($collaborator, $axis, $assessment, requiredLevel: 4);

        $this->actingAsApi($hr)->postJson('/api/v1/evaluations', [
            'user_id' => $collaborator->id,
            'type' => 'initial',
            'assessment_id' => $assessment->id,
            'due_at' => now()->addWeek()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors('assessment_id');
    }

    public function test_assigning_an_evaluation_requires_exactly_one_question_source(): void
    {
        $company = Company::factory()->create();
        $hr = $this->user($company, UserRole::HumanResources);
        $collaborator = $this->user($company, UserRole::Collaborator);
        $assessment = Assessment::where('code', 'OPERATIVE_SKILLS_V1')->firstOrFail();

        $this->actingAsApi($hr)->postJson('/api/v1/evaluations', [
            'user_id' => $collaborator->id,
            'type' => 'initial',
            'due_at' => now()->addWeek()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['question_count']);

        $this->actingAsApi($hr)->postJson('/api/v1/evaluations', [
            'user_id' => $collaborator->id,
            'type' => 'initial',
            'assessment_id' => $assessment->id,
            'question_count' => 5,
            'due_at' => now()->addWeek()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['assessment_id']);
    }

    public function test_a_collaborator_cannot_answer_another_collaborators_evaluation(): void
    {
        $company = Company::factory()->create();
        $axis = OrganizationalAxis::where('code', 'operational')->firstOrFail();
        $hr = $this->user($company, UserRole::HumanResources);
        $collaborator = $this->user($company, UserRole::Collaborator);
        $intruder = $this->user($company, UserRole::Collaborator);
        $assessment = Assessment::where('code', 'OPERATIVE_SKILLS_V1')->firstOrFail();

        $this->positionFor($collaborator, $axis, $assessment, requiredLevel: 4);

        $evaluation = $this->assign($hr, $collaborator, $assessment);
        $item = $evaluation->questions()->firstOrFail();

        $this->actingAsApi($intruder)->postJson("/api/v1/evaluations/{$evaluation->id}/responses", [
            'evaluation_question_id' => $item->id,
            'likert_value' => 5,
        ])->assertForbidden();
    }

    public function test_the_organizational_diagnostic_consumes_the_instrument_results(): void
    {
        $company = Company::factory()->create();
        $axis = OrganizationalAxis::where('code', 'operational')->firstOrFail();
        $hr = $this->user($company, UserRole::HumanResources);
        $collaborator = $this->user($company, UserRole::Collaborator);
        $assessment = Assessment::where('code', 'OPERATIVE_SKILLS_V1')->firstOrFail();

        $this->positionFor($collaborator, $axis, $assessment, requiredLevel: 5);

        $evaluation = $this->assign($hr, $collaborator, $assessment);
        $this->answerAll($collaborator, $evaluation, likert: 3, optionScore: 3);
        $this->actingAsApi($collaborator)->postJson("/api/v1/evaluations/{$evaluation->id}/complete")->assertOk();

        $today = now()->toDateString();
        $created = $this->actingAsApi($hr)->postJson('/api/v1/diagnostics', [
            'period_start' => $today,
            'period_end' => $today,
        ]);

        $created->assertCreated();
        $this->assertSame(1, $created->json('data.total_evaluated'));
        $this->assertCount(3, $created->json('data.matrix_cells'));
        $this->assertCount(3, $created->json('data.gaps'));
        $this->assertSame('critical', $created->json('data.gaps.0.priority'));
    }

    public function test_a_company_cannot_read_another_companys_evaluations(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $hrA = $this->user($companyA, UserRole::HumanResources);
        $collaboratorB = $this->user($companyB, UserRole::Collaborator);

        $foreign = Evaluation::create([
            'company_id' => $companyB->id,
            'user_id' => $collaboratorB->id,
            'type' => 'initial',
            'status' => 'pending',
            'question_count' => 9,
            'assigned_at' => now(),
            'due_at' => now()->addWeek(),
        ]);

        // Las evaluaciones responden 403 (no 404) porque la autorización la resuelve la policy.
        $this->actingAsApi($hrA)->getJson("/api/v1/evaluations/{$foreign->id}")->assertForbidden();
        $this->actingAsApi($hrA)->getJson('/api/v1/evaluations')->assertOk()->assertJsonCount(0, 'data.data');
    }

    private function user(Company $company, UserRole $role): User
    {
        return User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
            'supabase_user_id' => (string) Str::uuid(),
        ]);
    }

    private function humanResources(): User
    {
        return $this->user(Company::factory()->create(), UserRole::HumanResources);
    }

    private function actingAsApi(User $user): static
    {
        $token = 'token-'.$user->id;
        $this->tokens[$token] = $user;

        Http::fake([
            'https://supabase.test/auth/v1/user' => function ($request) {
                $bearer = (string) ($request->header('Authorization')[0] ?? '');
                $user = $this->tokens[str_replace('Bearer ', '', $bearer)] ?? null;

                return $user === null
                    ? Http::response(['message' => 'invalid token'], 401)
                    : Http::response(['id' => $user->supabase_user_id, 'email' => $user->email]);
            },
        ]);

        return $this->withToken($token);
    }

    private function positionFor(User $collaborator, OrganizationalAxis $axis, Assessment $assessment, int $requiredLevel): Position
    {
        $area = Area::create([
            'company_id' => $collaborator->company_id,
            'name' => 'Área '.$collaborator->id,
            'is_active' => true,
        ]);

        $position = Position::create([
            'company_id' => $collaborator->company_id,
            'area_id' => $area->id,
            'organizational_axis_id' => $axis->id,
            'name' => 'Cargo '.$collaborator->id,
            'is_active' => true,
        ]);

        $position->skills()->sync(
            $assessment->questions
                ->pluck('skill_id')
                ->unique()
                ->mapWithKeys(fn (int $skillId): array => [$skillId => ['weight' => 10, 'required_level' => $requiredLevel]])
                ->all()
        );

        $collaborator->update(['position_id' => $position->id]);

        return $position;
    }

    private function assign(User $actor, User $collaborator, Assessment $assessment): Evaluation
    {
        $response = $this->actingAsApi($actor)->postJson('/api/v1/evaluations', [
            'user_id' => $collaborator->id,
            'type' => 'initial',
            'assessment_id' => $assessment->id,
            'due_at' => now()->addWeek()->toIso8601String(),
        ]);

        $response->assertCreated();

        return Evaluation::findOrFail($response->json('data.id'));
    }

    private function answerAll(User $collaborator, Evaluation $evaluation, int $likert, int $optionScore): void
    {
        $client = $this->actingAsApi($collaborator);

        foreach ($evaluation->questions()->with('question.options')->get() as $item) {
            $payload = $item->question->type === QuestionType::SelfReport
                ? ['evaluation_question_id' => $item->id, 'likert_value' => $likert]
                : ['evaluation_question_id' => $item->id, 'question_option_id' => $item->question->options->firstWhere('score', $optionScore)->id];

            $response = $client->postJson("/api/v1/evaluations/{$evaluation->id}/responses", $payload);

            $this->assertSame(200, $response->status(), $response->json('message') ?? $response->getContent());
        }
    }
}
