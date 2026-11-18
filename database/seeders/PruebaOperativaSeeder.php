<?php

namespace Database\Seeders;

/**
 * Prueba 1 — Eje operativo (contenido literal del anexo de la monografía).
 */
class PruebaOperativaSeeder extends PruebaSeeder
{
    protected function definition(): array
    {
        return [
            'code' => 'OPERATIVE_SKILLS_V1',
            'name' => 'Prueba de habilidades blandas — Eje operativo',
            'axis' => 'operational',
            'description' => 'Mayor peso: trabajo colaborativo y cognitivas/autogestión.',
            'axis_description' => 'Nivel de ejecución diaria de tareas: procesos de producción, procesos de apoyo y equipos cuyo desempeño depende de la coordinación inmediata entre sus integrantes. En Gracol: personal de producción y apoyo logístico de la planta.',
            'indicators' => [
                'Nivel de coordinación percibida entre turnos o equipos.',
                'Frecuencia de incidencias atribuibles a fallas de comunicación operativa.',
                'Evolución del clima de equipo en la reevaluación periódica.',
            ],
            'skills' => [
                [
                    'name' => 'Trabajo en equipo',
                    'weight' => 1.5,
                    'likert' => [
                        'Cuando un compañero de mi línea se atrasa, le ayudo a terminar su parte aunque no me lo pidan.',
                        'Comparto con mi equipo los métodos o trucos que me ayudan a trabajar mejor.',
                    ],
                    'scenario' => 'Al final del turno, a tu compañero de línea le faltan 40 unidades por empacar y tú ya cumpliste tu meta. ¿Qué haces?',
                    'options' => [
                        ['Me voy; yo ya cumplí mi parte.', 1],
                        ['Le ayudo a terminar y le aviso al supervisor para que quede registrado.', 5],
                        ['Le digo que se apure porque el equipo va a quedar mal.', 2],
                        ['Le ayudo solo si el supervisor me lo pide.', 3],
                    ],
                ],
                [
                    'name' => 'Comunicación operativa entre turnos y equipos',
                    'weight' => 1.5,
                    'likert' => [
                        'Al entregar mi turno, informo con claridad las novedades, fallas y pendientes de mi puesto.',
                        'Cuando no entiendo una instrucción, pregunto antes de empezar la tarea.',
                    ],
                    'scenario' => 'Durante tu turno la máquina tuvo una falla intermitente que se corrigió sola. Al entregar el turno, ¿qué haces?',
                    'options' => [
                        ['No digo nada, porque ya está funcionando.', 1],
                        ['Se lo comento de pasada al compañero que llega.', 3],
                        ['Lo anoto en la bitácora de entrega y se lo explico al compañero del siguiente turno y al supervisor.', 5],
                        ['Espero a ver si vuelve a fallar antes de reportarlo.', 2],
                    ],
                ],
                [
                    'name' => 'Gestión del tiempo',
                    'weight' => 1.5,
                    'likert' => [
                        'Organizo las tareas de mi turno para cumplir la meta de producción a tiempo.',
                        'Al iniciar el turno tengo listo mi puesto (herramientas, elementos de protección y materiales).',
                    ],
                    'scenario' => 'Son las 11 a. m. y tienes tres tareas: limpiar tu puesto, terminar un pedido urgente que se despacha a las 2 p. m. y llenar el formato de control de calidad. ¿Cómo te organizas?',
                    'options' => [
                        ['Hago primero lo más fácil para salir rápido de eso.', 2],
                        ['Priorizo el pedido urgente, lleno el formato a medida que avanzo y dejo la limpieza para el final.', 5],
                        ['Hago las tareas en el orden en que me las dieron.', 3],
                        ['Espero a que el supervisor me diga qué hacer primero.', 1],
                    ],
                ],
                [
                    'name' => 'Resolución de problemas inmediatos',
                    'weight' => 1.5,
                    'likert' => [
                        'Cuando algo falla en mi puesto, busco la causa antes de seguir trabajando.',
                        'Propongo soluciones prácticas cuando aparece un problema en el proceso.',
                    ],
                    'scenario' => 'Notas que varias piezas están saliendo con un defecto pequeño que antes no tenían. ¿Qué haces?',
                    'options' => [
                        ['Sigo produciendo; si es grave, calidad lo detectará.', 1],
                        ['Separo las piezas defectuosas, reviso la máquina y los parámetros, y reporto al supervisor lo que encontré.', 5],
                        ['Detengo la línea y espero a que llegue mantenimiento.', 3],
                        ['Ajusto la máquina por mi cuenta sin avisar a nadie.', 2],
                    ],
                ],
                [
                    'name' => 'Adaptabilidad ante cambios en la asignación de tareas',
                    'weight' => 1.0,
                    'likert' => [
                        'Me adapto rápido cuando me cambian de puesto o de tarea durante el turno.',
                        'Estoy dispuesto(a) a aprender nuevos procedimientos cuando cambian los métodos de trabajo.',
                    ],
                    'scenario' => 'A mitad del turno el supervisor te pasa a otra línea que conoces poco porque faltó un operario. ¿Qué haces?',
                    'options' => [
                        ['Acepto, pido que me expliquen lo básico y pregunto cuando tengo dudas.', 5],
                        ['Acepto, pero trabajo más lento para que noten que ese no es mi puesto.', 1],
                        ['Le pido que busque a otra persona.', 2],
                        ['Acepto y trato de hacerlo solo, sin preguntar, para no molestar.', 3],
                    ],
                ],
            ],
        ];
    }
}
