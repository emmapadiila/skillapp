<?php

namespace Database\Seeders;

/**
 * Prueba 2 — Eje misional (contenido literal del anexo de la monografía).
 */
class PruebaMisionalSeeder extends PruebaSeeder
{
    protected function definition(): array
    {
        return [
            'code' => 'MISSIONAL_SKILLS_V1',
            'name' => 'Prueba de habilidades blandas — Eje misional',
            'axis' => 'mission',
            'description' => 'Mayor peso: comunicativas y relacionales.',
            'axis_description' => 'Nivel intermedio, ligado a la razón de ser de la organización: los procesos que generan el producto o servicio que se entrega al cliente y la articulación entre las áreas de esa cadena de valor. En Gracol: personal responsable de la relación con los clientes y del cumplimiento de los estándares de calidad.',
            'indicators' => [
                'Calidad percibida de la articulación entre áreas del proceso central.',
                'Cumplimiento de los estándares de interacción con el cliente interno o externo.',
                'Consistencia entre el resultado de la evaluación y los indicadores de calidad del proceso misional.',
            ],
            'skills' => [
                [
                    'name' => 'Orientación al cliente',
                    'weight' => 1.5,
                    'likert' => [
                        'Antes de entregar un pedido, verifico que cumpla exactamente lo que pidió el cliente.',
                        'Después de una entrega, hago seguimiento para saber si el cliente quedó satisfecho.',
                    ],
                    'scenario' => 'Un cliente llama molesto porque su pedido llegó incompleto. ¿Qué haces?',
                    'options' => [
                        ['Le explico que el error fue de despacho y que se comunique con esa área.', 1],
                        ['Escucho el reclamo, verifico el pedido, le doy una fecha concreta para completar el faltante y le hago seguimiento hasta que lo reciba.', 5],
                        ['Le pido disculpas y le digo que lo vamos a revisar.', 3],
                        ['Le ofrezco un descuento de inmediato sin revisar qué pasó.', 2],
                    ],
                ],
                [
                    'name' => 'Empatía',
                    'weight' => 1.5,
                    'likert' => [
                        'Trato de entender la situación de la otra persona antes de responder a un reclamo.',
                        'Noto cuando un compañero o un cliente está molesto o preocupado, aunque no lo diga.',
                    ],
                    'scenario' => 'Un compañero que siempre es puntual lleva varios días llegando tarde y se le ve preocupado. ¿Qué haces?',
                    'options' => [
                        ['Lo reporto de inmediato al jefe.', 2],
                        ['No me meto; no es asunto mío.', 1],
                        ['Le pregunto en privado si está bien y si puedo ayudarle en algo del trabajo.', 5],
                        ['Cubro sus tareas sin decirle nada.', 3],
                    ],
                ],
                [
                    'name' => 'Comunicación asertiva con partes interesadas',
                    'weight' => 1.5,
                    'likert' => [
                        'Expreso mis desacuerdos con respeto y con argumentos, sin atacar a la otra persona.',
                        'Cuando debo decir "no" a una solicitud de un cliente o de otra área, explico las razones y ofrezco alternativas.',
                    ],
                    'scenario' => 'Un cliente importante pide adelantar su entrega una semana, pero producción no tiene capacidad. ¿Qué le respondes?',
                    'options' => [
                        ['Le digo que sí para no perderlo y después veo cómo se resuelve.', 1],
                        ['Le digo que no es posible y cierro el tema.', 2],
                        ['Le explico la capacidad real, le propongo una entrega parcial en la fecha que pide y el resto en la fecha original, y lo confirmo con producción.', 5],
                        ['Le digo que lo voy a intentar, sin comprometerme a nada.', 3],
                    ],
                ],
                [
                    'name' => 'Colaboración interfuncional entre áreas',
                    'weight' => 1.5,
                    'likert' => [
                        'Coordino con otras áreas (producción, despacho, compras) para que los pedidos salgan a tiempo.',
                        'Comparto con otras áreas información útil aunque no me la pidan.',
                    ],
                    'scenario' => 'Ventas le prometió a un cliente un pedido especial y producción dice que nadie le avisó. ¿Qué haces?',
                    'options' => [
                        ['Dejo claro que la culpa es de ventas por no avisar.', 1],
                        ['Propongo una reunión corta entre ventas y producción para acordar una fecha posible y definir cómo se avisarán los pedidos especiales en adelante.', 5],
                        ['Le pido a producción que lo priorice como pueda.', 3],
                        ['Le aviso al cliente que habrá retraso sin hablar con las áreas.', 2],
                    ],
                ],
                [
                    'name' => 'Compromiso con los estándares de calidad',
                    'weight' => 1.0,
                    'likert' => [
                        'Sigo los procedimientos de calidad aunque nadie me esté supervisando.',
                        'Reporto los errores, incluso los míos, para que se corrijan a tiempo.',
                    ],
                    'scenario' => 'Te das cuenta de que un lote ya despachado pudo salir sin completar la prueba de calidad. ¿Qué haces?',
                    'options' => [
                        ['No digo nada; si hay un problema, el cliente avisará.', 1],
                        ['Informo de inmediato a mi jefe y a calidad para evaluar si hay que contactar al cliente.', 5],
                        ['Reviso con más cuidado los lotes siguientes, pero no reporto el anterior.', 2],
                        ['Lo comento con un compañero para ver qué opina.', 3],
                    ],
                ],
            ],
        ];
    }
}
