<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Company;
use App\Models\ImprovementActivity;
use App\Models\ImprovementPlan;
use App\Models\OrganizationalAxis;
use App\Models\Position;
use App\Models\Question;
use App\Models\RecommendedResource;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\DiagnosticService;
use App\Services\EvaluationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrowserWorkspaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_workspace_data_contract_supports_both_roles_and_complete_workflows(): void
    {
        $this->freezeTime();
        config()->set('services.supabase.url', 'https://supabase.test');
        config()->set('services.supabase.anon_key', 'anon-test');
        config()->set('security.rate_limits.api', 1000);
        $company = Company::factory()->create();
        $axis = OrganizationalAxis::factory()->create(['code' => 'operational', 'name' => 'Operativo']);
        $category = SkillCategory::factory()->create(['name' => 'Comunicativas']);
        $area = Area::factory()->for($company)->create();
        $skill = Skill::factory()->for($company)->create(['skill_category_id' => $category->id, 'name' => 'Comunicación de prueba']);
        $skill->axes()->attach($axis);
        $position = Position::factory()->for($company)->for($area)->create(['organizational_axis_id' => $axis->id]);
        $position->skills()->attach($skill, ['weight' => 1, 'required_level' => 5]);
        $admin = User::factory()->for($company)->create(['role' => UserRole::HumanResources, 'supabase_user_id' => (string) Str::uuid(), 'name' => 'Administradora de prueba']);
        $employee = User::factory()->for($company)->for($position)->create(['supabase_user_id' => (string) Str::uuid(), 'first_name' => 'Persona', 'last_name' => 'Prueba', 'name' => 'Persona de prueba']);
        $question = Question::factory()->for($company)->for($skill)->create(['organizational_axis_id' => $axis->id]);
        $service = app(EvaluationService::class);
        $evaluation = $service->assign($admin, ['user_id' => $employee->id, 'type' => 'initial', 'question_count' => 1, 'due_at' => now()->addDays(2)]);
        $service->respond($evaluation, $employee, ['evaluation_question_id' => $evaluation->questions[0]->id, 'likert_value' => 4]);
        $service->complete($evaluation);
        $pending = $service->assign($admin, ['user_id' => $employee->id, 'type' => 'reevaluation', 'parent_evaluation_id' => $evaluation->id, 'question_count' => 1, 'due_at' => now()->addDays(3)]);
        $plan = ImprovementPlan::factory()->for($employee, 'user')->for($skill)->create(['company_id' => $company->id]);
        ImprovementActivity::factory()->for($plan, 'plan')->create(['title' => 'Actividad de prueba']);
        RecommendedResource::factory()->create(['improvement_plan_id' => $plan->id, 'url' => 'https://example.com/learning']);
        $diagnostic = app(DiagnosticService::class)->generate($admin, ['period_start' => today()->toDateString(), 'period_end' => today()->toDateString()]);
        $fixtures = [];
        $currentIdentity = [];
        Http::fake(['https://supabase.test/auth/v1/user' => function () use (&$currentIdentity) {
            return Http::response($currentIdentity);
        }]);

        foreach (['admin' => $admin, 'collaborator' => $employee] as $role => $user) {
            $currentIdentity = ['id' => $user->supabase_user_id, 'email' => $user->email];
            $this->actingAs($user)->withSession(['supabase.access_token' => 'browser-test-token']);
            $html = $this->get(route($role === 'admin' ? 'workspace' : 'personal.workspace'))->assertOk()->getContent();
            $resources = ['me', 'catalogs', 'evaluations', 'improvement-plans', 'notifications', 'evaluations/'.$evaluation->id, 'evaluations/'.$pending->id, 'improvement-plans/'.$plan->id];
            if ($role === 'admin') {
                $resources = array_merge($resources, ['employees', 'positions', 'areas', 'skills', 'questions', 'diagnostics', 'diagnostics/'.$diagnostic->id, 'employees/'.$employee->id, 'skills/'.$skill->id, 'questions/'.$question->id, 'positions/'.$position->id, 'areas/'.$area->id]);
            }
            $responses = [];
            foreach ($resources as $resource) {
                $responses[$resource] = $this->withHeaders(['Authorization' => 'Bearer browser-test-token'])->getJson('/api/v1/'.$resource)->assertOk()->json();
            }
            $fixtures[$role] = ['html' => $html, 'responses' => $responses, 'ids' => ['employee' => $employee->id, 'evaluation' => $pending->id, 'completed' => $evaluation->id, 'plan' => $plan->id]];
        }

        $this->assertSame('4.00', $fixtures['collaborator']['responses']['evaluations/'.$evaluation->id]['data']['result']['total_score']);
        $this->assertSame($employee->id, $fixtures['collaborator']['responses']['me']['data']['id']);
        $this->assertSame(1, $fixtures['admin']['responses']['diagnostics/'.$diagnostic->id]['data']['total_evaluated']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://supabase.test/auth/v1/user');

        if (getenv('WORKSPACE_BROWSER_FIXTURES') === '1') {
            $directory = storage_path('framework/testing/workspace-browser');
            File::ensureDirectoryExists($directory);
            File::put($directory.'/fixtures.json', json_encode($fixtures, JSON_THROW_ON_ERROR));
            File::put($directory.'/login.html', $this->get(route('login'))->assertOk()->getContent());
        }
    }
}
