# Forma de una muestra — contrato

**EIF402 · Proyecto Rivendel · Grupo 4 · Parte 2 — Instrumento de salud**

> Segundo de los tres contratos de la Fase 0 (§11 del
> [plan de la parte 2](EIF402_Monitor_de_Salud_parte_2.md)). Define la estructura
> que **produce el recolector y consume el motor de cálculo**, y con ella la
> frontera exacta entre los dos.
>
> Es el contrato que permite escribir el motor sin que exista el agente y el agente
> sin que exista el motor. Mientras las dos partes respeten esta forma, se pueden
> desarrollar y probar por separado.
>
> Las métricas citadas son las diez originales del
> [catálogo de métricas](catalogo-metricas-v0.md) (que desde su versión 1 trae
> quince); los valores de los ejemplos son lecturas reales del contenedor
> `becajo-oracle` y no cambian con la ampliación.

---

## 1. Dónde está la frontera

```
Oracle ──► RECOLECTOR ──► muestra cruda ──► MOTOR ──► muestra evaluada ──► persistencia
           solo E/S                          todo el                        y tablero
                                             razonamiento
```

**El recolector no razona.** Ejecuta las siete consultas del §4 del catálogo, anota
lo que devolvieron y sella la hora. No normaliza, no clasifica, no compara con
umbrales, no consulta la muestra anterior y **no inventa valores**.

**El motor no consulta Oracle.** Recibe muestras crudas —de la base o de un archivo
de ejemplo, le da igual— y produce mediciones, componentes, índice y alertas.

La división no es estética. Todo lo que depende del **tiempo** —tasas, histéresis,
línea base, ciclo de vida de las alertas— vive en el motor, porque el motor es el
único que ve más de una muestra. El recolector ve un instante y nada más.

### 1.1 Por qué el recolector no calcula las tasas

`M-PRO-04` mide milisegundos por escritura de redo, pero `v$system_event` entrega
acumulados desde el arranque de la instancia. La tasa exige dos muestras.

Si la calculara el recolector, tendría que leer la muestra anterior del
repositorio, y entonces el recolector dependería de la base del monitor para poder
recolectar. La muestra cruda transporta **el acumulado**, y el motor deriva la
diferencia. Es la misma razón por la que la histéresis no está en el recolector.

---

## 2. Principios del contrato

1. **Una muestra es un instante.** Todas las lecturas comparten `muestra_id`, y esa
   simultaneidad es lo que permite agrupar alertas en episodios (§6.1 del plan) sin
   reconstruir nada.
2. **La hora la pone el monitor, no la instancia vigilada**, en UTC y con zona
   explícita (A.8.17). Dos instancias con relojes distintos deben seguir siendo
   comparables.
3. **La muestra se persiste siempre, incluso cuando todo falló.** Una recolección
   fallida es un dato: dice que la instancia no respondió a esa hora. Saltarla deja
   un hueco que después parece un periodo sano.
4. **Cero filas nunca es cero.** Toda lectura declara su estado de recolección. Una
   métrica que no se pudo obtener sale del denominador; no vale 0 (invariante 3).
5. **El valor crudo viaja entero.** No solo el porcentaje: también sus partes, para
   que el tablero pueda decir «110 de 322» y no obligue al DBA a multiplicar de
   cabeza.
6. **La estructura es un arreglo de PHP**, y el archivo de ejemplo es un `.php` que
   devuelve uno — el mismo patrón de `config/contenido.php` y
   `config/instrumento-bd.php`. No se introduce JSON ni ninguna dependencia para
   esto.

---

## 3. La muestra cruda

```php
[
    'instancia'   => 'FREEPDB1',                // clave en la tabla `instancia`
    'tomada_en'   => '2026-08-19T14:35:02+00:00', // UTC, reloj del monitor
    'duracion_ms' => 842,
    'contextos'   => [                          // cómo fue cada conexión
        'RAIZ'        => ['estado' => 'OK', 'duracion_ms' => 611],
        'CONTENEDOR'  => ['estado' => 'OK', 'duracion_ms' => 231],
    ],
    'lecturas'    => [ /* una entrada por métrica planificada */ ],
]
```

`contextos` existe porque el agente abre **dos conexiones** —la raíz y el
contenedor— y pueden fallar por separado. Sin este campo, doce lecturas en error
no dirían si el problema fue de doce métricas o de una conexión.

### 3.1 Los cuatro tipos de lectura

Uno por familia de señal. El tipo no viaja en la lectura: lo declara el catálogo.

**Proporción** — el recolector entrega la medida derivada y sus partes:

```php
'M-PRO-01' => [
    'estado' => 'OK',
    'valor'  => 34.16,                                // en la unidad de la métrica
    'partes' => ['actual' => 110, 'limite' => 322],   // para mostrar el crudo
],
```

**Tasa** — el recolector entrega **solo el acumulado**; el motor deriva:

```php
'M-PRO-04' => [
    'estado'     => 'OK',
    'acumulados' => ['esperas' => 8412, 'micros' => 10430880],
],
```

**Identidad** — igual que una tasa, el recolector entrega solo el valor de este
instante y no dice nada de la historia; a diferencia de una tasa, lo que se
compara no es una magnitud sino una cadena, y lo que produce no es una medida
derivada sino una compuerta (§1.3 del catálogo). La usa `M-PRO-05`, que detecta
un reinicio de proceso de fondo comparando el `spid` actual contra el de la
muestra anterior — algo que `M-PRO-03` no ve porque solo mira una foto:

```php
'M-PRO-05' => [
    'estado'    => 'OK',
    'identidad' => 'CKPT:1238,DBW0:1236,LGWR:1237,PMON:1234,SMON:1235',
],
```

Orden fijo alfabético por nombre de proceso: la comparación contra la huella
anterior (`RepositorioMonitor::huellasAnteriores()`) es una igualdad de cadenas,
no una interpretación de qué cambió, y no puede depender del orden en que Oracle
devolvió las filas.

**Compuerta** — booleano y el detalle de qué falta:

```php
'M-PRO-03' => [
    'estado'  => 'OK',
    'abierta' => true,
    'detalle' => [
        'esperados' => ['PMON', 'SMON', 'DBW0', 'LGWR', 'CKPT'],
        'ausentes'  => [],
    ],
],
```

El `detalle` no es adorno: es lo que la alerta necesita para decir **qué** proceso
falta. Una alerta que dice «compuerta cerrada» obliga a ir a mirar a mano.

### 3.2 Estados de recolección

| Estado | Significa | Qué hace el motor |
|---|---|---|
| `OK` | La consulta respondió con datos | Calcula |
| `VACIA` | Respondió sin error y sin filas | **No calcula.** Fuera del denominador |
| `ERROR` | Excepción, tiempo agotado o conexión caída | **No calcula.** Fuera del denominador |
| `NO_APLICA` | La métrica no corresponde a esta instancia | Fuera del denominador y **fuera de las planificadas** |

`VACIA` es el estado que existe por el §10.1 del plan: `v$resource_limit` devuelve
cero filas desde un PDB **sin lanzar error**, y `v$recovery_area_usage` devuelve
cero filas siempre que no haya área de recuperación. Sin este estado, un agente
ingenuo publicaría «0 % de sesiones en uso» y el tablero mostraría salud perfecta
justo en la métrica que dejó de funcionar.

La diferencia entre `VACIA` y `NO_APLICA` es que la primera **debía** dar datos y no
los dio: baja la cobertura y merece revisión. La segunda nunca los iba a dar y ni
siquiera cuenta como planificada.

Con estado distinto de `OK`, la lectura lleva `mensaje`:

```php
'M-PRO-01' => [
    'estado'  => 'VACIA',
    'mensaje' => 'v$resource_limit devolvió 0 filas: ¿conexión al PDB en vez de a CDB$ROOT?',
],
```

---

## 4. Ejemplo completo

Las diez métricas del catálogo v0 con lecturas reales de `becajo-oracle`:

```php
[
    'instancia'   => 'FREEPDB1',
    'tomada_en'   => '2026-08-19T14:35:02+00:00',
    'duracion_ms' => 842,
    'contextos'   => [
        'RAIZ'       => ['estado' => 'OK', 'duracion_ms' => 611],
        'CONTENEDOR' => ['estado' => 'OK', 'duracion_ms' => 231],
    ],
    'lecturas' => [
        'M-PRO-01' => ['estado' => 'OK', 'valor' => 34.16,
                       'partes' => ['actual' => 110, 'limite' => 322]],
        'M-PRO-02' => ['estado' => 'OK', 'valor' => 43.00,
                       'partes' => ['actual' => 86, 'limite' => 200]],
        'M-PRO-03' => ['estado' => 'OK', 'abierta' => true,
                       'detalle' => ['esperados' => ['PMON','SMON','DBW0','LGWR','CKPT'],
                                     'ausentes'  => []]],
        'M-PRO-04' => ['estado' => 'OK',
                       'acumulados' => ['esperas' => 8412, 'micros' => 10430880]],
        'M-MEM-01' => ['estado' => 'OK', 'valor' => 100.00,
                       'partes' => ['aciertos_pct' => 100.00]],
        'M-MEM-02' => ['estado' => 'OK', 'valor' => 75.64,
                       'partes' => ['asignada' => 406099968, 'objetivo' => 536870912]],
        'M-MEM-03' => ['estado' => 'OK', 'valor' => 18.80,
                       'partes' => ['libre' => 100663296, 'total' => 535822336]],
        'M-ARC-01' => ['estado' => 'OK', 'valor' => 0.03,
                       'partes' => ['tablespace' => 'SYSTEM',
                                    'usado_mb' => 304, 'maximo_mb' => 969389]],
        'M-ARC-02' => ['estado' => 'OK', 'abierta' => true,
                       'detalle' => ['total' => 4, 'validos' => 4, 'invalidos' => []]],
        'M-ARC-03' => ['estado' => 'OK', 'abierta' => true,
                       'detalle' => ['grupos' => 2, 'miembros' => 2, 'invalidos' => []]],
    ],
]
```

---

## 5. La muestra evaluada

Lo que el motor devuelve y lo que se persiste. Cada bloque corresponde a una tabla
del §7.1 del plan.

```php
[
    'instancia'     => 'FREEPDB1',
    'tomada_en'     => '2026-08-19T14:35:02+00:00',
    'resultado'     => 'OK',              // OK | PARCIAL | FALLIDA
    'cobertura_pct' => 100.0,             // recolectadas ÷ planificadas

    'mediciones' => [                                          // → tabla `medicion`
        'M-PRO-01' => ['valor_crudo' => 34.16, 'valor_normalizado' => 93.2,
                       'estado' => 'OPTIMO',    'umbral_id' => 7],
        'M-PRO-02' => ['valor_crudo' => 43.00, 'valor_normalizado' => 91.4,
                       'estado' => 'OPTIMO',    'umbral_id' => 8],
        'M-PRO-04' => ['valor_crudo' =>  1.24, 'valor_normalizado' => 98.8,
                       'estado' => 'OPTIMO',    'umbral_id' => 10],
        'M-MEM-01' => ['valor_crudo' => 100.00,'valor_normalizado' => 100.0,
                       'estado' => 'OPTIMO',    'umbral_id' => 11],
        'M-MEM-02' => ['valor_crudo' => 75.64, 'valor_normalizado' => 85.8,
                       'estado' => 'SALUDABLE', 'umbral_id' => 12],
        'M-MEM-03' => ['valor_crudo' => 18.80, 'valor_normalizado' => 91.0,
                       'estado' => 'OPTIMO',    'umbral_id' => 13],
        'M-ARC-01' => ['valor_crudo' =>  0.03, 'valor_normalizado' => 100.0,
                       'estado' => 'OPTIMO',    'umbral_id' => 14],
        // las compuertas se guardan con su estado, sin valor normalizado
        'M-PRO-03' => ['abierta' => true, 'estado' => 'OPTIMO'],
        'M-ARC-02' => ['abierta' => true, 'estado' => 'OPTIMO'],
        'M-ARC-03' => ['abierta' => true, 'estado' => 'OPTIMO'],
    ],

    'componentes' => [
        'PROCESOS' => ['bruto' => 94.3, 'publicado' => 94.3, 'estado' => 'OPTIMO'],
        'MEMORIA'  => ['bruto' => 91.3, 'publicado' => 90.0, 'estado' => 'SALUDABLE'],
        'ARCHIVOS' => ['bruto' => 100.0,'publicado' => 100.0,'estado' => 'OPTIMO'],
    ],

    'indice' => [                                              // → tabla `indice`
        'isbd_bruto' => 94.8,
        'isbd'       => 90.0,
        'estado'     => 'SALUDABLE',
        'causa'      => ['M-MEM-02'],       // las métricas en el peor estado
    ],
]
```

### 5.1 Este ejemplo es también un caso de prueba

La aritmética completa, para que se pueda seguir a mano:

```
IP_bruto = (3×93,2 + 2×91,4 + 2×98,8) / 7 = 94,3   peor: ÓPTIMO    → IP = 94,3
IM_bruto = (2×100,0 + 3×85,8 + 2×91,0) / 7 = 91,3  peor: SALUDABLE → IM = mín(91,3; 90) = 90,0
IA_bruto = 100,0  (una sola proporción; dos compuertas abiertas)   → IA = 100,0

ISBD_bruto = 0,30×94,3 + 0,35×90,0 + 0,35×100,0 = 94,8
peor estado entre componentes: SALUDABLE (IM)                      → tope 90

ISBD = mín(94,8 ; 90) = 90,0   →   SALUDABLE
```

Dos cosas que este caso demuestra y que conviene señalar en la defensa:

- **La fila SALUDABLE → 90 del §5.4 sí trabaja.** Sin ella, este índice se
  publicaría como 94,8 · ÓPTIMO con un componente que no lo está. Una sola métrica
  de memoria al 75,6 % de su objetivo impide que el conjunto se anuncie como
  óptimo, que es exactamente lo que debe pasar.
- **Las compuertas no inflan nada.** `IA` vale 100,0 porque su única proporción vale
  100,0, no porque dos compuertas abiertas hayan aportado sendos cienes.

---

## 6. Casos límite — la lista de pruebas obligatorias

No son excepciones raras: son el comportamiento normal de un monitor y la mitad
ocurre el primer día.

| Caso | Muestra cruda | Qué debe hacer el motor |
|---|---|---|
| **Cero filas en una métrica** | `estado: VACIA` | Fuera del denominador. `cobertura_pct` baja. Nunca valor 0 |
| **Primera muestra de una tasa** | `acumulados` presentes, sin previa | `M-PRO-04` sale del denominador esta vez. No es un error |
| **La instancia se reinició** | Acumulado **menor** que el anterior | Los contadores se reiniciaron: no se calcula la tasa, se descarta el tramo. Una resta negativa nunca es una tasa |
| **Un contexto caído** | `contextos.RAIZ.estado: ERROR` | Las ocho métricas de raíz fuera; quedan dos. Cobertura 20 % → bajo el piso → `PARCIAL`, **sin publicar ISBD** |
| **La instancia no responde** | `resultado: FALLIDA`, sin lecturas | Se persiste la muestra igual. El tablero muestra el último dato bueno **con su antigüedad** |
| **Una compuerta cerrada** | `abierta: false` con `detalle` | Componente a 0 y CRÍTICO sin promediar; ISBD topado en 40 |
| **Un componente entero sin datos** | Todas sus métricas fuera | Su peso se reparte entre los otros dos. No cuenta como salud cero |

El cuarto caso merece atención: es el más probable de todos, porque una sola
conexión mal configurada deja fuera ocho de las diez métricas. El resultado correcto
no es un ISBD calculado con las dos que quedan —sería un número bonito sobre nada—,
sino **«muestra incompleta»**.

---

## 7. Lo que este contrato le exige al catálogo

Tres columnas de `metrica` existen por lo que aquí se define:

| Columna | Por qué |
|---|---|
| `ambito` | `RAIZ` o `CONTENEDOR`: decide en qué conexión se recolecta y qué contexto la deja fuera cuando falla |
| `acumulada` | Si es verdadera, el recolector entrega `acumulados` y el motor deriva la tasa |
| `u_max` | El techo de la normalización, que no siempre es 100 (`M-MEM-02` llega a 150) |
| `es_identidad` | Si es verdadera, el recolector entrega `identidad` y el motor la compara contra `huellasAnteriores()` en vez de normalizarla (B-7 del plan) |

---

## 8. Compatibilidad

**La forma no cambia cuando crece el catálogo.** Añadir métricas añade entradas en
`lecturas`; no toca la estructura. Ni el motor ni las vistas se enteran.

**Una lectura desconocida no rompe nada.** Si la muestra trae una métrica que el
catálogo no tiene, el motor la ignora y lo registra. Es lo que pasa cuando el agente
va por delante del catálogo, y no debe detener la evaluación de las demás.

**Una métrica planificada que no viene en la muestra** cuenta como `ERROR`: el
recolector debió al menos declarar que no pudo. El silencio no es un estado.
