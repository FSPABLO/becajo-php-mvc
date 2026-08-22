<?php

declare(strict_types=1);

namespace App\Models\Calculo;

use App\Models\Entidades\Metrica;
use App\Models\Entidades\Umbral;
use InvalidArgumentException;

/**
 * Traduce una utilizacion en salud, y todo lo demas del motor descansa sobre ella.
 * Por eso vive aparte del motor: si la conversion estuviera dentro de `MotorCalculoSalud`,
 * probar la tabla de verificacion del §5.2.1 exigiria construir una muestra entera para
 * comprobar una funcion de una linea.
 *
 * Todos los metodos son estaticos y sin estado a proposito: no hay nada que
 * configurar aqui. Los cuatro umbrales y el techo son dato —vienen de las
 * tablas `umbral` y `metrica` (B-8)— y entran por parametro en cada llamada.
 */
final class Escala
{
    public const OPTIMO = 'OPTIMO';
    public const SALUDABLE = 'SALUDABLE';
    public const ADVERTENCIA = 'ADVERTENCIA';
    public const DEGRADADO = 'DEGRADADO';
    public const CRITICO = 'CRITICO';

    /**
     * Las cinco bandas de mejor a peor. El orden es la definicion de
     * severidad: `peor()` compara posiciones en este arreglo.
     *
     * @var list<string>
     */
    public const BANDAS = [
        self::OPTIMO,
        self::SALUDABLE,
        self::ADVERTENCIA,
        self::DEGRADADO,
        self::CRITICO,
    ];

    /**
     * Frontera INFERIOR de salud de cada banda, de mejor a peor.
     *
     * Los tramos de `s` estan cerrados por arriba —`(90, 100]`, `(75, 90]`…—,
     * asi que 90 es SALUDABLE y no OPTIMO. La convencion no es un detalle: si
     * las dos escalas se cerraran del mismo lado, `u = 70` caeria en una banda
     * y su `s = 75` en la contigua, y la prueba de coincidencia fallaria en
     * las cinco fronteras (§5.2.1 del plan).
     */
    private const PISOS_DE_SALUD = [
        self::OPTIMO => 90.0,
        self::SALUDABLE => 75.0,
        self::ADVERTENCIA => 60.0,
        self::DEGRADADO => 40.0,
        self::CRITICO => 0.0,
    ];

    /**
     * Tope que impone cada banda cuando es el peor estado observado en un
     * nivel (§5.4). ÓPTIMO no topa nada.
     *
     * Son las fronteras exactas y no un punto por debajo: un indicador topado
     * en 40,0 pertenece a CRITICO porque las bandas de `s` estan cerradas por
     * arriba, y restar un punto solo introduciria un artefacto de un decimal
     * en la cifra publicada.
     */
    private const TOPES = [
        self::OPTIMO => null,
        self::SALUDABLE => 90.0,
        self::ADVERTENCIA => 75.0,
        self::DEGRADADO => 60.0,
        self::CRITICO => 40.0,
    ];

    /** Salud en las cinco fronteras de utilizacion, en el mismo orden. */
    private const SALUD_EN_FRONTERAS = [100.0, 90.0, 75.0, 60.0, 40.0, 0.0];

    /**
     * La utilizacion con la que se compara contra los umbrales.
     *
     * Para una metrica «mayor es mejor» se aplica la misma tabla sobre
     * `techo − v` (§5.2.1 del plan, §1.1 del catalogo): no hay una segunda
     * formula que mantener, y por eso los umbrales de `M-MEM-01` y `M-MEM-03`
     * se siembran ya transformados.
     */
    public static function utilizacionEfectiva(float $valor, float $uMax, string $sentido): float
    {
        $u = $sentido === Metrica::MAYOR_MEJOR ? $uMax - $valor : $valor;

        return max(0.0, min($uMax, $u));
    }

    /**
     * De utilizacion a salud: funcion lineal por tramos anclada en las cinco
     * fronteras de la tabla del §5.2.
     *
     * Devuelve el valor SIN redondear. Redondear aqui romperia la propiedad
     * de coincidencia: `u = 49,9` da `s = 90,02`, que es OPTIMO, y su version
     * redondeada a un decimal —90,0— cae en SALUDABLE. El redondeo es cosa de
     * quien publica la cifra, no de quien la calcula.
     */
    public static function normalizar(float $valor, Umbral $umbral, float $uMax, string $sentido): float
    {
        $fronteras = self::fronteras($umbral, $uMax);
        $u = self::utilizacionEfectiva($valor, $uMax, $sentido);

        for ($i = 0; $i < 5; $i++) {
            $u1 = $fronteras[$i];
            $u2 = $fronteras[$i + 1];
            $s1 = self::SALUD_EN_FRONTERAS[$i];
            $s2 = self::SALUD_EN_FRONTERAS[$i + 1];

            // Tramos cerrados por abajo; el ultimo incluye tambien el techo.
            if ($u < $u2 || $i === 4) {
                if ($u2 - $u1 <= 0.0) {
                    return $s2;
                }

                return $s1 - ($u - $u1) * ($s1 - $s2) / ($u2 - $u1);
            }
        }

        return 0.0;
    }

    public static function bandaPorSalud(float $salud): string
    {
        $s = max(0.0, min(100.0, $salud));

        foreach (self::PISOS_DE_SALUD as $banda => $piso) {
            if ($s > $piso) {
                return $banda;
            }
        }

        return self::CRITICO;
    }

    /**
     * La banda a la que pertenece una utilizacion segun sus cuatro umbrales.
     * Tramos cerrados por abajo.
     *
     * Existe para COMPROBAR la coincidencia con `bandaPorSalud()`. El
     * motor clasifica siempre por salud
     */
    public static function bandaPorUtilizacion(float $valor, Umbral $umbral, float $uMax, string $sentido): string
    {
        $fronteras = self::fronteras($umbral, $uMax);
        $u = self::utilizacionEfectiva($valor, $uMax, $sentido);

        if ($u < $fronteras[1]) {
            return self::OPTIMO;
        }

        if ($u < $fronteras[2]) {
            return self::SALUDABLE;
        }

        if ($u < $fronteras[3]) {
            return self::ADVERTENCIA;
        }

        if ($u < $fronteras[4]) {
            return self::DEGRADADO;
        }

        return self::CRITICO;
    }

    /** Posicion en la escala de severidad: 0 es ÓPTIMO, 4 es CRÍTICO. */
    public static function severidad(string $banda): int
    {
        $posicion = array_search($banda, self::BANDAS, true);

        if ($posicion === false) {
            throw new InvalidArgumentException("Banda desconocida: {$banda}");
        }

        return $posicion;
    }

    /**
     * La peor de un conjunto de bandas. Devuelve null si el conjunto esta
     * vacio, que es el caso de un componente sin metricas recolectadas: no
     * tiene peor estado, y por el invariante 3 tampoco vale cero.
     *
     * @param list<string> $bandas
     */
    public static function peor(array $bandas): ?string
    {
        $peor = null;

        foreach ($bandas as $banda) {
            if ($peor === null || self::severidad($banda) > self::severidad($peor)) {
                $peor = $banda;
            }
        }

        return $peor;
    }

    /** El tope que impone una banda como peor estado observado. Null: sin tope. */
    public static function tope(string $banda): ?float
    {
        self::severidad($banda); // valida

        return self::TOPES[$banda];
    }

    /**
     * La regla del eslabon mas debil: el promedio puede bajar la nota, nunca
     * sacar del rojo a un componente que esta en rojo.
     *
     * Se aplica en los dos niveles metrica a componente y componente a
     * ISBD—, y lo que viaja hacia arriba es siempre el valor ya topado.
     */
    public static function aplicarTope(float $bruto, ?string $peorBanda): float
    {
        if ($peorBanda === null) {
            return $bruto;
        }

        $tope = self::tope($peorBanda);

        return $tope === null ? $bruto : min($bruto, $tope);
    }

    /** La cifra como se publica: un decimal, formato de `medicion.valor_normalizado`. */
    public static function publicar(float $salud): float
    {
        return round($salud, 1);
    }

    /**
     * Las seis fronteras de utilizacion: 0, los cuatro umbrales y el techo.
     *
     * Valida de paso que el juego de umbrales sea coherente. Un umbral
     * desordenado no produce un numero raro: produce un tramo con pendiente
     * invertida, es decir, una metrica que mejora su salud al empeorar. Es
     * preferible que reviente al cargar el catalogo y no que publique una
     * cifra tranquilizadora.
     *
     * @return list<float>
     */
    private static function fronteras(Umbral $umbral, float $uMax): array
    {
        $fronteras = [0.0, $umbral->uOpt, $umbral->uAdv, $umbral->uDeg, $umbral->uCrit, $uMax];

        for ($i = 1; $i < 6; $i++) {
            if ($fronteras[$i] < $fronteras[$i - 1]) {
                throw new InvalidArgumentException(
                    "Umbrales incoherentes en {$umbral->codigoMetrica}: se esperaba "
                    . '0 <= u_opt <= u_adv <= u_deg <= u_crit <= u_max.'
                );
            }
        }

        return $fronteras;
    }
}
