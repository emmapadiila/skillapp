<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_admin_and_an_operational_collaborator_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->assertSame(2, User::count());

        $admin = User::where('email', 'admin@aicroom.test')->firstOrFail();
        $collaborator = User::with('position.organizationalAxis')->where('email', 'usuario@aicroom.test')->firstOrFail();

        $this->assertSame(UserRole::HumanResources, $admin->role);
        $this->assertSame(UserRole::Collaborator, $collaborator->role);
        $this->assertSame($admin->company_id, $collaborator->company_id);
        $this->assertSame('Operario de producción', $collaborator->position->name);
        $this->assertSame('operational', $collaborator->position->organizationalAxis->code);
        $this->assertTrue(Hash::check('password', $collaborator->password));
    }
}
