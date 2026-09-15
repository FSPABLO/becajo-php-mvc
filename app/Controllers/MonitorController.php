<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Core\Facetas;

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
 * Son DOS pantallas, igual que el histórico de auditorías:
 *
 * - `/monitoreo` es la antesala: una ficha por base de datos vigilada, con el
 *   mismo panel de facetas que /evaluacion/comparar. Solo se ELIGE.
 * - `/monitoreo/{clave}` es la consola de operación de UNA instancia.
 *
 * Igual que HerramientasController, aquí no hay aritmética: el controlador
 * pide una muestra YA EVALUADA y se la entrega a la vista. Cuando el módulo
 * se conecte de verdad, cambia de dónde sale el arreglo (public/index.php) y
 * ni este controlador ni las vistas se enteran — que es el mismo trato que
 * CLAUDE.md ya describe para la ficha de «Bases de datos conectadas».
 */
final class MonitorController extends Controlador
{
    /**
     * Órdenes de la rejilla de instancias. El primero es el de por defecto:
     * quien abre un monitor viene a ver qué está mal, no a leer la lista en
     * orden alfabético.
     */
    private const ORDENES = ['atencion', 'indice', 'reciente', 'clave'];

    /**
     * Opciones de los grupos de filtro que son una ESCALA, en el orden en que
     * se ofrecen. Entorno y motor no están: son datos de cada instancia, y se
     * ofrecen por frecuencia.
     */
    private const OPCIONES_FACETA = [
        // De mejor a peor, como las zonas del histórico. 'SIN' no es una banda
        // más: es no tener índice publicado.
        'banda'    => ['OPTIMO', 'SALUDABLE', 'ADVERTENCIA', 'DEGRADADO', 'CRITICO', 'SIN'],
        'conexion' => ['COMPLETA', 'PARCIAL', 'FALLIDA'],
    ];

    /**
     * La ANTESALA: una ficha por base de datos vigilada.
     *
     * Antes `/monitoreo` abría directamente la consola de la primera instancia,
     * y el único índice de la cartera era el desplegable de su cabecera. Con
     * cuatro bases alcanzaba; con veinte, elegir a ciegas en un desplegable no
     * es elegir. Aquí se busca, se filtra y se entra — la misma forma que
     * /evaluacion/comparar, porque es el mismo gesto sobre otro sujeto.
     *
     * Todo sale del mismo arreglo que ya lee la consola: ni una consulta más.
     */
    public function cartera(): void
    {
        $this->exigirUsuario();

        $instancias = array_values($this->contenedor->monitor()['instancias'] ?? []);

        if ($instancias === []) {
            $this->verVacio();

            return;
        }

        // El buscador va ANTES que las facetas, para que sus recuentos hablen
        // de lo que se está mirando. Ver AuditoriaController::comparar().
        $buscar  = $this->peticion()->entrada('buscar');
        $base    = $this->buscarInstancias($instancias, $buscar);
        $facetas = $this->facetasInstancia();

        $seleccion = $facetas->seleccion($this->peticion());

        // Un orden inventado en la URL cae en el de por defecto.
        $orden = in_array($this->peticion()->entrada('orden'), self::ORDENES, true)
            ? (string) $this->peticion()->entrada('orden')
            : self::ORDENES[0];

        $datos = [
            'meta'       => $this->meta(
                'Monitor de salud',
                'Bases de datos bajo vigilancia: índice de salud (ISBD), estado de la '
                . 'última muestra y entorno de cada instancia.',
            ),
            'total'      => count($instancias),
            'instancias' => $this->ordenarInstancias($facetas->filtrar($base, $seleccion), $orden),
            'facetas'    => $facetas->contar($base, $seleccion),
            'seleccion'  => $seleccion,
            'buscar'     => $buscar,
            'orden'      => $orden,
            'ordenes'    => self::ORDENES,
        ];

        /*
         * El guion que refiltra al escribir recibe ESTA MISMA VISTA sin el
         * marco, igual que en /evaluacion/comparar. Los destellos van vacíos:
         * leerlos los consume, y una tecla no puede gastarse un aviso.
         */
        if ($this->peticion()->esAsincrona()) {
            $this->json([
                'html' => $this->contenedor->vista()->renderizar('monitoreo/cartera', [
                    ...$datos,
                    'mensajes' => ['aviso' => null, 'error' => null],
                ]),
            ]);
        }

        $this->verPanel('monitoreo/cartera', [
            ...$this->contexto(),
            ...$datos,
            'guiones' => ['assets/js/facetas.js'],
        ]);
    }

    /** La consola de operación de UNA instancia. */
    public function panel(): void
    {
        $usuario = $this->exigirUsuario();
        $datos   = $this->contenedor->monitor();

        /** @var array<string, array<string, mixed>> $instancias */
        $instancias = $datos['instancias'] ?? [];

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
         *
         * Lo que no casa vuelve a la antesala con un destello, igual que una
         * empresa desconocida en /evaluacion/comparar/{empresa}. Antes se
         * enseñaba la primera de la cartera con un aviso encima, y eso es
         * pintar la consola de una base que nadie pidió: con cuatro instancias
         * de nombre parecido, el aviso se lee tarde y la cifra se lee primero.
         * El destello no repite la clave pedida: es texto de la URL, y
         * devolverlo impreso deja a cualquiera escribir en la pantalla de otro.
         */
        $pedida = (string) $this->parametro('instancia', '');

        if (!isset($instancias[$pedida])) {
            $this->sesion()->destello('error', $this->t('mon.instancia_no_encontrada'));
            $this->redirigir('/monitoreo');
        }

        $seleccionada = $instancias[$pedida];

        $this->verPanel('monitoreo/panel', [
            ...$this->contexto(),
            'meta'          => $this->meta(
                'Monitor de salud · ' . $pedida,
                'Índice de salud de base de datos (ISBD), componentes, procesos '
                . 'evaluados y memoria de las instancias bajo vigilancia.',
            ),
            // Por debajo de «Monitor» la miga la pone el controlador: una
            // instancia no es una sección del menú, es un registro.
            'migaPagina'    => [['etiqueta' => $pedida]],
            'usuarioActual' => $usuario,
            'pisoCobertura' => (float) ($datos['piso_cobertura'] ?? 80.0),
            'pesos'         => $datos['pesos'] ?? [],
            // Para el desplegable de la cabecera, ya en el orden de la
            // antesala por defecto: las dos listas no pueden discrepar.
            'cartera'       => $this->ordenarInstancias(array_values($instancias), self::ORDENES[0]),
            'seleccionada'  => $seleccionada,
            'procesos'      => $this->procesosPorIndice($datos, $seleccionada),
            /*
             * El guion solo aporta comodidad: alterna las fichas de índice y
             * refresca el gráfico de memoria. Sin él las tres tablas quedan
             * visibles una tras otra y el gráfico se queda quieto en su última
             * lectura — se pierde comodidad, no información.
             */
            'guiones'       => ['assets/js/monitor.js'],
        ]);
    }

    /**
     * Qué valores tiene cada instancia en cada grupo de filtro.
     *
     * Ninguno CALCULA salud: todos leen un campo que la muestra evaluada ya
     * trae. Filtrar por una banda dada no es decidir la banda, igual que
     * ordenar por ella no lo era en el selector de la consola.
     */
    private function facetasInstancia(): Facetas
    {
        return new Facetas([
            'banda' => static fn (array $ins): array => [
                $ins['isbd'] === null ? 'SIN' : (string) $ins['banda'],
            ],

            // Cómo terminó la última toma: es lo que dice si la cifra de la
            // ficha se puede creer, y por eso se filtra aparte de la banda.
            'conexion' => static fn (array $ins): array => [(string) $ins['muestra']],

            'entorno' => static fn (array $ins): array => [(string) $ins['entorno']],
            'motor'   => static fn (array $ins): array => [(string) $ins['motor']],
        ], self::OPCIONES_FACETA);
    }

    /**
     * Filtra por lo que se escribió: clave, motor o entorno.
     *
     * Las tres a la vez por lo mismo que buscarEmpresas() mira nombre y áreas:
     * quien escribe «19c» busca un motor y quien escribe «producción» busca un
     * entorno, y obligarle a saber en qué campo vive lo que recuerda es
     * trasladarle la estructura del arreglo.
     *
     * @param list<array<string, mixed>> $instancias
     * @return list<array<string, mixed>>
     */
    private function buscarInstancias(array $instancias, ?string $termino): array
    {
        if ($termino === null) {
            return $instancias;
        }

        $buscado = $this->normalizar($termino);

        return array_values(array_filter($instancias, function (array $ins) use ($buscado): bool {
            foreach (['clave', 'motor', 'entorno'] as $campo) {
                if (str_contains($this->normalizar((string) $ins[$campo]), $buscado)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Ordena la rejilla (y el desplegable de la consola).
     *
     * A igualdad siempre desempata la clave: sin un segundo criterio, dos
     * instancias en la misma banda cambiarían de sitio entre dos cargas.
     *
     * @param list<array<string, mixed>> $instancias
     * @return list<array<string, mixed>>
     */
    private function ordenarInstancias(array $instancias, string $orden): array
    {
        usort($instancias, function (array $a, array $b) use ($orden): int {
            $porClave = $this->normalizar((string) $a['clave']) <=> $this->normalizar((string) $b['clave']);

            return match ($orden) {
                /*
                 * Mejor índice primero, y las SIN ÍNDICE al final —nunca
                 * mezcladas con las de índice bajo—. Un null no es un cero:
                 * invariante 3.
                 */
                'indice' => (($a['isbd'] === null ? 1 : 0) <=> ($b['isbd'] === null ? 1 : 0))
                    ?: ((float) $b['isbd'] <=> (float) $a['isbd'])
                    ?: $porClave,

                // La muestra más fresca primero.
                'reciente' => ((int) $a['hace_min'] <=> (int) $b['hace_min']) ?: $porClave,

                'clave' => $porClave,

                default => (self::gravedad($a) <=> self::gravedad($b)) ?: $porClave,
            };
        });

        return $instancias;
    }

    /**
     * Cuánta atención pide una instancia: menos es más urgente.
     *
     * Es un orden DECLARADO, no calculado: se lee de la banda que la muestra ya
     * trae. Una muestra que no publica ISBD (caída o incompleta) va PRIMERO y no
     * al final: «no sé cómo está» es más urgente que «está degradada», porque la
     * segunda al menos se está midiendo.
     *
     * Vivía en la vista de la consola, que ordenaba su desplegable. Subió aquí
     * al llegar la antesala: con dos copias, la rejilla y el desplegable podían
     * poner primero bases distintas.
     *
     * @param array<string, mixed> $ins
     */
    private static function gravedad(array $ins): int
    {
        if ($ins['muestra'] === 'FALLIDA') {
            return 0;
        }

        if ($ins['isbd'] === null) {
            return 1;
        }

        return match ($ins['banda']) {
            'CRITICO'     => 2,
            'DEGRADADO'   => 3,
            'ADVERTENCIA' => 4,
            'SALUDABLE'   => 5,
            default       => 6,
        };
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
