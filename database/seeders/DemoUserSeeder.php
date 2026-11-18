<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Company;
use App\Models\OrganizationalAxis;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Crea una empresa demo con un administrador (Talento Humano) y un colaborador del eje operativo.
 *
 * Es idempotente: busca por NIT, nombre y correo, así que puede ejecutarse varias veces.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['nit' => '900000001-1'],
            ['name' => 'Empresa Demo Manufacturera', 'sector' => 'Manufactura', 'is_active' => true],
        );

        $productionArea = Area::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Producción'],
            ['is_active' => true],
        );

        // El eje del colaborador sale de su cargo: así el sistema le asigna la prueba operativa.
        $operatorPosition = Position::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Operario de producción'],
            [
                'area_id' => $productionArea->id,
                'organizational_axis_id' => OrganizationalAxis::query()->where('code', 'operational')->value('id')
                    ?? throw new RuntimeException('Ejecuta primero DatabaseSeeder para cargar los ejes.'),
                'description' => 'Opera las máquinas de la línea de producción.',
                'is_active' => true,
            ],
        );

        $humanResourcesArea = Area::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Talento Humano'],
            ['is_active' => true],
        );

        // El administrador no presenta prueba; su cargo se ubica en el eje estratégico por ser de gestión.
        $managerPosition = Position::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Gestor de Talento Humano'],
            [
                'area_id' => $humanResourcesArea->id,
                'organizational_axis_id' => OrganizationalAxis::query()->where('code', 'strategic')->value('id'),
                'description' => 'Administra las evaluaciones y consulta los tableros de diagnóstico.',
                'is_active' => true,
            ],
        );

        $this->user('admin@skillapp.test', 'Administrador', UserRole::HumanResources, $company, $managerPosition, 'SEED_ADMIN_PASSWORD', consented: false);
        $this->user('usuario@skillapp.test', 'Usuario Demo', UserRole::Collaborator, $company, $operatorPosition, 'SEED_USER_PASSWORD', consented: true);
    }

    private function user(string $email, string $name, UserRole $role, Company $company, ?Position $position, string $passwordVariable, bool $consented): void
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        [$firstName, $lastName] = explode(' ', $name.' ', 2);

        $user->forceFill([
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => trim($lastName) ?: null,
            'role' => $role,
            'company_id' => $company->id,
            'position_id' => $position?->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make($this->password($passwordVariable)),
            'consent_accepted_at' => $consented ? ($user->consent_accepted_at ?? now()) : $user->consent_accepted_at,
            'deleted_at' => null,
        ])->save();
    }

    private function password(string $variable): string
    {
        // shortcut: env() devuelve null si la configuración está en caché; entonces aplica el valor por defecto local o falla.
        $password = env($variable);

        if (is_string($password) && $password !== '') {
            return $password;
        }

        if (app()->environment('local', 'testing')) {
            return 'password';
        }

        throw new RuntimeException("Define {$variable} en el .env antes de ejecutar este seeder.");
    }
}
