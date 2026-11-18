<?php

namespace Database\Seeders;

use App\Models\OrganizationalAxis;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Carga las 4 categorías y las 16 habilidades del modelo de diagnóstico en 3 ejes.
 *
 * Idempotente: actualiza por código (categorías) y por nombre (habilidades globales).
 */
class CategoriaHabilidadSeeder extends Seeder
{
    /** @var array<string, string> Código de categoría => nombre (CR, TC, CA, DD). */
    private const CATEGORIES = [
        'communication' => 'Comunicativas y relacionales',
        'collaboration' => 'Trabajo colaborativo',
        'cognitive' => 'Cognitivas y de autogestión',
        'leadership' => 'Dirección y desarrollo',
    ];

    /** @var array<string, array{0: string, 1: string}> Habilidad => [código de categoría, código de eje]. */
    private const SKILLS = [
        'Trabajo en equipo' => ['collaboration', 'operational'],
        'Comunicación operativa entre turnos y equipos' => ['communication', 'operational'],
        'Gestión del tiempo' => ['cognitive', 'operational'],
        'Resolución de problemas inmediatos' => ['cognitive', 'operational'],
        'Adaptabilidad ante cambios en la asignación de tareas' => ['cognitive', 'operational'],
        'Orientación al cliente' => ['communication', 'mission'],
        'Empatía' => ['communication', 'mission'],
        'Comunicación asertiva con partes interesadas' => ['communication', 'mission'],
        'Colaboración interfuncional entre áreas' => ['collaboration', 'mission'],
        'Compromiso con los estándares de calidad' => ['cognitive', 'mission'],
        'Liderazgo' => ['leadership', 'strategic'],
        'Toma de decisiones basada en datos' => ['leadership', 'strategic'],
        'Pensamiento estratégico' => ['leadership', 'strategic'],
        'Negociación' => ['communication', 'strategic'],
        'Gestión del cambio' => ['leadership', 'strategic'],
        'Inteligencia emocional en la conducción de equipos' => ['leadership', 'strategic'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $code => $name) {
            SkillCategory::query()->updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        $categories = SkillCategory::query()->pluck('id', 'code');
        $axes = OrganizationalAxis::query()->pluck('id', 'code');

        foreach (self::SKILLS as $name => [$categoryCode, $axisCode]) {
            $axisId = $axes[$axisCode] ?? throw new RuntimeException("Falta el eje [{$axisCode}]; ejecuta primero DatabaseSeeder.");

            $skill = Skill::withTrashed()->firstOrNew(['company_id' => null, 'name' => $name]);
            $skill->fill([
                'skill_category_id' => $categories[$categoryCode],
                'target_level' => $skill->target_level ?? 3,
                'is_active' => true,
            ])->forceFill(['deleted_at' => null])->save();

            $skill->axes()->sync([$axisId]);
        }
    }
}
