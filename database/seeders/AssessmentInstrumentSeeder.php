<?php

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\OrganizationalAxis;
use App\Models\Question;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Instala las tres pruebas exploratorias de habilidades blandas.
 *
 * Son instrumentos de demostración académica: sirven para recorrer el flujo
 * completo (asignar, responder, calificar y diagnosticar), no como pruebas
 * psicométricas validadas. El seeder es idempotente: puede ejecutarse varias
 * veces sin duplicar habilidades, preguntas ni opciones.
 */
class AssessmentInstrumentSeeder extends Seeder
{
    public function run(): void
    {
        $axes = OrganizationalAxis::query()->pluck('id', 'code')->all();
        $categories = SkillCategory::query()->pluck('id', 'code')->all();

        if ($axes === [] || $categories === []) {
            throw new RuntimeException('Ejecuta primero DatabaseSeeder para cargar los ejes y las categorías.');
        }

        foreach ($this->instruments() as $instrument) {
            $axisId = $axes[$instrument['axis']] ?? null;

            if ($axisId === null) {
                throw new RuntimeException("Falta el eje organizacional [{$instrument['axis']}].");
            }

            $assessment = Assessment::query()->updateOrCreate(
                ['code' => $instrument['code']],
                [
                    'name' => $instrument['name'],
                    'description' => $instrument['description'],
                    'organizational_axis_id' => $axisId,
                    'is_active' => true,
                ],
            );

            $displayOrder = 0;

            foreach ($instrument['skills'] as $skillIndex => $skillDefinition) {
                $categoryId = $categories[$skillDefinition['category']] ?? null;

                if ($categoryId === null) {
                    throw new RuntimeException("Falta la categoría [{$skillDefinition['category']}].");
                }

                $skill = Skill::withTrashed()->firstOrNew([
                    'company_id' => null,
                    'name' => $skillDefinition['name'],
                ]);

                $skill->fill([
                    'skill_category_id' => $categoryId,
                    'description' => $skillDefinition['description'] ?? null,
                    'target_level' => $skillDefinition['target_level'] ?? 3,
                    'is_active' => true,
                ])->forceFill(['deleted_at' => null])->save();

                $skill->axes()->sync([$axisId]);

                foreach ($skillDefinition['questions'] as $questionIndex => $questionDefinition) {
                    $displayOrder++;

                    $question = Question::withTrashed()->firstOrNew([
                        'code' => sprintf('%s-S%dQ%d', $instrument['code'], $skillIndex + 1, $questionIndex + 1),
                    ]);

                    $question->fill([
                        'company_id' => null,
                        'assessment_id' => $assessment->id,
                        'skill_id' => $skill->id,
                        'organizational_axis_id' => $axisId,
                        'type' => QuestionType::from($questionDefinition['type']),
                        'difficulty' => QuestionDifficulty::from($questionDefinition['difficulty']),
                        'text' => $questionDefinition['text'],
                        'is_active' => true,
                        'display_order' => $displayOrder,
                    ])->forceFill(['deleted_at' => null])->save();

                    $question->options()->delete();

                    foreach ($questionDefinition['options'] ?? [] as $index => $option) {
                        $question->options()->create([
                            'label' => $option['label'],
                            'text' => $option['text'],
                            'score' => $option['score'],
                            'display_order' => $index + 1,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function instruments(): array
    {
        return [
            $this->operativeInstrument(),
            $this->missionalInstrument(),
            $this->strategicInstrument(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function operativeInstrument(): array
    {
        return [
            'code' => 'OPERATIVE_SKILLS_V1',
            'name' => 'Prueba de habilidades blandas — Eje operativo',
            'description' => 'Para colaboradores de producción, apoyo logístico y equipos de ejecución diaria. '
                .'Explora el trabajo en equipo, la comunicación entre turnos, la gestión del tiempo, la resolución '
                .'de problemas y la adaptabilidad. Prueba de demostración académica; su resultado es formativo.',
            'axis' => 'operational',
            'skills' => [
                $this->skill(
                    'Trabajo en equipo', 'collaboration',
                    'Coopera con sus compañeros de línea para cumplir la meta del turno.',
                    [
                        'Cuando un compañero de mi línea se atrasa, le ofrezco ayuda sin que me lo pida.',
                        'Cumplo la parte del trabajo que me corresponde para que el equipo alcance la meta del turno.',
                    ],
                    'Faltan dos horas para terminar el turno y la meta está en riesgo porque la estación siguiente no da abasto. Tu estación va al día. ¿Qué haces?',
                    [
                        ['Aviso al supervisor y, con su visto bueno, apoyo la estación atrasada sin descuidar la mía.', 5],
                        ['Sigo con mi trabajo; cada uno responde por su estación.', 1],
                        ['Le digo a mi compañero que se apure para no afectar la meta.', 2],
                        ['Lo ayudo por mi cuenta, aunque tenga que dejar sola mi estación.', 3],
                    ],
                ),
                $this->skill(
                    'Comunicación operativa', 'communication',
                    'Transmite novedades, incidentes y pendientes con claridad entre turnos y equipos.',
                    [
                        'Al entregar el turno, informo las novedades de la máquina a quien me recibe.',
                        'Cuando no entiendo una instrucción, pregunto antes de empezar la tarea.',
                    ],
                    'Durante tu turno una máquina falló y quedó funcionando con un arreglo temporal. Ya vas a entregar el turno. ¿Qué haces?',
                    [
                        ['Explico la falla y el arreglo temporal a quien me recibe y lo dejo anotado en la bitácora de entrega.', 5],
                        ['No digo nada porque la máquina ya está funcionando.', 1],
                        ['Lo comento de pasada con algún compañero del siguiente turno.', 2],
                        ['Lo anoto en la bitácora, pero no lo hablo con quien me recibe.', 3],
                    ],
                ),
                $this->skill(
                    'Gestión del tiempo', 'cognitive',
                    'Prioriza y organiza las tareas del turno para cumplirlas a tiempo.',
                    [
                        'Organizo mis tareas del turno para terminar primero las más importantes.',
                        'Llego a mi puesto con tiempo suficiente para iniciar la operación a la hora indicada.',
                    ],
                    'Al iniciar el turno te asignan tres tareas: alistar material para un pedido urgente, limpiar tu área y reponer etiquetas que se acabarán a media mañana. ¿Cómo te organizas?',
                    [
                        ['Empiezo por el pedido urgente, repongo las etiquetas antes de que se acaben y hago la limpieza en un momento de menos carga.', 5],
                        ['Hago primero la limpieza porque es lo más rápido.', 2],
                        ['Hago las tareas en el orden en que me las dieron.', 3],
                        ['Espero a que el supervisor me diga qué hacer en cada momento.', 1],
                    ],
                ),
                $this->skill(
                    'Resolución de problemas inmediatos', 'cognitive',
                    'Identifica y atiende los problemas que surgen en su puesto durante la operación.',
                    [
                        'Cuando surge un problema en mi puesto, busco la causa antes de aplicar una solución.',
                        'Si no puedo resolver un problema por mi cuenta, lo reporto de inmediato a quien corresponde.',
                    ],
                    'Notas que varios productos salen de la máquina con un defecto que antes no aparecía. ¿Qué haces?',
                    [
                        ['Separo el producto defectuoso, reviso los ajustes que conozco y, si no encuentro la causa, aviso al supervisor o a mantenimiento.', 5],
                        ['Sigo produciendo y aviso al final del turno.', 1],
                        ['Detengo la máquina y espero a que pase el supervisor para que la revise.', 3],
                        ['Muevo los ajustes de la máquina a prueba y error hasta que el defecto desaparezca.', 2],
                    ],
                ),
                $this->skill(
                    'Adaptabilidad', 'cognitive',
                    'Se ajusta a los cambios en la asignación de tareas sin perder calidad ni seguridad.',
                    [
                        'Cuando me cambian de puesto o de tarea, me adapto rápido a la nueva actividad.',
                        'Mantengo la calidad de mi trabajo aunque cambien las prioridades del día.',
                    ],
                    'A mitad del turno te trasladan a otra línea porque falta personal, y nunca has operado esa máquina. ¿Qué haces?',
                    [
                        ['Acepto el cambio, pido una explicación al operario o al supervisor y repaso las normas de seguridad antes de empezar.', 5],
                        ['Me niego porque no es mi puesto habitual.', 1],
                        ['Acepto, pero empiezo a operar sin preguntar para no quitarle tiempo a nadie.', 2],
                        ['Acepto a regañadientes y le pido al supervisor que sea solo por hoy.', 3],
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function missionalInstrument(): array
    {
        return [
            'code' => 'MISSIONAL_SKILLS_V1',
            'name' => 'Prueba de habilidades blandas — Eje misional',
            'description' => 'Para colaboradores ligados a la relación con clientes y a los estándares de calidad del producto. '
                .'Explora la orientación al cliente, la empatía, la comunicación asertiva, la colaboración entre áreas '
                .'y el compromiso con la calidad. Prueba de demostración académica; su resultado es formativo.',
            'axis' => 'mission',
            'skills' => [
                $this->skill(
                    'Orientación al cliente', 'communication',
                    'Entiende y atiende las necesidades del cliente con soluciones concretas.',
                    [
                        'Tengo presente que el producto que fabrico o despacho debe cumplir lo que el cliente espera.',
                        'Cuando un cliente presenta una queja, busco entender qué necesita antes de responder.',
                    ],
                    'Un cliente llama molesto porque su pedido llegó con menos unidades de las facturadas. ¿Qué haces?',
                    [
                        ['Lo escucho, verifico el pedido en el sistema, le doy una solución con fecha concreta y le hago seguimiento.', 5],
                        ['Le digo que el problema seguramente fue de la transportadora.', 1],
                        ['Le pido que envíe un correo para revisarlo cuando haya tiempo.', 2],
                        ['Le ofrezco disculpas y le prometo que se resolverá, sin verificar todavía qué pasó.', 3],
                    ],
                ),
                $this->skill(
                    'Empatía', 'communication',
                    'Comprende la situación y las emociones de compañeros y clientes antes de actuar.',
                    [
                        'Antes de juzgar la reacción de alguien, intento entender cómo se siente.',
                        'Escucho a mis compañeros y clientes sin interrumpirlos.',
                    ],
                    'Un compañero de otra área, normalmente cumplido, lleva varios días entregando tarde la información que necesitas y se le ve preocupado. ¿Qué haces?',
                    [
                        ['Busco un momento para hablar con él en privado, le pregunto cómo está y acordamos cómo organizar las entregas.', 5],
                        ['Me quejo con su jefe para que lo corrija.', 2],
                        ['Hago su parte yo mismo sin decirle nada.', 3],
                        ['Le reclamo frente a todos para que entienda que está afectando mi trabajo.', 1],
                    ],
                ),
                $this->skill(
                    'Comunicación asertiva con partes interesadas', 'communication',
                    'Expresa posiciones y límites con claridad y respeto ante clientes, proveedores y otras áreas.',
                    [
                        'Expreso mi desacuerdo con respeto, sin quedarme callado.',
                        'Informo los retrasos a clientes o proveedores en cuanto los conozco.',
                    ],
                    'Un cliente importante exige adelantar una entrega a una fecha que la planta no puede cumplir. ¿Cómo respondes?',
                    [
                        ['Le explico con respeto la capacidad real, le propongo una entrega parcial o una fecha alcanzable y confirmo el acuerdo por escrito.', 5],
                        ['Acepto la fecha para no perder al cliente y luego veo cómo se cumple.', 1],
                        ['Le digo que no es posible y termino la conversación.', 2],
                        ['Le pido que hable directamente con el área de producción.', 3],
                    ],
                ),
                $this->skill(
                    'Colaboración interfuncional', 'collaboration',
                    'Trabaja con otras áreas para resolver problemas que ninguna puede resolver sola.',
                    [
                        'Comparto con otras áreas la información que necesitan para hacer bien su trabajo.',
                        'Cuando un problema involucra a varias áreas, participo en la búsqueda de una solución conjunta.',
                    ],
                    'Calidad rechazó un lote y Producción dice que el problema viene de la materia prima que compró otra área. ¿Qué haces?',
                    [
                        ['Propongo una reunión corta entre Calidad, Producción y Compras para revisar los datos del lote y acordar acciones.', 5],
                        ['Defiendo a mi área y dejo que las demás lo resuelvan.', 1],
                        ['Escalo el problema a gerencia para que decida quién tiene la culpa.', 2],
                        ['Reviso solo lo que le corresponde a mi área y lo informo por correo.', 3],
                    ],
                ),
                $this->skill(
                    'Compromiso con los estándares de calidad', 'cognitive',
                    'Cumple los procedimientos de calidad aun bajo presión de tiempo.',
                    [
                        'Sigo los procedimientos de calidad aunque nadie me esté supervisando.',
                        'Reporto los productos no conformes que detecto, aunque eso retrase la entrega.',
                    ],
                    'Para cumplir una entrega urgente, te sugieren despachar un lote al que le falta la inspección final de calidad. ¿Qué haces?',
                    [
                        ['No lo despacho sin inspección; aviso del posible retraso y pido que se priorice la inspección.', 5],
                        ['Lo despacho porque el cliente necesita el producto.', 1],
                        ['Despacho una parte sin inspeccionar y dejo el resto para revisión.', 2],
                        ['Hago una revisión rápida por mi cuenta y lo despacho.', 3],
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function strategicInstrument(): array
    {
        return [
            'code' => 'STRATEGIC_SKILLS_V1',
            'name' => 'Prueba de habilidades blandas — Eje estratégico',
            'description' => 'Para gerencia y cargos de dirección y planeación. Explora el liderazgo, la toma de decisiones '
                .'basada en datos, el pensamiento estratégico, la negociación, la gestión del cambio y la inteligencia '
                .'emocional. Prueba de demostración académica; su resultado es formativo.',
            'axis' => 'strategic',
            'skills' => [
                $this->skill(
                    'Liderazgo', 'leadership',
                    'Orienta y motiva al equipo hacia las metas, dando sentido a lo que se pide.',
                    [
                        'Explico a mi equipo el porqué de las metas, no solo lo que deben hacer.',
                        'Reconozco públicamente los logros de las personas de mi equipo.',
                    ],
                    'La planta lleva dos meses sin cumplir la meta de producción y el ánimo de los supervisores está bajo. ¿Qué haces?',
                    [
                        ['Me reúno con ellos, revisamos juntos las causas, acordamos metas intermedias y hago seguimiento semanal.', 5],
                        ['Les advierto que habrá sanciones si no se cumple la meta el próximo mes.', 1],
                        ['Bajo la meta para que sea más fácil de cumplir.', 2],
                        ['Asumo personalmente la supervisión de las líneas.', 3],
                    ],
                ),
                $this->skill(
                    'Toma de decisiones basada en datos', 'leadership',
                    'Contrasta indicadores y evidencia antes de decidir, y explica el criterio usado.',
                    [
                        'Antes de tomar una decisión importante, reviso los indicadores disponibles.',
                        'Cuando los datos contradicen mi opinión, estoy dispuesto a cambiar mi decisión.',
                    ],
                    'Debes decidir si compras una segunda máquina para una línea. El jefe de producción insiste en que es urgente, pero no hay datos de uso de la máquina actual. ¿Qué haces?',
                    [
                        ['Pido datos de utilización, paradas y demanda proyectada, y decido con esa información en un plazo definido.', 5],
                        ['La compro porque el jefe de producción conoce mejor la línea.', 2],
                        ['Aplazo la decisión hasta tener certeza total.', 1],
                        ['Comparo precios de proveedores y compro la más económica.', 3],
                    ],
                ),
                $this->skill(
                    'Pensamiento estratégico', 'leadership',
                    'Anticipa escenarios y conecta las decisiones actuales con los objetivos de largo plazo.',
                    [
                        'Pienso en cómo las decisiones de hoy afectarán a la empresa en los próximos años.',
                        'Sigo los cambios del mercado que pueden afectar a la empresa.',
                    ],
                    'Un cliente que representa el 40 % de las ventas anuncia que reducirá sus pedidos el próximo año. ¿Qué haces?',
                    [
                        ['Analizo el impacto, defino con el equipo directivo un plan para diversificar clientes y ajusto la planeación de producción.', 5],
                        ['Reduzco personal de inmediato para bajar costos.', 2],
                        ['Espero a ver si el cliente realmente reduce sus pedidos.', 1],
                        ['Le ofrezco descuentos al cliente para que mantenga sus pedidos.', 3],
                    ],
                ),
                $this->skill(
                    'Negociación', 'communication',
                    'Busca acuerdos sostenibles que protejan los intereses de la empresa y de la otra parte.',
                    [
                        'En una negociación, busco acuerdos que beneficien a ambas partes.',
                        'Antes de negociar, defino hasta dónde puedo ceder.',
                    ],
                    'El principal proveedor de materia prima anuncia un aumento del 15 % en el precio. ¿Cómo lo manejas?',
                    [
                        ['Reviso el historial de compras y las alternativas del mercado, y le propongo al proveedor un acuerdo de volumen o plazo que nos sirva a ambos.', 5],
                        ['Acepto el aumento para no arriesgar el suministro.', 2],
                        ['Termino la relación y busco otro proveedor de inmediato.', 1],
                        ['Pido un descuento sin llevar una propuesta concreta.', 3],
                    ],
                ),
                $this->skill(
                    'Gestión del cambio', 'leadership',
                    'Acompaña la transformación, gestiona la resistencia y ajusta el plan con evidencia.',
                    [
                        'Cuando lidero un cambio, explico a las personas por qué es necesario.',
                        'Escucho las preocupaciones de las personas antes de implementar un cambio.',
                    ],
                    'La empresa implementará un nuevo sistema de control de producción y los supervisores con más antigüedad se resisten. ¿Cómo lo abordas?',
                    [
                        ['Escucho sus razones, los involucro en un piloto, programo capacitación y acompaño la transición por etapas.', 5],
                        ['Les comunico que el cambio es obligatorio desde la próxima semana.', 2],
                        ['Aplazo el sistema hasta que todos estén de acuerdo.', 3],
                        ['Implemento el sistema solo con los supervisores nuevos y dejo de lado a los demás.', 1],
                    ],
                ),
                $this->skill(
                    'Inteligencia emocional', 'leadership',
                    'Reconoce y regula sus emociones y las del equipo al conducirlo.',
                    [
                        'Reconozco cuándo mis emociones pueden afectar la forma en que trato a mi equipo.',
                        'En momentos de presión, mantengo la calma al dar instrucciones.',
                    ],
                    'En una reunión, un jefe de área te responde de forma agresiva frente a otros por una decisión que tomaste. ¿Qué haces?',
                    [
                        ['Mantengo la calma, reconozco su inconformidad y le propongo hablar en privado después de la reunión.', 5],
                        ['Le respondo en el mismo tono para dejar clara mi autoridad.', 1],
                        ['Ignoro el comentario y sigo con la reunión.', 3],
                        ['Termino la reunión de inmediato.', 2],
                    ],
                ),
            ],
        ];
    }

    /**
     * Cada habilidad se mide con dos ítems Likert (1–5) y un ítem de juicio situacional de 4 opciones.
     *
     * @param  array{0: string, 1: string}  $selfReports
     * @param  array<int, array{0: string, 1: int}>  $options  [texto, puntaje 1–5]
     * @return array<string, mixed>
     */
    private function skill(string $name, string $category, string $description, array $selfReports, string $scenario, array $options): array
    {
        return [
            'name' => $name,
            'category' => $category,
            'target_level' => 4,
            'description' => $description,
            'questions' => [
                ['type' => 'self_report', 'difficulty' => 'basic', 'text' => $selfReports[0]],
                ['type' => 'self_report', 'difficulty' => 'intermediate', 'text' => $selfReports[1]],
                [
                    'type' => 'situational_judgment',
                    'difficulty' => 'advanced',
                    'text' => $scenario,
                    'options' => array_map(
                        fn (array $option, string $label): array => ['label' => $label, 'text' => $option[0], 'score' => $option[1]],
                        $options,
                        ['A', 'B', 'C', 'D'],
                    ),
                ],
            ],
        ];
    }
}
