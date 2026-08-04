<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Proceso;

/**
 * Lee el instrumento desde las tablas dominio, proceso y control de Oracle.
 *
 * Implementa el mismo contrato que RepositorioInstrumentoArreglo, así que el
 * controlador y las vistas no se enteran del cambio: es exactamente el
 * escenario que anticipaba el comentario de config/instrumento-bd.php.
 *
 * DOS COSAS QUE CONVIENE SABER
 *
 * 1. Cuatro de los siete métodos del contrato se delegan al repositorio de
 *    arreglo (el "complemento"): meta(), escala(), marco() y referencias(). El
 *    esquema de Persona 2 modela el catálogo evaluable —lo que el auditor
 *    responde— pero no la escala de madurez, el marco normativo ni la
 *    bibliografía, que son texto fijo de la herramienta y no datos de
 *    auditoría. Mientras no existan esas tablas, siguen viniendo del archivo
 *    PHP. Es una decisión pendiente de acordar con Persona 2, no un descuido.
 *
 * 2. El ORDEN DE PRESENTACIÓN se deduce del código del control. Ni dominio ni
 *    proceso tienen columna de orden, y sus claves naturales no sirven:
 *    ordenar dominios por clave los dejaría alfabéticos (accesos, almacena-
 *    miento, configuración...) en vez de en el orden del instrumento, y
 *    proceso.numero está documentado como "el número original del catálogo, no
 *    el orden de presentación". En cambio C-001..C-075 sí siguen el orden de
 *    presentación, así que ordenar por el menor código de control que cuelga
 *    de cada dominio y de cada proceso reconstruye el orden correcto sin tocar
 *    el esquema.
 *
 *    Si en el Bloque 5 el catálogo pasa a editarse desde pantalla, habrá que
 *    revisar esto: un control nuevo con código alto alteraría el orden de su
 *    dominio. Ahí sí tocará agregar una columna de orden.
 */
final class RepositorioInstrumentoOracle implements RepositorioInstrumento
{
    /** @var list<Dominio>|null */
    private ?array $dominios = null;

    /** @var list<Proceso>|null */
    private ?array $procesos = null;

    /** @var list<Control>|null */
    private ?array $controles = null;

    public function __construct(
        private readonly BaseDatos $bd,
        /**
         * Fuente de las secciones que no viven en la base. Se recibe ya
         * construido en vez de crearse aquí para no fijar la ruta del archivo
         * dentro de esta clase.
         */
        private readonly RepositorioInstrumento $complemento,
    ) {
    }

    /** @return array{titulo: string, descripcion: string, version: string} */
    public function meta(): array
    {
        return $this->complemento->meta();
    }

    /** @return list<Dominio> */
    public function dominios(): array
    {
        // Las vistas piden los dominios varias veces por petición (los tabs,
        // el tablero, el filtro). Se consulta una sola vez y se recuerda.
        if ($this->dominios !== null) {
            return $this->dominios;
        }

        $filas = $this->bd->consultar(
            'SELECT d.clave, d.nombre, d.nombre_corto, d.descripcion
               FROM dominio d
               LEFT JOIN proceso p ON p.clave_dominio = d.clave
               LEFT JOIN control c ON c.numero_proceso = p.numero
              GROUP BY d.clave, d.nombre, d.nombre_corto, d.descripcion
              ORDER BY MIN(c.codigo) NULLS LAST, d.clave'
        );

        return $this->dominios = array_map(
            static fn (array $fila): Dominio => new Dominio(
                clave:       (string) $fila['clave'],
                nombre:      (string) $fila['nombre'],
                corto:       (string) ($fila['nombre_corto'] ?? $fila['nombre']),
                descripcion: (string) ($fila['descripcion'] ?? ''),
            ),
            $filas,
        );
    }

    /** @return list<Proceso> */
    public function procesos(): array
    {
        if ($this->procesos !== null) {
            return $this->procesos;
        }

        $filas = $this->bd->consultar(
            'SELECT p.numero, p.clave_dominio, p.nombre, p.ancla
               FROM proceso p
               LEFT JOIN control c ON c.numero_proceso = p.numero
              GROUP BY p.numero, p.clave_dominio, p.nombre, p.ancla
              ORDER BY MIN(c.codigo) NULLS LAST, p.numero'
        );

        return $this->procesos = array_map(
            static fn (array $fila): Proceso => new Proceso(
                numero:  (int) $fila['numero'],
                dominio: (string) $fila['clave_dominio'],
                nombre:  (string) $fila['nombre'],
                ancla:   (string) ($fila['ancla'] ?? ''),
            ),
            $filas,
        );
    }

    /** @return list<Control> */
    public function controles(): array
    {
        if ($this->controles !== null) {
            return $this->controles;
        }

        // enunciado, evidencia_esperada y pregunta son CLOB; BaseDatos los
        // devuelve ya convertidos a texto (OCI_RETURN_LOBS).
        $filas = $this->bd->consultar(
            'SELECT codigo, numero_proceso, referencia_iso,
                    enunciado, evidencia_esperada, pregunta
               FROM control
              ORDER BY codigo'
        );

        return $this->controles = array_map(
            static fn (array $fila): Control => new Control(
                id:        (string) $fila['codigo'],
                proceso:   (int) $fila['numero_proceso'],
                iso:       (string) ($fila['referencia_iso'] ?? ''),
                enunciado: (string) ($fila['enunciado'] ?? ''),
                evidencia: (string) ($fila['evidencia_esperada'] ?? ''),
                pregunta:  (string) ($fila['pregunta'] ?? ''),
            ),
            $filas,
        );
    }

    /** @return list<array{nivel: int, nombre: string, descripcion: string}> */
    public function escala(): array
    {
        return $this->complemento->escala();
    }

    /** @return list<array{norma: string, titulo: string, aporte: string}> */
    public function marco(): array
    {
        return $this->complemento->marco();
    }

    /** @return list<array{titulo: string, fuente: string, enlace: string}> */
    public function referencias(): array
    {
        return $this->complemento->referencias();
    }
}
