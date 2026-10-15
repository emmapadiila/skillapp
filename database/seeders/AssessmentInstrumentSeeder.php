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
            'name' => 'Prueba de habilidades operativas (demostración)',
            'description' => 'Instrumento exploratorio para colaboradores de producción, logística y procesos de apoyo. '
                .'Explora la comunicación durante el turno, el trabajo en equipo y la gestión del tiempo. '
                .'Es una prueba de demostración académica pendiente de validación; su resultado es formativo.',
            'axis' => 'operational',
            'skills' => [
                [
                    'name' => 'Comunicación operativa',
                    'category' => 'communication',
                    'target_level' => 4,
                    'description' => 'Transmite novedades, incidentes y pendientes con claridad y oportunidad durante la operación.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'basic',
                            'text' => 'Cuando identifico una novedad que puede afectar a otros compañeros, la comunico de forma oportuna y clara.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'A pocos minutos de terminar tu turno detectas una incidencia que podría detener el proceso. ¿Cuál es la forma más adecuada de comunicarla?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Termino mi turno y la informo al día siguiente.', 'score' => 1],
                                ['label' => 'B', 'text' => 'La reporto de inmediato al responsable del turno entrante y dejo registro escrito.', 'score' => 5],
                                ['label' => 'C', 'text' => 'Intento resolverla solo y solo aviso si no lo logro.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Le comento el caso a un compañero para que él decida si se reporta.', 'score' => 2],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'Debes informar al equipo un cambio en la secuencia de producción que reduce el tiempo disponible de una tarea. ¿Qué haces primero?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Reúno al equipo, explico el motivo del cambio y las nuevas prioridades.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Publico el cambio en el tablero sin explicar el motivo.', 'score' => 3],
                                ['label' => 'C', 'text' => 'Informo solo a quien me pregunte por qué cambió la tarea.', 'score' => 2],
                                ['label' => 'D', 'text' => 'Espero a que el cambio llegue por escrito antes de decir algo.', 'score' => 1],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Trabajo en equipo',
                    'category' => 'collaboration',
                    'target_level' => 4,
                    'description' => 'Apoya a los compañeros y sostiene la coordinación del grupo frente a dificultades del proceso.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'basic',
                            'text' => 'Colaboro con mis compañeros cuando una tarea del área se retrasa, aunque no sea mi responsabilidad directa.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'Un compañero no alcanza a terminar su parte y tu turno está por cerrar con la meta cumplida. ¿Cómo actúas?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Termino mi tarea y me retiro: su parte es su responsabilidad.', 'score' => 1],
                                ['label' => 'B', 'text' => 'Le ofrezco ayuda concreta y coordino con el responsable para redistribuir la carga.', 'score' => 5],
                                ['label' => 'C', 'text' => 'Aviso del retraso al responsable y continúo con lo mío.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Hago su parte sin avisarle para que el proceso no se detenga.', 'score' => 2],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'intermediate',
                            'text' => 'Dos compañeros discuten frente al equipo por la asignación de una tarea. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Escucho a ambos, separo el problema de las personas y propongo una salida concreta.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Les pido que lo resuelvan entre ellos cuando termine el turno.', 'score' => 3],
                                ['label' => 'C', 'text' => 'Le doy la razón al de mayor experiencia para cerrar la discusión.', 'score' => 2],
                                ['label' => 'D', 'text' => 'Ignoro la discusión para no tomar partido.', 'score' => 1],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Gestión del tiempo',
                    'category' => 'cognitive',
                    'target_level' => 4,
                    'description' => 'Prioriza tareas según impacto y urgencia, y sostiene los compromisos del turno.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'intermediate',
                            'text' => 'Organizo mis tareas por prioridad antes de comenzar la jornada.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'advanced',
                            'text' => 'Al iniciar el turno tienes tres pendientes y tiempo para completar solo dos. ¿Qué criterio aplicas?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Empiezo por la que me resulta más cómoda de hacer.', 'score' => 2],
                                ['label' => 'B', 'text' => 'Evalúo el impacto y la urgencia de cada una y priorizo la más crítica.', 'score' => 5],
                                ['label' => 'C', 'text' => 'Avanzo las tres al mismo tiempo para no dejar ninguna atrás.', 'score' => 1],
                                ['label' => 'D', 'text' => 'Pregunto a un compañero cuál haría él y sigo esa recomendación.', 'score' => 3],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'Un imprevisto consume la mitad de tu turno y aún tienes tareas comprometidas. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Reprogramo lo pendiente con el responsable según el impacto y comunico el nuevo plan.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Acelero todo para cumplir, aunque deba saltar verificaciones.', 'score' => 1],
                                ['label' => 'C', 'text' => 'Cumplo solo las tareas fáciles y dejo el resto para el día siguiente.', 'score' => 2],
                                ['label' => 'D', 'text' => 'Reporto al final del turno que no fue posible cumplir con todo.', 'score' => 3],
                            ],
                        ],
                    ],
                ],
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
            'name' => 'Prueba de habilidades misionales (demostración)',
            'description' => 'Instrumento exploratorio para colaboradores de procesos centrales de valor, relación '
                .'con clientes y articulación entre áreas. Explora la comunicación asertiva, la empatía con el '
                .'cliente y la colaboración interfuncional. Es una prueba de demostración académica pendiente '
                .'de validación; su resultado es formativo.',
            'axis' => 'mission',
            'skills' => [
                [
                    'name' => 'Comunicación asertiva',
                    'category' => 'communication',
                    'target_level' => 4,
                    'description' => 'Expresa acuerdos y desacuerdos con claridad y respeto, y sostiene compromisos realistas.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'basic',
                            'text' => 'Expreso mis desacuerdos con respeto y sin dejar de decir lo que pienso.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'Un cliente insiste en un cambio que no es posible cumplir en el plazo que pide. ¿Cómo se lo comunicas?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Le digo que es imposible y cierro la conversación.', 'score' => 1],
                                ['label' => 'B', 'text' => 'Le explico el alcance real, ofrezco alternativas y confirmo un nuevo plazo.', 'score' => 5],
                                ['label' => 'C', 'text' => 'Le prometo el cambio para no incomodarlo y después reviso cómo cumplirlo.', 'score' => 2],
                                ['label' => 'D', 'text' => 'Evito responder hasta tener una respuesta definitiva.', 'score' => 3],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'En una reunión entre áreas tu propuesta es criticada duramente frente a todos. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Escucho, pregunto en qué se basa la crítica y respondo con argumentos.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Defiendo mi propuesta subiendo el tono para no perder credibilidad.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Retiro la propuesta para evitar el conflicto.', 'score' => 1],
                                ['label' => 'D', 'text' => 'Pido continuar la discusión en privado al terminar la reunión.', 'score' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Empatía y orientación al cliente',
                    'category' => 'collaboration',
                    'target_level' => 4,
                    'description' => 'Comprende la necesidad del cliente y sostiene una respuesta honesta y oportuna ante inconformidades.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'basic',
                            'text' => 'Escucho completo el reclamo de un cliente antes de proponer una solución.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'Un cliente reporta un retraso y está molesto. Necesitas coordinar la respuesta con otra área. ¿Qué haces primero?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Reconozco su molestia, confirmo los datos del pedido y averiguo el estado real con el área responsable.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Le explico que el retraso no depende de mi área.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Le ofrezco una compensación sin consultarlo con nadie.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Le pido que vuelva a escribir más tarde.', 'score' => 1],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'Varios clientes reportan el mismo problema y el área técnica todavía no encuentra la causa. ¿Cómo procedes?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Informo un estado real, dejo un canal de seguimiento y presiono internamente con evidencia.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Espero a que el área técnica resuelva para no dar información equivocada.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Respondo a cada cliente con mensajes distintos según su insistencia.', 'score' => 1],
                                ['label' => 'D', 'text' => 'Escalo el caso a la dirección y dejo de responder consultas.', 'score' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Colaboración interfuncional',
                    'category' => 'collaboration',
                    'target_level' => 4,
                    'description' => 'Coordina con otras áreas, comparte información y resuelve desacuerdos en función del servicio.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'intermediate',
                            'text' => 'Cuando una tarea depende de otra área, mantengo informados a quienes la necesitan.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'Tu área y otra dependencia manejan información distinta sobre un compromiso con un cliente. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Acuerdo con ambas partes una versión única del dato y la dejo registrada.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Defiendo la información de mi área porque es la correcta.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Pido a la dirección que decida cuál dato se debe usar.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Dejo que cada área responda con su propia información.', 'score' => 1],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'Otra área incumple un acuerdo y eso afecta la calidad del servicio. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Hablo con su responsable, muestro el impacto y propongo un ajuste conjunto.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Reporto el incumplimiento a la dirección sin avisar al área.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Asumo el trabajo del área para no afectar al cliente, sin comentarlo.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Documento el incumplimiento y espero a que se repita.', 'score' => 1],
                            ],
                        ],
                    ],
                ],
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
            'name' => 'Prueba de habilidades estratégicas (demostración)',
            'description' => 'Instrumento exploratorio para responsables de dirección, planificación y toma de '
                .'decisiones. Explora el liderazgo, la decisión basada en información y la gestión del cambio. '
                .'Es una prueba de demostración académica pendiente de validación; su resultado es formativo.',
            'axis' => 'strategic',
            'skills' => [
                [
                    'name' => 'Liderazgo',
                    'category' => 'leadership',
                    'target_level' => 5,
                    'description' => 'Acompaña al equipo, gestiona conflictos y decide con criterios explicables.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'basic',
                            'text' => 'Acompaño a mi equipo cuando enfrenta dificultades, en lugar de limitarme a señalar errores.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'Dos integrantes del equipo mantienen un conflicto que afecta los resultados. ¿Cómo lo abordas?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Hablo por separado con cada uno, identifico el origen y acordamos compromisos concretos.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Cambio a uno de ellos de equipo para evitar el conflicto.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Espero a que el conflicto se resuelva solo con el tiempo.', 'score' => 1],
                                ['label' => 'D', 'text' => 'Les pido que dejen sus diferencias fuera del trabajo.', 'score' => 3],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'Debes asignar un proyecto clave a alguien del equipo. ¿En qué te basas?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Evalúo competencias, carga actual y oportunidad de desarrollo, y explico la decisión.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Se lo asigno a la persona con más antigüedad para no generar molestias.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Se lo asigno a quien tenga más disponibilidad en el momento.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Asumo yo el proyecto para asegurar el resultado.', 'score' => 1],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Toma de decisiones basada en información',
                    'category' => 'cognitive',
                    'target_level' => 5,
                    'description' => 'Contrasta datos y evidencia antes de decidir, y explica el criterio usado.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'basic',
                            'text' => 'Antes de decidir reviso los datos disponibles en lugar de guiarme solo por la intuición.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'Debes elegir entre dos proveedores y solo tienes la recomendación verbal de un colega. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Comparo costos, tiempos y antecedentes de ambos con datos verificables antes de decidir.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Elijo el que recomendó el colega porque lo conoce.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Elijo el más económico sin revisar su desempeño.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Pospongo la decisión hasta que otra persona la tome.', 'score' => 1],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'Los indicadores muestran una caída, pero la causa no es evidente. ¿Cómo procedes?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Formulo hipótesis, las contrasto con datos de varias fuentes y decido con evidencia.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Tomo la primera explicación disponible y actúo de inmediato.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Espero al siguiente informe para ver si la caída se mantiene.', 'score' => 3],
                                ['label' => 'D', 'text' => 'Cambio el proceso completo para cubrir todas las posibilidades.', 'score' => 1],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Gestión del cambio',
                    'category' => 'leadership',
                    'target_level' => 5,
                    'description' => 'Acompaña la transformación, gestiona la resistencia y ajusta el plan con evidencia.',
                    'questions' => [
                        [
                            'type' => 'self_report',
                            'difficulty' => 'intermediate',
                            'text' => 'Cuando un cambio afecta mi trabajo, busco entender su propósito antes de oponerme.',
                        ],
                        [
                            'type' => 'situational',
                            'difficulty' => 'intermediate',
                            'text' => 'La organización implementará un nuevo proceso y parte del equipo se resiste. ¿Cómo abordas la situación?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Escucho las razones de la resistencia, explico el propósito y acompaño la transición por etapas.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Comunico que el cambio es obligatorio y que debe aplicarse desde mañana.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Implemento el cambio solo con quienes están de acuerdo.', 'score' => 1],
                                ['label' => 'D', 'text' => 'Propongo posponer el cambio hasta que todos estén convencidos.', 'score' => 3],
                            ],
                        ],
                        [
                            'type' => 'situational_judgment',
                            'difficulty' => 'advanced',
                            'text' => 'A mitad de la implementación de un cambio detectas efectos no previstos en un área. ¿Qué haces?',
                            'options' => [
                                ['label' => 'A', 'text' => 'Analizo el impacto con datos, ajusto el plan y comunico el ajuste a los afectados.', 'score' => 5],
                                ['label' => 'B', 'text' => 'Continúo según lo planeado para no perder el avance logrado.', 'score' => 2],
                                ['label' => 'C', 'text' => 'Cancelo el cambio hasta tenerlo todo previsto.', 'score' => 1],
                                ['label' => 'D', 'text' => 'Delego el problema al área afectada para que se adapte.', 'score' => 3],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
