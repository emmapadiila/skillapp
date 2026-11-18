<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\OrganizationalAxis;
use App\Models\Position;
use App\Models\User;
use App\Services\EvaluationService;
use App\Services\LocalTokenService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AxisDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.supabase.enabled', false);
        $this->seed(DatabaseSeeder::class);
        $this->company = Company::factory()->create();
    }

    public function test_the_dashboards_aggregate_results_by_axis_with_matrix_and_vertical_reading(): void
    {
        $operator = $this->completedCollaborator('operational', 'Producción', likert: 5, optionScore: 5);
        $this->completedCollaborator('mission', 'Calidad', likert: 1, optionScore: 1);

        $data = $this->dashboard($this->humanResources())->assertOk()->json('data');
        [$operative, $missional, $strategic] = $data['axes'];

        $this->assertSame(['operational', 'mission', 'strategic'], array_column($data['axes'], 'code'));
        $this->assertSame(1, $operative['evaluated']);
        $this->assertEquals(100, $operative['average']);
        $this->assertSame('strength', $operative['level']);
        $this->assertSame($operator->name, $operative['people'][0]['name']);
        $this->assertSame('Producción', $operative['people'][0]['area']);
        $this->assertCount(5, $operative['skills']);
        $this->assertEquals(1.5, collect($operative['skills'])->firstWhere('name', 'Trabajo en equipo')['weight']);
        $this->assertCount(3, $operative['assessment']['indicators']);

        $this->assertEquals(0, $missional['average']);
        $this->assertEquals(['strength' => 0, 'developing' => 0, 'to_develop' => 1], $missional['distribution']);
        $this->assertSame(0, $strategic['evaluated']);
        $this->assertNull($strategic['average']);

        // 4 categorías × 3 ejes; "Dirección y desarrollo" solo se mide en el eje estratégico.
        $this->assertCount(12, $data['matrix']);
        $leadership = collect($data['matrix'])->where('category_id', 4)->keyBy('axis_code');
        $this->assertFalse($leadership['operational']['measured']);
        $this->assertTrue($leadership['strategic']['measured']);
        $this->assertNull($leadership['strategic']['average']);

        $this->assertSame('Resolución de problemas inmediatos', $data['vertical'][0][0]['name']);
        $this->assertEquals(100, $data['vertical'][0][0]['average']);
        $this->assertEquals(0, $data['vertical'][0][1]['average']);
    }

    public function test_the_dashboards_filter_by_area_position_and_period(): void
    {
        $operator = $this->completedCollaborator('operational', 'Producción', likert: 4, optionScore: 3);
        $this->completedCollaborator('operational', 'Logística', likert: 2, optionScore: 2);
        $hr = $this->humanResources();

        $area = Area::where('company_id', $this->company->id)->where('name', 'Producción')->value('id');
        $this->assertSame(1, $this->dashboard($hr, ['area_id' => $area])->json('data.axes.0.evaluated'));
        $this->assertSame(1, $this->dashboard($hr, ['position_id' => $operator->position_id])->json('data.axes.0.evaluated'));
        $this->assertSame(2, $this->dashboard($hr, ['from' => now()->toDateString()])->json('data.axes.0.evaluated'));
        $this->assertSame(0, $this->dashboard($hr, ['to' => now()->subDay()->toDateString()])->json('data.axes.0.evaluated'));
        $this->dashboard($hr, ['from' => '2026-12-31', 'to' => '2026-01-01'])->assertStatus(422);
    }

    public function test_only_the_latest_result_of_each_person_counts(): void
    {
        $operator = $this->completedCollaborator('operational', 'Producción', likert: 1, optionScore: 1);
        $first = Evaluation::where('user_id', $operator->id)->firstOrFail();
        $first->update(['completed_at' => now()->subMonth()]);

        $reevaluation = app(EvaluationService::class)->assign($this->humanResources(), [
            'user_id' => $operator->id,
            'type' => 'reevaluation',
            'parent_evaluation_id' => $first->id,
            'assessment_id' => $first->assessment_id,
            'due_at' => now()->addWeek(),
        ]);
        $this->answerAndComplete($operator, $reevaluation, likert: 5, optionScore: 5);

        $axis = $this->dashboard($this->humanResources())->json('data.axes.0');

        $this->assertSame(1, $axis['evaluated']);
        $this->assertEquals(100, $axis['average']);
    }

    public function test_collaborators_and_other_companies_cannot_see_the_dashboards(): void
    {
        $this->completedCollaborator('operational', 'Producción', likert: 5, optionScore: 5);
        $collaborator = User::where('role', UserRole::Collaborator)->where('company_id', $this->company->id)->firstOrFail();

        $this->dashboard($collaborator)->assertForbidden();

        $outsider = User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => UserRole::HumanResources]);
        $this->assertSame(0, $this->dashboard($outsider)->json('data.axes.0.evaluated'));
    }

    private function dashboard(User $user, array $query = []): TestResponse
    {
        return $this->withToken(app(LocalTokenService::class)->issue($user)['access_token'])
            ->getJson('/api/v1/dashboards/axes?'.http_build_query($query));
    }

    private function humanResources(): User
    {
        return User::factory()->create(['company_id' => $this->company->id, 'role' => UserRole::HumanResources]);
    }

    private function completedCollaborator(string $axisCode, string $areaName, int $likert, int $optionScore): User
    {
        $area = Area::firstOrCreate(['company_id' => $this->company->id, 'name' => $areaName], ['is_active' => true]);
        $position = Position::create([
            'company_id' => $this->company->id,
            'area_id' => $area->id,
            'organizational_axis_id' => OrganizationalAxis::where('code', $axisCode)->value('id'),
            'name' => "Cargo {$areaName} {$axisCode}",
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $this->company->id, 'position_id' => $position->id, 'role' => UserRole::Collaborator]);

        $this->answerAndComplete($user, app(EvaluationService::class)->startOwn($user), $likert, $optionScore);

        return $user;
    }

    private function answerAndComplete(User $user, Evaluation $evaluation, int $likert, int $optionScore): void
    {
        $service = app(EvaluationService::class);

        foreach ($evaluation->questions()->with('question.options')->get() as $item) {
            $service->respond($evaluation, $user, $item->question->type === QuestionType::SelfReport
                ? ['evaluation_question_id' => $item->id, 'likert_value' => $likert]
                : ['evaluation_question_id' => $item->id, 'question_option_id' => $item->question->options->firstWhere('score', $optionScore)->id]);
        }

        $service->complete($evaluation->refresh());
    }
}
