# `RepositorioMonitor` y rutas — contrato

**EIF402 · Proyecto Rivendel · Grupo 4 · Parte 2 — Instrumento de salud**

> Tercero y último de los contratos de la Fase 0 (§11 del
> [plan de la parte 2](EIF402_Monitor_de_Salud_parte_2.md)). Define cómo el sitio y
> el motor de cálculo piden datos del monitor sin saber de dónde salen, y qué URL
> tiene cada pantalla.
>
> Depende de los otros dos: las entidades salen del
> [catálogo v0](catalogo-metricas-v0.md) y la estructura que persiste
> `guardarMuestra()` es la muestra evaluada del
> [contrato de muestra](contrato-muestra.md).

---

## 1. Son dos interfaces, no una

El producto ya resolvió este problema una vez y conviene copiar la solución en
lugar de inventar otra. `RepositorioInstrumento` (lectura) y `RepositorioCatalogo`
(escritura) son **dos contratos que la misma clase Oracle implementa**, mientras que
la implementación de arreglo solo cumple el primero porque no sabe escribir. Por eso
`Contenedor::catalogo()` comprueba el tipo con `instanceof` en vez de convertirlo a
secas.

El monitor tiene exactamente la misma forma:

| Contrato | Quién lo usa | Implementaciones |
|---|---|---|
| `RepositorioMonitor` | El sitio y el motor de cálculo | `RepositorioMonitorOracle`, `RepositorioMonitorArreglo` |
| `RepositorioMonitorEscritura` | Solo el agente | `RepositorioMonitorOracle` |

Separarlos no es formalismo. Es lo que permite que **el motor de cálculo y las
vistas se desarrollen y se prueben sin Oracle levantado**, contra un archivo de
muestras de ejemplo, tal como el sitio público se pudo trabajar sin base de datos.
Y deja escrito en el sistema de tipos algo que el §4 del plan exige: el navegador
solo lee lo ya calculado, nunca escribe.

---

## 2. `RepositorioMonitor` — lectura

```php
<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Alerta;
use App\Models\Entidades\Episodio;
use App\Models\Entidades\Instancia;
use App\Models\Entidades\Medicion;
use App\Models\Entidades\Metrica;
use App\Models\Entidades\Muestra;

/**
 * Contrato de lectura del monitor de salud.
 *
 * Lo usan dos consumidores con necesidades distintas y por eso los métodos van
 * en bloques: el sitio, que pinta lo ya calculado, y el motor de cálculo, que
 * necesita mirar hacia atrás para derivar tasas, sostener la histéresis y
 * comparar contra la línea base.
 *
 * Ninguno de estos métodos calcula nada. Las series y los resúmenes salen de
 * pkg_monitor, igual que los indicadores de la parte 1 salen de
 * pkg_indicadores: una cifra nueva en pantalla es un procedimiento nuevo, no un
 * SELECT en PHP.
 */
interface RepositorioMonitor
{
    // ── Instancias vigiladas ─────────────────────────────────────────────────

    /** @return list<Instancia> */
    public function instancias(): array;

    public function instancia(string $clave): ?Instancia;

    // ── Estado actual ────────────────────────────────────────────────────────

    /**
     * La última muestra evaluada de cada instancia, con su índice ya resuelto.
     *
     * Una sola llamada para la pantalla que las lista todas. La alternativa
     * —una consulta por instancia— crece con la cartera y es la forma habitual
     * de que un tablero se vuelva lento sin que nadie sepa por qué.
     *
     * @return list<Muestra>
     */
    public function ultimasMuestras(): array;

    public function ultimaMuestra(string $clave): ?Muestra;

    /**
     * El detalle de una muestra: una fila por métrica, con su valor crudo, su
     * normalizado y el estado con que se publicó.
     *
     * @return list<Medicion>
     */
    public function mediciones(int $idMuestra): array;

    // ── Series históricas ────────────────────────────────────────────────────

    /**
     * Evolución del ISBD entre dos instantes. Los tramos sin muestras NO vienen
     * en el resultado: rellenar huecos es cosa de quien dibuja, porque solo la
     * vista sabe si corta la línea o une los extremos.
     *
     * Misma decisión que evolucionAuditor() en RepositorioAuditorias.
     *
     * @return list<array<string, mixed>>
     */
    public function serieIndice(string $clave, string $desdeUtc, string $hastaUtc): array;

    /**
     * Evolución de una métrica. Por encima de la retención en crudo (30 días)
     * la implementación responde desde resumen_hora, no desde medicion.
     *
     * @return list<array<string, mixed>>
     */
    public function serieMetrica(
        string $clave,
        string $codigoMetrica,
        string $desdeUtc,
        string $hastaUtc,
    ): array;

    // ── Alertas y episodios ──────────────────────────────────────────────────

    /**
     * Alertas abiertas, de una instancia o de todas.
     *
     * @return list<Alerta>
     */
    public function alertasAbiertas(?string $clave = null): array;

    /**
     * Episodios: alertas concurrentes agrupadas por causa común (§6.1 del plan).
     *
     * @return list<Episodio>
     */
    public function episodios(string $clave, string $desdeUtc, string $hastaUtc): array;

    // ── Catálogo ─────────────────────────────────────────────────────────────

    /**
     * El catálogo de métricas con los umbrales VIGENTES ahora.
     *
     * Para explicar una medición histórica no sirve: hay que usar el umbral_id
     * que esa medición guardó, porque los umbrales se versionan (§7.2).
     *
     * @return list<Metrica>
     */
    public function metricas(): array;

    // ── Lo que necesita el motor de cálculo ──────────────────────────────────

    /**
     * Los valores acumulados de la muestra anterior, por código de métrica.
     *
     * Es lo que permite derivar las tasas: v$system_event entrega totales desde
     * el arranque de la instancia y la tasa exige dos lecturas. Devuelve un
     * arreglo vacío si no hay muestra previa, y en ese caso las métricas de tasa
     * salen del denominador — no son un error.
     *
     * @return array<string, float>
     */
    public function acumuladosAnteriores(string $clave): array;

    /**
     * Los últimos estados publicados de una métrica, del más reciente al más
     * antiguo. Alimenta la histéresis: k de las últimas n muestras (§5.5).
     *
     * @return list<string>
     */
    public function estadosRecientes(string $clave, string $codigoMetrica, int $cuantas): array;

    /**
     * Los valores normalizados de una métrica en el mismo tramo horario de los
     * últimos N días, para la línea base del §5.5 — el paso 6 de la guía.
     *
     * @return list<float>
     */
    public function ventanaLineaBase(
        string $clave,
        string $codigoMetrica,
        int $dias,
        int $tramoHora,
    ): array;
}
```

---

## 3. `RepositorioMonitorEscritura` — el agente

```php
<?php

declare(strict_types=1);

namespace App\Models\Contratos;

/**
 * Contrato de escritura del monitor. Solo lo usa bin/monitor.php.
 *
 * Vive aparte de RepositorioMonitor por la misma razón que RepositorioCatalogo
 * vive aparte de RepositorioInstrumento: la implementación de arreglo no sabe
 * escribir, y el sitio no debe poder hacerlo.
 */
interface RepositorioMonitorEscritura
{
    // ── Persistencia de la muestra ───────────────────────────────────────────

    /**
     * Guarda una muestra evaluada completa —cabecera, mediciones e índice— y
     * devuelve su identificador.
     *
     * Recibe la estructura definida en el contrato de muestra, §5. Es todo o
     * nada: una muestra a medio guardar es peor que ninguna, porque parece
     * completa.
     *
     * Una muestra FALLIDA también se guarda. Que la instancia no respondiera a
     * esa hora es un dato; saltarla deja un hueco que después parece un periodo
     * sano.
     *
     * @param array<string, mixed> $muestraEvaluada
     */
    public function guardarMuestra(array $muestraEvaluada): int;

    // ── Ciclo de vida de las alertas ─────────────────────────────────────────

    /**
     * Abre una alerta si no hay ninguna abierta para (instancia, métrica); si la
     * hay, actualiza su nivel y sus ocurrencias.
     *
     * La clave NO incluye el nivel: un problema que empeora escala dentro de su
     * alerta en vez de abrir una segunda (§6 del plan).
     */
    public function registrarAlerta(
        string $clave,
        string $codigoMetrica,
        string $nivel,
        float $valor,
        float $umbral,
        string $vistaUtc,
    ): int;

    public function cerrarAlerta(
        int $idAlerta,
        string $motivo,
        ?string $accion,
        ?int $idResponsable,
    ): void;

    /**
     * Agrupa alertas concurrentes en un episodio y señala la causa probable
     * cuando la tabla de precedencias permite deducirla.
     *
     * @param list<int> $idsAlerta
     */
    public function agruparEnEpisodio(array $idsAlerta, ?int $idAlertaCausa): int;

    // ── Umbrales versionados ─────────────────────────────────────────────────

    /**
     * Cierra el juego de umbrales vigente y abre uno nuevo.
     *
     * Nunca hay UPDATE sobre los cuatro números: recalibrar no debe reescribir
     * la historia (§7.2).
     */
    public function versionarUmbral(
        string $codigoMetrica,
        ?string $clave,
        float $uOpt,
        float $uAdv,
        float $uDeg,
        float $uCrit,
        string $desdeUtc,
    ): int;

    // ── Mantenimiento (pkg_monitor) ──────────────────────────────────────────

    /** Consolida en resumen_hora. Debe correr ANTES de purgar y verificarse. */
    public function consolidarHora(string $hastaUtc): void;

    /** Purga según la política del §7.4. Si la consolidación falló, no purga. */
    public function purgar(string $hastaUtc): void;
}
```

---

## 4. Entidades nuevas

Siete, en `App\Models\Entidades`. Ninguna choca con las once que ya existen.

| Entidad | Qué representa |
|---|---|
| `Instancia` | Una base vigilada: clave, motor, entorno, criticidad, si es demostrativa |
| `Muestra` | Cabecera de una recolección con su índice: `tomada_en`, `resultado`, `cobertura_pct`, `isbd`, `isbd_bruto`, `estado`, `causa` |
| `Medicion` | Una métrica dentro de una muestra: crudo, normalizado, estado, `umbral_id` |
| `Metrica` | Entrada del catálogo con sus umbrales vigentes |
| `Umbral` | Un juego de cuatro umbrales con su vigencia |
| `Alerta` | Ciclo de vida completo: nivel actual y máximo, ocurrencias, responsable, acción |
| `Episodio` | Agrupación de alertas concurrentes con su causa probable |

**`Muestra` trae su índice dentro.** No se separan en dos objetos porque no existe
una muestra sin índice ni un índice sin muestra, y tenerlos juntos evita que la
vista tenga que pedir dos cosas para pintar una tarjeta.

**`Medicion` necesita una columna que el §7.1 del plan no tenía:
`valor_acumulado`**, nulable. Es donde las métricas de tasa guardan el total leído,
para que la muestra siguiente pueda restar. Sin ella, `acumuladosAnteriores()` no
tiene de dónde leer y `M-PRO-04` no se puede calcular nunca.

---

## 5. Rutas

```php
// ── Monitoreo (instrumento de salud) ─────────────────────────────────────────
// Igual que en /evaluacion, las rutas literales van ANTES que la de parámetro:
// "/monitoreo/alertas" nunca se lo queda "/monitoreo/{instancia}".
$enrutador->get('/monitoreo',           [MonitorController::class, 'panel']);
$enrutador->get('/monitoreo/alertas',   [MonitorController::class, 'alertas']);
$enrutador->get('/monitoreo/metricas',  [MonitorController::class, 'catalogo']);

$enrutador->get('/monitoreo/{instancia}',                  [MonitorController::class, 'instancia']);
$enrutador->get('/monitoreo/{instancia}/metrica/{codigo}', [MonitorController::class, 'metrica']);

$enrutador->post('/monitoreo/alertas/{id}/reconocer', [MonitorController::class, 'reconocer']);
$enrutador->post('/monitoreo/alertas/{id}/cerrar',    [MonitorController::class, 'cerrar']);
```

| Ruta | Pantalla |
|---|---|
| `/monitoreo` | Todas las instancias con su ISBD, estado y **antigüedad del último dato** |
| `/monitoreo/alertas` | Alertas abiertas de toda la cartera, agrupadas en episodios |
| `/monitoreo/metricas` | El catálogo con sus fichas y umbrales vigentes — el instrumento de salud, visible |
| `/monitoreo/{instancia}` | Detalle: componentes, mediciones de la última muestra, evolución del ISBD |
| `/monitoreo/{instancia}/metrica/{codigo}` | Serie de una métrica con su línea base |

Todas van con `verPanel()`, dentro del diseño `panel`: barra lateral, barra
superior pegajosa, `py-8` y **sin** el `pt-24` del sitio público.

---

## 6. Cómo se enchufa al producto

Cinco puntos, todos con precedente en el repositorio:

1. **Interruptor.** `Contenedor::hayMonitor()` junto a `hayAuditorias()` y
   `hayCatalogo()`. Sin `config/monitor.php`, `Contenedor::monitor()` lanza
   excepción y las vistas ocultan el menú en vez de asumir que existe.
2. **`Contenedor::monitorEscritura()` comprueba con `instanceof`**, como
   `catalogo()`: la implementación de arreglo no cumple el contrato de escritura y
   convertirla a secas sería mentir.
3. **El menú se arma una sola vez** en `layouts/panel.php`. La entrada de
   monitoreo se declara ahí y llega resuelta a la barra lateral y a la miga de pan.
   El elemento activo gana por ruta declarada más larga, así que
   `/monitoreo/{instancia}` ilumina «Monitoreo» sin necesidad de entrada propia.
4. **Autorización.** Todas las acciones abren con `exigirUsuario()`. Los nombres de
   host y servicio son información sensible (§8.3) y no salen en páginas públicas.
5. **CSRF: aquí toca subir el ayudante.** `exigirToken()` está hoy duplicado en
   `AuditoriaController` y `CatalogoController`, y CLAUDE.md dice que un tercer
   controlador con escritura obliga a repetirlo **o a subirlo a `Controlador`**.
   `MonitorController` escribe —reconocer y cerrar alertas—, así que es el tercero:
   toca subirlo. Tres copias de lo mismo ya no es una coincidencia.

---

## 7. Lo que este contrato hereda del producto

Reglas que ya rigen y que no se renegocian aquí:

- **La vista no recalcula.** Ninguna plantilla deriva un porcentaje, una banda ni un
  estado. Si hace falta una cifra nueva, es un procedimiento nuevo en `pkg_monitor`
  (riesgo 7 del §10).
- **Agregar es trabajo de SQL.** Las series y los resúmenes se agregan en la base,
  no en PHP, y con la razón correcta —no promediando promedios—, como ya hace
  `sp_evolucion_auditor`.
- **Todo dato impreso pasa por `e()`.** Sin excepciones, y aquí importa más de lo
  habitual: los nombres de instancia y los mensajes de error del agente vienen de
  fuera.
- **PRG en todo POST.** Reconocer o cerrar una alerta termina en `redirigir()`.
- **Estado con `pill()`**, con el tono `opt` añadido (§9 del plan), y cifras con
  clase `tabular` en formato español: `90,0` y no `90.0`.

---

## 8. Fase 0 cerrada

Con los tres contratos escritos, los cuatro frentes del §11 pueden arrancar el mismo
día:

| Frente | Contra qué trabaja desde el primer día |
|---|---|
| Instrumento de salud | El catálogo v0, ampliándolo |
| Datos y agente | La forma de la muestra cruda y `RepositorioMonitorEscritura` |
| Motor de cálculo | Muestras de ejemplo y `RepositorioMonitor`, sin Oracle |
| Interfaz | `RepositorioMonitorArreglo` y estas rutas, sin Oracle |
