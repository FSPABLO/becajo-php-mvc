# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Todo el proyecto está escrito en español: nombres de clase, métodos, variables,
comentarios y mensajes de commit. Mantenga esa convención al escribir código nuevo.

## Comandos

```bash
# Todo de cero (instala Docker si falta, crea config/base_datos.php,
# levanta contenedores y carga los tres scripts SQL). Requiere bash.
./instalar.sh

# Levantar el entorno completo (Apache + PHP 8.3 + Oracle 23ai Free)
docker compose up --build          # http://localhost:8080

# Solo el sitio, sin Oracle ni módulo de auditorías (arranque instantáneo)
php -S localhost:8000 -t public

# Cargar / recargar el esquema a mano. DESDE BASH, no desde PowerShell: la
# tubería de PowerShell recodifica y las tildes entran dobles ("Gestión" queda
# guardado como "GestiÃ³n"). Se detecta con LENGTHB, no a ojo — la consola de
# sqlplus también deforma acentos y el dato puede estar bien.
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/01_esquema.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/02_datos_semilla.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/03_procedimientos_indicadores.sql

# Opcional: cartera de varios meses para un auditor, para que el panel tenga
# una evolución que dibujar. Re-ejecutable y solo inserta; no pisa respuestas.
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/05_datos_demo_evolucion.sql

# SQL interactivo
docker exec -it becajo-oracle sqlplus becajo/becajo@FREEPDB1

# Comprobar sintaxis de un archivo PHP (no hay linter configurado)
docker exec becajo-web php -l app/Controllers/AuditoriaController.php

# ¿Se compiló oci8 en la imagen?
docker exec becajo-web php -m | grep oci8
```

Cuentas de prueba tras cargar `02_datos_semilla.sql`:
`ana.alfaro@consultora.example / auditor2026` (AUDITOR) y
`luis.rojas@empresa.example / adminbd2026` (ADMIN_BD).

**No hay npm ni linter, y Composer entra con una sola excepción acordada.** El
autoloader está escrito a mano (`app/Core/Autoloader.php`) y Tailwind se carga
por CDN desde `app/Views/partials/head.php`. No introduzca dependencias
externas sin acordarlo.

La excepción, y su alcance exacto (frente 3 del plan de la parte 2):
**Composer y PHPUnit solo como dependencia de desarrollo, solo para las pruebas
del motor de cálculo.** `composer.json` declara PHPUnit en `require-dev` y nada
en `require` salvo la versión de PHP. El autoloader propio sigue siendo el de la
aplicación —`tests/bootstrap.php` lo registra a mano, y `App\` no aparece en el
`autoload` de Composer— de modo que **`vendor/` no participa en ninguna petición
web**, no viaja dentro de la imagen (`.dockerignore`) y no se versiona
(`.gitignore`); `composer.json` y `composer.lock` sí se versionan.

```bash
composer install          # solo la primera vez, trae PHPUnit
vendor/bin/phpunit        # las pruebas del motor de cálculo
```

Las pruebas cubren **únicamente `app/Models/Calculo`**, que es aritmética pura y
no necesita Oracle ni Apache. Para todo lo demás —controladores, vistas,
repositorios— verificar un cambio sigue significando abrirlo en el navegador.

## Motor de cálculo del monitor (frente 3, parte 2)

`app/Models/Calculo/` convierte lecturas crudas de Oracle en salud, estado y
alertas. Es el único código del proyecto con pruebas automatizadas, porque es
el único que es aritmética pura: no toca la base, no abre sesión y no pinta
nada.

| Clase | Responsabilidad |
|---|---|
| `Escala` | Las cinco bandas, la normalización por tramos y los topes del eslabón más débil. Sin estado: umbrales y techo entran por parámetro. |
| `MotorCalculoSalud` | De muestra cruda a muestra evaluada: mediciones, cobertura, componentes e ISBD. |
| `LineaBase` | Media y desviación sobre la ventana móvil, para el comportamiento anómalo de A.8.16. |
| `MotorAlertas` | Decide qué alertas abrir, escalar y cerrar, y las agrupa en episodios. **No escribe**: devuelve decisiones. |
| `MotorCalculoReal` | Raíz de composición. El único sitio donde se eligen piso, ventana e histéresis. Es la clase que busca `bin/monitor.php`. |

Reglas que conviene no romper al tocar este código:

- **Sin dato no es cero** (invariante 3). Una métrica no recolectada sale del
  denominador y se anota en `fuera` con su motivo. Publicar un 0 convertiría
  una falla de recolección en una falla de la base.
- **Las compuertas no promedian.** Abierta no aporta un 100; cerrada manda el
  componente a 0 y CRÍTICO sin promediar.
- **El tope se aplica en los dos niveles** y lo que viaja hacia arriba es el
  valor ya topado: el ISBD promedia publicados, nunca brutos.
- **La banda se decide sobre la salud sin redondear.** En las fronteras, el
  valor publicado y el exacto caen en bandas distintas.
- **Los umbrales son dato, no código** (B-8). Igual que las precedencias: se
  leen del repositorio, no de una constante.
- Una tasa o una identidad que todavía no puede derivar **persiste igual su
  valor de continuidad**, con `estado` nulo. Sin eso la métrica queda atascada
  en su primera muestra para siempre.

Dos huecos conocidos, ambos pendientes de decisión con el frente 2:
`medicion.valor_acumulado` es una sola columna y `M-PRO-04` necesita dos, así
que su tasa no sobrevive a la persistencia; y `medicion` no guarda el estado
observado, por lo que la histéresis solo sostiene los empeoramientos y publica
las mejoras de inmediato.

## Monitor de salud (frente 4, parte 2) — HOY ES UNA MAQUETA

`/monitoreo` ya se puede abrir desde la barra lateral («Monitoreo → Monitor»,
ícono `corazon`), pero **todas sus cifras son sintéticas**: salen de
`config/monitor-mockup.php` y no de ninguna instancia real. No consulta Oracle,
no usa `RepositorioMonitorOracle` ni `RepositorioMonitorArreglo` (frente 2) y no
invoca `MotorCalculoReal` (frente 3). Se hizo así a propósito, para que el frente
4 pudiera avanzar sin esperar a que los otros dos se fusionaran.

- **La pantalla ya NO avisa de que es una maqueta.** Había un banner arriba que
  lo decía; se retiró por decisión de diseño del frente 4. Queda una sola
  advertencia, la nota al pie del gráfico de memoria, que dice que la lectura la
  genera el navegador.

  Conviene tenerlo presente porque el §12 del plan pide que ninguna cifra de
  salud se muestre sin venir de una muestra realmente tomada, y esa casilla ya
  no se puede marcar mirando la pantalla. **Antes de enseñar esto como producto
  —defensa, demo a un tercero— hay que reponer el aviso o conectar la fuente
  real**: cuatro instancias con nombres verosímiles y cifras coherentes se leen
  como datos de verdad, que es justo lo que el criterio quiere evitar.
- **La antigüedad del dato tampoco está visible.** El §9 la pide siempre a la
  vista («última muestra hace 4 min») y se retiró junto con el recuento de
  instancias conectadas y la cobertura. Sigue existiendo en el bloque de «último
  valor conocido» de una instancia caída, y solo ahí.
- **La costura está en `public/index.php`**, en el `require` de
  `config/monitor-mockup.php` que entra al `Contenedor` como arreglo —igual que
  `config/conexiones.php` y por la misma razón: hoy es configuración, no hay nada
  que consultar. Cuando el monitoreo se conecte, se cambia ese `require` por el
  repositorio y **ni `MonitorController` ni las vistas cambian**.
  `Contenedor::hayMonitor()` es el interruptor: sin el archivo, la entrada del
  menú no se pinta.
- **El archivo guarda muestras YA EVALUADAS**, no lecturas crudas. Es la frontera
  del contrato: el recolector no razona, el motor no consulta y **la vista no
  calcula**. Si alguna vista del monitor empieza a normalizar, promediar o decidir
  bandas, la separación se rompió.
- La aritmética de la maqueta está comprobada contra el §5.4: `PRODCORE1` da
  ISBD bruto 70,4 y publicado 40,0 · CRÍTICO. Las cuatro instancias existen para
  demostrar un caso límite cada una (eslabón más débil, tope de SALUDABLE,
  cobertura bajo el piso, instancia caída). **Si toca esos números, recompruebe
  que siguen cuadrando** — es fácil dejar una maqueta que enseña aritmética falsa.

### La pantalla: consola de operación

`/monitoreo` se ordena como un panel de guardia y no como un listado: primero el
instrumento, después la evidencia.

0. **A todo el ancho, y sin rótulo.** `/monitoreo` es la única pantalla del
   módulo sin el `max-w-6xl` que centra a las demás: su tabla es una matriz de
   seis columnas de métrica y con la caja centrada pedía barra horizontal
   habiendo sitio de sobra a los lados. Lo que NO se estira es la prosa —los
   párrafos llevan `max-w-[95ch]`—, porque una línea de doscientos caracteres
   deja de leerse por mucho ancho que haya. El `<h1>` sigue existiendo en
   `sr-only` aunque no haya título visible: sin él, quien no ve la pantalla se
   queda sin saber en qué página está.
1. **Selector de base de datos** (`monitor-selector`). Es un `<details>` con
   enlaces, no un `<select>`: elegir una instancia NAVEGA a `/monitoreo/{clave}`,
   así que las opciones son enlaces de verdad —se abren en otra pestaña, se
   comparten, funcionan sin JavaScript— y el navegador no permite marcado dentro
   de `<option>`, que es donde van el punto y el estado. **El punto verde/gris
   nunca va solo**: cada fila lleva además la palabra («Conectada», «Muestra
   incompleta», «Sin conexión»). Verde es «la muestra se completó»; gris cubre a
   la vez desconectada e incompleta, porque en los dos casos la respuesta a
   «¿puedo fiarme de la cifra?» es la misma. A su derecha no queda nada: el
   recuento de conectadas, la antigüedad y la cobertura se retiraron.
2. **La consola**: medidor radial y las tres fichas arriba, y a todo su ancho la
   tabla de procesos, la tendencia del ISBD y el gráfico de memoria. **No tiene
   relleno propio**: se delimita
   solo con `border-oro/45`, el mismo token con el que el plan destacado de la
   portada se distingue del gratuito. Sus tarjetas quedan en `bg-superficie`
   sobre el pergamino de la página, igual que las de fuera.

   **DESVIACIÓN DECLARADA (§5.2)**, la misma que ya estaba anotada en
   `home/secciones/planes.php`: el oro está reservado a la referencia normativa
   y aquí el borde es ORNAMENTO. Dentro de la consola el oro sigue significando
   lo que debe —los códigos de métrica en `.rv-id`—, y no compite con el borde
   porque uno es contorno y el otro es tinta.

   Sin relleno tampoco lleva relieve: el neumorfismo extruye una SUPERFICIE, y
   una sombra alrededor de algo transparente se lee como un error de pintado.

   Este bloque pasó por dos lienzos antes: región `.rv-oscuro` y después tinte
   dorado (`bg-oro-tinte`). Los dos se retiraron por decisión de diseño del
   frente 4, y en ninguno de los dos cambios hubo que tocar nada de dentro —
   todo usa tokens, que era exactamente lo que el sistema prometía.
**La ficha de CONSULTAS se retiró** de la pantalla. La muestra la sigue trayendo
—`M-CON-01` se recolecta y el componente se evalúa—, pero ya no se pinta en
ninguna parte, así que el criterio del §12 «el componente CONSULTAS se recolecta,
se muestra y alerta, y no aparece en la fórmula del ISBD» solo se puede
demostrar hoy por su primera mitad. Es la segunda casilla del §12 que la pantalla
deja de cubrir, después del episodio de alerta.

Las dos series de tiempo van **LADO A LADO** dentro de la consola: tendencia del
ISBD a la izquierda, memoria de la base a la derecha. Son la misma instancia
mirada en el tiempo, y en paralelo se comparan de un vistazo —¿el índice cae
cuando la memoria sube?—, que es la pregunta con la que se abren las dos. Por
debajo de `lg` se apilan, y **si no hay memoria la tendencia ocupa las dos
columnas** (`lg:col-span-2`): media tarjeta con el otro medio en blanco se lee
como algo que no cargó, y es justo el caso de una instancia caída.

Estuvieron apiladas antes, y la memoria incluso fuera de la consola, porque en
media columna salía espachurrada. Eso se arregló donde tocaba: **cada componente
declara su propio `$ancho` de `viewBox`** —470×200 la tendencia, 480×250 la
memoria— en vez de los 700×220 y 700×250 de cuando ocupaban el ancho entero. La
proporción es lo que hay que tocar al cambiarlas de sitio: un lienzo de 3,2:1 en
media columna se aplasta, y como **el texto de un SVG escala con el dibujo**, un
rótulo de `font-size` 10 que a todo el ancho se veía a 15 px cae a 9 —de ahí que
los rótulos de eje de los dos gráficos estén hoy en 11—. Si alguna vuelve al
ancho completo, hay que devolver el `$ancho` largo; si no, se verá gigante.

**El episodio de alerta se retiró** de la pantalla (vista y datos de maqueta).
Era lo único que demostraba el §6.1 del plan —agrupar alertas concurrentes en un
episodio y señalar la causa probable por precedencia— y uno de los criterios de
aceptación del §12. Si hay que reponerlo, está en el historial de git; el modelo
de datos del frente 2 y `MotorAlertas` del frente 3 siguen contemplándolo.

**La tabla de mediciones se retiró.** Listaba las quince métricas en filas
planas; ahora esa información vive por PROCESO dentro de cada índice, que es la
unidad sobre la que un DBA actúa —una métrica no se arregla, un proceso sí—.

**La ficha «Por qué vale eso» se retiró** (la que iba bajo el medidor cuando el
ISBD publicado no era el promedio: decía cuánto daba el promedio ponderado y qué
componente lo topaba, con `mon.causa` / `mon.tope_explicacion`, hoy fuera de los
dos archivos de idioma). Era lo único en pantalla que explicaba el eslabón más
débil sobre la instancia mirada, así que **el invariante 5 —«el ISBD nunca se
muestra sin causa»— ya no se puede comprobar mirando el panel**; el dato sigue
viajando en la muestra (`isbd_bruto` y `tope`) y `monitor-indices` sigue marcando
el tope de cada componente. Es la tercera casilla del §12 que la pantalla deja de
cubrir. Bajo el medidor solo quedan las dos explicaciones de por qué NO hay
cifra: instancia caída y cobertura bajo el piso.

Sobre los gráficos:

- **El medidor** (`monitor-medidor`) lleva muescas en 40, 60, 75 y 90 para que el
  arco diga en qué banda cae y no solo «más o menos lleno». El carril va en gris
  neutro y **no** en `--rv-border`: en la región oscura ese token es un verde
  apagado y un carril verdoso detrás de un arco corto se lee como un segundo dato.
  Sin índice publicado el anillo adelgaza a un punteado fino y pierde las muescas
  — un anillo grueso a trazos parece un engranaje, y uno grueso lleno de gris
  parece un valor bajo.
- **La tendencia** (`monitor-tendencia`) son 40 lecturas. **Las mesetas planas
  sobre 40, 60, 75 y 90 no son un fallo del dibujo**: son las lecturas en las que
  mordió el eslabón más débil, y por eso caen clavadas en la frontera de la banda.
  El rombo las marca, y es una FORMA además de un color, para que se distingan en
  gris y con daltonismo. Un hueco (lectura sin índice) parte la línea y se señala
  con una vertical punteada: unir por encima dibujaría una pendiente que nadie
  midió.

- **El gráfico de memoria** (`monitor-memoria`) añade una lectura cada treinta
  segundos. Eje vertical: megabytes ocupados. Eje horizontal: **tiempo activo de
  la instancia**, no la hora del reloj. Los dos umbrales se declaran en porcentaje
  —así se pactan— pero se dibujan en MB contra el mismo techo que la serie: en su
  propia escala habrían metido un segundo eje vertical por la puerta de atrás.
  Van rotulados sobre el dibujo, no en una leyenda aparte.

  El primer fotograma lo pinta el servidor, entero. `monitor.js` solo desplaza la
  ventana: si el guion no carga, el gráfico se ve igual pero quieto. **La lectura
  nueva la inventa el navegador** mientras esto sea una maqueta, y la tarjeta lo
  dice; la geometría se la pasa PHP por `data-*` para que no haya dos copias de
  los mismos números.

### Fichas de índice, semáforo y tabla de procesos

- **La tabla de procesos es una MATRIZ**: cada métrica del índice tiene su propia
  columna y su código vive en la cabecera, no repetido en cada celda. Así
  `M-PRO-03` se recorre de arriba abajo y se ve de un vistazo que vale 1,00 en
  los cinco procesos de fondo. **Celda en blanco y celda con guion no son lo
  mismo**: en blanco = esa métrica no evalúa a ese proceso; guion = sí lo evalúa
  y no se pudo recolectar. Es el invariante 3 llevado a la cuadrícula.
- **El ancho se reparte en dos mitades con necesidades opuestas.** A la
  izquierda lo que se compara —proceso, una columna por métrica, resultado—, que
  quiere estar junto para poder recorrerlo con la vista; a la derecha lo que se
  lee. Solo las columnas de prosa llevan anchura declarada (`w-[30%]`), y por eso
  las de métrica se ajustan a su contenido en vez de repartirse el sobrante.
- **DESCRIPCIÓN y RECOMENDACIÓN son dos columnas**, y vienen ya separadas del
  catálogo (`descripcion` / `recomendacion`), no partidas al pintar. Responden
  preguntas distintas —qué es esto, qué hago con ello— y en un solo párrafo
  obligaban a leerse la definición entera cada vez que uno venía a por la acción.
  La recomendación va en tinta de cuerpo y la descripción en la secundaria: es la
  única celda que pide actuar.
- **Cada código de métrica lleva su ficha al pasar por encima**: nombre y qué
  mide, de `catalogo_metricas` en la maqueta. El código identifica —remite a la
  ficha del catálogo— pero no explica, y en la cabecera no cabe el nombre: seis
  columnas de texto largo dejarían la matriz ilegible. El subrayado punteado es
  lo que anuncia que hay algo que consultar. El disparador es un
  `<span tabindex="0">` y no un botón: no ejecuta nada, y un botón anunciaría
  una acción que no existe. Los `id` van con prefijo `metrica-` porque
  `ficha-procesos` ya es la tarjeta IP — dos cosas llamadas «ficha» en la misma
  página confunden a quien lea el DOM.

  El panel se abre hacia ABAJO y centrado, no hacia arriba: el contenedor de la
  tabla lleva `overflow-x-auto` y recorta lo que se salga por el borde superior.
- **Los botones «?» son un componente: `monitor-ayuda`.** Hay cuatro —uno por
  tabla de procesos y otro en la cabecera de la consola— y la mecánica no es
  trivial, así que vive en un solo sitio: repetirla era garantizar que un día
  uno se quedara sin `aria-describedby`, o con un `hidden` que lo saca del árbol
  de accesibilidad sin que nadie lo note.

  Se abre con cursor, con teclado (`group-focus-within`) y al tocarlo en un
  móvil, **sin JavaScript**. El panel no se oculta con `hidden` ni con
  `invisible`: se atenúa con opacidad y se le desactiva el puntero, y al abrirse
  recupera `pointer-events` para poder pasar el cursor por encima sin que se
  cierre — que es lo que pide WCAG 1.4.13.

  **La ayuda de la consola** guarda los dos textos que antes vivían bajo las
  fichas: la leyenda del semáforo y la fórmula del ISBD. Está en la cabecera del
  panel y no junto a las tarjetas porque explican el panel entero —cómo se
  colorea el medidor y cómo se compone su cifra—, y siempre visibles eran dos
  párrafos de letra pequeña que se leen una vez y después solo empujan el resto
  hacia abajo.
- **Las fichas IP / IM / IA son pestañas** (`monitor-indices` + `monitor.js`):
  al pulsar una se despliega debajo la tabla de los procesos que ese índice
  evalúa (`monitor-procesos`). Nace abierta la PEOR de las tres, no la primera:
  quien abre un monitor viene a ver qué está mal, y abrir siempre por PROCESOS
  cobraría un clic extra justo cuando hay prisa. Sin JavaScript quedan las tres
  tablas visibles, que es más largo pero no es un error.
- **El semáforo NO es una segunda escala.** `semaforo()` AGRUPA las cinco bandas
  del §5.2 conservando sus fronteras: rojo = Crítico o Degradado (≤60), ámbar =
  Advertencia (60-75], verde = Saludable u Óptimo (>75). Por eso la franja de
  color y la pill de la banda no pueden contradecirse. **No le ponga cortes
  propios** (30/60, por ejemplo): un índice de 65 saldría verde en la ficha y
  ADVERTENCIA en la alerta, sobre el mismo número y en la misma pantalla.
- **El ISBD se tiñe por la regla del conjunto**, no por su propia banda:
  `semaforoGeneral()` da rojo si algún índice está en rojo y verde solo si los
  tres lo están. Hoy coincide siempre con la banda del ISBD —la regla del eslabón
  más débil ya lo garantiza— y se calcula aparte a propósito: si mañana se tocaran
  los topes, el color seguiría obedeciendo la regla sin depender de otra.
- **La casilla de resultado tiene TRES estados**, no dos: correcto, con hallazgo
  y **sin dato**. El tercero no es un adorno: un proceso cuyas métricas no se
  pudieron recolectar no ha fallado. Marcarlo con aspa convertiría una falla del
  agente en una falla de la base — invariante 3. Y cada casilla lleva su palabra
  al lado, porque palomita y aspa se distinguen por forma pero también por color.
- **Los valores de la tabla salen de las mediciones**, cruzados con
  `catalogo_procesos` en `MonitorController::procesosPorIndice()`. El catálogo
  solo declara la estructura (qué procesos, con qué métricas, qué hace cada uno);
  duplicar los valores por instancia habría sido la vía rápida para que las dos
  tablas dejaran de coincidir.

Dos piezas del sistema visual que este frente añadió y que ya son del producto,
no de la maqueta:

- **`pill()` tiene el tono `opt`** (§9 del plan): comparte el color de `ok` y se
  distingue por el ícono `escudo`, igual que `crit` comparte el de `bad` y se
  distingue por el octógono. Cuatro niveles de color, cinco de significado. **No
  agregue un quinto color ni un verde más claro.**
- **El ícono `aspa`**, negativo de `check`, para la casilla de resultado. No es
  un octógono ni un triángulo: ahí no se comunica severidad, solo «esta
  comprobación no pasó».
- **`tonoBanda()`** traduce banda → tono de `pill()` en un solo sitio. Repetir esa
  correspondencia dentro de una vista es la «tabla de traducción» que el §5.2 del
  plan existe para evitar. `null` va a `na`, nunca a un color de estado.

Falta del frente 4, y no está empezado: `/monitoreo/alertas`,
`/monitoreo/metricas`, exportación, `Scripts/08_datos_demo_monitor.sql`, manual
de usuario y sección del manual técnico.

## Sistema visual (Rivendel)

Normativo: `documentacion/design/rivendel-sistema-visual-prompt.md` y
`documentacion/design/diseño-general.md`. **No invente colores ni tipografías.**

Los tokens viven en `public/assets/css/rivendel.css` como ternas de canales RGB
(`--rv-bg: 12 22 17`), y `tailwind.config` los expone como colores
(`rgb(var(--rv-bg) / <alpha-value>)`). Esa notación, y no el hexadecimal del
documento, es lo que permite el modificador de opacidad (`bg-primario/10`).
Consecuencia práctica: **cambiar la paleta es tocar un solo archivo**, y las
utilidades de Tailwind siguen el cambio solas.

- **No hay tema conmutable.** No existe conmutador, ni `data-tema`, ni
  persistencia de tema: nadie puede poner el producto en claro desde la
  interfaz. Lo que sí hay son **regiones**, y cada una nace con su paleta.
- **Tres clases de región**, todas declaradas en `rivendel.css`:

  | Clase | Paleta | Dónde |
    |---|---|---|
  | `:root` | «Imladris de noche» | El sitio público entero y todo lo que no diga otra cosa. |
  | `.rv-claro` | «Pergamino élfico» | El `<body>` del módulo interno (`layouts/panel`). |
  | `.rv-oscuro` | «Imladris de noche» | Vuelve a la noche dentro de una región clara. Hoy solo la barra lateral del módulo. |
  | `.rv-alterno` | «Pergamino élfico» | Estilo de sección: la cadencia oscuro-claro-oscuro de la portada (§3). Va en retos, stack, testimonios y planes; contacto cierra en oscuro. |

- Cada paleta tiene **una sola definición**, compartida por selector: la oscura
  en `:root, .rv-oscuro` y la clara en `.rv-alterno, .rv-claro`. Si corrige un
  valor, corríjalo ahí y vale para todos los usos. **No copie la paleta a una
  clase nueva** — así es como se desincronizan.
- `.rv-oscuro` repite también los tokens `--nm-*` de relieve, no solo los
  colores: sobre pergamino la sombra se deriva del texto, y una barra oscura que
  heredara ese relieve se vería lavada.
- Al escribir para cualquiera de esas regiones, **use solo tokens**
  (`text-texto`, `text-oro-texto`, `text-primario-texto`…): son los que
  invierten. Un color fijado a mano se rompe en cuanto cambia de región. Hoy el
  módulo entero cumple esa regla, y es lo que permitió invertir su lienzo sin
  tocar una sola vista.
- Fuera de esas clases, la paleta clara solo aparece en `@media print` (para
  que el reporte no gaste tinta de fondo).
- **Cuatro voces excluyentes**: `.rv-marca` (Cinzel, solo logotipo y encabezado
  de informe), `.rv-titulo` (EB Garamond, títulos y prosa, nunca < 16 px),
  `font-sans` (Inter, interfaz y datos), `.rv-id` (JetBrains Mono, códigos de
  control — va en oro porque el oro **solo** significa referencia normativa).
- **Estado**: use `pill('ok'|'warn'|'bad'|'crit'|'na', $etiqueta)` de
  `funciones.php`. Nunca comunique un estado solo con color: el ayudante obliga
  a llevar ícono y etiqueta. Contorno y texto teñido, jamás relleno sólido.
- **`text-primario-texto`** en todo botón relleno: `text-texto` sobre el verde
  no alcanza 4,5:1 en ninguno de los dos temas.
- **Cifras**: clase `tabular` y formato español —
  `number_format($v, 1, ',', '') . ' %'` → `68,0 %`.
- **Gráficos: Rivendel no tiene un segundo tono categórico.** El oro significa
  referencia normativa y la escala de estado significa estado, así que no queda
  ningún par disponible para «serie 1 / serie 2» — verde contra gris se separan
  por 3 sobre 100 con daltonismo protán, y por 9 con visión normal, cuando el
  mínimo utilizable es 15. **No invente un tercer color para salir del paso.**
  Distinga las series por la FORMA de la marca: una columna y una línea no se
  confunden con ninguna visión ni impresas en gris (`components/evolucion-mensual`).
  Si de verdad hacen falta dos series iguales, son dos gráficos.
- **Nunca dos ejes verticales.** Si las medidas no comparten escala, van en
  gráficos distintos o normalizadas a una base común. Con dos escalas, quien
  elige los topes decide cuál línea va por encima, y eso no es un dato.
- Sobre relleno lleno (celdas de la matriz, botones) el texto va en
  `text-primario-texto`: la tinta del cuerpo no alcanza el contraste mínimo
  sobre el verde, el oro ni el rojo en ninguno de los dos lienzos.
- **Cajas de texto de tamaño fijo**: `rivendel.css` aplica `resize: none` a todo
  `textarea`. El alto se declara con `rows`; no se devuelve el tirador en
  ninguna vista.
- **Neumorfismo**: `.rv-extruido` / `.rv-hundido` (+ `.rv-relieve-sutil` en
  tablas densas). El hundido señala «aquí se recibe algo»: campos, franja de
  mensajes, estados vacíos.

## Arquitectura

MVC propio sobre PHP 8.3. Una petición recorre:

`public/index.php` (front controller, único archivo alcanzable por el navegador)
→ construye los servicios → `Enrutador` busca en `config/rutas.php`
→ instancia el controlador con el `Contenedor` → el controlador pide datos a un
repositorio y llama a `ver()` → `Vista` renderiza la plantilla dentro de
`layouts/principal`.

`Apache` apunta el DocumentRoot a `public/`; el `.htaccess` de la raíz solo
existe para instalaciones tipo WAMP donde se copia la carpeta completa.

### El interruptor de la base de datos

**La presencia de `config/base_datos.php` decide qué mitad del sistema existe.**
El archivo está en `.gitignore`; se copia desde `config/base_datos.ejemplo.php`.

- **Sin él**: `Contenedor::auditorias()` lanza excepción, el instrumento se lee de
  `config/instrumento-bd.php`, y el sitio público (portada, instrumento) funciona
  igual. Nadie queda bloqueado por no tener Oracle levantado.
- **Con él**: se construye `BaseDatos` (conexión perezosa — no abre socket hasta
  la primera consulta), `RepositorioAuditoriasOracle`, y si
  `instrumento_en_oracle` es `true`, `RepositorioInstrumentoOracle`.

Las vistas consultan `Contenedor::hayAuditorias()` / `hayCatalogo()` para ocultar
menús en lugar de asumir que existen.

### Repositorios: la separación que importa

Cuatro interfaces en `app/Models/Contratos/` describen qué datos ofrece el
sistema sin decir de dónde salen:

| Contrato | Implementaciones |
|---|---|
| `RepositorioContenido` | `RepositorioArreglo` (lee `config/contenido.php`) |
| `RepositorioInstrumento` | `RepositorioInstrumentoArreglo`, `RepositorioInstrumentoOracle` |
| `RepositorioCatalogo` | solo `RepositorioInstrumentoOracle` (escritura) |
| `RepositorioAuditorias` | solo `RepositorioAuditoriasOracle` |

`RepositorioInstrumentoOracle` **decora** a `RepositorioInstrumentoArreglo`: lee
dominios, procesos y controles de las tablas, pero delega `meta()`, `escala()`,
`marco()` y `referencias()` al arreglo, porque el esquema no tiene tablas para
esas cuatro secciones. Al tocar ese archivo, respete la delegación.

`RepositorioCatalogo` es la "otra cara" del mismo objeto que devuelve
`instrumento()`: `Contenedor::catalogo()` comprueba el tipo con `instanceof` en
vez de convertirlo a secas, porque la implementación de arreglo no sabe escribir.

### Oracle

`app/Core/BaseDatos.php` es el **único** lugar que llama funciones `oci_*`.
Reglas que ya están codificadas ahí y conviene no romper:

- Todo parámetro se enlaza con `oci_bind_by_name`; nunca se concatena en el SQL.
- Los nombres de columna se pasan a minúsculas al leer (`$fila['codigo']`).
- Los campos CLOB (`hallazgo`, `enunciado`, `pregunta`) van en el argumento
  `$clobs` de `ejecutar()`, no en `$parametros`: por encima de 4000 bytes
  Oracle lanza ORA-01461. Un CLOB vacío se guarda como NULL a propósito.
- Los INSERT usan `RETURNING ... INTO :id` con `insertar()`, porque las tablas
  son `GENERATED ALWAYS AS IDENTITY`.
- **Los indicadores salen de procedimientos almacenados, no de SELECT sueltos**
  (requisito del curso). `pkg_indicadores` en `Scripts/03_*.sql` expone
  `calcular_riesgo_auditoria` (sin cursor, vía `procedimiento()`) y el resto con
  `OUT SYS_REFCURSOR`, que se leen con `cursor()`; el bloque PL/SQL debe nombrar
  el parámetro `:cursor`. Una cifra nueva en pantalla es un procedimiento nuevo
  en ese archivo, no un SELECT en PHP — y tras editarlo hay que recargarlo:
  `docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/03_*.sql`.
- Al agregar en la aplicación, **agregue en SQL**. `sp_evolucion_auditor` mete
  las auditorías del mes en una razón única (`SUM(si) / SUM(si+no)`) en vez de
  promediar los cumplimientos de cada una: promediar razones le da el mismo peso
  a una auditoría de tres controles que a una de setenta y cinco.

### Autenticación y autorización

`app/Core/Autenticacion.php` concentra la lógica; los controladores solo la
invocan. En sesión se guarda **únicamente el id del usuario**, y se reconsulta
en cada petición para que desactivar una cuenta surta efecto de inmediato.

- `Controlador::exigirUsuario()` abre toda acción del módulo de auditorías;
  `exigirAdministrador()` añade el rol ADMIN_BD (catálogo maestro).
- Filtrar la lista por auditor no basta: `AuditoriaController::auditoriaPropia()`
  comprueba la propiedad porque `/evaluacion/9` es una URL adivinable.
- `Autenticacion::registrar()` fuerza el rol AUDITOR; el rol nunca viene del
  formulario.
- **CSRF**: todo formulario POST imprime `<?= $vista->campoToken() ?>` y el
  controlador llama a su `exigirToken($destino)` antes de tocar nada. Ese helper
  está duplicado en `AuditoriaController` y `CatalogoController` — si añade un
  tercer controlador con escritura, repítalo o súbalo a `Controlador`.
- `Sesion` es perezosa: no envía cookie mientras nadie lea ni escriba. Por eso
  `Controlador::mensajesPendientes()` consulta `existePrevia()` antes de leer
  destellos, y el idioma vive en su propia cookie y no en la sesión.

### Dos marcos: sitio público y módulo interno

Hay **tres** diseños en `app/Views/layouts/`, y elegir mal el marco es el error
fácil al añadir una pantalla:

| Plantilla | Cómo se pide | Para qué |
|---|---|---|
| `principal` | `ver($vista, $datos)` | Portada, autenticación, documentos públicos. Barra superior + pie con el formulario de contacto. |
| `panel` | `verPanel($vista, $datos)` | Todo `/evaluacion`, `/remediaciones` y `/catalogo`. Barra lateral fija + barra superior delgada + pie mínimo. |
| `imprimir` | `ver($vista, $datos, 'imprimir')` | Solo el reporte ejecutivo. |

- El menú del módulo se arma **una sola vez en `layouts/panel.php`** y llega ya
  resuelto a `partials/panel/barra-lateral` (lo pinta) y a
  `partials/panel/barra-superior` (saca de él la miga de pan). No lo duplique en
  un parcial: el elemento activo se calcula ahí y las dos piezas no pueden
  contradecirse.
- El elemento activo gana por **ruta declarada más larga** que sea prefijo de la
  actual. Por eso `/evaluacion/9/resultados`, que no tiene entrada propia,
  ilumina «Mis auditorías», y `/evaluacion/nueva` no enciende las dos.
- El grupo «Administración» solo existe si `$usuarioActual->esAdministrador()`,
  igual que las rutas que enlaza.
- La barra superior del panel es **pegajosa, no fija**: por eso las vistas del
  módulo llevan `py-8` y no el `pt-24` que reservaba el alto de la barra fija
  del sitio público. Una vista nueva en `panel` no debe compensar nada arriba.
- La ficha «Bases de datos conectadas» lee `config/conexiones.php`, que llega al
  `Contenedor` desde `public/index.php` como arreglo y **no detrás de un
  contrato**: hoy no hay nada que consultar y es configuración, como las rutas.
  Su forma —una fila por instancia, con su motor— sí es la que tendrá la tabla
  del monitoreo; cuando exista se cambia quién lee la lista y la vista no se
  entera. El conteo de la ficha es el largo de `instancias`, nunca una cifra
  escrita al lado.
- El tablero del panel (matriz de la última auditoría + evolución mensual) se
  filtra por **empresa auditada**, que es la del administrador de BD
  entrevistado. La lista de empresas sale de las auditorías que ya están en
  memoria, y **lo que llega en `?organizacion=` se comprueba contra esa lista**
  antes de tocar el repositorio: sin eso, la URL sería una forma de preguntar
  por la cartera de otra consultora — el mismo razonamiento de
  `auditoriaPropia()` frente a `/evaluacion/9`.
- **El lienzo se invierte**: `<body class="rv-claro">` y la barra lateral con
  `.rv-oscuro`. Navegación de noche, trabajo sobre pergamino. No es un tema
  (ver «Sistema visual»); es cómo se separan las dos cosas sin gastar un borde.
- La barra **se pliega** con el botón de su cabecera y vuelve con el gemelo de
  la barra superior. El estado vive en `data-lateral` sobre `<html>` y lo
  escribe el **servidor** desde la cookie `becajo_lateral`, para que la página
  nazca plegada en vez de encogerse a la vista en cada navegación; el guion solo
  alterna el atributo y reescribe la cookie. Por debajo de `lg` eso no aplica:
  ahí la barra es un cajón y lo gobierna `-translate-x-full`. Los dos
  mecanismos viven en anchos distintos y no se cruzan.
- No repita en la vista lo que ya está en la barra lateral (salir, sesión,
  saltos a otra sección). La cabecera de cada pantalla es para las acciones de
  esa pantalla.

### Convenciones de controlador y vista

- Todo POST que modifica termina en `redirigir()` (patrón PRG). Al fallar la
  validación se guarda el intento con `guardarIntento($errores, $valores)` y se
  redirige al GET, que los recupera con `erroresGuardados()` / `valoresGuardados()`.
- Los datos comunes del layout se difunden con `[...$this->contexto(), ...]`.
- La validación se escribe en PHP aunque el `CHECK` exista en la base: las
  restricciones son la última línea de defensa, no la primera (un ORA-02290 en
  pantalla no es un mensaje de error).
- `Vista::renderizar()` usa `extract(EXTR_SKIP)`; sus variables internas llevan
  prefijo `__` para no chocar con los datos de la vista. `$vista` y `$rutaBase`
  se asignan después de `extract()` y no se pueden suplantar.
- Enlaces con `$vista->url()` (respeta la subcarpeta de instalación) o
  `$vista->destino()` para entradas de menú que pueden ser anclas de la portada.

### Idiomas

Dos capas independientes: `config/idiomas/{es,en}.php` para textos de interfaz,
consultados con `$vista->t('clave')` (una clave sin traducir se imprime tal cual,
para que se note); y `config/contenido.php` / `contenido.en.php`, elegido una sola
vez en `public/index.php` según la cookie, con respaldo al español.

## Reglas del equipo

1. **Todo dato impreso pasa por `e()`.** Sin excepciones.
2. Archivos de vistas y recursos en **minúscula**; **PascalCase** solo para
   clases. Linux distingue mayúsculas y Windows no.
3. Rutas siempre con `/`. UTF-8 sin BOM. `declare(strict_types=1)` en todo PHP.
4. Un commit por cambio con sentido propio, mensaje en imperativo y en español.
5. Nunca versione `config/base_datos.php` ni credenciales reales.
