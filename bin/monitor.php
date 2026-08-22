#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Agente de monitoreo — Proyecto Rivendel, parte 2.
 *
 * CLI, lanzado por el programador de tareas cada N minutos (§4.1 del plan:
 * fuera de la petición web, para que una instancia colgada no deje la página
 * esperando y para que la serie de tiempo tenga regularidad real). No sirve
 * páginas ni sabe de sesiones.
 *
 * Tres conexiones, dos cuentas distintas:
 *   - RAIZ y CONTENEDOR: C##RIVENDEL_MONITOR, de solo lectura, contra la
 *     instancia vigilada (Scripts/00_usuario_monitor.sql).
 *   - ESCRITURA: becajo, la misma cuenta de la aplicación — el esquema del
 *     monitor vive en el mismo usuario que las tablas de la parte 1
 *     (advertencia de diseño del §7 del plan, aceptada para el proyecto
 *     académico). La cuenta de solo lectura JAMÁS debe usarse para escribir
 *     aquí tampoco: por eso son conexiones separadas y no una sola.
 *
 * recolecta (RecolectorMonitor) → calcula (MotorCalculo, F3 — B-10 del
 * Anexo A) → persiste (RepositorioMonitorEscritura::guardarMuestra()). Si el
 * motor todavía no existe, la muestra se recolecta y se guarda como FALLIDA
 * en vez de perderse o de guardar un cálculo que nadie hizo.
 */

const RAIZ_PROYECTO = __DIR__ . '/..';

require RAIZ_PROYECTO . '/app/Core/Autoloader.php';
(new App\Core\Autoloader('App\\', RAIZ_PROYECTO . '/app'))->registrar();
require RAIZ_PROYECTO . '/app/Core/funciones.php';

use App\Core\BaseDatos;
use App\Models\Entidades\Instancia;
use App\Models\RecolectorMonitor;
use App\Models\RepositorioMonitorOracle;

// ── 1. Configuración ─────────────────────────────────────────────────────
//
// Dos archivos, dos interruptores distintos (§4.2 del plan): sin
// base_datos.php no hay dónde escribir; sin monitor.php no hay con qué
// leer la instancia vigilada. Cualquiera de los dos ausente detiene el
// agente con un mensaje explícito — no tiene sentido "recolectar a medias".
$archivoBd = RAIZ_PROYECTO . '/config/base_datos.php';
$archivoMonitor = RAIZ_PROYECTO . '/config/monitor.php';

if (!is_file($archivoBd) || !is_file($archivoMonitor)) {
    fwrite(STDERR, "Faltan config/base_datos.php y/o config/monitor.php. Copie los .ejemplo.php correspondientes.\n");

    exit(1);
}

/** @var array{cadena: string, usuario: string, clave: string, charset?: string} $configBd */
$configBd = require $archivoBd;
/** @var array<string, mixed> $configMonitor */
$configMonitor = require $archivoMonitor;

$bdEscritura = new BaseDatos($configBd);
$repositorio = new RepositorioMonitorOracle($bdEscritura);

$tiempoLimiteConsultaSeg = (int) ($configMonitor['tiempo_limite_consulta_seg'] ?? 10);
$tiempoLimiteMuestraSeg = (int) ($configMonitor['tiempo_limite_muestra_seg'] ?? 60);
$fallosParaPausar = (int) ($configMonitor['fallos_consecutivos_para_pausar'] ?? 3);
$umbralConsultaMs = (float) ($configMonitor['umbral_consulta_costosa_ms'] ?? 1000.0);

// ── 2. Motor de cálculo (MotorCalculo, B-10 del Anexo A) ────────────────
//
// No es uno de los tres contratos de Fase 0: es la costura entre este
// agente y el motor de F3, que se documentó al escribir este script porque
// nunca se había necesitado antes. Si la clase todavía no existe, el
// agente sigue recolectando (para no perder la serie de tiempo) pero
// guarda cada muestra como FALLIDA en vez de inventar un cálculo.
$claseMotor = 'App\\Servicios\\MotorCalculoReal';
$motor = class_exists($claseMotor) ? new $claseMotor() : null;

if ($motor === null) {
    fwrite(STDERR, "Aviso: {$claseMotor} no existe todavía. Se recolecta, pero ninguna muestra se evalúa.\n");
}

// ── 3. Un ciclo: cada instancia activa, no demostrativa ──────────────────
//
// Las instancias demostrativas (B-5) tienen su historia en
// Scripts/08_datos_demo_monitor.sql, no en una recolección real — mezclar
// las dos sería presentar como medición real algo que no se midió (§8.3).
foreach ($repositorio->instancias() as $instancia) {
    if (!$instancia->activa || $instancia->demostrativa) {
        continue;
    }

    if (enPausaPorFallosConsecutivos($bdEscritura, $instancia->clave, $fallosParaPausar)) {
        echo "{$instancia->clave}: en pausa (>= {$fallosParaPausar} fallos consecutivos), se salta este ciclo.\n";

        continue;
    }

    procesarInstancia(
        $instancia,
        $repositorio,
        $configMonitor,
        $motor,
        $tiempoLimiteConsultaSeg,
        $tiempoLimiteMuestraSeg,
        $umbralConsultaMs,
    );
}

// ── Funciones ─────────────────────────────────────────────────────────────

/**
 * Cortacircuitos (§8.2 del plan): tras n fallos consecutivos se espacia el
 * reintento, para no martillar una base que ya está en problemas. Se mira
 * el historial en vez de guardar un contador aparte — instancia no tiene
 * (ni necesita) una columna para esto.
 */
function enPausaPorFallosConsecutivos(BaseDatos $bd, string $clave, int $umbral): bool
{
    if ($umbral <= 0) {
        return false;
    }

    $filas = $bd->consultar(
        'SELECT resultado FROM (
             SELECT resultado
               FROM muestra
              WHERE clave_instancia = :clave
              ORDER BY tomada_en DESC
         )
         WHERE ROWNUM <= :umbral',
        ['clave' => $clave, 'umbral' => $umbral],
    );

    if (count($filas) < $umbral) {
        return false;
    }

    foreach ($filas as $fila) {
        if ($fila['resultado'] !== 'FALLIDA') {
            return false;
        }
    }

    return true;
}

/**
 * @param array<string, mixed> $configMonitor
 */
function procesarInstancia(
    Instancia $instancia,
    RepositorioMonitorOracle $repositorio,
    array $configMonitor,
    ?object $motor,
    int $tiempoLimiteConsultaSeg,
    int $tiempoLimiteMuestraSeg,
    float $umbralConsultaMs,
): void {
    $tomadaEnUtc = gmdate('Y-m-d\TH:i:s') . '+00:00';
    $inicio = microtime(true);

    $bdRaiz = conexionMonitor($instancia->host, $instancia->puerto, $instancia->servicioRaiz, $configMonitor);
    $bdContenedor = conexionMonitor($instancia->host, $instancia->puerto, $instancia->servicioContenedor, $configMonitor);
    $bdRaiz->establecerTiempoLimite($tiempoLimiteConsultaSeg);
    $bdContenedor->establecerTiempoLimite($tiempoLimiteConsultaSeg);

    $recolector = new RecolectorMonitor($bdRaiz, $bdContenedor, $umbralConsultaMs);

    try {
        $muestraCruda = $recolector->recolectar($instancia, $tomadaEnUtc);
    } catch (\Throwable $error) {
        // Fallo total del recolector, no de una lectura suelta (esas ya se
        // protegen solas dentro de RecolectorMonitor). La muestra se guarda
        // igual: saltarla deja un hueco que después parece un periodo sano
        // (contrato-muestra.md, principio 3).
        $repositorio->guardarMuestra([
            'instancia' => $instancia->clave,
            'tomada_en' => $tomadaEnUtc,
            'resultado' => 'FALLIDA',
            'mensaje'   => $error->getMessage(),
        ]);
        echo "{$instancia->clave}: FALLIDA ({$error->getMessage()})\n";

        return;
    }

    if ((microtime(true) - $inicio) > $tiempoLimiteMuestraSeg) {
        fwrite(STDERR, "aviso: {$instancia->clave} tardó más del límite de {$tiempoLimiteMuestraSeg}s por muestra\n");
    }

    if ($motor === null) {
        $muestraCruda['resultado'] = 'FALLIDA';
        $muestraCruda['mensaje'] = 'Motor de cálculo no disponible todavía';
        $repositorio->guardarMuestra($muestraCruda);
        echo "{$instancia->clave}: recolectada, sin evaluar (motor no disponible)\n";

        return;
    }

    $muestraEvaluada = $motor->evaluar($muestraCruda, $repositorio);
    $idMuestra = $repositorio->guardarMuestra($muestraEvaluada);
    $isbd = $muestraEvaluada['indice']['isbd'] ?? null;

    echo "{$instancia->clave}: {$muestraEvaluada['resultado']}"
        . ($isbd !== null ? " ISBD={$isbd}" : '')
        . " (muestra {$idMuestra})\n";
}

/** @param array<string, mixed> $configMonitor */
function conexionMonitor(string $host, ?int $puerto, string $servicio, array $configMonitor): BaseDatos
{
    return new BaseDatos([
        'cadena'  => $host . ':' . ($puerto ?? 1521) . '/' . $servicio,
        'usuario' => (string) $configMonitor['usuario'],
        'clave'   => (string) $configMonitor['clave'],
        'charset' => (string) ($configMonitor['charset'] ?? 'AL32UTF8'),
    ]);
}
