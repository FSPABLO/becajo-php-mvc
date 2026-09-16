<?php

declare(strict_types=1);

/**
 * Muestras crudas de ejemplo.
 *
 * Son la mitad de entrada del contrato de la Fase 0: lo que produce el
 * recolector y consume el motor de cálculo. Con este archivo el frente 3 se
 * desarrolla y se prueba sin Oracle levantado y sin que exista el agente,
 * que es exactamente lo que el contrato prometía cuando se escribió.
 *
 * `completa` es literalmente el ejemplo del §4 de `contrato-muestra.md`, con
 * las diez métricas de la v0 del catálogo y **lecturas reales** del contenedor
 * `becajo-oracle`. No es un caso inventado: su aritmética está desarrollada
 * paso a paso en el §5.1 del contrato y por eso sirve de prueba de aceptación
 * del motor entero.
 *
 * Las otras dos son los dos casos límite del §6 que no se pueden construir
 * quitándole lecturas a la completa, porque cambian la cabecera y no las
 * lecturas: una conexión caída y una instancia que no respondió.
 */

return [

    // ── El ejemplo del contrato, §4 ─────────────────────────────────────────

    'completa' => [
        'instancia'   => 'FREEPDB1',
        'tomada_en'   => '2026-08-19T14:35:02+00:00',
        'duracion_ms' => 842,
        'contextos'   => [
            'RAIZ'       => ['estado' => 'OK', 'duracion_ms' => 611],
            'CONTENEDOR' => ['estado' => 'OK', 'duracion_ms' => 231],
        ],
        'lecturas' => [
            'M-PRO-01' => [
                'estado' => 'OK',
                'valor'  => 34.16,
                'partes' => ['actual' => 110, 'limite' => 322],
            ],
            'M-PRO-02' => [
                'estado' => 'OK',
                'valor'  => 43.00,
                'partes' => ['actual' => 86, 'limite' => 200],
            ],
            'M-PRO-03' => [
                'estado'  => 'OK',
                'abierta' => true,
                'detalle' => [
                    'esperados' => ['PMON', 'SMON', 'DBW0', 'LGWR', 'CKPT'],
                    'ausentes'  => [],
                ],
            ],
            'M-PRO-04' => [
                'estado'     => 'OK',
                'acumulados' => ['esperas' => 8412, 'micros' => 10430880],
            ],
            'M-MEM-01' => [
                'estado' => 'OK',
                'valor'  => 100.00,
                'partes' => ['aciertos_pct' => 100.00],
            ],
            'M-MEM-02' => [
                'estado' => 'OK',
                'valor'  => 75.64,
                'partes' => ['asignada' => 406099968, 'objetivo' => 536870912],
            ],
            'M-MEM-03' => [
                'estado' => 'OK',
                'valor'  => 18.80,
                'partes' => ['libre' => 100663296, 'total' => 535822336],
            ],
            'M-ARC-01' => [
                'estado' => 'OK',
                'valor'  => 0.03,
                'partes' => ['tablespace' => 'SYSTEM', 'usado_mb' => 304, 'maximo_mb' => 969389],
            ],
            'M-ARC-02' => [
                'estado'  => 'OK',
                'abierta' => true,
                'detalle' => ['total' => 4, 'validos' => 4, 'invalidos' => []],
            ],
            'M-ARC-03' => [
                'estado'  => 'OK',
                'abierta' => true,
                'detalle' => ['grupos' => 2, 'miembros' => 2, 'invalidos' => []],
            ],
        ],
    ],

    // ── Caso límite: un contexto caído ──────────────────────────────────────

    'contexto-raiz-caido' => [
        'instancia'   => 'FREEPDB1',
        'tomada_en'   => '2026-08-19T14:40:02+00:00',
        'duracion_ms' => 3184,
        'contextos'   => [
            'RAIZ' => [
                'estado'      => 'ERROR',
                'duracion_ms' => 3000,
                'mensaje'     => 'ORA-12541: TNS: no listener en el servicio FREE',
            ],
            'CONTENEDOR' => ['estado' => 'OK', 'duracion_ms' => 184],
        ],
        'lecturas' => [
            'M-ARC-01' => [
                'estado' => 'OK',
                'valor'  => 0.03,
                'partes' => ['tablespace' => 'SYSTEM', 'usado_mb' => 304, 'maximo_mb' => 969389],
            ],
            'M-ARC-02' => [
                'estado'  => 'OK',
                'abierta' => true,
                'detalle' => ['total' => 4, 'validos' => 4, 'invalidos' => []],
            ],
        ],
    ],

    // ── Caso límite: la instancia no respondió ──────────────────────────────
    //
    // Se persiste igual. Una recolección fallida es un dato: dice que la
    // instancia no respondió a esa hora. Saltarla deja un hueco que después
    // parece un periodo sano.

    'fallida' => [
        'instancia'   => 'FREEPDB1',
        'tomada_en'   => '2026-08-19T14:45:02+00:00',
        'duracion_ms' => 6002,
        'contextos'   => [
            'RAIZ' => [
                'estado'      => 'ERROR',
                'duracion_ms' => 3001,
                'mensaje'     => 'ORA-12170: TNS: tiempo de espera agotado',
            ],
            'CONTENEDOR' => [
                'estado'      => 'ERROR',
                'duracion_ms' => 3001,
                'mensaje'     => 'ORA-12170: TNS: tiempo de espera agotado',
            ],
        ],
        'lecturas' => [],
    ],
];
