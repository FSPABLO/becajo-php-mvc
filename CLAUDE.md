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

# Cargar / recargar el esquema a mano
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/01_esquema.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/02_datos_semilla.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/03_procedimientos_indicadores.sql

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
`app/Views/partials/head.php`, con la paleta personalizada (`marina`, `acento`,
`exito`, `alerta`, `aviso`) declarada ahí mismo en `tailwind.config`. No
introduzca dependencias externas sin acordarlo: la ausencia de `vendor/` es
deliberada. Verificar un cambio significa abrirlo en el navegador.

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
  `calcular_riesgo_auditoria` (sin cursor, vía `procedimiento()`) y cinco
  procedimientos con `OUT SYS_REFCURSOR` que se leen con `cursor()`; el bloque
  PL/SQL debe nombrar el parámetro `:cursor`.

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
