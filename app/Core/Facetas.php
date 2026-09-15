<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Filtro por FACETAS sobre una lista que ya está en memoria.
 *
 * Lo usan las dos antesalas del módulo que se recorren con el mismo panel de
 * filtros: /evaluacion/comparar (una ficha por empresa) y /monitoreo (una ficha
 * por base de datos vigilada). Vivía privado en AuditoriaController y subió
 * aquí al llegar la segunda pantalla, en vez de hacer una copia: filtrar y
 * contar son la misma regla en las dos, y dos copias de un recuento son dos
 * recuentos que un día dejan de coincidir con lo que la rejilla enseña.
 *
 * Las reglas que conviene no romper:
 *
 * - **Dentro de un grupo las opciones SUMAN y entre grupos se CRUZAN.** Es lo
 *   que espera quien ha usado cualquier buscador con facetas, y lo que impide
 *   que dos casillas del mismo grupo se anulen en vez de ampliar el resultado.
 * - **Cada grupo se cuenta con los demás aplicados pero sin el suyo.** Si se
 *   contara a sí mismo, marcar una opción dejaría las otras en cero y el panel
 *   se vaciaría con el primer clic.
 * - **Una opción marcada se sigue ofreciendo aunque cuente cero**: una casilla
 *   activa que desaparece del panel es un filtro que no hay forma de quitar.
 *
 * No sabe nada de empresas ni de instancias: cada pantalla declara sus grupos
 * como cierres que dicen qué valores tiene una fila en ese grupo.
 */
final class Facetas
{
    /**
     * @param array<string, Closure(array<string, mixed>): list<string>> $grupos
     *        Grupo → qué valores tiene una fila en él. Casi siempre uno; las
     *        áreas evaluadas de una empresa son la excepción.
     * @param array<string, list<string>> $opciones
     *        Opciones DECLARADAS de los grupos que son una escala, en el orden
     *        en que se ofrecen. Un grupo que no aparece aquí es de datos: se
     *        ofrece por frecuencia y, a igualdad, por nombre.
     */
    public function __construct(
        private readonly array $grupos,
        private readonly array $opciones = [],
    ) {
    }

    /**
     * Lo que está marcado en el panel, grupo por grupo.
     *
     * Solo se leen los grupos declarados: un parámetro inventado en la URL no
     * llega a la selección. Y un valor inventado dentro de un grupo que sí
     * existe no casa con ninguna fila y deja la rejilla vacía; no abre ninguna
     * puerta, porque todo esto se filtra sobre una lista que ya es de quien
     * pregunta.
     *
     * @return array<string, list<string>>
     */
    public function seleccion(Peticion $peticion): array
    {
        $seleccion = [];

        foreach (array_keys($this->grupos) as $grupo) {
            $seleccion[$grupo] = $peticion->entradaLista($grupo);
        }

        return $seleccion;
    }

    /**
     * Aplica los filtros marcados. Un grupo sin nada marcado no filtra.
     *
     * @param list<array<string, mixed>>  $filas
     * @param array<string, list<string>> $seleccion
     * @param string|null $excepto Grupo que NO se aplica; lo usa contar() para
     *                             contar sin contarse a sí mismo.
     * @return list<array<string, mixed>>
     */
    public function filtrar(array $filas, array $seleccion, ?string $excepto = null): array
    {
        $grupos = $this->grupos;

        return array_values(array_filter(
            $filas,
            static function (array $fila) use ($grupos, $seleccion, $excepto): bool {
                foreach ($grupos as $grupo => $valoresDe) {
                    $marcadas = $seleccion[$grupo] ?? [];

                    if ($grupo === $excepto || $marcadas === []) {
                        continue;
                    }

                    if (array_intersect($valoresDe($fila), $marcadas) === []) {
                        return false;
                    }
                }

                return true;
            },
        ));
    }

    /**
     * Cuántas filas tiene detrás cada casilla del panel de filtros.
     *
     * @param list<array<string, mixed>>  $filas
     * @param array<string, list<string>> $seleccion
     * @return array<string, array<string, int>>
     */
    public function contar(array $filas, array $seleccion): array
    {
        $facetas = [];

        foreach ($this->grupos as $grupo => $valoresDe) {
            $candidatas = $this->filtrar($filas, $seleccion, $grupo);
            $marcadas   = $seleccion[$grupo] ?? [];

            // Las opciones declaradas salen siempre y en su orden, y las
            // marcadas también aunque hoy no cuenten ninguna.
            $conteo = array_fill_keys($this->opciones[$grupo] ?? [], 0)
                    + array_fill_keys($marcadas, 0);

            foreach ($candidatas as $fila) {
                foreach ($valoresDe($fila) as $valor) {
                    $conteo[$valor] = ($conteo[$valor] ?? 0) + 1;
                }
            }

            // Los grupos SIN orden declarado son datos: se ofrecen por
            // frecuencia y, a igualdad, por nombre. Sin el segundo criterio el
            // panel saldría en otro orden entre dos cargas iguales.
            //
            // Las claves van tipadas int|string y no string: PHP convierte a
            // entero toda clave que parezca un número, así que un área evaluada
            // llamada «2024» llegaría aquí como int y, con strict_types, un
            // parámetro string reventaría con TypeError.
            if (!isset($this->opciones[$grupo])) {
                uksort($conteo, static fn (int|string $a, int|string $b): int
                    => [$conteo[$b], (string) $a] <=> [$conteo[$a], (string) $b]);
            }

            // Una opción que no tiene ninguna fila detrás y que nadie marcó no
            // se ofrece: un filtro que solo puede vaciar la rejilla no es una
            // opción, es una trampa.
            $facetas[$grupo] = array_filter(
                $conteo,
                static fn (int $n, int|string $valor): bool
                    => $n > 0 || in_array((string) $valor, $marcadas, true),
                \ARRAY_FILTER_USE_BOTH,
            );
        }

        return $facetas;
    }
}
