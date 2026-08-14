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

**No hay Composer, npm, PHPUnit ni linter.** El autoloader está escrito a mano
(`app/Core/Autoloader.php`) y Tailwind se carga por CDN desde
`app/Views/partials/head.php`. No introduzca dependencias externas sin
acordarlo: la ausencia de `vendor/` es deliberada. Verificar un cambio
significa abrirlo en el navegador.

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
