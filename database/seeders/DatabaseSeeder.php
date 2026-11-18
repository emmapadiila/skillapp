<?php

namespace Database\Seeders;

use App\Models\OrganizationalAxis;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        OrganizationalAxis::query()->upsert([
            ['code' => 'operational', 'name' => 'Operativo', 'description' => 'Roles de ejecución y operación.', 'is_active' => true],
            ['code' => 'mission', 'name' => 'Misional', 'description' => 'Roles directamente ligados a la misión empresarial.', 'is_active' => true],
            ['code' => 'strategic', 'name' => 'Estratégico', 'description' => 'Roles de dirección y decisión estratégica.', 'is_active' => true],
        ], ['code'], ['name', 'description', 'is_active']);

        $this->call([
            CategoriaHabilidadSeeder::class,
            PruebaOperativaSeeder::class,
            PruebaMisionalSeeder::class,
            PruebaEstrategicaSeeder::class,
            DemoUserSeeder::class,
        ]);
    }
}
