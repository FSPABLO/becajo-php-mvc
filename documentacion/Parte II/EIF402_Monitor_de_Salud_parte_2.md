# Monitor de Salud de una Base de Datos Oracle — Plan de la parte 2

**EIF402 · Administración de Bases de Datos — Universidad Nacional, Escuela de Informática**
**Proyecto Rivendel · Grupo 4 · Parte 2**

> Ubicación en el repositorio: `documentacion/Parte II/EIF402_Monitor_de_Salud_parte_2.md`.
> Este archivo es el contrato de diseño de la parte 2, igual que
> `documentacion/Parte I/EIF402_Proyecto_Integrador_parte_1.md` lo fue de la primera.
> Nada de lo que se implemente debería contradecirlo sin actualizarlo antes.
>
> **Sobre el documento de origen, y hasta dónde llega.** La parte 2 no tiene un
> enunciado equivalente al de la parte 1. Lo que hay es
> [`EIF402_Guia_monitoreo_procesos_Oracle.md`](EIF402_Guia_monitoreo_procesos_Oracle.md):
> un **método de catorce pasos**, no una especificación. Dice qué hacer —identificar
> procesos, crear una línea base, definir umbrales, alertar, historiar, presentar— y
> deliberadamente **no fija ninguna cifra**: ni fórmulas, ni escalas, ni pesos, ni
> componentes.
>
> De ahí se sigue lo más importante de este documento: **todo el modelo numérico es
> aporte del equipo.** Las fórmulas, las cinco bandas, los umbrales, los pesos, la
> regla del eslabón más débil y los invariantes no se heredan de ninguna parte y no
> se defienden diciendo «así venía»: se defienden con el razonamiento que los
> acompaña y con las normas del §2. El §2.5 rastrea paso por paso qué del método está
> cubierto y dónde, y el anexo A recoge cada decisión de modelo con su justificación.
>
> El plan se ancla, entonces, en tres cosas verificables y en ninguna más: la guía,
> las normas ISO del §2, y el producto que ya existe (los 75 controles cargados, el
> sistema visual Rivendel, el patrón de repositorios).

---

## 0. Qué cambia respecto de la parte 1, en una frase

**Son dos instrumentos distintos y no hay que confundirlos:**

- El **instrumento de medición** es la parte 1: los 75 controles, el cuestionario,
  la madurez y la exposición al riesgo. **Está terminado y evaluado.** Este
  documento no lo modifica ni propone modificarlo; solo se apoya en él.
- El **instrumento de salud** es la parte 2: conectar bases de datos y vigilar sus
  procesos. Es lo que se diseña aquí, y no existe todavía.

La parte 1 mide **cómo está diseñado el control**: se pregunta, se pide evidencia,
se califica madurez. La parte 2 mide **cómo se está comportando la máquina**: se
conecta, se consulta, se normaliza, se vigila.

Es la diferencia entre revisar que el hospital tenga protocolo de urgencias y
tomarle el pulso al paciente. Las dos cosas son necesarias y **no se suman**: son
dos números distintos con dos significados distintos, y ninguna cifra de la parte 2
entra en los cálculos de la parte 1.

| | Parte 1 — Instrumento de medición | Parte 2 — Instrumento de salud |
|---|---|---|
| Objeto medido | Control de seguridad (75 controles) | Procesos de fondo y recursos de la instancia (memoria, archivos) |
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

Cómo cubre cada uno, porque los tres no se cubren igual:

- **C-064** («monitoreo continuo con alertas configuradas y destinatario definido»)
  con la existencia misma del agente, el catálogo de alertas del §6 y la cobertura
  de instancias del §7.1.
- **C-065** («indicadores con meta, responsable y periodicidad, reportados a la
  dirección») con las fichas de medición del §2.1, que son literalmente eso.
- **C-066** («las consultas de mayor consumo se identifican periódicamente y se
  optimizan con seguimiento de la mejora») con el componente de consultas del §3:
  registra el top de sentencias por costo en cada muestra, y la serie histórica es
  el comparativo «antes y después» que el control pide como evidencia.

---

## 1. La trampa que hay que evitar antes de escribir una línea de código

El paso 7 de la guía pide «definir umbrales, tres niveles, por ejemplo Normal,
Advertencia, Crítico», y el paso 12, «presentar la información mediante
indicadores». Entre esas dos frases hay un abismo que se traga a la mayoría de los
monitores caseros, y conviene cruzarlo a propósito y no de casualidad.

La forma natural de escribir un indicador de utilización es esta:

```
IP = Procesos actuales / Límite de procesos × 100
0–69 NORMAL · 70–84 ADVERTENCIA · 85–94 ALTO · 95–100 CRÍTICO
```

y la forma natural de escribir un índice global de salud es esta:

```
90–100 Óptimo · 75–89 Saludable · 60–74 Advertencia · 40–59 Degradado · 0–39 Crítico
```

Las dos son razonables por separado y **corren en sentidos opuestos**. En la
primera, 95 es una emergencia; en la segunda, 95 es excelencia. Juntas producen un
sistema donde dos componentes en rojo y uno excelente arrojan un índice que se lee
como salud aceptable, y hay que salir del paso anotando a mano que el estado real
es crítico. El §5.4 desarrolla el caso con números.

No es un problema de ponderación. Es un termómetro que marca 95 y significa fiebre
en una página y salud en la otra. Ningún promedio arregla eso, y ninguna cantidad
de trabajo posterior lo repara: si las dos escalas entran al modelo, todo lo que se
construya encima hereda la ambigüedad.

De ahí salen los dos invariantes que gobiernan el resto del documento. **No vienen
de ninguna fuente externa**; son la decisión de diseño que hace que las catorce
piezas del método encajen sin contradecirse.

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
documentaron los 75 controles de la parte 1. **Esas fichas son el instrumento de
salud** —el entregable conceptual de la parte 2— y valen tanto como el código.

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

### 2.5 Rastreo del método de la guía

La guía del curso es un método de catorce pasos. Esta tabla dice dónde queda
cubierto cada uno; es lo primero que hay que poder mostrar si preguntan si el
proyecto responde a lo que se pidió.

| # | Paso de la guía | Dónde queda |
|---|---|---|
| 1 | Identificar los procesos importantes (DBWn, LGWR, SMON, PMON, CKPT) | §3.2 — componente PROCESOS, familia de vitalidad |
| 2 | Definir qué conocer de cada proceso | §2.1 — ficha de medición por métrica |
| 3 | Seleccionar las métricas | Tabla `metrica` (§7.1); el catálogo es el instrumento de salud (§11) |
| 4 | Identificar dónde obtenerlas | Columna `vista_origen` de `metrica`; fuentes en §14 |
| 5 | Realizar mediciones normales | Ventana móvil de la línea base, §5.5 |
| 6 | Crear una línea base | §5.5 — media y desviación por métrica y tramo horario |
| 7 | Definir umbrales (tres niveles) | §5.2 — **cinco bandas**, decisión propia; ver anexo A |
| 8 | Automatizar la medición | §4 — agente CLI con intervalo, fuera de la petición web |
| 9 | Generar alertas | §6 — con ciclo de vida, no una fila por muestra |
| 10 | Guardar un historial | §7 — serie de tiempo, consolidación y retención |
| 11 | Relacionar las alertas | §6.1 — agrupación por causa común |
| 12 | Presentar la información | §9 — tablero, histórico, estados |
| 13 | Analizar y corregir | §6 — responsable, nota de cierre y acción registrada |
| 14 | Revisar y ajustar el monitoreo | §5.6 (pesos) y §7.2 (umbrales versionados) |

Dos observaciones que conviene tener listas para la defensa:

- **El paso 6 no es valor agregado, es requisito.** La línea base estadística está
  pedida explícitamente por la guía y por A.8.16. El §13 lo tiene en cuenta.
- **Los pasos 1 al 4 son trabajo de instrumento, no de código.** Son el equivalente
  exacto de seleccionar y justificar los 75 controles de la parte 1, y es donde se
  juega la nota igual que se jugó allá.

---

## 3. Alcance de la versión 1

Se implementan **los catorce pasos de la guía** (rastreados uno a uno en §2.5) con
el modelo de cálculo del §5: tres componentes que forman el índice, más un
componente de consultas que no entra al índice (§3.1). Las ampliaciones que la guía
no pide (E/S, seguridad, respaldos, predicción) quedan documentadas como trabajo
futuro y **no se prometen en la defensa**.

Dentro de alcance:

- Recolección periódica desde una o varias instancias Oracle registradas.
- Tres indicadores de componente que forman el índice: **IP** (procesos),
  **IM** (memoria), **IA** (archivos).
- Un cuarto componente **observado y no indexado**: **CONSULTAS** (§3.1).
- Índice de Salud de la Base de Datos: **ISBD**, 0–100, con estado y causa.
- Alertas con nivel, umbral y ciclo de vida.
- Tablero por instancia y vista histórica.
- Persistencia de la serie de tiempo y consolidación horaria.

Fuera de alcance en la versión 1: E/S, cadenas de bloqueo detalladas, predicción,
notificación por correo o mensajería, agentes en motores distintos de Oracle.

### 3.1 El componente CONSULTAS: se mide, se muestra, no se promedia

Existe por una razón concreta: el control **C-066** del catálogo de la parte 1 pide
que «las consultas de mayor consumo se identifiquen periódicamente y se optimicen
con seguimiento de la mejora obtenida», y su evidencia esperada es un comparativo
de tiempos antes y después. Sin este componente, el monitor cubriría dos de los
tres controles del proceso 20 y habría que recortar la promesa del §0.

Cada muestra registra el top-N de sentencias por costo (`V$SQLSTATS`:
`sql_id`, `plan_hash_value`, ejecuciones, tiempo de CPU y transcurrido por
ejecución, lecturas lógicas por ejecución) y el conteo de sentencias que superan un
umbral de tiempo por ejecución.

**Pero no entra al ISBD**, por tres razones que conviene tener escritas:

1. **Los pesos.** El §5.6 se compromete a un análisis de sensibilidad de 30/35/35.
   Un cuarto sumando cambia los tres pesos y deja ese análisis sin línea de
   comparación.
2. **No es una proporción.** «14,2 s de CPU por ejecución» no tiene techo natural
   como lo tiene «1607 de 4000 procesos». Normalizarlo a salud 0–100 exige un
   umbral arbitrario que no se puede defender con la solidez de los otros.
   El §5.1 ya prohíbe mezclar familias de señal distintas en un promedio.
3. **El control no pide un número, pide una serie.** Lo que C-066 exige como
   evidencia es el comparativo antes/después de una sentencia concreta. Eso lo da
   el histórico del top-N; resumirlo en un índice lo destruiría.

Sí genera alertas y sí aparece en el tablero, con su propia ficha de medición.
Es un componente de pleno derecho; simplemente no es un sumando del ISBD.

### 3.2 El componente PROCESOS mide dos cosas distintas

El paso 1 de la guía es explícito y da nombres: *«determinar cuáles procesos de
Oracle se van a monitorear, por ejemplo, DBWn, LGWR, SMON, PMON y CKPT»*. Son los
**procesos de fondo** del motor, y su salud no es un porcentaje de nada: es si
están vivos y si están haciendo su trabajo a tiempo.

Eso es distinto de «cuántas sesiones caben todavía», que es agotamiento de un
recurso. Un monitor que solo mide lo segundo puede informar holgura perfecta con
el escritor de redo atascado. El componente PROCESOS mide **las dos familias**:

| Familia | Ejemplo de métrica | Fuente | Tipo (§5.1) |
|---|---|---|---|
| Agotamiento del recurso | Sesiones / límite; procesos / límite | `V$RESOURCE_LIMIT` (desde `CDB$ROOT`, §10.1) | Proporción, menor es mejor |
| Vitalidad del proceso | Presencia de PMON, SMON, DBWn, LGWR, CKPT | `V$BGPROCESS` con `paddr <> '00'` | **Estado, binaria — veta** |
| Vitalidad del proceso | Reinicio detectado: el proceso reaparece con otro `SPID` respecto de la muestra anterior | `V$PROCESS` | **Estado, binaria — veta** |
| Desempeño del proceso | Espera media de escritura de redo (`log file parallel write`) | `V$SYSTEM_EVENT` | Proporción contra meta, menor es mejor |
| Desempeño del proceso | Antigüedad del último punto de control | `V$INSTANCE_RECOVERY` | Proporción contra meta, menor es mejor |

**Por qué van dentro de PROCESOS y no en un componente nuevo:** un cuarto sumando
obligaría a redistribuir los pesos 30/35/35 y dejaría el análisis de sensibilidad
del §5.6 sin línea de comparación, por el mismo argumento del §3.1. Y
conceptualmente pertenecen ahí: el paso 1 de la guía se titula «identificar los
procesos importantes», y estos lo son.

**La maquinaria para tratarlos ya existe y no hay que inventar nada.** Un proceso
de fondo ausente es una señal de familia ESTADO, que el §5.1 ya resuelve: no se
promedia, **veta** —salud 0 y componente en CRÍTICO—. Encadenado con el tope del
§5.4, si PMON no está, el ISBD no puede publicarse por encima de 40 por mucha
memoria libre que haya. Que es exactamente la respuesta correcta: una instancia sin
monitor de procesos no está sana, y ningún promedio debería poder decir lo
contrario.

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
que produce el absurdo que corrige el §5.4.

| Familia | Ejemplo | Cómo entra al índice |
|---|---|---|
| **Proporción, menor es mejor** | procesos / límite; % de *tablespace* usado | Se normaliza (§5.2) y se promedia |
| **Proporción, mayor es mejor** | *cache hit percentage* de PGA | Se normaliza con las bandas invertidas |
| **Estado, binaria** | Proceso de fondo ausente (PMON, SMON, DBWn, LGWR, CKPT); *datafile* OFFLINE; grupo de *redo* INVALID | **Compuerta, no sumando.** Abierta, no entra al promedio; cerrada, salud 0 y componente CRÍTICO. Ver §1.3 del catálogo |

### 5.2 Una sola escala de cinco bandas

La guía pide umbrales de «tres niveles, por ejemplo Normal, Advertencia, Crítico»
y no dice nada sobre la escala del índice global, así que la elección es del
equipo. El §1 mostró el riesgo de improvisarla: si la métrica se califica con un
juego de estados (NORMAL, ADVERTENCIA, ALTO, CRÍTICO) y el índice con otro (Óptimo,
Saludable, Advertencia, Degradado, Crítico), hay que mantener una tabla de
traducción en el código, en la base y en la interfaz, y NORMAL queda cubriendo dos
bandas a la vez —es decir, la propiedad de coincidencia no se puede enunciar como
una igualdad ni, por lo tanto, probar.

**Decisión: cinco bandas en los tres niveles** —métrica, componente e índice—
con los nombres de la escala del índice. NORMAL se parte en ÓPTIMO y SALUDABLE
(la métrica gana un cuarto umbral, `u_opt`) y ALTO pasa a llamarse DEGRADADO.

| Banda | Salud `s` | Utilización `u` (menor es mejor) | `pill()` |
|---|---|---|---|
| **ÓPTIMO** | `(90, 100]` | `[0, u_opt)` — por omisión `[0, 50)` | `opt` |
| **SALUDABLE** | `(75, 90]` | `[u_opt, u_adv)` — `[50, 70)` | `ok` |
| **ADVERTENCIA** | `(60, 75]` | `[u_adv, u_deg)` — `[70, 85)` | `warn` |
| **DEGRADADO** | `(40, 60]` | `[u_deg, u_crit)` — `[85, 95)` | `bad` |
| **CRÍTICO** | `[0, 40]` | `[u_crit, 100]` — `[95, 100]` | `crit` |

**Convención de intervalos, que no es un detalle:** los tramos de `u` van cerrados
por abajo y los de `s` cerrados por arriba. La función es decreciente, así que el
extremo cerrado de un lado es el extremo cerrado del otro; si las dos escalas se
cierran del mismo lado, `u = 70` cae en una banda y su `s = 75` en la contigua, y
la prueba de coincidencia falla en las cinco fronteras.

**El quinto tono no inventa un color.** `pill()` ya resuelve este caso: hoy `crit`
comparte color con `bad` y se distingue por el ícono —un octógono es la señal de
alto, no una advertencia más ([`app/Core/funciones.php`](../../app/Core/funciones.php)).
`opt` se agrega igual: comparte el tono de `ok` y lleva el ícono `escudo`. El
sistema visual sigue teniendo cuatro niveles de color y cinco de significado, que
es exactamente lo que se necesita.

### 5.2.1 Normalización: de utilización a salud

Función lineal por tramos, anclada en las fronteras de la tabla anterior. Para una
métrica «menor es mejor»:

| Tramo de `u` | Tramo de `s` |
|---|---|
| `[0, 50)` | `(90, 100]` |
| `[50, 70)` | `(75, 90]` |
| `[70, 85)` | `(60, 75]` |
| `[85, 95)` | `(40, 60]` |
| `[95, 100]` | `[0, 40]` |

En cada tramo `[u₁, u₂) → (s₂, s₁]`:

```
s = s₁ − (u − u₁) × (s₁ − s₂) / (u₂ − u₁)
```

Para una métrica «mayor es mejor» se aplica la misma tabla sobre `100 − u`; no hay
una segunda fórmula que mantener.

**La propiedad que hay que verificar en las pruebas** es que la banda a la que
pertenece `s` coincide con la banda a la que pertenece `u`. Si un valor que la
tabla de umbrales llama ADVERTENCIA aterriza en un `s` que la escala del índice
llama Saludable, la calibración está mal y todo lo que se construya encima hereda
la mentira. Con los tramos de arriba, las cinco fronteras coinciden por
construcción:

| `u` | Banda por umbral | `s` | Banda por salud | |
|---|---|---|---|---|
| 0 | ÓPTIMO | 100,0 | ÓPTIMO | ✓ |
| 49,9 | ÓPTIMO | 90,0 | ÓPTIMO | ✓ |
| 50 | SALUDABLE | 90,0 | SALUDABLE | ✓ |
| 62 | SALUDABLE | 81,0 | SALUDABLE | ✓ |
| 70 | ADVERTENCIA | 75,0 | ADVERTENCIA | ✓ |
| 76 | ADVERTENCIA | 69,0 | ADVERTENCIA | ✓ |
| 85 | DEGRADADO | 60,0 | DEGRADADO | ✓ |
| 95 | CRÍTICO | 40,0 | CRÍTICO | ✓ |
| 96 | CRÍTICO | 32,0 | CRÍTICO | ✓ |
| 100 | CRÍTICO | 0,0 | CRÍTICO | ✓ |

Esa tabla es, literalmente, el primer caso de prueba del motor de cálculo.

Los umbrales —ahora cuatro por métrica— viven en la tabla `metrica`, con
posibilidad de anularlos por instancia (tabla `umbral`, §7.1). Una base de desarrollo
y una de producción no se califican igual. **Los umbrales son dato, no código** —
misma decisión que se tomó con el catálogo de 75 controles. `u_opt = 50` es solo el
valor por omisión: la mitad del recurso consumido ya no es holgura, aunque falte
mucho para el problema.

### 5.3 De métrica a componente, y de componente a ISBD

```
I_componente = Σ (peso_i × salud_i) / Σ peso_i        ← solo métricas recolectadas
ISBD_bruto   = 0,30·IP + 0,35·IM + 0,35·IA
```

Dos precisiones sobre esa segunda línea:

- **CONSULTAS no aparece.** Es deliberado y está justificado en el §3.1. El
  componente se calcula, se muestra y alerta, pero no es un sumando.
- **`IP`, `IM` e `IA` son los valores ya publicados**, es decir, después del tope
  del §5.4, no los brutos del componente. El tope se aplica en el nivel donde se
  observa el peor estado y el resultado es lo que viaja hacia arriba; si el ISBD
  promediara los brutos, un componente ya rescatado del rojo volvería a entrar
  como si no lo estuviera.

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

El promedio ponderado es una máquina de esconder emergencias: con dos componentes
en rojo y uno excelente, la media sale en verde. Se corrige con un **tope por peor
estado**, aplicado en los dos niveles (métrica→componente y componente→ISBD). El
tope de cada banda es su frontera superior, la misma de la tabla del §5.2:

| Peor estado observado en el nivel | Tope del indicador |
|---|---|
| CRÍTICO | 40 |
| DEGRADADO | 60 |
| ADVERTENCIA | 75 |
| SALUDABLE | 90 |
| ÓPTIMO | sin tope |

```
ISBD = mín( ISBD_bruto , tope(peor estado entre IP, IM, IA) )
```

Los topes son las fronteras exactas (40, 60, 75, 90) y no un punto por debajo
(39, 59, 74): por el invariante 2 el índice es continuo hasta que se muestra, las
bandas de `s` están cerradas por arriba y un ISBD topado en 40,0 pertenece a
CRÍTICO. Restar un punto solo introduciría un artefacto de un decimal en el número
que se publica.

La fila de SALUDABLE es nueva y se sigue del mismo razonamiento: un componente
que apenas está saludable no puede sostener un índice global que se anuncie como
óptimo.

En cristiano: **el promedio puede bajar la nota, nunca sacar del rojo a un
componente que está en rojo.** Una cadena vale lo que su eslabón más débil, y la
salud de una base de datos se parece más a una cadena que a un promedio de notas.

#### Caso trabajado

Una muestra completa de `PRODCORE1`, con los pesos de métrica entre paréntesis:

| Componente | Métrica | `u` | `s` | Banda |
|---|---|---|---|---|
| **IP** | Sesiones / límite (2) | 96,0 | 32,0 | CRÍTICO |
| | Procesos / límite (1) | 62,0 | 81,0 | SALUDABLE |
| **IM** | PGA usada / objetivo (2) | 76,0 | 69,0 | ADVERTENCIA |
| | *Shared pool* usada (1) | 44,0 | 91,2 | ÓPTIMO |
| **IA** | *Tablespace* usado (2) | 41,0 | 91,8 | ÓPTIMO |
| | *Datafiles* en línea (compuerta) | — | abierta | ÓPTIMO |

```
IP_bruto = (2×32,0 + 1×81,0) / 3 = 48,3    peor: CRÍTICO      → IP = mín(48,3; 40) = 40,0
IM_bruto = (2×69,0 + 1×91,2) / 3 = 76,4    peor: ADVERTENCIA  → IM = mín(76,4; 75) = 75,0
IA_bruto = 91,8 (la compuerta no promedia)  peor: ÓPTIMO       → IA = 91,8

ISBD_bruto = 0,30×40,0 + 0,35×75,0 + 0,35×91,8 = 70,4
peor estado entre componentes: CRÍTICO (IP)                   → tope 40

ISBD = mín(70,4 ; 40) = 40,0   →   CRÍTICO
```

Lo que hay que leer en ese bloque: **70,4 se publicaría como ADVERTENCIA**, es
decir, como una base que aguanta el fin de semana. Y la base está a cuatro sesiones
de rechazar conexiones. El tope no es un ajuste cosmético; es la diferencia entre
un tablero que avisa y uno que tranquiliza.

Obsérvese además que el tope actuó dos veces y por motivos distintos: dentro de IP
(48,3 → 40,0), porque una métrica en crítico no puede promediarse con una sana; y
sobre el índice (70,4 → 40,0), porque un componente en crítico no puede
promediarse con dos buenos. La diferencia `isbd_bruto − isbd` que el §7.2 manda
guardar vale aquí 30,4 puntos: eso es exactamente cuánto tuvo que intervenir la
regla, y es el dato que conviene llevar a la defensa.

**Invariante 5 — El número nunca viaja solo.**
En toda pantalla, informe o exportación, el ISBD se muestra siempre acompañado de
(a) su estado y (b) la lista de métricas que están en el peor estado. Un número
sin causa no es información, es decoración.

### 5.5 Estabilidad: histéresis y línea base

- **Histéresis.** Una métrica cambia de estado solo si `k` de las últimas `n`
  muestras lo sostienen (valor de arranque: 2 de 3 para subir de severidad, 3 de
  3 para bajar). Un pico aislado no es una enfermedad, y una alerta que parpadea
  entrena al operador a ignorarla — el modo de falla que A.8.16 llama fatiga de
  alertas.
- **Línea base.** Sobre una ventana móvil (propuesta: 14 días del mismo tramo
  horario) se calculan media y desviación por métrica. Una desviación
  significativa levanta una señal aunque el umbral absoluto no se haya cruzado.
  Sin esto no se cumple el «comportamiento anómalo» de A.8.16: solo se cumple el
  umbral fijo, que es otra cosa.

### 5.6 Los pesos 30/35/35 hay que justificarlos o cambiarlos

La guía no menciona pesos ni índice global: los tres números son una elección del
equipo (A-6) y por eso hay que sostenerlos en vez de declararlos. Dos entregables:

1. **Justificación por consecuencia de falla**, no por intuición: un *datafile*
   fuera de línea detiene el servicio; una *shared pool* apretada lo degrada.
   Archivos y memoria pesan más que procesos porque su falla es más terminal.
   Se documenta con la misma lógica de relación C-I-D que la parte 1 usa para los
   procesos del catálogo. Desarrollo completo en §5.6.1.
2. **Análisis de sensibilidad**: se recalculan las muestras históricas variando
   cada peso ±10 % y se cuenta cuántas veces cambia el **estado** publicado. Si
   cambia con facilidad, el modelo es frágil y hay que decirlo en la defensa. Si
   casi no cambia, la elección de pesos importa poco y también hay que decirlo.
   Cualquiera de los dos resultados es una contribución legítima; inventar una
   precisión que no se tiene, no.

   **De dónde salen esas muestras.** `medicion` se purga a los 30 días (§7.4), así
   que el análisis no puede depender de ella. Antes de la purga se congela un lote
   con nombre —una ventana continua declarada, no una selección conveniente— y ese
   lote es el que se reanaliza y el que se cita en la defensa. Elegir la ventana
   después de ver los resultados sería escoger los datos que confirman la
   conclusión, y eso no es un análisis de sensibilidad.

### 5.6.1 Desarrollo del entregable 1: por qué 30/35/35 y no otra cosa

El criterio es **alcance del impacto** y **reversibilidad**, tomados del modo de
falla que cada catálogo de métricas ya documenta (`catalogo-metricas-v0.md`,
§3) — no una impresión de cuál componente "se siente" más importante.

| Componente | Peor modo de falla documentado | Alcance | ¿Se recupera solo? | ¿Hay pérdida de disponibilidad o de datos? |
|---|---|---|---|---|
| **PROCESOS** (30) | `ORA-00018` / `ORA-00020` (`M-PRO-01`, `M-PRO-02`): ninguna sesión o proceso **nuevo** entra | Solo admisión de trabajo nuevo. Las sesiones ya conectadas y sus transacciones en curso **siguen operando** | Sí — en cuanto una sesión existente termina y libera su cupo, el error desaparece sin intervención | Ninguna. Es un cuello de botella de admisión, no un problema de integridad ni de continuidad |
| **MEMORIA** (35) | `ORA-04030` / `ORA-04031` (`M-MEM-02`, `M-MEM-03`): no se puede reservar memoria de proceso o de *shared pool* | Cualquier sesión —nueva **o ya conectada**— que intente una operación que necesite esa memoria. Antes del error, el efecto ya es general y silencioso: todo el trabajo de ordenamiento se vuelve más lento (`M-MEM-01`) | Se recupera al liberar memoria, pero mientras tanto **cada** sesión activa puede verse afectada, no solo las que intentan entrar | Ninguna pérdida de datos directa, pero un `ORA-04031` puede interrumpir operaciones en curso, no solo rechazarlas antes de empezar |
| **ARCHIVOS** (35) | `M-ARC-02` (datafile fuera de línea) y `M-ARC-03` (todos los grupos de redo inválidos): una porción de los datos queda inaccesible; sin redo utilizable, **la instancia no puede confirmar ninguna transacción y se detiene** | Un datafile offline afecta solo los objetos alojados ahí; el redo agotado afecta a **toda** la instancia por igual | No. El datafile offline exige `ALTER DATABASE DATAFILE ... ONLINE` y típicamente recuperación; una instancia detenida por falta de redo no se reinicia sola | Sí, en el caso límite: datos inaccesibles hasta recuperación manual, o parada total del servicio |

**Por qué PROCESOS queda con el peso más bajo.** Es el único de los tres cuyo
peor desenlace es ruidoso, se autolimita (un error `ORA-` explícito, no una
degradación silenciosa) y no pone en riesgo datos ni continuidad: el sistema le
cierra la puerta a trabajo nuevo, pero lo que ya estaba corriendo no se ve
amenazado. Comparado con eso, tanto MEMORIA como ARCHIVOS pueden afectar
sesiones que **ya estaban dentro** — la relación de riesgo con la disponibilidad
del servicio completo, no solo de su admisión, es mayor en los dos.

**Por qué MEMORIA y ARCHIVOS quedan empatados en 35 y no uno por encima del
otro.** Son severos por razones distintas y no comparables en la misma unidad,
lo cual es justamente el argumento para no intentar diferenciarlos con una
tercera cifra que no se podría defender mejor que las otras dos:

- **MEMORIA gana en alcance**: su degradación (`M-MEM-01`) es general y
  progresiva, y toca a toda sesión que ordene o agrupe datos, mucho antes de
  llegar al error duro.
- **ARCHIVOS gana en severidad del peor caso**: ninguna de las otras dos
  puede terminar en pérdida de disponibilidad de datos o parada total de la
  instancia. Solo `M-ARC-03` llega ahí.

Sin una medición que compare "cuánto duele" cada tipo de daño en una escala
común —que el proyecto no tiene, y que además el §3.1 ya explica por qué no se
inventa una—, declarar una jerarquía entre ambos sería exactamente la
"intuición" que este punto del checklist pide evitar. Tratarlos como iguales es
la posición que **menos** supone.

**Relación con C-I-D (misma lógica que la parte 1).** PROCESOS compromete
únicamente **disponibilidad de admisión** (temporal, autolimitada); MEMORIA
compromete **disponibilidad de operación** (general, mientras dura la presión);
ARCHIVOS puede comprometer **disponibilidad de servicio** y, en el límite,
**integridad/durabilidad** de lo que debía quedar confirmado en el registro de
rehacer. Es la misma escalada de gravedad —de "no entra trabajo nuevo" a "el
servicio entero se detiene"— que separa un hallazgo menor de uno crítico en el
catálogo de 75 controles de la parte 1.

**Lo que este argumento no hace.** No prueba que 30/35/35 sea el único reparto
correcto — no existe una unidad común para medir "cuánto peor" es un servicio
detenido frente a una operación degradada, y cualquier cifra exacta seguiría
siendo una elección del equipo. Lo que sí establece es un **orden** defendible
(archivos y memoria por encima de procesos, y archivos-memoria sin un ganador
claro entre sí) con el modo de falla de cada métrica como evidencia, no como
adorno. Si el análisis de sensibilidad del entregable 2 muestra que el estado
publicado apenas cambia dentro de ±10 %, eso refuerza que el reparto exacto
importa menos que el orden; si cambia con facilidad, hay que decirlo en la
defensa tal como está escrito arriba, y no ajustar los números después de
verlo.

---

## 6. Alertas

Una alerta **no es una fila de bitácora**. Tiene ciclo de vida:

```
abierta ──→ reconocida ──→ cerrada
   ▲                          │
   └────── reapertura ────────┘
```

- **Deduplicación:** existe como máximo una alerta abierta por
  `(instancia, métrica)` —**no** por `(instancia, métrica, nivel)`—. Las muestras
  siguientes actualizan `vista_por_ultima_vez` y `ocurrencias`; no insertan filas
  nuevas. Un *tablespace* al 96 % con muestreo cada 5 minutos generaría 288 filas
  diarias si se hace mal.
- **El nivel escala dentro de la alerta, no la duplica.** Si el nivel entra en la
  clave, un *tablespace* que pasa de ADVERTENCIA a DEGRADADO y luego a CRÍTICO
  deja tres alertas abiertas para un solo problema —exactamente el ruido que esta
  sección quiere evitar—. La alerta guarda `nivel_actual` y `nivel_maximo`, y cada
  cambio de severidad se anota en su bitácora. Quien la atiende ve una historia,
  no tres avisos que hay que correlacionar a mano.
- **Cierre automático** cuando la métrica vuelve a NORMAL y la histéresis lo
  confirma; cierre manual con nota y responsable.
- **Cada alerta registra** lo mínimo para poder actuar —fecha, hora, componente,
  variable, valor, umbral, nivel, descripción— más el responsable de atenderla.
  Sin responsable, la alerta no es un control: es un aviso.
- **Retención** de las alertas conforme a A.8.16 (registros de monitoreo con
  periodo de retención definido). Se propone 13 meses, para cubrir un ciclo anual
  de auditoría.
- **La acción queda registrada, no solo el responsable.** El paso 13 de la guía
  pide «utilizar la información recopilada para determinar qué está ocurriendo y
  tomar una acción». Una alerta cerrada guarda qué se hizo, no únicamente quién la
  cerró. Sin eso, el histórico dice que el problema desapareció pero no si alguien
  lo resolvió o si se fue solo, y son cosas muy distintas para una auditoría.

### 6.1 Relacionar alertas: el paso 11

La guía pide *«analizar si varios problemas están relacionados y pueden tener una
misma causa»*. Es el paso que separa un buzón de avisos de una herramienta de
diagnóstico, y el modelo de datos ya deja el trabajo medio hecho: **todas las
métricas de un instante comparten `muestra_id`**, así que las coincidencias
temporales no hay que reconstruirlas, ya están agrupadas.

La versión 1 agrupa por dos criterios, ambos baratos y explicables:

1. **Coincidencia en la muestra.** Alertas que se abren en la misma muestra o en
   muestras contiguas de la misma instancia forman un **episodio**, con su propio
   identificador. El tablero muestra el episodio, no siete alertas sueltas.
2. **Precedencia conocida.** El catálogo `metrica` declara qué métrica suele
   arrastrar a cuál —el *tablespace* lleno arrastra al fallo de escritura; el
   agotamiento de sesiones arrastra a la espera de procesos—. Dentro de un
   episodio, la alerta cuya métrica no es consecuencia de ninguna otra presente se
   marca como **causa probable**, y las demás quedan colgando de ella.

**Lo que la versión 1 no hace, y conviene decirlo antes que lo pregunten:** no
infiere causalidad de los datos. La precedencia es conocimiento declarado en el
catálogo, revisable y discutible, no una correlación calculada. Con muestras cada
cinco minutos y unas pocas semanas de historia, cualquier correlación que se
calculara sería ruido con aspecto de hallazgo — y presentar ruido como causa en una
herramienta de auditoría es peor que no ofrecer la función.

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
| `metrica` | Catálogo de variables medibles: código, componente (`PROCESOS`/`MEMORIA`/`ARCHIVOS`/`CONSULTAS`), nombre, unidad, vista `V$` de origen, sentido (`MENOR_MEJOR`/`MAYOR_MEJOR`/`ESTADO`), peso, **los cuatro umbrales** (`u_opt`, `u_adv`, `u_deg`, `u_crit`) y ancla ISO. Es el equivalente al catálogo de controles de la parte 1. |
| `umbral` | **Juegos de umbrales con vigencia.** Una fila es `(metrica_id, instancia_id, u_opt, u_adv, u_deg, u_crit, valido_desde, valido_hasta)`. Con `instancia_id` nulo es el umbral general de la métrica; con valor, la anulación para esa instancia. Sustituye a la tabla `umbral_instancia` del diseño anterior: la anulación por instancia y el versionado son el mismo problema y no merecen dos tablas. |
| `muestra` | Cabecera de una recolección: instancia, `tomada_en` (UTC), duración, resultado (`OK`/`PARCIAL`/`FALLIDA`), `cobertura_pct`, mensaje. |
| `medicion` | Una fila por métrica y muestra: `valor_crudo`, `valor_normalizado`, `estado`, **`umbral_id`** (el juego vigente en ese momento) y **`valor_acumulado`**, nulable, donde las métricas de tasa guardan el total leído para que la muestra siguiente pueda restar. Tabla angosta y de alto volumen. |
| `indice` | Una fila por muestra: `ip`, `im`, `ia`, `isbd_bruto`, `isbd`, `estado`, `causa`. |
| `alerta` | Ciclo de vida completo según §6: `nivel_actual`, `nivel_maximo`, responsable, acción registrada al cerrar, y `episodio_id`. |
| `episodio` | Agrupación de alertas concurrentes de una misma instancia (§6.1), con su `alerta_causa_id` cuando la precedencia permite señalar una. |
| `precedencia` | Qué métrica suele arrastrar a cuál: `(metrica_origen_id, metrica_consecuencia_id, nota)`. Conocimiento declarado y revisable, no correlación calculada. |
| `consulta_observada` | Top-N de sentencias por muestra (§3.1): `sql_id`, `plan_hash_value`, ejecuciones, CPU y tiempo transcurrido por ejecución, lecturas lógicas por ejecución. Alimenta la evidencia de C-066; no entra al ISBD. |
| `resumen_hora` | Consolidación horaria por instancia y métrica (mín, máx, promedio, p95, conteo de muestras). |

### 7.2 Decisiones que conviene dejar por escrito

- `valor_crudo` **y** `valor_normalizado` se guardan ambos. El crudo es lo que el
  DBA quiere ver («86 de 200 procesos»); el normalizado es lo que entró al índice.
  Recalcular el crudo desde el normalizado es imposible, y recalcular el
  normalizado con umbrales que cambiaron después falsearía la historia.
- **Los umbrales se versionan, no se editan.** Cambiar un umbral cierra la fila
  vigente (`valido_hasta`) y abre una nueva; nunca se hace `UPDATE` sobre los
  cuatro números. Como `medicion` guarda el `umbral_id` con el que se calificó,
  dentro de un año se puede responder no solo «qué valor tenía» sino «por qué eso
  era ADVERTENCIA entonces». Sin esto, una recalibración reescribe el pasado en
  silencio, que es justo lo que A.8.15 no quiere de un registro de seguridad.
  El costo es un entero por fila en la tabla grande, no cuatro números repetidos.
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

- Usuario común dedicado (`C##RIVENDEL_MONITOR`), creado en `CDB$ROOT` porque es
  desde ahí donde se consulta (§10.1). **Solo lectura**, sin privilegios de
  administración, sin `DBA`, sin `SYSDBA`.
- **Concesiones explícitas sobre las vistas necesarias**, con `CONTAINER = ALL` y
  no `SELECT_CATALOG_ROLE` completo. El rol entero concede lectura sobre todo el
  diccionario, que es mucho más de lo que el monitor necesita — y el privilegio
  mínimo (A.8.2) se demuestra con la lista de `GRANT`, no con una declaración de
  intenciones.
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
- **Ninguna cifra de salud fabricada.** Si no hay muestras, se pinta el estado
  vacío: nunca un ISBD, un porcentaje de utilización o una alerta inventados. Es la
  misma regla que ya rige el banner de la portada. Una herramienta de auditoría que
  publica una medición falsa contradice todo lo que afirma.
- **Los datos de demostración sí son legítimos, con dos condiciones.** El proyecto
  necesita una demo que se pueda enseñar sin esperar semanas de recolección, y ya
  resolvió ese problema una vez: `Scripts/05_datos_demo_evolucion.sql` puebla la
  evolución mensual del panel. El monitor sigue la misma convención con
  `Scripts/06_datos_demo_monitor.sql`. Las dos condiciones son: **(a)** viven en un
  script propio, cargable y omitible, nunca sembrados desde el código de la
  aplicación; y **(b)** la instancia queda marcada como demostrativa en la tabla
  `instancia`, de modo que la interfaz pueda distinguirlas si hace falta.
  La línea que no se cruza no es «no inventar nada», es **no presentar como
  medición real algo que no se midió**.

---

## 9. Interfaz

Rutas nuevas: `/monitoreo` (todas las instancias) y `/monitoreo/{instancia}`
(detalle e histórico).

Reglas del sistema visual de Rivendel que aplican aquí y que ya están decididas
—no se renegocian en esta parte del proyecto:

- **Estado con `pill()`**, nunca solo con color: contorno y texto teñido, con
  icono y etiqueta. Un tablero que comunica el rojo únicamente con rojo no sirve
  para una parte de la población y no cumple WCAG 2.2.
- **Las cinco bandas del §5.2 se pintan con cuatro colores.** `pill()` gana un
  tono `opt` que comparte el color de `ok` y se distingue por el ícono `escudo`,
  exactamente como `crit` comparte hoy el color de `bad` y se distingue por el
  octógono. Es un cambio de tres líneas en `app/Core/funciones.php` y **no
  introduce ningún color nuevo**: el sistema visual mantiene cuatro niveles de
  color y pasa a tener cinco de significado. Cualquier otra solución (un quinto
  color, un verde más claro) contradice el sistema visual y no debe intentarse.
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
| 1 | **`V$RESOURCE_LIMIT` no devuelve nada desde dentro de un PDB.** ~~Riesgo~~ **hecho verificado** en el contenedor del proyecto — ver §10.1. Rompe el indicador de procesos en silencio. | El agente se conecta a `CDB$ROOT` con un usuario común (§8.1) y trata «cero filas» como recolección fallida, no como salud perfecta. |
| 2 | Límites del producto en ediciones acotadas (XE): el techo lo fija la licencia, no la máquina. | Documentar el techo real de la instancia de pruebas antes de calibrar. Los umbrales se calibran contra el límite efectivo. |
| 3 | Umbrales calibrados en una base de desarrollo ociosa | Anulación por instancia en la tabla `umbral`; y en la defensa se presenta la calibración como propuesta con su método, no como verdad. |
| 4 | Crecimiento de `medicion` | Retención y consolidación desde el día uno (§7.4). |
| 5 | Relojes desincronizados entre instancias | Sellado con el reloj del monitor, almacenamiento en UTC (A.8.17). |
| 6 | El agente cargando la base vigilada | Intervalo configurable, consultas acotadas, tiempo límite, cortacircuitos. |
| 7 | Deriva entre lo que la vista muestra y lo que el motor calcula | El motor de cálculo es un servicio con pruebas propias; la vista no recalcula nada. |

### 10.1 El riesgo 1, comprobado

No hace falta discutirlo: se probó contra `becajo-oracle`, la misma instancia del
proyecto, conectando como `SYS` para descartar que fuera un problema de permisos.

| Punto de conexión | `SELECT COUNT(*) FROM v$resource_limit` |
|---|---|
| `sys/…@FREEPDB1 as sysdba` (dentro del PDB) | **0 filas** |
| `sys/…@FREE as sysdba` (`CDB$ROOT`) | **27 filas** — `processes` 86 / 200, `sessions` 112 / 322 |

Tres consecuencias de diseño, y ninguna es opcional:

1. **El agente se conecta a la raíz, no al PDB.** El servicio del CDB es un dato
   distinto del que usa la aplicación; `config/monitor.php` lo declara aparte de
   `config/base_datos.php`. Son dos conexiones a la misma máquina con propósitos y
   privilegios distintos, y conviene que se note en la configuración.
2. **La cuenta del agente es un usuario común** (`C##RIVENDEL_MONITOR`), no un
   usuario del PDB. Eso cambia los `GRANT` del §8.1: se conceden con
   `CONTAINER = ALL` sobre las vistas necesarias.
3. **Cero filas es una falla de recolección, no un cero.** Esta es la trampa: la
   consulta no lanza error, devuelve un conjunto vacío. Un agente ingenuo
   calcularía `0 / 0` o publicaría «0 % de procesos en uso» y el tablero mostraría
   salud perfecta justo en la métrica que dejó de funcionar. El invariante 3 aplica
   literalmente aquí: la métrica sale del denominador, baja `cobertura_pct`, y si
   la cobertura cae bajo el piso no se publica ISBD (invariante 4). **Este es el
   primer caso de prueba del agente**, y es más importante que el camino feliz.

Nota sobre el techo real (riesgo 2): los límites de esta instancia son 200 procesos
y 322 sesiones, no los que traería una edición mayor. Los umbrales se calibran
contra el límite efectivo que devuelve `LIMIT_VALUE`, nunca contra una cifra
supuesta.

---

## 11. Frentes de trabajo

Igual que en la parte 1, primero se fijan los contratos y después cada frente
avanza sin depender de que otro termine. Este documento describe **el trabajo**,
no quién lo hace: el reparto vive en el documento de organización del grupo.

### Fase 0 — Los tres contratos · **escritos**

Antes de escribir código se acuerdan tres papeles. Los tres están en el repositorio:

1. **[Catálogo de métricas versión 1](catalogo-metricas-v0.md)** — quince métricas
   con ficha completa, las cuatro familias representadas (las tres del ISBD más
   la excepción declarada de CONSULTAS) y las consultas comprobadas contra el
   contenedor del proyecto, salvo cinco que llegaron en esta ampliación y todavía
   esperan su calibración.
2. **[Forma de una muestra](contrato-muestra.md)** — la estructura que produce el
   recolector y consume el motor, con los siete casos límite obligatorios.
3. **[`RepositorioMonitor` y rutas](contrato-repositorio-monitor.md)** — dos
   interfaces (lectura y escritura), siete entidades y las rutas de `/monitoreo`.

Con esos tres papeles escritos, los cuatro frentes pueden arrancar el mismo día.

### Frente 1 — Instrumento de salud

Los pasos 1 a 4 de la guía: identificar los procesos de fondo a vigilar (§3.2),
decidir qué se quiere saber de cada uno, elegir la métrica y localizar la vista
`V$` que la da. Catálogo completo con su ficha estilo Anexo B de 27004,
justificación de **los cuatro umbrales** de cada métrica —incluido `u_opt`, que
nace de la escala de cinco bandas (A-4)—, la tabla de `precedencia` que alimenta la
correlación del §6.1, y el análisis de sensibilidad de los pesos (§5.6). Incluye
las fichas del componente CONSULTAS (§3.1).

Es a la parte 2 lo que el catálogo de 75 controles fue a la parte 1: **el trabajo
intelectual central**, y donde se juega la nota igual que se jugó allá.

### Frente 2 — Datos y agente

Esquema `MONITOR` (DDL, diccionario de datos, datos semilla del catálogo),
`RepositorioMonitorOracle`, `bin/monitor.php`, programación de la tarea,
consolidación y purga, y la cuenta de solo lectura con sus `GRANT`.

### Frente 3 — Motor de cálculo

Normalización, indicadores por componente, ISBD, regla del eslabón más débil,
histéresis, línea base, motor de alertas con ciclo de vida y agrupación en
episodios. **Con pruebas unitarias en PHPUnit**, incluidas la tabla de verificación
del §5.2.1, el caso trabajado del §5.4, el invariante 3 (sin dato ≠ cero) y el caso
de cero filas del §10.1. Trabaja contra `RepositorioMonitorArreglo`, sin depender de
que Oracle esté levantado.

> **Excepción acordada a la regla de dependencias.** CLAUDE.md establece que el
> proyecto no lleva Composer y que no se introducen dependencias externas sin
> acordarlo. El acuerdo existe y su alcance es este: **Composer y PHPUnit solo como
> dependencia de desarrollo, solo para las pruebas del motor de cálculo.** El
> autoloader propio sigue siendo el de la aplicación; `vendor/` no participa en
> ninguna petición web. Quien lo introduzca actualiza en el mismo commit
> `CLAUDE.md`, el `Dockerfile` y `.gitignore`, para que la regla escrita y el
> repositorio no se contradigan.

### Frente 4 — Interfaz y documentación

Vistas `/monitoreo`, tablero, histórico, listado de alertas y episodios, estados
vacíos, exportación, textos en `config/idiomas/`, manual de usuario y sección del
manual técnico. También el tono `opt` de `pill()` (§9) y la entrada del menú en
`layouts/panel.php`, que se arma en un solo sitio. Trabaja contra el repositorio de
arreglo desde el primer día.

---

## 12. Criterios de aceptación

La parte 2 está terminada cuando **todas** estas afirmaciones son verificables:

- [ ] Ninguna utilización cruda entra a un promedio: todo pasa por normalización a salud.
- [ ] Existe **un solo vocabulario de cinco bandas** en métrica, componente e índice. No hay tabla de traducción en ninguna parte del código.
- [ ] Para cada métrica, la banda de estado derivada de `s` coincide con la derivada de `u`. Hay una prueba automatizada que recorre las diez filas de verificación del §5.2.1 y las cinco fronteras.
- [ ] Una métrica no recolectada sale del denominador; no vale cero. Hay una prueba que lo comprueba.
- [ ] Con cobertura bajo el piso, el sistema no publica ISBD; publica «muestra incompleta».
- [ ] Ningún componente en estado CRÍTICO puede convivir con un ISBD publicado sobre 40; ninguno en DEGRADADO, sobre 60.
- [ ] El caso trabajado del §5.4 se reproduce exactamente con el motor de cálculo (ISBD 40,0 y no 70,4). Es una prueba, no un ejemplo del documento.
- [ ] El componente CONSULTAS se recolecta, se muestra y alerta, y **no aparece en la fórmula del ISBD**.
- [ ] Los catorce pasos de la guía tienen destino verificable en el producto; el rastreo del §2.5 se recorre entero y ninguna fila queda en «pendiente».
- [ ] Con un proceso de fondo ausente, el componente PROCESOS queda en CRÍTICO y el ISBD no se publica sobre 40, aunque memoria y archivos estén en ÓPTIMO (comprobado con datos sintéticos).
- [ ] Varias alertas abiertas en la misma muestra se presentan como **un episodio**, no como avisos sueltos, y se señala la causa probable cuando la precedencia lo permite.
- [ ] Las cinco bandas se pintan con los cuatro colores existentes: `opt` comparte tono con `ok`. No se agregó ningún color al sistema visual.
- [ ] El ISBD nunca se muestra sin estado y sin causa.
- [ ] Un pico aislado no cambia el estado publicado (histéresis verificada con datos sintéticos).
- [ ] Una condición sostenida genera **una** alerta, no una por muestra.
- [ ] La cuenta del agente no puede ejecutar DML en la instancia vigilada (comprobado intentándolo).
- [ ] No hay credenciales en el repositorio.
- [ ] El tablero pinta correctamente cuando la instancia vigilada está caída, mostrando la antigüedad del último dato.
- [ ] Ninguna cifra de salud (ISBD, utilización, alerta) se muestra sin provenir de una muestra realmente tomada.
- [ ] Los datos de demostración están en `Scripts/06_datos_demo_monitor.sql`, su instancia está marcada como demostrativa, y el sistema funciona igual si el script no se carga.
- [ ] Todo estado se comunica con icono y etiqueta, no solo con color; contrastes verificados contra WCAG 2.2.
- [ ] Cada métrica tiene su ficha de medición documentada (necesidad de información, medida base, función, indicador, criterio de decisión, periodicidad, responsable).
- [ ] Cada métrica declara su anclaje normativo.

---

## 13. Valor agregado para la defensa

Tres funcionalidades que van más allá de lo pedido y que se pueden justificar
con norma en la mano:

1. **El monitor como evidencia de la auditoría.** Los controles C-064, C-065 y
   C-066 dejan de responderse con una afirmación y pasan a responderse con datos
   medidos. Es exactamente lo que ISO/IEC 27007 pide de la evidencia de auditoría
   y lo que el propio proyecto ya exige en su esquema
   (`evidencia_verificada` obligatoria cuando el estado es «Sí»).
2. **Historia inalterable: umbrales versionados.** El paso 14 de la guía pide
   revisar y ajustar los umbrales con el tiempo, y ahí está la trampa: si los
   umbrales se editan, toda la historia anterior queda calificada con criterios que
   ya nadie recuerda, y una recalibración reescribe el pasado en silencio. El
   monitor versiona los juegos de umbrales y cada medición guarda con cuál se
   calificó (§7.2), de modo que dentro de un año se puede responder no solo «qué
   valor tenía» sino «por qué eso era ADVERTENCIA entonces». Es lo que A.8.15 pide
   de un registro de seguridad —que no sea alterable por el operador vigilado— y no
   lo pide ningún paso de la guía.

   *Nota para la defensa:* la **línea base estadística** no entra en esta lista
   aunque sea una de las piezas más fuertes del monitor. Es el paso 6 de la guía y
   está pedida por A.8.16: es requisito, no valor agregado. Presentarla como extra
   sería regalar un punto que ya está ganado y arriesgar la pregunta incómoda.
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

## Anexo A. De dónde sale cada decisión

### A.1 El modelo de cálculo es aporte del equipo

**Esta es la sección que hay que leer antes de la defensa.** La guía del curso es un
método de catorce pasos y no fija una sola cifra: no trae fórmulas, ni escalas, ni
umbrales, ni pesos, ni componentes, ni índice global. Todo eso lo puso el equipo.

La consecuencia práctica es que **ninguna de estas decisiones se defiende diciendo
«así venía»**. Cada una se defiende con su razón, y esa razón está escrita:

| # | Decisión de modelo | Por qué | § |
|---|---|---|---|
| A-1 | Convertir toda variable a **salud 0–100 donde más alto es mejor** antes de promediar | Una escala de utilización y una de salud corren en sentidos opuestos; mezclarlas hace que 95 signifique fiebre en una página y excelencia en la otra. Invariante 1 | §1 |
| A-2 | Calcular continuo y clasificar solo al mostrar | Clasificar primero pierde información que después no se recupera. Invariante 2 | §1 |
| A-3 | **Cinco bandas** en métrica, componente e índice, con un solo vocabulario | Único punto donde el plan se aparta de la guía, que sugiere tres niveles «por ejemplo». Con cinco, la coincidencia de bandas se enuncia como igualdad y se puede probar; con dos vocabularios distintos haría falta una tabla de traducción en el código, la base y la interfaz | §5.2 |
| A-4 | Cuatro umbrales por métrica (`u_opt`, `u_adv`, `u_deg`, `u_crit`), `u_opt = 50` por omisión | Consecuencia de A-3: la banda ÓPTIMO necesita su frontera | §5.2 |
| A-5 | Intervalos semiabiertos: `u` cerrado por abajo, `s` cerrado por arriba | La función es decreciente; cerrar del mismo lado manda las cinco fronteras a la banda contigua | §5.2 |
| A-6 | Pesos **30 / 35 / 35** | Por consecuencia de falla, no por intuición: un *datafile* fuera de línea detiene el servicio, una *shared pool* apretada lo degrada. Procesos pesa menos porque su peor caso es autolimitado y no toca datos; memoria y archivos quedan empatados porque son severos en dimensiones distintas (alcance frente a irreversibilidad) y no comparables en una escala común. Se somete a análisis de sensibilidad en vez de afirmarse | §5.6.1 |
| A-7 | **Regla del eslabón más débil** con topes en 40 / 60 / 75 / 90 | Un promedio ponderado esconde emergencias: sin tope, dos componentes en rojo y uno excelente publican salud aceptable | §5.4 |
| A-8 | El ISBD promedia los componentes **ya topados** | Si promediara los brutos, un componente rescatado del rojo volvería a entrar como si no lo estuviera | §5.3 |
| A-9 | **Sin dato no es cero**: la métrica no recolectada sale del denominador | Heredado de la regla del «No aplica» de la parte 1, anclada en la Declaración de Aplicabilidad (ISO/IEC 27001, cl. 6.1.3) | §5.3 |
| A-10 | Cuatro componentes, pero **CONSULTAS no es sumando del ISBD** | Cubre C-066 sin mover los pesos ni arruinar el análisis de sensibilidad; además una consulta cara no es una proporción con techo natural | §3.1 |
| A-11 | Los procesos de fondo se miden **dentro de PROCESOS**, como señal de estado que veta | Es el paso 1 de la guía. Un cuarto componente movería los pesos; la familia ESTADO del §5.1 ya sabe tratarlos | §3.2 |
| A-12 | La correlación de alertas usa **precedencia declarada**, no correlación calculada | Con cinco minutos de muestreo y semanas de historia, cualquier correlación calculada sería ruido con aspecto de hallazgo | §6.1 |

Dos observaciones que conviene tener preparadas:

- **Solo A-3 se aparta de la guía.** Todo lo demás ocupa espacio que la guía dejó
  deliberadamente en blanco. Eso no es una debilidad del trabajo: es el trabajo.
- **El caso trabajado del §5.4 es propio**, construido para exhibir la regla del
  eslabón más débil con números que se pueden seguir a mano.

### A.2 Decisiones de equipo

Estas no corrigen nada: resuelven preguntas que el plan había dejado abiertas.

| # | Decisión | Alcance | §  |
|---|---|---|---|
| B-1 | El agente se conecta a `CDB$ROOT` con el usuario común `C##RIVENDEL_MONITOR` | Comprobado contra el contenedor del proyecto: dentro del PDB la vista devuelve cero filas sin error | §10.1, §8.1 |
| B-2 | Composer y PHPUnit **como dependencia de desarrollo**, excepción acordada a la regla de CLAUDE.md | Solo pruebas del motor de cálculo; `vendor/` no participa en ninguna petición web | §11 |
| B-3 | Una alerta abierta por `(instancia, métrica)`; el nivel escala dentro de ella | Evita tres alertas vivas para un solo problema que empeora | §6 |
| B-4 | Umbrales versionados con vigencia; `medicion` guarda el `umbral_id` usado | Una recalibración no reescribe el pasado. La tabla `umbral` absorbe a `umbral_instancia` | §7.1, §7.2 |
| B-5 | Los datos de demostración se conservan, con origen separado y rótulo | El equipo debe poder enseñar el tablero poblado en la defensa sin esperar semanas de recolección. Misma convención que `Scripts/05_datos_demo_evolucion.sql`. Lo prohibido es presentar como medición real algo que no se midió | §8.3, §12 |
| B-6 | El análisis de sensibilidad corre sobre un lote congelado antes de la purga | La ventana se declara antes de ver los resultados | §5.6 |

---

*Declaración de uso de herramientas de inteligencia artificial generativa
conforme al punto VII.9 de la normativa del programa: este documento fue
elaborado con asistencia de un modelo de lenguaje a partir de la documentación del
curso y del estado actual del repositorio, y revisado por el equipo.*
