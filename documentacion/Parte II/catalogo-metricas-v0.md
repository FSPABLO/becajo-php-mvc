# Catálogo de métricas — versión 0

**EIF402 · Proyecto Rivendel · Grupo 4 · Parte 2 — Instrumento de salud**

> Primero de los tres contratos de la Fase 0 (§11 del
> [plan de la parte 2](EIF402_Monitor_de_Salud_parte_2.md)). Cubre los pasos 1 a 4
> de la [guía del curso](EIF402_Guia_monitoreo_procesos_Oracle.md): identificar los
> procesos importantes, definir qué conocer de cada uno, seleccionar las métricas y
> localizar de dónde se obtienen.
>
> **Qué es una versión 0.** Diez métricas elegidas para que el modelo de cálculo se
> pueda escribir y probar entero, no para cubrir la instancia. Están representadas
> las tres familias de señal y los tres componentes del índice. El catálogo
> definitivo crece sobre esta forma; no la cambia.
>
> **Todo lo que aquí se afirma está comprobado** contra el contenedor
> `becajo-oracle` del proyecto. Las lecturas de calibración son de una única
> observación: sirven para saber que la consulta funciona y que el umbral no es
> absurdo, **no son una línea base**. La línea base son los pasos 5 y 6 de la guía
> y necesitan la ventana del §5.5.

---

## 1. Convenciones

### 1.1 Las tres familias de señal

Definidas en el §5.1 del plan. Toda métrica pertenece a una y solo una:

| Familia | Qué es | Cómo entra al índice |
|---|---|---|
| **Proporción, menor es mejor** | Consumo de un recurso contra su límite | Se normaliza (§5.2.1) y se promedia |
| **Proporción, mayor es mejor** | Eficacia o holgura: el máximo es lo bueno | Se normaliza sobre `techo − v`, misma tabla |
| **Estado, binaria** | Algo está o no está | **No se promedia: es una compuerta** (§1.3) |

### 1.2 El ámbito no es opcional

Cada métrica declara desde dónde se consulta, porque **las vistas no ven lo mismo
desde los dos lados**:

| Ámbito | Dónde se consulta | Qué vive ahí |
|---|---|---|
| `RAIZ` | `CDB$ROOT` | Recursos de la instancia: límites, memoria, procesos de fondo, redo |
| `CONTENEDOR` | El PDB auditado | Lo que es de la base vigilada: tablespaces, sus datafiles |

Comprobado: `v$datafile` devuelve 11 filas en la raíz y 4 en el PDB;
`dba_tablespace_usage_metrics` devuelve cinco tablespaces **distintos** en cada
lado; `v$system_event` no ve `log file parallel write` desde el PDB, y
`v$resource_limit` devuelve **cero filas sin error** dentro del PDB (§10.1 del plan).

**Consecuencia para el agente:** necesita dos contextos de conexión, no uno.

### 1.3 Las métricas de estado son compuertas, no sumandos

Una métrica de familia ESTADO **no entra en el promedio del componente**. Solo
puede hacer una cosa: cerrar la compuerta.

- Todas las compuertas abiertas → el componente vale lo que dice su promedio de
  proporciones.
- Cualquier compuerta cerrada → **salud 0 y componente en CRÍTICO**, sin promediar.
- Un componente formado solo por compuertas vale 100 con todas abiertas y 0 con
  cualquiera cerrada.

La razón es que una compuerta no aporta grado. Si un datafile en línea entrara al
promedio como 100, subiría la nota del componente sin decir nada sobre qué tan
bien está el recurso que sí se mide — y un componente con una proporción al 50 y
una compuerta abierta leería 75, que no significa nada.

> **Nota de consistencia (ya aplicada).** El caso trabajado del §5.4 del plan
> incluía la compuerta «datafiles en línea» en el promedio de IA. Con esta regla,
> `IA_bruto` pasó de 94,5 a **91,8** e `ISBD_bruto` de 71,3 a **70,4**. El ISBD
> publicado no cambia —sigue siendo **40,0 · CRÍTICO** por el tope—, lo que además
> muestra que la conclusión del ejemplo no dependía de ese detalle.

### 1.4 La normalización no exige porcentajes

El §5.2.1 está escrito con umbrales en `%` porque es el caso común, pero la función
por tramos solo necesita que **los cuatro umbrales y el techo estén en la misma
unidad que la medida derivada**. `M-PRO-04` los declara en milisegundos y se
normaliza igual.

### 1.5 El techo se declara, no se supone

`u_max` es columna de `metrica`. Por omisión vale 100, pero hay medidas que superan
el 100 % legítimamente: la PGA asignada puede exceder su objetivo sin que eso sea
una falla. Con el techo fijado a 100 esa métrica se satura y deja de distinguir
entre «un poco por encima» y «al triple».

---

## 2. Las diez métricas

| Código | Componente | Familia | Peso | Ámbito | Mide |
|---|---|---|---|---|---|
| `M-PRO-01` | PROCESOS | menor es mejor | 3 | RAIZ | Sesiones sobre el límite |
| `M-PRO-02` | PROCESOS | menor es mejor | 2 | RAIZ | Procesos sobre el límite |
| `M-PRO-03` | PROCESOS | **estado** | — | RAIZ | Procesos de fondo obligatorios presentes |
| `M-PRO-04` | PROCESOS | menor es mejor | 2 | RAIZ | Espera media de escritura de redo |
| `M-MEM-01` | MEMORIA | **mayor es mejor** | 2 | RAIZ | Aciertos de caché de PGA |
| `M-MEM-02` | MEMORIA | menor es mejor | 3 | RAIZ | PGA asignada sobre el objetivo |
| `M-MEM-03` | MEMORIA | **mayor es mejor** | 2 | RAIZ | Memoria libre de la *shared pool* |
| `M-ARC-01` | ARCHIVOS | menor es mejor | 3 | CONTENEDOR | Utilización del peor tablespace |
| `M-ARC-02` | ARCHIVOS | **estado** | — | CONTENEDOR | Datafiles en estado válido |
| `M-ARC-03` | ARCHIVOS | **estado** | — | RAIZ | Grupos de redo sin miembros inválidos |

**Reparto:** 5 «menor es mejor» · 2 «mayor es mejor» · 3 compuertas. Los tres
componentes del índice representados.

**Debilidad conocida de la v0:** ARCHIVOS descansa en una sola proporción más dos
compuertas. Es suficiente para ejercitar el modelo, insuficiente como cobertura; la
primera ampliación del catálogo debería ir ahí.

---

## 3. Fichas de medición

Formato según el modelo de medición de ISO/IEC 27004:2016, Anexo B: medida base →
función de medición → medida derivada → modelo analítico → indicador → criterios de
decisión.

---

### M-PRO-01 · Utilización de sesiones

| | |
|---|---|
| **Necesidad de información** | Saber si la instancia puede seguir aceptando conexiones. Es el recurso que se agota primero y el que corta el servicio de forma más visible. |
| **Componente · peso** | PROCESOS · 3 |
| **Familia · ámbito** | Proporción, menor es mejor · `RAIZ` |
| **Medidas base** | `current_utilization`, `limit_value` de `v$resource_limit` |
| **Función de medición** | `u = current_utilization / limit_value × 100` |
| **Medida derivada** | Porcentaje del límite · techo 100 |
| **Modelo analítico** | Normalización por tramos (§5.2.1) → salud 0–100 |
| **Criterios de decisión** | `u_opt` 50 · `u_adv` 70 · `u_deg` 85 · `u_crit` 95 |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | ISO/IEC 27002:2022 A.8.6 — Gestión de capacidad |
| **Modo de falla** | Al alcanzar el límite: `ORA-00018, maximum number of sessions exceeded`. Ninguna sesión nueva entra, incluidas las del administrador. |
| **Lectura de calibración** | ≈ 34 % (110 de 322) |

```sql
SELECT resource_name, current_utilization, limit_value
  FROM v$resource_limit
 WHERE resource_name IN ('sessions', 'processes')
   AND limit_value > 0;
```

**Nota.** El límite es el que devuelve `LIMIT_VALUE` —322 aquí—, no una cifra de
manual. Calibrar contra un número supuesto es el error más común de esta métrica.

**Justificación de umbrales.** El modo de falla es un corte total y sin
gradiente —`ORA-00018` no deja escribir ni al DBA—, así que los cuatro tramos se
leen como «cuánto margen queda frente a un evento binario», no como una curva de
degradación. `u_opt = 50`: con la mitad del límite libre, un pico normal de
conexiones no cambia la conclusión. `u_adv = 70`: todavía queda un 30 % de
holgura, pero ya es visible una tendencia que conviene vigilar antes de la
próxima ventana de carga. `u_deg = 85`: el margen restante (15 %) es del orden
de lo que un pico de conexiones concurrentes puede consumir de una vez, así que
cruzar esta frontera ya exige acción, no solo atención. `u_crit = 95`: a un 5 %
del límite, cualquier evento breve —un lote, una reconexión masiva tras un
corte de red— puede disparar el `ORA-00018` antes de que alguien alcance a
reaccionar. El techo se deja en 100 porque el límite es literal: no hay
«utilización de sesiones» por encima del 100 % que tenga sentido, a diferencia
de `M-MEM-02`, donde el objetivo sí se puede superar sin error.

---

### M-PRO-02 · Utilización de procesos

| | |
|---|---|
| **Necesidad de información** | Vigilar el mismo agotamiento un nivel más abajo: cada sesión necesita un proceso servidor, y el límite de procesos se alcanza antes en configuraciones dedicadas. |
| **Componente · peso** | PROCESOS · 2 |
| **Familia · ámbito** | Proporción, menor es mejor · `RAIZ` |
| **Medidas base** | `current_utilization`, `limit_value` de `v$resource_limit` |
| **Función de medición** | `u = current_utilization / limit_value × 100` |
| **Medida derivada** | Porcentaje del límite · techo 100 |
| **Modelo analítico** | Normalización por tramos → salud |
| **Criterios de decisión** | 50 · 70 · 85 · 95 |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.6 — Gestión de capacidad |
| **Modo de falla** | `ORA-00020, maximum number of processes exceeded`. |
| **Lectura de calibración** | ≈ 43 % (86 de 200) |

Sale de la misma consulta que `M-PRO-01`: **una consulta, dos métricas**. El
catálogo permite que varias métricas compartan origen; el agente no debe ejecutar
la misma consulta dos veces.

**Nota.** Va con peso menor que sesiones y declara `M-PRO-01` como consecuencia
suya en la tabla `precedencia`: cuando los procesos se agotan, las sesiones se
agotan detrás. Sin esa declaración, la correlación del §6.1 vería dos problemas
donde hay uno.

**Justificación de umbrales.** Comparte los cuatro umbrales de `M-PRO-01`
(50 · 70 · 85 · 95) a propósito y no por omisión: sale de la misma vista, el
mismo modo de falla —`ORA-00020` es tan abrupto como `ORA-00018`— y, sobre todo,
la tabla de precedencias del §5 declara que el agotamiento de procesos arrastra
al de sesiones. Si las dos métricas usaran escalas distintas, un mismo evento de
capacidad calificaría distinto según cuál de las dos lo mida primero, y el
episodio del §6.1 tendría que reconciliar dos criterios para lo que es un solo
problema. El techo se mantiene en 100 por el mismo motivo que en `M-PRO-01`: el
límite de procesos también es un tope literal, sin margen por encima.

---

### M-PRO-03 · Procesos de fondo obligatorios presentes

| | |
|---|---|
| **Necesidad de información** | El paso 1 de la guía, literal. Si PMON, SMON, DBWn, LGWR o CKPT no están, la instancia no está sana por mucha holgura de recursos que muestre. |
| **Componente · peso** | PROCESOS · compuerta, sin peso |
| **Familia · ámbito** | **Estado, binaria** · `RAIZ` |
| **Medidas base** | `name`, `paddr` de `v$bgprocess` |
| **Función de medición** | Presentes = los cinco nombres con `paddr <> '00'` |
| **Medida derivada** | Booleano: los cinco presentes, sí o no |
| **Modelo analítico** | Compuerta (§1.3): abierta → no promedia; cerrada → salud 0 y componente CRÍTICO |
| **Criterios de decisión** | Cualquier ausencia cierra la compuerta |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.16 — Actividades de seguimiento; A.5.30 — Continuidad de las TIC |
| **Modo de falla** | Sin PMON no se limpian sesiones muertas ni se liberan sus bloqueos; sin LGWR no se confirma ninguna transacción; sin DBWn los cambios no bajan a disco. Ninguno es un problema de grado. |
| **Lectura de calibración** | 5 de 5 presentes: `CKPT DBW0 LGWR PMON SMON` |

```sql
SELECT name
  FROM v$bgprocess
 WHERE paddr <> '00'
   AND name IN ('PMON', 'SMON', 'DBW0', 'LGWR', 'CKPT');
```

**Nota.** `paddr <> '00'` es el filtro que distingue proceso **activo** de entrada
del catálogo: `v$bgprocess` lista 83 procesos activos de un catálogo mucho mayor.
Sin ese filtro la métrica siempre da «presente». Con más de un escritor la lista
crece a `DBW1`, `DBW2`…; el nombre exacto es dato del catálogo, no del código.

**Justificación de umbrales.** No aplica: una métrica de familia ESTADO no tiene
`u_opt`/`u_adv`/`u_deg`/`u_crit`, tiene un único criterio binario (§1.3). No es
una omisión ni una simplificación conveniente — introducir cuatro umbrales aquí
obligaría a inventar «qué tan ausente» está un proceso de fondo, y eso no existe:
PMON está o no está. Este es, de hecho, el argumento central de por qué el
catálogo distingue familias de señal en primer lugar (§1.1).

---

### M-PRO-04 · Espera media de escritura de redo

| | |
|---|---|
| **Necesidad de información** | Saber si LGWR está haciendo su trabajo a tiempo. Es la espera que se traslada directamente a cada `COMMIT`: cuando sube, toda la aplicación se siente lenta sin que ningún recurso aparezca agotado. |
| **Componente · peso** | PROCESOS · 2 |
| **Familia · ámbito** | Proporción, menor es mejor · `RAIZ` |
| **Medidas base** | `total_waits`, `time_waited_micro` de `v$system_event` para `log file parallel write` |
| **Función de medición** | `u = (Δtime_waited_micro / Δtotal_waits) / 1000` — **diferencias respecto de la muestra anterior** |
| **Medida derivada** | Milisegundos por escritura · techo 20 |
| **Modelo analítico** | Normalización por tramos, umbrales en ms (§1.4) |
| **Criterios de decisión** | `u_opt` 10 · `u_adv` 14 · `u_deg` 17 · `u_crit` 19 ms |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.16 — Actividades de seguimiento |
| **Modo de falla** | No hay un tope que produzca error: la base se degrada de forma continua hasta que los `COMMIT` dominan el tiempo de respuesta. |
| **Lectura de calibración** | 1,24 ms acumulados desde el arranque |

```sql
SELECT total_waits, time_waited_micro
  FROM v$system_event
 WHERE event = 'log file parallel write';
```

**Nota — la más importante del catálogo.** `v$system_event` **acumula desde el
arranque de la instancia**. El 1,24 ms observado es la media de toda la vida de la
instancia, no la de ahora, y con el tiempo se vuelve insensible: un mal día no
mueve una media de tres meses. La métrica solo sirve si el agente guarda el
acumulado y publica la **diferencia entre muestras**. De ahí sale una exigencia
para el contrato de muestra: las métricas de tasa transportan el acumulado además
del valor derivado.

**Justificación de umbrales.** No hay un `ORA-` que marque la frontera —el modo
de falla es degradación continua, no corte—, así que los umbrales no salen de un
límite de Oracle sino de la latencia que empieza a sentirse en cada `COMMIT`.
`u_opt = 10 ms`: por debajo de esto la escritura de redo es indistinguible de
«no es el cuello de botella» para una aplicación transaccional típica.
`u_adv = 14 ms`: la latencia ya se acerca a lo perceptible en operaciones con
muchos `COMMIT` pequeños, aunque todavía no domina el tiempo de respuesta.
`u_deg = 17 ms`: a este nivel la espera de redo empieza a explicar una parte
significativa de la lentitud que reportaría un usuario, sin que ningún otro
recurso muestre agotamiento. `u_crit = 19 ms`: casi el doble del óptimo; el
`COMMIT` ya tarda un orden de magnitud más que el de una base sana. El techo se
fija en 20 ms, un solo milisegundo sobre `u_crit`, a propósito: por encima de esa
frontera cualquier valor —25 ms o 250 ms— es igual de urgente, y extender la
escala solo comprimiría el rango donde sí hay distinción útil (0–20 ms) sin
aportar ninguna granularidad adicional del lado malo.

---

### M-MEM-01 · Aciertos de caché de PGA

| | |
|---|---|
| **Necesidad de información** | Saber qué proporción del trabajo de ordenamiento y agrupación se resuelve en memoria en lugar de bajar a disco temporal. |
| **Componente · peso** | MEMORIA · 2 |
| **Familia · ámbito** | **Proporción, mayor es mejor** · `RAIZ` |
| **Medidas base** | `value` de `v$pgastat` donde `name = 'cache hit percentage'` y `con_id = 0` |
| **Función de medición** | `v = value` (ya viene en porcentaje) |
| **Medida derivada** | Porcentaje de aciertos · se normaliza sobre `100 − v` |
| **Modelo analítico** | Normalización con las bandas invertidas (§5.2.1) |
| **Criterios de decisión** | ÓPTIMO ≥ 95 · SALUDABLE ≥ 90 · ADVERTENCIA ≥ 80 · DEGRADADO ≥ 70 · CRÍTICO < 70 |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.6 — Gestión de capacidad |
| **Modo de falla** | No hay error: las operaciones pasan a disco temporal y todo se vuelve más lento de forma silenciosa. |
| **Lectura de calibración** | 100 % |

```sql
SELECT name, value
  FROM v$pgastat
 WHERE con_id = 0
   AND name IN ('cache hit percentage',
                'total PGA allocated',
                'aggregate PGA target parameter');
```

**Nota.** Es el único ratio de acierto del catálogo, y entra porque lo publica
Oracle como métrica de asesor, no porque lo hayamos derivado. Afinar por ratios de
acierto propios es una mala práctica conocida: se pueden mejorar sin que el sistema
mejore. Un 100 % sostenido en una base ociosa dice poco; esta métrica solo tiene
valor con la línea base delante.

**Justificación de umbrales.** Es «mayor es mejor» y el modo de falla es
silencioso —spill a disco temporal, no un error—, así que el criterio es cuánta
proporción del trabajo de ordenamiento deja de resolverse en memoria.
`ÓPTIMO ≥ 95 %`: el margen de spill (5 %) es el que cualquier PGA bien
dimensionada absorbe en el uso normal, con picos ocasionales de operaciones
grandes. `SALUDABLE ≥ 90 %`: 1 de cada 10 operaciones ya pasa a disco temporal,
medible pero todavía dentro de lo esperable en cargas mixtas. `ADVERTENCIA ≥ 80 %`:
1 de cada 5 —una fracción demasiado alta para atribuirla a unas pocas consultas
pesadas puntuales— sugiere que el dimensionamiento de PGA empieza a quedarse
corto para la carga actual. `DEGRADADO ≥ 70 %`: casi un tercio del trabajo de
ordenamiento ya compite por I/O de disco temporal, con el impacto de rendimiento
correspondiente. `CRÍTICO < 70 %`: a partir de aquí el patrón ya no es «algunas
consultas grandes», es que la memoria de trabajo configurada no alcanza para la
carga que la instancia recibe. No lleva techo declarado porque es un porcentaje
de acierto propiamente dicho: no puede superar 100 %.

---

### M-MEM-02 · PGA asignada sobre el objetivo

| | |
|---|---|
| **Necesidad de información** | Saber si la memoria de trabajo real se aleja del objetivo configurado, que es el aviso previo al agotamiento de memoria del servidor. |
| **Componente · peso** | MEMORIA · 3 |
| **Familia · ámbito** | Proporción, menor es mejor · `RAIZ` |
| **Medidas base** | `total PGA allocated` y `aggregate PGA target parameter` de `v$pgastat`, `con_id = 0` |
| **Función de medición** | `u = total_asignada / objetivo × 100` |
| **Medida derivada** | Porcentaje del objetivo · **techo 150** |
| **Modelo analítico** | Normalización por tramos con techo declarado (§1.5) |
| **Criterios de decisión** | `u_opt` 70 · `u_adv` 90 · `u_deg` 100 · `u_crit` 120 |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.6 — Gestión de capacidad |
| **Modo de falla** | `ORA-04030` al no poder reservar memoria de proceso; antes de eso, intercambio con disco a nivel de sistema operativo. |
| **Lectura de calibración** | ≈ 76 % (387 MB de 512 MB) |

Misma consulta que `M-MEM-01`.

**Nota — trampa verificada.** El filtro `con_id = 0` es obligatorio: en el PDB
(`con_id = 3`) el objetivo de PGA vale **0**, de modo que sin filtro esta métrica es
una división por cero esperando su turno. Es un caso concreto de la regla general:
**cada vista trata `CON_ID` a su manera y hay que comprobarlo antes de agregar**
—`v$pgastat` duplica y hay que filtrar, `v$sgastat` reparte y hay que sumar todo—.

**Justificación de umbrales — y del techo, que aquí es el dato interesante.**
`u_opt = 70 %`: Oracle documenta que la gestión automática de PGA reserva
holgura por diseño, así que operar bien por debajo del objetivo configurado es
el estado esperado, no una señal de sobredimensionamiento. `u_adv = 90 %`:
acercarse al objetivo todavía es variabilidad normal de carga, pero deja de ser
holgura cómoda. `u_deg = 100 %`: exactamente en el objetivo es la frontera
donde el motor empieza a aplicar más presión para no superarlo —más operaciones
*one-pass* o *multi-pass* en vez de *optimal*—, que es exactamente lo que
`M-MEM-01` mide del otro lado. `u_crit = 120 %`: superar el objetivo en un 20 %
significa que el parámetro configurado ya no describe el uso real, y es el punto
donde el sistema operativo empieza a sentir presión de memoria de verdad. El
**techo en 150 %**, no en 100, es la justificación que pide el §1.5 del
catálogo: la asignación de PGA puede exceder legítimamente su objetivo sin que
eso sea, por sí solo, la falla —es la distancia lo que importa, no el mero
hecho de superarlo—. Y se detiene en 150 y no más arriba porque, pasado ese
punto, la severidad ya no distingue: un 160 % y un 300 % están igual de cerca
de `ORA-04030`, así que extender la escala solo restaría resolución al tramo
70–150 %, que es donde de verdad hay grados que comunicar.

---

### M-MEM-03 · Memoria libre de la *shared pool*

| | |
|---|---|
| **Necesidad de información** | Saber si queda espacio para planes de ejecución y metadatos del diccionario. Cuando se acaba, aparecen errores de asignación que se ven como fallos de la aplicación. |
| **Componente · peso** | MEMORIA · 2 |
| **Familia · ámbito** | **Proporción, mayor es mejor** · `RAIZ` |
| **Medidas base** | `bytes`, `name` de `v$sgastat` con `pool = 'shared pool'` |
| **Función de medición** | `v = Σ bytes(name='free memory') / Σ bytes × 100`, **sobre todos los `con_id`** |
| **Medida derivada** | Porcentaje libre |
| **Modelo analítico** | Normalización con las bandas invertidas |
| **Criterios de decisión** | ÓPTIMO ≥ 10 · SALUDABLE ≥ 5 · ADVERTENCIA ≥ 2 · DEGRADADO ≥ 1 · CRÍTICO < 1 |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.6 — Gestión de capacidad |
| **Modo de falla** | `ORA-04031, unable to allocate ... shared memory`, que llega a la aplicación como un error de consulta. |
| **Lectura de calibración** | 18,8 % libre |

```sql
SELECT ROUND(SUM(DECODE(name, 'free memory', bytes, 0)) / SUM(bytes) * 100, 2) AS libre_pct
  FROM v$sgastat
 WHERE pool = 'shared pool';
```

**Justificación de umbrales — parecen bajos y no lo son.** Una *shared pool* casi llena es
el estado normal de una base sana: la memoria está ahí para usarse, y Oracle la
llena de planes en caché a propósito. Un umbral ingenuo del tipo «menos del 50 %
libre es advertencia» produciría alerta permanente. Por eso el umbral de ÓPTIMO
está en 10 % y el de CRÍTICO en 1 %. Es el ejemplo más claro de por qué **los
umbrales son dato y no código**.

Comprobado, además, que aquí **no se filtra por `con_id`**: la memoria libre se
reporta bajo `con_id = 0` y la ocupada se reparte entre los contenedores, así que
filtrar daría 100 % libre. Exactamente al revés que `v$pgastat`.

---

### M-ARC-01 · Utilización del peor tablespace

| | |
|---|---|
| **Necesidad de información** | Saber si alguna estructura de almacenamiento está por quedarse sin espacio. Basta con que uno se llene para que las escrituras fallen. |
| **Componente · peso** | ARCHIVOS · 3 |
| **Familia · ámbito** | Proporción, menor es mejor · `CONTENEDOR` |
| **Medidas base** | `used_percent` de `dba_tablespace_usage_metrics` |
| **Función de medición** | `u = MAX(used_percent)` — **el peor, nunca el promedio** |
| **Medida derivada** | Porcentaje del máximo alcanzable · techo 100 |
| **Modelo analítico** | Normalización por tramos |
| **Criterios de decisión** | 50 · 70 · 85 · 95 |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.8.6 — Gestión de capacidad |
| **Modo de falla** | `ORA-01653 / ORA-01654`: la extensión no se puede ampliar y la transacción falla. |
| **Lectura de calibración** | 0,03 % (SYSTEM, el peor de cinco) |

```sql
SELECT tablespace_name, ROUND(used_percent, 2) AS usado_pct
  FROM dba_tablespace_usage_metrics
 ORDER BY used_percent DESC
 FETCH FIRST 1 ROWS ONLY;
```

**Nota — por qué el peor y no el promedio.** Promediar cinco tablespaces con uno al
99 % y cuatro vacíos da 20 %, que es un número tranquilizador sobre una base a punto
de fallar. El recurso es divisible y la falla es local: la salud del conjunto es la
del peor.

**Nota — por qué 0,03 % es correcto y a la vez inútil hoy.** SYSTEM usa 304 MB de
969 389 MB **máximos alcanzables**, porque los archivos crecen automáticamente hasta
32 GB. La cifra es exacta y significa lo que debe significar: aquí no hay presión de
espacio. Pero implica que la métrica se quedará en ÓPTIMO para siempre en esta
instancia y **no ejercitará la demostración**: los datos de demostración
(`Scripts/08_datos_demo_monitor.sql`) tienen que aportar un tablespace cargado.
Cuidado con la lectura contraria, que es el error clásico: sobre un archivo **sin**
crecimiento automático, este porcentaje sí es «qué tan lleno está», y los dos casos
no se calibran igual.

**Justificación de umbrales.** Comparte la forma 50 · 70 · 85 · 95 de `M-PRO-01`
y `M-PRO-02` porque el modo de falla es del mismo tipo: un tope duro
(`ORA-01653`/`ORA-01654`) sin degradación previa perceptible por la aplicación.
`u_opt = 50 %`: con la mitad del máximo alcanzable libre, ni siquiera una carga
puntual grande cambia la conclusión. `u_adv = 70 %`: ya hay una tendencia de
consumo que conviene proyectar, sobre todo porque un *datafile* con crecimiento
automático limitado por disco físico puede agotar ese margen sin previo aviso.
`u_deg = 85 %` coincide, deliberadamente, con el umbral clásico de «warning» que
usa Oracle Enterprise Manager para espacio de *tablespace* —no es una coincidencia
casual, es apoyarse en una convención ya validada por la operación real de bases
Oracle—. `u_crit = 95 %`: solo queda margen para una carga masiva antes de que
la siguiente extensión falle. El techo queda en 100 porque `used_percent` ya
está definido sobre el máximo alcanzable (§ nota anterior): no hay «más de 100 %
lleno».

---

### M-ARC-02 · Datafiles en estado válido

| | |
|---|---|
| **Necesidad de información** | Saber si todos los archivos de datos de la base auditada están disponibles. Un archivo fuera de línea deja inaccesible una parte de los datos. |
| **Componente · peso** | ARCHIVOS · compuerta, sin peso |
| **Familia · ámbito** | **Estado, binaria** · `CONTENEDOR` |
| **Medidas base** | `status` de `v$datafile` |
| **Función de medición** | Válido = `status IN ('ONLINE', 'SYSTEM')` para todas las filas |
| **Medida derivada** | Booleano |
| **Modelo analítico** | Compuerta (§1.3) |
| **Criterios de decisión** | Cualquier archivo fuera de esos dos estados cierra la compuerta |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.5.30 — Continuidad de las TIC; A.8.6 |
| **Modo de falla** | Las consultas sobre los objetos alojados en ese archivo fallan; con el archivo del sistema fuera, no arranca la base. |
| **Lectura de calibración** | 4 archivos: 1 `SYSTEM` + 3 `ONLINE` |

```sql
SELECT status, COUNT(*) AS n
  FROM v$datafile
 GROUP BY status;
```

**Nota — trampa verificada.** `v$datafile` devuelve `status = 'SYSTEM'` para el
archivo del tablespace del sistema, **no** `'ONLINE'`. Una comprobación
`status = 'ONLINE'` marcaría como falla 1 de los 4 archivos de esta base, que está
perfectamente sana. Es la clase de error que produce una alerta permanente el
primer día y entrena al operador a ignorar el tablero.

**Justificación de umbrales.** No aplica, por la misma razón que `M-PRO-03`: es
una compuerta de familia ESTADO (§1.3), no una proporción. Un *datafile* está
`ONLINE`/`SYSTEM` o no lo está; no hay un «85 % disponible» intermedio que
tenga sentido físico.

---

### M-ARC-03 · Grupos de redo sin miembros inválidos

| | |
|---|---|
| **Necesidad de información** | Saber si el registro de rehacer puede escribirse. Sin redo utilizable no se confirma ninguna transacción y no hay recuperación posible. |
| **Componente · peso** | ARCHIVOS · compuerta, sin peso |
| **Familia · ámbito** | **Estado, binaria** · `RAIZ` |
| **Medidas base** | `status` de `v$log` y de `v$logfile` |
| **Función de medición** | Válido = ningún grupo `INVALID` y ningún miembro `INVALID` o `DELETED` |
| **Medida derivada** | Booleano |
| **Modelo analítico** | Compuerta (§1.3) |
| **Criterios de decisión** | Cualquier grupo o miembro inválido cierra la compuerta |
| **Periodicidad** | Cada muestra |
| **Ancla normativa** | A.5.30 — Continuidad de las TIC; A.8.15 — Registro |
| **Modo de falla** | Con todos los grupos inutilizables, la base se detiene: no puede rotar el registro. |
| **Lectura de calibración** | 2 grupos (`CURRENT`, `INACTIVE`), 2 miembros, ninguno inválido |

```sql
SELECT group#, status, members FROM v$log;
SELECT NVL(status, 'EN LINEA') AS status, COUNT(*) AS n FROM v$logfile GROUP BY status;
```

**Nota.** En `v$logfile`, `status` nulo significa que el miembro está en uso y sano;
tratarlo como valor ausente lo convertiría en falla. Aparte: esta instancia tiene
**dos grupos de un solo miembro**, que es el mínimo de la edición gratuita. Que sean
pocos y sin multiplexar es una debilidad de configuración —territorio del
instrumento de medición de la parte 1, control de continuidad—, no un problema de
salud, y por eso no entra aquí.

**Justificación de umbrales.** No aplica, misma razón que `M-PRO-03` y
`M-ARC-02`: compuerta de familia ESTADO. Un grupo o miembro de redo está
`INVALID`/`DELETED` o no lo está; no hay una proporción intermedia de «redo
funcionando a medias» que se pueda normalizar.

---

## 4. Lo que el agente ejecuta

Diez métricas salen de **siete consultas**, porque varias comparten origen. El
agente no debe repetir una consulta por métrica.

| Consulta | Ámbito | Alimenta |
|---|---|---|
| `v$resource_limit` | RAIZ | `M-PRO-01`, `M-PRO-02` |
| `v$bgprocess` | RAIZ | `M-PRO-03` |
| `v$system_event` | RAIZ | `M-PRO-04` |
| `v$pgastat` | RAIZ | `M-MEM-01`, `M-MEM-02` |
| `v$sgastat` | RAIZ | `M-MEM-03` |
| `v$log` + `v$logfile` | RAIZ | `M-ARC-03` |
| `dba_tablespace_usage_metrics` | CONTENEDOR | `M-ARC-01` |
| `v$datafile` | CONTENEDOR | `M-ARC-02` |

**Cero filas nunca es cero.** Cualquiera de estas consultas puede devolver un
conjunto vacío sin lanzar error —`v$resource_limit` lo hace desde el PDB y
`v$recovery_area_usage` lo hace siempre en esta instancia—. En ese caso la métrica
se marca **no recolectada**, sale del denominador del componente y baja
`cobertura_pct`. Nunca se publica como valor 0, que se leería como salud perfecta
justo en la métrica que dejó de funcionar.

---

## 5. Precedencias declaradas

Materia prima de la correlación del §6.1 del plan. Son relaciones **declaradas**,
revisables y discutibles, no correlaciones calculadas.

| Origen | Consecuencia | Por qué |
|---|---|---|
| `M-PRO-02` | `M-PRO-01` | Agotados los procesos, no hay dónde alojar sesiones nuevas |
| `M-ARC-01` | `M-ARC-02` | Un tablespace sin espacio deja archivos que no pueden extenderse |
| `M-MEM-03` | `M-MEM-01` | Sin espacio en la *shared pool* se descartan planes y crece el trabajo en PGA |

---

## 6. Lo que la versión 0 deja fuera a propósito

- **`CONSULTAS`**: ninguna métrica todavía. Conviene añadir una en cuanto el camino
  del índice esté probado, porque el componente que **no** suma es un camino de
  código distinto y fácil de equivocar.
- **Área de recuperación rápida**: `v$recovery_area_usage` devuelve cero filas aquí;
  entra cuando exista una FRA configurada.
- **Reinicio de procesos de fondo**: exige comparar el `SPID` con la muestra
  anterior. Es una buena métrica y una complicación innecesaria para la v0.
- **Bloqueos y cadenas de espera**: fuera del alcance de la versión 1 del plan.
- **Espacio del sistema de archivos**: con crecimiento automático activo, la salud
  real de `M-ARC-01` depende también de que el disco pueda crecer, y eso no se ve
  desde dentro de la base.
