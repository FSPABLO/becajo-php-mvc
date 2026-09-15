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

1. **COBIT 2019, no 4.1.** La versión 4.1 solo se sigue citando donde el proyecto usa su Apéndice II (peso y relación P/S de los procesos).
2. **Sin Essential Eight.** Se retiró del alcance. El script 14 lo borra si una base lo tenía de una versión anterior.
3. **Catálogos independientes, no equivalencias.** COBIT no se mapea a ISO; cada norma tiene sus dominios, procesos y controles.
4. **La norma se fija al crear la auditoría** y no se edita después.
5. **Capacidad por objetivo declarada por el auditor** (0 a 5, con justificación obligatoria). No se calcula desde las prácticas.
6. **Prácticas con grado de logro N/P/L/F** (escala de ISO/IEC 33020). El estado se deriva: L y F → Sí; N y P → No. Así cumplimiento, avance y remediaciones funcionan sin cambios.
7. **Se mantiene el riesgo C/I/D en COBIT** porque lo exige el enunciado. Cada dimensión promedia la capacidad de los objetivos, con peso 2 si la relación es P y 1 si es S.
8. **El modo de captura es un dato** (`estandar.modo_evaluacion`: `CONTROL` u `OBJETIVO`), no un `if` sobre el nombre de la norma.
9. **El catálogo administrable solo edita ISO.** Abrir un control COBIT por URL responde 404.

## Instalación en Oracle

Orden obligatorio: **14 → 15 → 03**. El 03 depende de las columnas que crean el 14 y el 15.

```powershell
docker cp Scripts\14_multinorma.sql becajo-oracle:/tmp/14.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/14.sql"
docker cp Scripts\15_cobit_capacidad.sql becajo-oracle:/tmp/15.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/15.sql"
docker cp Scripts\03_procedimientos_indicadores.sql becajo-oracle:/tmp/03.sql
docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/03.sql"
```

Usar `docker cp` y no la tubería de PowerShell, que daña las tildes. Los dos scripts nuevos se pueden correr más de una vez. `instalar.sh` ya los incluye.

## Verificación pendiente (los SQL no se han probado contra Oracle)

- [ ] El script 14 termina sin errores y sus consultas finales muestran 2 normas, COBIT 4/12/24, ISO 7/25/75 y la madurez en 0..5.
- [ ] El script 15 muestra ISO27002 = CONTROL y COBIT2019 = OBJETIVO.
- [ ] Una auditoría ISO existente, al recalcularla, conserva **exactamente** el mismo índice general.
- [ ] Una auditoría ISO sigue mostrando 7 dominios y 75 controles, sin rastro de COBIT.
- [ ] Una auditoría COBIT nueva muestra los dominios APO/BAI/DSS/MEA, 24 prácticas y fichas de objetivo con la escala Incompleto…Optimizado.
- [ ] Declarar capacidad 3 en APO12 (relación P en las tres dimensiones) da **0.600 y zona amarilla** en las tres dimensiones.
- [ ] Una práctica calificada P aparece como hallazgo en remediaciones.
- [ ] Una práctica L o F sin evidencia descrita se rechaza con mensaje.
- [ ] `/evaluacion/{id}/controles/C-001` en una auditoría COBIT responde 404, y `/catalogo/controles/CB-001` también.

Si alguna falla, el mensaje de cada commit y la sección "Multinorma" de `CLAUDE.md` explican dónde vive cada pieza.

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
