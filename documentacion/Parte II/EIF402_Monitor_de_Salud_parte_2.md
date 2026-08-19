# Monitor de Salud de una Base de Datos Oracle — Plan de la parte 2

**EIF402 · Administración de Bases de Datos — Universidad Nacional, Escuela de Informática**
**Proyecto Rivendel · Grupo 4 · Parte 2**

> Ubicación propuesta en el repositorio: `documentacion/EIF402_Monitor_de_Salud_parte_2.md`.
> Este archivo es el contrato de diseño de la parte 2, igual que
> `documentacion/EIF402_Proyecto_Integrador_parte_1.md` lo fue de la primera.
> Nada de lo que se implemente debería contradecirlo sin actualizarlo antes.

---

## 0. Qué cambia respecto de la parte 1, en una frase

La parte 1 mide **cómo está diseñado el control**: se pregunta, se pide evidencia,
se califica madurez. La parte 2 mide **cómo se está comportando la máquina**: se
consulta, se normaliza, se vigila.

Es la diferencia entre revisar que el hospital tenga protocolo de urgencias y
tomarle el pulso al paciente. Las dos cosas son necesarias y **no se suman**: son
dos números distintos con dos significados distintos.

| | Parte 1 — Instrumento de auditoría | Parte 2 — Monitor de salud |
|---|---|---|
| Objeto medido | Control de seguridad (75 controles) | Recurso de la instancia (procesos, memoria, archivos) |
| Origen del dato | Entrevista + evidencia documental | Vistas dinámicas `V$` de Oracle |
| Frecuencia | Puntual (una auditoría) | Continua (una muestra cada N minutos) |
| Juez | El auditor | El agente, sin intervención humana |
| Resultado | Cumplimiento, madurez, exposición al riesgo | ISBD (0–100) y estado operativo |
| Ancla normativa | ISO/IEC 27002:2022 · 27007:2020 | ISO/IEC 27002:2022 A.8.16, A.8.6, A.8.15 · ISO/IEC 27004:2016 |

**El puente entre ambas:** el monitor es la *evidencia verificable* de los
controles C-064, C-065 y C-066 del catálogo de la parte 1 (proceso 20,
«Monitoreo y gestión del desempeño», anclado en A.8.16, A.8.6 e ISO/IEC 27004).
Hoy esos tres controles se responden con la palabra del auditado. Con la parte 2,
se responden con una serie de tiempo. Ese es el argumento de valor agregado más
fuerte que tiene el proyecto y debe decirse explícitamente en la defensa.

---

## 1. El error que hay que corregir antes de escribir una línea de código

El enunciado define el indicador de procesos así:

```
IP = Procesos actuales / Límite de procesos × 100
0–69 NORMAL · 70–84 ADVERTENCIA · 85–94 ALTO · 95–100 CRÍTICO
```

y más adelante define la escala del índice global así:

```
90–100 Óptimo · 75–89 Saludable · 60–74 Advertencia · 40–59 Degradado · 0–39 Crítico
```

**Las dos escalas corren en sentidos opuestos.** En la primera, 95 es una
emergencia; en la segunda, 95 es excelencia. El propio enunciado tropieza con
esto en la sección 20, donde «Procesos 95 🔴, Memoria 91 🔴» produce un índice
global de 94, y el documento tiene que salir del paso escribiendo a mano que el
estado real es crítico.

Eso no es un problema de ponderación. Es un termómetro que marca 95 y significa
fiebre en una página y salud en la otra. Ningún promedio arregla eso.

**Invariante 1 — Un solo sentido.**
Toda variable, antes de entrar a cualquier promedio, se convierte en una
**puntuación de salud** en el rango 0–100 donde **más alto es mejor, siempre**.
La utilización cruda (`%` de procesos, `%` de tablespace, `%` de PGA) nunca se
promedia directamente. Se guarda como valor crudo para mostrarla, y se promedia
su valor normalizado.

Esto es el mismo error de orden que ya se corrigió en la parte 1 (redondear antes
de derivar el porcentaje). La regla general se aplica igual aquí:

**Invariante 2 — Primero lo continuo, después lo discreto.**
Se calcula el número continuo, y solo al mostrarlo se lo clasifica en una banda.
Nunca al revés.

---

## 2. Marco normativo del monitor

El monitor no se inventa una metodología: instancia una que ya existe.

### 2.1 ISO/IEC 27004:2016 — el modelo de medición

Es la norma de la familia 27000 que da orientación para monitorear, medir,
analizar y evaluar el desempeño de la seguridad de la información, dando
cumplimiento a la cláusula 9.1 de ISO/IEC 27001. Su modelo de medición encadena
cuatro piezas y el monitor debe respetar esa cadena, con esos nombres:

| Pieza del modelo (27004) | En el monitor |
|---|---|
| **Medida base** (*base measure*) — atributo medido directamente, independiente de otras medidas | Una columna de una vista `V$`: `CURRENT_UTILIZATION`, `LIMIT_VALUE`, `bytes` |
| **Función de medición** → **medida derivada** | `utilizacion = current / limit × 100` |
| **Modelo analítico** → **indicador** | Normalización a salud, ponderación por componente, ISBD |
| **Criterios de decisión** — umbrales, metas o patrones que determinan si hace falta actuar | La tabla de umbrales por métrica y las bandas de estado |

Consecuencia práctica: **cada métrica del monitor se documenta con una ficha**,
al estilo del Anexo B de 27004 (necesidad de información, medida, fórmula, meta,
responsable, periodicidad). Es exactamente la misma disciplina con la que se
documentaron los 75 controles de la parte 1. Esas fichas son el «instrumento» de
la parte 2 y valen tanto como el código.

### 2.2 ISO/IEC 27002:2022 A.8.16 — Actividades de seguimiento

Control nuevo en la revisión 2022. Pide que redes, sistemas y aplicaciones se
vigilen en busca de comportamiento anómalo, y que se tomen acciones apropiadas
para evaluar posibles incidentes. Tres exigencias concretas que aterrizan en el
diseño:

1. **Línea base.** Hay que establecer qué es «normal» antes de poder detectar lo
   anormal. → El monitor no vive solo de umbrales fijos: calcula media y
   desviación por métrica sobre una ventana móvil y señala desviaciones
   significativas (§5.4).
2. **Alertas con pocos falsos positivos.** → Histéresis y ciclo de vida de la
   alerta (§6), no una fila nueva por muestra.
3. **Retención definida de los registros de monitoreo.** → Política de retención
   y consolidación explícita en el modelo de datos (§7.4).

### 2.3 ISO/IEC 27002:2022 A.8.6 — Gestión de capacidad

Control de doble naturaleza, preventivo y detectivo: exige vigilancia proactiva y
planificación de capacidad, no solo espacio en disco. El monitor de archivos y la
proyección de crecimiento de *tablespaces* responden a este control. Sin serie
histórica no hay planificación de capacidad; por eso el histórico no es un
adorno del tablero, es un requisito normativo.

### 2.4 Otros anclajes

- **A.8.15 Registro (*logging*)** — los registros del propio monitor son
  registros de seguridad: protegidos, con reloj confiable, no alterables por el
  operador vigilado.
- **A.8.17 Sincronización de relojes** — las marcas de tiempo de instancias
  distintas solo son comparables si los relojes lo son. Se almacena en UTC y se
  sella con el reloj del monitor, no con el de la instancia vigilada.
- **A.8.2 Derechos de acceso privilegiado** — la cuenta del agente es de solo
  lectura y de privilegio mínimo (§8.1).
- **A.5.30 Preparación de las TIC para la continuidad** — el monitor de archivos
  y de *redo logs* alimenta la evaluación de continuidad.

---

## 3. Alcance de la versión 1

Se implementa **exactamente lo que pide el enunciado**, ni una variable más:
tres componentes, un índice, alertas, tablero e histórico. Las versiones 2 a 4
del enunciado (E/S, SQL, seguridad, respaldos, predicción) quedan documentadas
como trabajo futuro y **no se prometen en la defensa**.

Dentro de alcance:

- Recolección periódica desde una o varias instancias Oracle registradas.
- Tres indicadores de componente: **IP** (procesos), **IM** (memoria), **IA** (archivos).
- Índice de Salud de la Base de Datos: **ISBD**, 0–100, con estado y causa.
- Alertas con nivel, umbral y ciclo de vida.
- Tablero por instancia y vista histórica.
- Persistencia de la serie de tiempo y consolidación horaria.

Fuera de alcance en la versión 1: E/S, análisis de SQL, cadenas de bloqueo
detalladas, predicción, notificación por correo o mensajería, agentes en motores
distintos de Oracle.

---

## 4. Arquitectura dentro de Rivendel

El monitor **no** se construye aparte. Se enchufa donde el sitio ya dejó el
hueco: `config/conexiones.php` documenta en su cabecera que es la
previsualización del monitoreo y que su forma —una fila por instancia, con su
motor— es la que tendrá la tabla cuando exista. Esa promesa se cumple ahora.

```
┌─────────────────────────────────────────────────────────────┐
│  INSTANCIAS ORACLE VIGILADAS   (solo lectura, V$)           │
└───────────────────────────┬─────────────────────────────────┘
                            │  cada N minutos, fuera de la petición web
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  AGENTE   bin/monitor.php  (CLI, lanzado por el programador  │
│  de tareas)                                                  │
│  recolecta → normaliza → calcula IP/IM/IA/ISBD → persiste    │
└───────────────────────────┬─────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  ESQUEMA MONITOR  (Oracle)  muestra · medicion · indice ·   │
│  alerta · resumen_hora                                       │
└───────────────────────────┬─────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  SITIO   RepositorioMonitor → /monitoreo                     │
│  el navegador SOLO LEE lo ya calculado                       │
└─────────────────────────────────────────────────────────────┘
```

### 4.1 Por qué la recolección va en un CLI y no en el controlador

Tres razones, y conviene tenerlas escritas porque alguien va a proponer lo
contrario por comodidad:

1. **Una serie de tiempo exige regularidad.** Una métrica muestreada solo cuando
   alguien abre la página no es una serie de tiempo, es una anécdota. La
   planificación de capacidad de A.8.6 no se puede hacer con anécdotas.
2. **La página no puede depender de una instancia colgada.** Si la base vigilada
   está en apuros —justo cuando más se necesita el tablero—, una consulta síncrona
   deja la petición web esperando el *timeout*. El tablero debe seguir pintando,
   con el último dato bueno y su antigüedad a la vista.
3. **El monitor no debe ser una carga.** Un agente con intervalo controlado y
   consultas acotadas es medible; N usuarios recargando F5 no lo es.

### 4.2 Contratos, para que nadie quede bloqueado

Se replica el patrón que ya funciona en el repositorio (`RepositorioContenido`,
`RepositorioInstrumento`, y el interruptor `config/base_datos.php`):

- `App\Models\Contratos\RepositorioMonitor` — interfaz: última muestra por
  instancia, serie histórica, alertas abiertas, catálogo de métricas.
- `RepositorioMonitorOracle` — implementación real.
- `RepositorioMonitorArreglo` — muestras de ejemplo en un archivo PHP, para que
  las vistas y el motor de cálculo se desarrollen y se prueben **sin Oracle
  levantado**. Es el mismo criterio que permitió trabajar el sitio público sin
  base de datos.
- Nuevo interruptor: `config/monitor.php` (en `.gitignore`, con su
  `monitor.ejemplo.php`) contiene credenciales del agente e intervalo. Sin él, la
  sección `/monitoreo` muestra su estado vacío y nada más se rompe.

---

## 5. Modelo de cálculo

### 5.1 Tres familias de señal, no una

No todo lo que mide el monitor es un porcentaje. Mezclarlas en un promedio es lo
que produce el absurdo de la sección 20 del enunciado.

| Familia | Ejemplo | Cómo entra al índice |
|---|---|---|
| **Proporción, menor es mejor** | procesos / límite; % de *tablespace* usado | Se normaliza (§5.2) y se promedia |
| **Proporción, mayor es mejor** | *cache hit percentage* de PGA | Se normaliza con las bandas invertidas |
| **Estado, binaria** | *datafile* OFFLINE; grupo de *redo* INVALID | **No se promedia: veta.** Salud 0 y estado CRÍTICO del componente |

### 5.2 Normalización: de utilización a salud

Función lineal por tramos, anclada en las mismas fronteras que la escala de
estado. Para una métrica «menor es mejor» con utilización `u` y umbrales
`u_adv` (70), `u_alto` (85), `u_crit` (95):

| Tramo de `u` | Tramo de salud `s` |
|---|---|
| `[0, 70]` | `[100, 85]` |
| `(70, 85]` | `(85, 60]` |
| `(85, 95]` | `(60, 30]` |
| `(95, 100]` | `(30, 0]` |

En cada tramo `[u₁,u₂] → [s₁,s₂]`:

```
s = s₁ + (u − u₁) × (s₂ − s₁) / (u₂ − u₁)
```

**La propiedad que hay que verificar en las pruebas** es que la banda a la que
pertenece `s` coincide con la banda a la que pertenece `u`. Si un valor que la
tabla de umbrales llama ADVERTENCIA aterriza en un `s` que la escala del índice
llama Saludable, la calibración está mal y todo lo que se construya encima
hereda la mentira.

Los umbrales viven en la tabla `metrica`, con posibilidad de anularlos por
instancia (`umbral_instancia`). Una base de desarrollo y una de producción no se
califican igual. **Los umbrales son dato, no código** — misma decisión que se
tomó con el catálogo de 75 controles.

### 5.3 De métrica a componente, y de componente a ISBD

```
I_componente = Σ (peso_i × salud_i) / Σ peso_i        ← solo métricas recolectadas
ISBD_bruto   = 0,30·IP + 0,35·IM + 0,35·IA
```

**Invariante 3 — Sin dato no es cero.**
Una métrica que no se pudo recolectar **sale del denominador**; no entra como 0.
Es literalmente la misma regla del «No aplica» de la parte 1, que sale del
denominador del cumplimiento por analogía con la Declaración de Aplicabilidad
(ISO/IEC 27001, cl. 6.1.3). Si un componente entero queda sin datos, su peso se
reparte proporcionalmente entre los otros dos; no se cuenta como salud cero.

**Invariante 4 — Cobertura mínima.**
Cada muestra guarda su `cobertura_pct` (métricas obtenidas ÷ métricas
planificadas). Por debajo de un piso configurable (se propone 80 %), **el ISBD no
se publica**: la muestra se marca PARCIAL y el tablero dice «muestra incompleta»
en lugar de un número. Un índice calculado sobre la mitad de la evidencia es
peor que ningún índice, porque parece uno bueno.

### 5.4 La regla del eslabón más débil

El promedio ponderado es una máquina de esconder emergencias: con dos
componentes en rojo y uno excelente, el enunciado mismo produce un 94. Se corrige
con un **tope por peor estado**, aplicado en los dos niveles (métrica→componente
y componente→ISBD):

| Peor estado observado en el nivel | Tope del indicador |
|---|---|
| CRÍTICO | 39 |
| DEGRADADO | 59 |
| ADVERTENCIA | 74 |
| — | sin tope |

```
ISBD = mín( ISBD_bruto , tope(peor estado entre IP, IM, IA) )
```

En cristiano: **el promedio puede bajar la nota, nunca sacar del rojo a un
componente que está en rojo.** Una cadena vale lo que su eslabón más débil, y la
salud de una base de datos se parece más a una cadena que a un promedio de notas.

Reaplicando el ejemplo problemático del enunciado con esta regla y con las
utilizaciones ya convertidas a salud, el resultado deja de necesitar una nota al
pie escrita a mano: el tope lo hace solo.

**Invariante 5 — El número nunca viaja solo.**
En toda pantalla, informe o exportación, el ISBD se muestra siempre acompañado de
(a) su estado y (b) la lista de métricas que están en el peor estado. Un número
sin causa no es información, es decoración.

### 5.5 Estabilidad: histéresis y línea base

- **Histéresis.** Una métrica cambia de estado solo si `k` de las últimas `n`
  muestras lo sostienen (propuesta inicial: 2 de 3 para subir de severidad, 3 de
  3 para bajar). Un pico aislado no es una enfermedad, y una alerta que parpadea
  entrena al operador a ignorarla — el modo de falla que A.8.16 llama fatiga de
  alertas.
- **Línea base.** Sobre una ventana móvil (propuesta: 14 días del mismo tramo
  horario) se calculan media y desviación por métrica. Una desviación
  significativa levanta una señal aunque el umbral absoluto no se haya cruzado.
  Sin esto no se cumple el «comportamiento anómalo» de A.8.16: solo se cumple el
  umbral fijo, que es otra cosa.

### 5.6 Los pesos 30/35/35 hay que justificarlos o cambiarlos

El enunciado los declara como punto de partida y pide que una etapa posterior
determine si son adecuados. Esa etapa somos nosotros. Dos entregables:

1. **Justificación por consecuencia de falla**, no por intuición: un *datafile*
   fuera de línea detiene el servicio; una *shared pool* apretada lo degrada.
   Archivos y memoria pesan más que procesos porque su falla es más terminal.
   Se documenta con la misma lógica de relación C-I-D que la parte 1 usa para los
   procesos del catálogo.
2. **Análisis de sensibilidad**: se recalculan las muestras históricas variando
   cada peso ±10 % y se cuenta cuántas veces cambia el **estado** publicado. Si
   cambia con facilidad, el modelo es frágil y hay que decirlo en la defensa. Si
   casi no cambia, la elección de pesos importa poco y también hay que decirlo.
   Cualquiera de los dos resultados es una contribución legítima; inventar una
   precisión que no se tiene, no.

---

## 6. Alertas

Una alerta **no es una fila de bitácora**. Tiene ciclo de vida:

```
abierta ──→ reconocida ──→ cerrada
   ▲                          │
   └────── reapertura ────────┘
```

- **Deduplicación:** existe como máximo una alerta abierta por
  `(instancia, métrica, nivel)`. Las muestras siguientes actualizan
  `vista_por_ultima_vez` y `ocurrencias`; no insertan filas nuevas. Un
  *tablespace* al 96 % con muestreo cada 5 minutos generaría 288 filas diarias si
  se hace mal.
- **Cierre automático** cuando la métrica vuelve a NORMAL y la histéresis lo
  confirma; cierre manual con nota y responsable.
- **Cada alerta registra** lo que pide el enunciado —fecha, hora, componente,
  variable, valor, umbral, nivel, descripción— más el responsable de atenderla.
  Sin responsable, la alerta no es un control: es un aviso.
- **Retención** de las alertas conforme a A.8.16 (registros de monitoreo con
  periodo de retención definido). Se propone 13 meses, para cubrir un ciclo anual
  de auditoría.

---

## 7. Modelo de datos

Esquema propio (`MONITOR`), separado del esquema de auditorías.

**Advertencia de diseño:** en la medida de lo posible, el repositorio del monitor
no debe vivir dentro de la instancia que vigila. Si la base cae, se lleva consigo
la historia que explicaría por qué cayó. Para el proyecto académico se acepta la
misma instancia, pero la limitación se documenta y se defiende como tal.

### 7.1 Tablas

| Tabla | Papel |
|---|---|
| `instancia` | Una fila por base vigilada: nombre, motor, host, servicio, entorno, criticidad, activa. **Es la tabla que `config/conexiones.php` venía anunciando.** |
| `metrica` | Catálogo de variables medibles: código, componente, nombre, unidad, vista `V$` de origen, sentido (`MENOR_MEJOR`/`MAYOR_MEJOR`/`ESTADO`), peso, umbrales, ancla ISO. Es el equivalente al catálogo de controles de la parte 1. |
| `umbral_instancia` | Anulación de umbrales para una instancia concreta. |
| `muestra` | Cabecera de una recolección: instancia, `tomada_en` (UTC), duración, resultado (`OK`/`PARCIAL`/`FALLIDA`), `cobertura_pct`, mensaje. |
| `medicion` | Una fila por métrica y muestra: `valor_crudo`, `valor_normalizado`, `estado`. Tabla angosta y de alto volumen. |
| `indice` | Una fila por muestra: `ip`, `im`, `ia`, `isbd_bruto`, `isbd`, `estado`, `causa`. |
| `alerta` | Ciclo de vida completo según §6. |
| `resumen_hora` | Consolidación horaria por instancia y métrica (mín, máx, promedio, p95, conteo de muestras). |

### 7.2 Decisiones que conviene dejar por escrito

- `valor_crudo` **y** `valor_normalizado` se guardan ambos. El crudo es lo que el
  DBA quiere ver («1607 de 4000 procesos»); el normalizado es lo que entró al
  índice. Recalcular el crudo desde el normalizado es imposible, y recalcular el
  normalizado con umbrales que cambiaron después falsearía la historia.
- `isbd_bruto` e `isbd` también se guardan ambos: la diferencia entre los dos es
  exactamente cuánto tuvo que intervenir la regla del eslabón más débil, y es un
  dato interesante para la defensa.
- Fechas en UTC con zona explícita (A.8.17).
- Índices mínimos: `muestra(instancia_id, tomada_en DESC)`,
  `medicion(muestra_id)`, `alerta(instancia_id, estado_atencion)`.

### 7.3 Volumen — el cálculo que evita la sorpresa

Con 25 métricas, 1 instancia y muestreo cada 5 minutos:
`25 × 288 = 7 200 filas/día` en `medicion`, unas **2,6 millones al año**. Con 5
instancias, 13 millones. No es dramático para Oracle, pero **exige política de
retención desde el día uno**, no cuando el disco avise.

### 7.4 Retención

| Dato | Retención | Razón |
|---|---|---|
| `medicion` en crudo | 30 días | Diagnóstico de incidentes recientes |
| `resumen_hora` | 13 meses | Planificación de capacidad (A.8.6) y comparación interanual |
| `indice` | 13 meses | Evolución del ISBD |
| `alerta` | 13 meses | Registro de monitoreo (A.8.16) |

Un trabajo programado consolida y purga. La consolidación corre **antes** de la
purga y su éxito se verifica; si falla, no se purga nada.

---

## 8. Seguridad del propio monitor

Un monitor mal hecho es una puerta trasera con tablero. Estas reglas no son
opcionales en un proyecto que audita ISO/IEC 27002.

### 8.1 La cuenta del agente

- Usuario dedicado (`RIVENDEL_MONITOR`), **solo lectura**, sin privilegios de
  administración, sin `DBA`, sin `SYSDBA`.
- **Concesiones explícitas sobre las vistas necesarias**, no `SELECT_CATALOG_ROLE`
  completo. El rol entero concede lectura sobre todo el diccionario, que es mucho
  más de lo que el monitor necesita — y el privilegio mínimo (A.8.2) se demuestra
  con la lista de `GRANT`, no con una declaración de intenciones.
- Contraseña fuera del repositorio: `config/monitor.php` en `.gitignore`, con su
  archivo de ejemplo versionado. Nunca en logs, nunca en mensajes de error,
  nunca en la salida del agente.
- Perfil con expiración y bloqueo por intentos fallidos.

### 8.2 El agente

- Tiempo límite por consulta y por muestra completa; una instancia que no
  responde marca la muestra FALLIDA y sigue con la siguiente.
- Cortacircuitos: tras `n` fallos consecutivos se espacia el reintento, para no
  martillar una base que ya está en problemas.
- El agente **nunca ejecuta DML sobre la instancia vigilada**. Solo `SELECT`.
  Ejecuciones que no sean `SELECT` deberían fallar por permisos, y además el
  código no debe tener la capacidad de emitirlas.

### 8.3 El tablero

- Requiere sesión iniciada, como el módulo de evaluación.
- Los nombres de host, servicios y usuarios son información sensible: no aparecen
  en páginas públicas ni en exportaciones sin control de acceso.
- Sin datos inventados. Si no hay muestras, se pinta el estado vacío. Es la misma
  regla que ya rige el banner de la portada: no se publica ninguna cifra
  fabricada. Una demostración con datos falsos en una herramienta de auditoría
  contradice todo lo que la herramienta afirma.

---

## 9. Interfaz

Rutas nuevas: `/monitoreo` (todas las instancias) y `/monitoreo/{instancia}`
(detalle e histórico).

Reglas del sistema visual de Rivendel que aplican aquí y que ya están decididas
—no se renegocian en esta parte del proyecto:

- **Estado con `pill()`**, nunca solo con color: contorno y texto teñido, con
  icono y etiqueta. Un tablero que comunica el rojo únicamente con rojo no sirve
  para una parte de la población y no cumple WCAG 2.2.
- **Cifras con clase `tabular`** y formato español: `82,8` no `82.8`. Un número
  que baila de posición al actualizarse es un número que se lee mal.
- **Gráfico histórico**: Rivendel no tiene un segundo tono categórico. Si hacen
  falta dos series (por ejemplo ISBD y una banda de referencia), se distinguen
  **por la forma de la marca** —columna contra línea—, no por color. **Nunca dos
  ejes verticales**: con dos escalas, quien elige los topes decide cuál línea va
  por encima, y eso no es un dato.
- **El oro es referencia normativa**, nunca estado. El estado tiene su propia
  escala semántica.
- **Antigüedad del dato siempre visible**: «última muestra hace 4 min». Un
  tablero que muestra un número de hace dos horas como si fuera de ahora es
  peligroso, y esa es la falla más común de los monitores caseros.

---

## 10. Riesgos técnicos identificados

| # | Riesgo | Mitigación |
|---|---|---|
| 1 | **`V$RESOURCE_LIMIT` no es consultable desde dentro de un PDB**: es información global de la instancia y debe consultarse desde `CDB$ROOT` o desde una base no multitenant. Rompe silenciosamente el indicador de procesos. | Decidir y documentar el punto de conexión desde el inicio. Probarlo en la primera semana, no en la última. |
| 2 | Límites del producto en ediciones acotadas (XE): el techo lo fija la licencia, no la máquina. | Documentar el techo real de la instancia de pruebas antes de calibrar. Los umbrales se calibran contra el límite efectivo. |
| 3 | Umbrales calibrados en una base de desarrollo ociosa | `umbral_instancia`; y en la defensa se presenta la calibración como propuesta con su método, no como verdad. |
| 4 | Crecimiento de `medicion` | Retención y consolidación desde el día uno (§7.4). |
| 5 | Relojes desincronizados entre instancias | Sellado con el reloj del monitor, almacenamiento en UTC (A.8.17). |
| 6 | El agente cargando la base vigilada | Intervalo configurable, consultas acotadas, tiempo límite, cortacircuitos. |
| 7 | Deriva entre lo que la vista muestra y lo que el motor calcula | El motor de cálculo es un servicio con pruebas propias; la vista no recalcula nada. |

---

## 11. División del trabajo (4 integrantes)

Igual que en la parte 1, primero se fijan los contratos y después cada quien
avanza sin depender de que otro termine.

### Fase 0 — Arranque conjunto (2 a 3 días, todo el equipo)

Se acuerdan tres contratos y se escriben en el repositorio:

1. **Catálogo de métricas versión 0**: código, componente, unidad, sentido,
   peso, umbrales. Aunque las consultas no existan todavía.
2. **Forma de una muestra** (estructura del arreglo/JSON que produce el agente y
   consume el motor de cálculo).
3. **Interfaz `RepositorioMonitor`** y rutas de las vistas.

Con esos tres papeles, las cuatro personas arrancan el mismo día.

### Persona 1 — Instrumento de medición

Catálogo de métricas completo con su ficha estilo Anexo B de 27004, consultas SQL
sobre las vistas `V$`, propuesta y justificación de umbrales, y el análisis de
sensibilidad de los pesos (§5.6). Es el equivalente al catálogo de 75 controles:
**el trabajo intelectual central de la parte 2.**

### Persona 2 — Datos y agente

Esquema `MONITOR` (DDL, diccionario de datos, datos semilla del catálogo),
`RepositorioMonitorOracle`, `bin/monitor.php`, programación de la tarea,
consolidación y purga, cuenta de solo lectura con sus `GRANT`.

### Persona 3 — Motor de cálculo

Normalización, indicadores por componente, ISBD, regla del eslabón más débil,
histéresis, línea base, motor de alertas con ciclo de vida. **Con pruebas
unitarias**, incluida la verificación del Invariante 1 (coincidencia de bandas) y
del Invariante 3 (sin dato ≠ cero). Trabaja contra
`RepositorioMonitorArreglo`, sin depender de que Oracle esté levantado.

### Persona 4 — Interfaz y documentación

Vistas `/monitoreo`, tablero, histórico, listado de alertas, estados vacíos,
exportación, textos en `config/idiomas/`, manual de usuario y sección del manual
técnico. Trabaja también contra el repositorio de arreglo desde el primer día.

---

## 12. Criterios de aceptación

La parte 2 está terminada cuando **todas** estas afirmaciones son verificables:

- [ ] Ninguna utilización cruda entra a un promedio: todo pasa por normalización a salud.
- [ ] Para cada métrica, la banda de estado derivada de `s` coincide con la derivada de `u`. Hay una prueba automatizada que lo comprueba.
- [ ] Una métrica no recolectada sale del denominador; no vale cero. Hay una prueba que lo comprueba.
- [ ] Con cobertura bajo el piso, el sistema no publica ISBD; publica «muestra incompleta».
- [ ] Ningún componente en estado CRÍTICO puede convivir con un ISBD publicado sobre 39.
- [ ] El ISBD nunca se muestra sin estado y sin causa.
- [ ] Un pico aislado no cambia el estado publicado (histéresis verificada con datos sintéticos).
- [ ] Una condición sostenida genera **una** alerta, no una por muestra.
- [ ] La cuenta del agente no puede ejecutar DML en la instancia vigilada (comprobado intentándolo).
- [ ] No hay credenciales en el repositorio.
- [ ] El tablero pinta correctamente cuando la instancia vigilada está caída, mostrando la antigüedad del último dato.
- [ ] No aparece ninguna instancia, métrica o cifra inventada en ninguna pantalla.
- [ ] Todo estado se comunica con icono y etiqueta, no solo con color; contrastes verificados contra WCAG 2.2.
- [ ] Cada métrica tiene su ficha de medición documentada (necesidad de información, medida base, función, indicador, criterio de decisión, periodicidad, responsable).
- [ ] Cada métrica declara su anclaje normativo.

---

## 13. Valor agregado para la defensa

Tres funcionalidades que van más allá del enunciado y que se pueden justificar
con norma en la mano:

1. **El monitor como evidencia de la auditoría.** Los controles C-064, C-065 y
   C-066 dejan de responderse con una afirmación y pasan a responderse con datos
   medidos. Es exactamente lo que ISO/IEC 27007 pide de la evidencia de auditoría
   y lo que el propio proyecto ya exige en su esquema
   (`evidencia_verificada` obligatoria cuando el estado es «Sí»).
2. **Línea base estadística además de umbrales fijos**, que es lo que A.8.16 pide
   de verdad cuando habla de comportamiento anómalo, y que casi ningún trabajo de
   curso implementa.
3. **Regla del eslabón más débil con análisis de sensibilidad de pesos.** No solo
   se propone una metodología: se muestra cuánto depende el resultado de las
   decisiones arbitrarias que la componen. Eso es honestidad metodológica y se
   nota en una defensa.

---

## 14. Fuentes

1. **ISO/IEC 27004:2016**, *Information technology — Security techniques —
   Information security management — Monitoring, measurement, analysis and
   evaluation*. Orientación para evaluar el desempeño de la seguridad de la
   información y la eficacia del SGSI, en cumplimiento de ISO/IEC 27001 cl. 9.1.
   Modelo de medición: medida base → función de medición → medida derivada →
   modelo analítico → indicador → criterios de decisión. Anexo B con ejemplos de
   constructos de medición. <https://www.iso.org/standard/64120.html>
2. **ISO/IEC 27002:2022, control 8.16 — Actividades de seguimiento.** Redes,
   sistemas y aplicaciones deben vigilarse en busca de comportamiento anómalo;
   exige establecer una línea base de comportamiento normal, alertar con pocos
   falsos positivos y conservar los registros de monitoreo durante un periodo
   definido. Control nuevo en la revisión de 2022.
   (Resumen normativo: ISACA, *A Guide to the Updated ISO/IEC 27002:2022
   Standard, Part 2*, 2023.)
3. **ISO/IEC 27002:2022, control 8.6 — Gestión de capacidad.** Control preventivo
   y detectivo; exige vigilancia proactiva y planificación de capacidad de los
   recursos, no solo espacio de almacenamiento.
4. **Oracle Database Reference — `V$RESOURCE_LIMIT`.** Uso global de recursos del
   sistema; columnas `CURRENT_UTILIZATION` (recursos en uso), `MAX_UTILIZATION`
   (máximo desde el último arranque de la instancia), `INITIAL_ALLOCATION` y
   `LIMIT_VALUE`. Superar `LIMIT_VALUE` produce error.
   <https://docs.oracle.com/en/database/oracle/oracle-database/18/refrn/V-RESOURCE_LIMIT.html>
5. **Oracle Database Reference** — `V$PROCESS`, `V$SESSION`, `V$SESSION_LONGOPS`,
   `V$SGASTAT`, `V$PGASTAT`, `V$MEMORY_DYNAMIC_COMPONENTS`, `V$DATAFILE`,
   `V$TEMPFILE`, `V$LOG`, `V$LOGFILE`. Fuentes de las medidas base del monitor.
6. **Limitación multitenant de `V$RESOURCE_LIMIT`**: no consultable desde dentro
   de un PDB por tratarse de información de nivel global; debe consultarse desde
   un CDB o desde una base no multitenant (Oracle Doc ID 2303360.1, reportado en
   la práctica en `lausser/check_oracle_health`, incidencia 32).
7. **ISO/IEC 27001:2022, cl. 6.1.3** — Declaración de Aplicabilidad. Origen de la
   regla de exclusión del denominador que la parte 2 hereda de la parte 1.

---

*Declaración de uso de herramientas de inteligencia artificial generativa
conforme al punto VII.9 de la normativa del programa: este documento fue
elaborado con asistencia de un modelo de lenguaje a partir del enunciado del
curso y del estado actual del repositorio, y revisado por el equipo.*
