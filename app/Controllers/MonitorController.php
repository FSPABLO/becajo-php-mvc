<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * Monitor de salud de bases de datos — MAQUETA del frente 4.
 *
 * Pinta las rutas `/monitoreo` del §9 del plan de la parte 2 contra muestras
 * sintéticas (`config/monitor-mockup.php`). No consulta Oracle, no invoca el
 * motor de cálculo y no toca ningún repositorio del monitor: es exactamente
 * lo que el §11 le pide a este frente —«trabaja contra el repositorio de
 * arreglo desde el primer día»— llevado a su forma más simple mientras los
 * frentes 2 y 3 se fusionan.
 *
 * Igual que HerramientasController, aquí no hay aritmética: el controlador
 * pide una muestra YA EVALUADA y se la entrega a la vista. Cuando el módulo
 * se conecte de verdad, cambia de dónde sale el arreglo (public/index.php) y
 * ni este controlador ni las vistas se enteran — que es el mismo trato que
 * CLAUDE.md ya describe para la ficha de «Bases de datos conectadas».
 */
final class MonitorController extends Controlador
{
    public function panel(): void
    {
        $usuario = $this->exigirUsuario();
        $datos   = $this->contenedor->monitor();

        /** @var array<string, array<string, mixed>> $instancias */
        $instancias = $datos['instancias'] ?? [];

        if ($instancias === []) {
            $this->verVacio();

            return;
        }

        /*
         * Qué instancia se está mirando.
         *
         * Lo que llega en la URL se comprueba CONTRA LA LISTA antes de usarlo
         * como clave. Es el mismo razonamiento de auditoriaPropia() frente a
         * /evaluacion/9 y del filtro ?organizacion= del tablero: una clave que
         * viene del cliente no se usa para indexar nada sin validarla. Aquí
         * todavía no hay secreto que proteger porque los datos son de mentira,
         * pero la comprobación se escribe ahora para que no falte después,
         * cuando cada instancia sea la de un cliente distinto.
         */
        $pedida = $this->parametro('instancia');
        $clave  = ($pedida !== null && isset($instancias[$pedida]))
            ? $pedida
            : array_key_first($instancias);

        $seleccionada = $instancias[$clave];

        $this->verPanel('monitoreo/panel', [
            ...$this->contexto(),
            'meta'          => $this->meta(
                'Monitor de salud',
                'Índice de salud de base de datos (ISBD), componentes, procesos '
                . 'evaluados y memoria de las instancias bajo vigilancia.',
            ),
            'usuarioActual' => $usuario,
            'pisoCobertura' => (float) ($datos['piso_cobertura'] ?? 80.0),
            'pesos'         => $datos['pesos'] ?? [],
            'instancias'    => $instancias,
            'seleccionada'  => $seleccionada,
            'procesos'      => $this->procesosPorIndice($datos, $seleccionada),
            /*
             * El guion solo aporta comodidad: alterna las fichas de índice y
             * refresca el gráfico de memoria. Sin él las tres tablas quedan
             * visibles una tras otra y el gráfico se queda quieto en su última
             * lectura — se pierde comodidad, no información.
             */
            'guiones'       => ['assets/js/monitor.js'],
            // Lo que se pidió y no existe: la vista lo dice en vez de callarse
            // y enseñar otra instancia como si fuera la que se pidió.
            'noEncontrada'  => $pedida !== null && !isset($instancias[$pedida]) ? $pedida : null,
        ]);
    }

    /**
     * Cruza el catálogo de procesos con las mediciones de esta muestra.
     *
     * El catálogo dice QUÉ evalúa cada índice (los procesos y con qué métricas);
     * la muestra dice CUÁNTO valió cada métrica. Aquí se juntan las dos cosas,
     * en el controlador y no en la vista, para que la plantilla reciba filas
     * listas de pintar — y sobre todo para que el valor de un proceso no pueda
     * salir de otro sitio que no sea su propia métrica.
     *
     * Tres decisiones que conviene no deshacer:
     *
     * - **El valor es la salud normalizada, no la lectura cruda.** Las métricas
     *   miden cosas distintas —porcentajes, milisegundos, conteos— y ponerlas en
     *   la misma fila sin normalizar invitaría a compararlas entre sí. En 0 a 1
     *   todas dicen lo mismo: cuánto de bien está eso.
     * - **Una compuerta vale 1 o 0**, que es lo único que puede valer: abierta o
     *   cerrada. No tiene banda intermedia y no se promedia con nada.
     * - **Sin dato NO es un fallo.** Un proceso cuyas métricas no se pudieron
     *   recolectar sale con `exitoso => null`, y la tabla lo pinta como «sin
     *   dato», no como una X. Marcarlo en rojo convertiría una falla del agente
     *   en una falla de la base — que es justo lo que prohíbe el invariante 3.
     *
     * Devuelve, por índice, las COLUMNAS de la matriz (los códigos de métrica
     * que evalúa), la FICHA de cada una —nombre y qué mide, para la ayuda de la
     * cabecera— y sus FILAS (un proceso cada una).
     *
     * @param array<string, mixed> $datos
     * @param array<string, mixed> $instancia
     * @return array<string, array{columnas: list<string>, fichas: array<string, array<string, string>>, filas: list<array<string, mixed>>}>
     */
    private function procesosPorIndice(array $datos, array $instancia): array
    {
        $catalogo = $datos['catalogo_procesos'] ?? [];
        $fichas   = $datos['catalogo_metricas'] ?? [];

        // Las mediciones, indexadas por código para no recorrerlas una vez por
        // cada métrica de cada proceso.
        $porCodigo = [];

        foreach ($instancia['mediciones'] ?? [] as $medicion) {
            $porCodigo[$medicion['codigo']] = $medicion;
        }

        $resultado = [];

        foreach ($catalogo as $indice => $procesos) {
            $filas = [];

            /*
             * Las COLUMNAS del índice: todas las métricas que evalúa alguno de
             * sus procesos, sin repetir y ordenadas por código.
             *
             * Se ordenan y no se dejan en el orden en que aparecen en el
             * catálogo porque el orden de aparición depende de qué proceso se
             * declaró primero: añadir un proceso al principio reordenaría toda
             * la cabecera de la tabla sin que nada haya cambiado de fondo.
             */
            $columnas = [];

            foreach ($procesos as $proceso) {
                foreach ($proceso['metricas'] as $codigo) {
                    $columnas[$codigo] = true;
                }
            }

            $columnas = array_keys($columnas);
            sort($columnas);

            /*
             * La ficha de cada columna —nombre y qué mide— viaja junto a la
             * tabla que la usa, y solo la de sus columnas. Pasar el catálogo
             * entero obligaría a la vista a saber cuál le toca, que es
             * justamente lo que ya resolvió al ordenar las columnas.
             */
            $fichasIndice = [];

            foreach ($columnas as $codigo) {
                if (isset($fichas[$codigo])) {
                    $fichasIndice[$codigo] = $fichas[$codigo];
                }
            }

            foreach ($procesos as $proceso) {
                /*
                 * Las métricas van INDEXADAS POR CÓDIGO y no como lista: la
                 * tabla es una matriz y cada fila tiene que poder preguntar
                 * «¿qué vale M-PRO-04 aquí?» sin recorrer nada. Una casilla sin
                 * entrada es una métrica que NO evalúa a ese proceso, que es
                 * distinto de una que sí lo evalúa y no se pudo recolectar.
                 */
                $metricas = [];
                $bandas   = [];

                foreach ($proceso['metricas'] as $codigo) {
                    $medicion = $porCodigo[$codigo] ?? null;

                    if ($medicion === null || ($medicion['estado'] ?? '') !== 'OK') {
                        $metricas[$codigo] = [
                            'valor'  => null,
                            'banda'  => null,
                            'motivo' => $medicion['motivo'] ?? null,
                        ];

                        continue;
                    }

                    $esCompuerta = ($medicion['tipo'] ?? '') === 'compuerta';

                    $valor = $esCompuerta
                        ? (($medicion['compuerta'] ?? '') === 'ABIERTA' ? 1.0 : 0.0)
                        : round(((float) $medicion['s']) / 100, 2);

                    $metricas[$codigo] = [
                        'valor'  => $valor,
                        'banda'  => $medicion['banda'] ?? null,
                        'motivo' => null,
                    ];

                    $bandas[] = $medicion['banda'] ?? null;
                }

                /*
                 * Éxito = ninguna de sus métricas enciende el semáforo. Se
                 * decide sobre la BANDA y no sobre el valor redondeado de la
                 * columna: en las fronteras, 0,75 y 0,7503 caen en bandas
                 * distintas y la columna las enseñaría igual.
                 */
                $exitoso = $bandas === []
                    ? null
                    : !in_array(false, array_map(
                        static fn (?string $banda): bool => semaforo($banda) === 'verde',
                        $bandas,
                    ), true);

                $filas[] = [
                    'nombre'        => $proceso['nombre'],
                    'metricas'      => $metricas,
                    'exitoso'       => $exitoso,
                    'descripcion'   => $proceso['descripcion'],
                    'recomendacion' => $proceso['recomendacion'],
                ];
            }

            $resultado[$indice] = [
                'columnas' => $columnas,
                'fichas'   => $fichasIndice,
                'filas'    => $filas,
            ];
        }

        return $resultado;
    }

    /**
     * Estado vacío: hay módulo, pero ninguna instancia registrada.
     *
     * Existe como método aparte porque la vista principal presupone una
     * instancia seleccionada, y rellenarla con una falsa para poder pintar el
     * vacío es justo lo que produce tableros que enseñan ceros inventados.
     */
    private function verVacio(): void
    {
        $this->verPanel('monitoreo/vacio', [
            ...$this->contexto(),
            'meta' => $this->meta('Monitor de salud'),
        ]);
    }
}
