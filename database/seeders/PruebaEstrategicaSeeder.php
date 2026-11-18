<?php

namespace Database\Seeders;

/**
 * Prueba 3 — Eje estratégico (contenido literal del anexo de la monografía).
 */
class PruebaEstrategicaSeeder extends PruebaSeeder
{
    protected function definition(): array
    {
        return [
            'code' => 'STRATEGIC_SKILLS_V1',
            'name' => 'Prueba de habilidades blandas — Eje estratégico',
            'axis' => 'strategic',
            'description' => 'Mayor peso: dirección y desarrollo.',
            'axis_description' => 'Nivel de dirección: planeación, toma de decisiones de largo plazo y gestión del cambio, donde las habilidades blandas determinan la capacidad de anticipación y liderazgo frente al entorno. En Gracol: gerencia y cargos de dirección responsables de la planeación.',
            'indicators' => [
                'Capacidad de anticipación frente a cambios del entorno reportada por los líderes.',
                'Alineación percibida entre los equipos y la dirección estratégica.',
                'Grado en que las decisiones de la alta dirección incorporan la información de los ejes operativo y misional.',
            ],
            'skills' => [
                [
                    'name' => 'Liderazgo',
                    'weight' => 1.5,
                    'likert' => [
                        'Comunico a mi equipo los objetivos del área y cómo aporta cada persona a cumplirlos.',
                        'Delego responsabilidades y hago seguimiento sin hacer yo el trabajo de los demás.',
                    ],
                    'scenario' => 'Tu equipo está desmotivado porque no alcanzó la meta del trimestre. ¿Qué haces?',
                    'options' => [
                        ['Les recuerdo que la meta es obligatoria y aumento la supervisión.', 2],
                        ['Reúno al equipo, analizamos juntos las causas, acordamos acciones concretas y reconozco los avances que sí hubo.', 5],
                        ['Espero a que el próximo trimestre mejore solo.', 1],
                        ['Hablo con cada persona por separado para pedirle más esfuerzo.', 3],
                    ],
                ],
                [
                    'name' => 'Toma de decisiones basada en datos',
                    'weight' => 1.5,
                    'likert' => [
                        'Antes de tomar una decisión importante, reviso los indicadores y datos disponibles.',
                        'Cuando los datos contradicen mi intuición, reconsidero mi postura.',
                    ],
                    'scenario' => 'Debes decidir si la empresa invierte en una segunda línea de producción. ¿Cómo procedes?',
                    'options' => [
                        ['Decido según mi experiencia; conozco bien el negocio.', 2],
                        ['Analizo la demanda proyectada, la capacidad actual, los costos y el retorno, consulto a las áreas involucradas y luego decido.', 5],
                        ['Hago lo mismo que está haciendo la competencia.', 1],
                        ['Le pido a un asesor externo que decida por mí.', 3],
                    ],
                ],
                [
                    'name' => 'Pensamiento estratégico',
                    'weight' => 1.5,
                    'likert' => [
                        'Anticipo cómo los cambios del mercado o del sector pueden afectar a la empresa.',
                        'Relaciono las decisiones del día a día con los objetivos de largo plazo de la empresa.',
                    ],
                    'scenario' => 'Un proveedor clave de materia prima anuncia que subirá sus precios un 20 % el próximo año. ¿Qué haces?',
                    'options' => [
                        ['Acepto el aumento y lo traslado al precio de venta.', 2],
                        ['Espero a ver si de verdad sube los precios.', 1],
                        ['Evalúo proveedores alternativos, negocio con el actual, analizo el impacto en costos y preparo escenarios para la junta directiva.', 5],
                        ['Cambio de inmediato al proveedor más barato que encuentre.', 3],
                    ],
                ],
                [
                    'name' => 'Negociación',
                    'weight' => 1.0,
                    'likert' => [
                        'En una negociación busco acuerdos en los que ambas partes ganen.',
                        'Preparo mis argumentos y mi margen de negociación antes de reunirme con la otra parte.',
                    ],
                    'scenario' => 'Un cliente grande exige un descuento del 15 % para renovar su contrato. ¿Qué haces?',
                    'options' => [
                        ['Acepto para no perderlo.', 2],
                        ['Me mantengo en el precio y le explico el valor que recibe.', 3],
                        ['Analizo el margen y le propongo un descuento menor a cambio de más volumen o de un contrato a más largo plazo.', 5],
                        ['Dejo que el área comercial lo resuelva sola.', 1],
                    ],
                ],
                [
                    'name' => 'Gestión del cambio',
                    'weight' => 1.5,
                    'likert' => [
                        'Explico a los equipos por qué se hace un cambio antes de implementarlo.',
                        'Escucho y atiendo las resistencias del personal frente a un cambio.',
                    ],
                    'scenario' => 'La empresa va a implementar un nuevo sistema de control de producción y varios supervisores se resisten. ¿Qué haces?',
                    'options' => [
                        ['Lo implemento igual; tendrán que adaptarse.', 2],
                        ['Aplazo la implementación hasta que todos estén de acuerdo.', 1],
                        ['Explico los beneficios, involucro a los supervisores en un piloto, doy capacitación y ajusto según sus aportes.', 5],
                        ['Contrato a alguien externo para que se encargue del cambio.', 3],
                    ],
                ],
                [
                    'name' => 'Inteligencia emocional en la conducción de equipos',
                    'weight' => 1.0,
                    'likert' => [
                        'Mantengo la calma y el respeto en situaciones de alta presión.',
                        'Reconozco cómo mis emociones influyen en la forma en que dirijo a mi equipo.',
                    ],
                    'scenario' => 'En una reunión, un jefe de área te cuestiona con dureza delante de todos. ¿Qué haces?',
                    'options' => [
                        ['Le respondo en el mismo tono para no perder autoridad.', 1],
                        ['Mantengo la calma, reconozco su punto, propongo revisarlo con datos y hablo con él en privado después.', 5],
                        ['Le digo que lo hablamos después y sigo con la agenda.', 3],
                        ['Termino la reunión antes de tiempo.', 2],
                    ],
                ],
            ],
        ];
    }
}
