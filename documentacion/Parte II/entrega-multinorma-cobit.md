# Entrega: Multinorma con COBIT 2019

Estado del trabajo para quien continúe la incorporación de COBIT al instrumento de auditoría.

## Qué hay hecho

Son dos commits que van en orden, sobre `Preparación-Parte-II` en el merge del PR #10 (`0555c05`, que ya incluye el asistente Lembas). Juntos pasan 137 pruebas.

| Commit | Archivo | Qué hace |
|---|---|---|
| 1. Multinorma: COBIT 2019 como segunda norma evaluable | `multinorma-cobit-v2.patch` | Al crear una auditoría se elige la norma. Cada auditoría ve solo el catálogo y la escala de su norma. |
| 2. COBIT: captura propia por objetivo y práctica | `cobit-pantalla-v2.patch` | COBIT deja de responderse como ISO: capacidad declarada por objetivo y grado de logro N/P/L/F por práctica. |

Aplicarlos:

```powershell
git switch Preparacion-Parte-II
git pull
git switch -c Multinorma-COBIT
git am --3way "ruta\multinorma-cobit-v2.patch"
git switch -c COBIT-Pantalla
git am --3way "ruta\cobit-pantalla-v2.patch"
```

Si `git am` falla: `git am --abort`. Si la base avanzó más allá de `0555c05`, copiar archivos completos pisaría cambios ajenos, así que hay que rehacer los commits sobre la base nueva.

## Decisiones ya tomadas (no reabrir sin hablar con Cami)

1. **COBIT 2019.** Se eligió 2019 por su integración amplia con ITIL, ISO/IEC 27001, TOGAF y otros marcos, y porque es la versión vigente publicada por ISACA.
2. **Sin Essential Eight.** Se retiró del alcance. El script 14 lo borra si una base lo tenía de una versión anterior.
3. **Catálogos independientes, no equivalencias.** COBIT no se mapea a ISO; cada norma tiene sus dominios, procesos y controles.
4. **La norma se fija al crear la auditoría** y no se edita después.
5. **Capacidad por objetivo declarada por el auditor** (0 a 5, con justificación obligatoria). No se calcula desde las prácticas.
6. **Prácticas con grado de logro N/P/L/F** (escala de ISO/IEC 33020). El estado se deriva: L y F → Sí; N y P → No. Así cumplimiento, avance y remediaciones funcionan sin cambios.
7. **Se mantiene el riesgo C/I/D en COBIT** porque lo exige el enunciado. Cada dimensión promedia la capacidad de los objetivos, con peso 2 si la relación es P y 1 si es S. La relación P/S por proceso y por dimensión (`proceso.relacion_confidencialidad/integridad/disponibilidad` en `Scripts/14_multinorma.sql`) es **criterio propio del equipo**, no una tabla oficial de COBIT 2019 — el marco no publica una matriz equivalente para C/I/D por proceso — y se asignó según la relevancia de cada proceso para cada criterio, a partir de su descripción en el catálogo de ISACA.
8. **El modo de captura es un dato** (`estandar.modo_evaluacion`: `CONTROL` u `OBJETIVO`), no un `if` sobre el nombre de la norma.
9. **El catálogo administrable solo edita ISO.** Abrir un control COBIT por URL responde 404.

## Instalación en Oracle

Orden obligatorio: **10 → 14 → 15 → 03**. El 03 depende de las columnas que crean el 14 y el 15, y de la vista `v_auditoria_entrevistado` que crea el 10 (ver "Bug encontrado #2" abajo). `11_evidencia_archivo.sql` y `13_perfil_usuario.sql` no son requisito de 03, pero conviene cargarlos en la misma pasada.

```powershell
docker cp Scripts\14_multinorma.sql becajo-oracle:/tmp/14.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/14.sql"
docker cp Scripts\15_cobit_capacidad.sql becajo-oracle:/tmp/15.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/15.sql"
docker cp Scripts\03_procedimientos_indicadores.sql becajo-oracle:/tmp/03.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/03.sql"
```

Usar `docker cp` y no la tubería de PowerShell, que daña las tildes. Los scripts son re-ejecutables. `instalar.sh` ya los incluye todos, en este orden.

## Verificación (ejecutada contra Oracle real — ya no está pendiente)

Corrida el 2026-09-15 contra un contenedor `becajo-oracle` recién levantado (solo 01+02 cargados de fábrica). Los 9 puntos pasan:

- [x] El script 14 termina sin errores y sus consultas finales muestran 2 normas, COBIT 4/12/24, ISO 7/25/75 y la madurez en 0..5.
- [x] El script 15 muestra ISO27002 = CONTROL y COBIT2019 = OBJETIVO.
- [x] Una auditoría ISO existente, al recalcularla, conserva **exactamente** el mismo índice general (probado con la auditoría 1: `.3` antes y después).
- [x] Una auditoría ISO sigue mostrando 7 dominios y 75 controles, sin rastro de COBIT (confirmado por el conteo separado de la verificación del script 14).
- [x] Una auditoría COBIT nueva muestra el catálogo de 4/12/24 y la escala Incompleto…Optimizado (confirmado a nivel de datos; la vista en pantalla no se probó en navegador).
- [x] Declarar capacidad 3 en APO12 (relación P en las tres dimensiones) da **0.600 y zona amarilla** en las tres dimensiones (probado con una auditoría COBIT de prueba, creada y borrada solo para esta verificación).
- [x] Una práctica calificada P aparece como hallazgo en remediaciones (verificado por código: `EvaluacionControl::estadoDeGrado()` mapea N/P→NO, y `AuditoriaController` marca como hallazgo toda evaluación con estado NO).
- [x] Una práctica L o F sin evidencia descrita se rechaza con mensaje (verificado por código: `AuditoriaController::validarRespuesta()` exige el campo `evidencia` cuando el estado derivado es SI, sin importar si vino de ISO o de un grado L/F).
- [x] `/evaluacion/{id}/controles/C-001` en una auditoría COBIT responde 404, y `/catalogo/controles/CB-001` también (verificado por código: `controlDelCatalogo()` y `controlIso()` filtran por catálogo de la norma y llaman `noEncontrado()` si no aparece).

Los tres últimos puntos se verificaron leyendo el código (lógica determinista, sin estado), no haciendo clic en el navegador — si se quiere la confirmación visual falta un paso manual.

### Bug encontrado #1 — las 7 consultas de verificación de 14 y 15 nunca se ejecutaban

Cada `PROMPT --- texto ---` que **termina en guion** hace que SQL*Plus trate ese guion como carácter de continuación de línea: se traga la siguiente línea completa (el `SELECT` de verificación) como si fuera parte del mismo texto, y la consulta nunca llega a ejecutarse — sin dar ningún error visible. Por eso esta checklist nunca se había podido correr de verdad: las 7 líneas `PROMPT ... ---` de `Scripts/14_multinorma.sql` y `Scripts/15_cobit_capacidad.sql` se corrigieron quitando el guion de cierre.

### Bug encontrado #2 — `pkg_indicadores` no compilaba en una base nueva

Dos causas independientes, ambas bloqueaban el 100% de los indicadores/reportes:

1. `Scripts/03_procedimientos_indicadores.sql` llama a `fn_zona()` (función privada del cuerpo del paquete) dentro de un `INSERT ... VALUES`, algo que Oracle no permite (`PLS-00231: function may not be used in SQL`). Se corrigió resolviendo la zona en una variable PL/SQL antes del INSERT.
2. `sp_historico_dominio`, `sp_evolucion_auditor` y `sp_remediaciones_vencidas` consultan la vista `v_auditoria_entrevistado`, que **solo crea `Scripts/10_administrador_manual.sql`** — y **`instalar.sh` nunca ejecutaba ese script**. En una instalación nueva de verdad (`./instalar.sh` desde cero), `pkg_indicadores` quedaba con errores de compilación y ningún indicador de riesgo funcionaba. **Corregido**: `instalar.sh` ahora carga `10_administrador_manual.sql`, `11_evidencia_archivo.sql` y `13_perfil_usuario.sql` (mismo patrón huérfano: ninguno de los tres se ejecutaba en una instalación nueva) antes de la sección de multinorma. Probada la secuencia completa `01→02→10→11→13→14→15→03` contra un Oracle recién levantado, con el mismo mecanismo de tubería (`<`) que usa el instalador: compila limpio.

## Pendiente (siguiente PR)

- **Resultados y reporte de COBIT:** hoy muestran el formato ISO. Falta un perfil de capacidad por objetivo.
- **Indicadores de madurez:** "Menor madurez" y el histórico por dominio salen vacíos en COBIT, porque sus prácticas no tienen madurez.
- **`programarReauditoria`:** no valida en el servidor que la auditoría de seguimiento sea de la misma norma (el desplegable sí filtra).
- **Catálogo administrable:** no permite crear ni editar controles COBIT.
- **Cobertura del catálogo COBIT:** 24 controles en 12 objetivos. Faltan equivalentes a capacidad (BAI04), incidentes (DSS02), proveedores (APO10) y un control explícito de respaldos.
- **Trazabilidad:** los controles COBIT citan solo el objetivo (APO12), no la práctica de gestión (APO12.01). Tomar los números del documento de ISACA.

## Archivos que tocan los commits

- **Scripts:** `14_multinorma.sql` y `15_cobit_capacidad.sql` (nuevos); `03`, `05` y `12`.
- **Modelos:** `Estandar` y `EvaluacionObjetivo` (nuevos); `Auditoria`, `EvaluacionControl`, los contratos `RepositorioInstrumento` y `RepositorioAuditorias`, y sus implementaciones Oracle y de arreglo.
- **Controladores:** `AuditoriaController` y `CatalogoController`.
- **Vistas:** `ficha-objetivo.php` (nuevo); `tarjeta-control-auditoria.php`, `evaluacion/mostrar.php` y `evaluacion/nueva.php`.
- **Configuración:** `rutas.php`, `idiomas/es.php`, `idiomas/en.php` y `contenido*.php`.
- **Otros:** `CLAUDE.md`, `instalar.sh` y dos archivos de pruebas nuevos en `tests/Modelos`.
