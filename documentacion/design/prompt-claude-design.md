# Prompt para Claude Design

> Especificación **funcional** del producto: qué hace cada página y cómo se
> navega entre ellas. No describe la interfaz actual — el diseño visual está
> abierto a propuesta.
>
> Copiar desde la línea siguiente hasta el final del archivo.

---

## Contexto del producto

**Becajo** es un sitio de consultoría en administración de bases de datos que
incorpora una herramienta interna de **auditoría de seguridad de la información**
basada en ISO/IEC 27002, ISO/IEC 27007 y COBIT 4.1. Conviven dos mitades:

- **Sitio público de marca** — capta clientes.
- **Aplicación de auditoría** — tras iniciar sesión: se levanta una auditoría, se
  responden 75 controles agrupados en 25 procesos y 7 dominios, se calcula un
  índice de riesgo y se da seguimiento a los plazos de corrección de los
  hallazgos.

Aplicación web renderizada en servidor. Disponible en **español e inglés**,
conmutables desde cualquier página.

**Roles**

| Rol | Alcance |
|---|---|
| Visitante (sin sesión) | Portada, instrumento público, ingresar, registrarse |
| `AUDITOR` | Todo lo anterior + sus propias auditorías |
| `ADMIN_BD` | Todo lo anterior + catálogo maestro, matriz C-I-D y panel global de vencidas |

Un auditor solo ve y edita **sus propias** auditorías.

---

## Qué necesito

Diseñá la interfaz de las páginas listadas abajo. Para cada una tenés su
**funcionalidad** y su **flujo de navegación** (de dónde se llega, hacia dónde
se sale). Las rutas son las reales del producto y no deben cambiar.

El diseño visual es tuyo: no hay decisiones de estilo heredadas que respetar.

---

## Navegación global

Disponible en todas las páginas salvo el reporte imprimible.

- **Identidad de la empresa**, que lleva al inicio.
- **Enlaces a las secciones de la portada.**
- **Menú «Herramientas»**, con entradas que tienen título y descripción breve.
- **Selector de idioma** (español / inglés).
- **Llamada a la acción de contacto.**
- **Sin sesión:** acceso a «Ingresar».
- **Con sesión:** «Mis auditorías», «Catálogo» (solo `ADMIN_BD`) y «Salir».
- Debe funcionar igual en pantallas pequeñas.

**Pie de página:** navegación, contacto y datos de la empresa.

**Mensajes de sistema:** toda acción que guarda o borra redirige y muestra un
aviso de resultado (éxito o error) en la página de destino. Hace falta un lugar
consistente donde aparezcan.

---

## Páginas

### 1. Portada — `/` · pública

Página única de venta, con secciones enlazadas desde la navegación en este orden:

- **Hero** — propuesta de valor y llamada a la acción principal.
- **Retos** — problemas del cliente que el servicio resuelve.
- **Servicios** — conjunto de servicios ofrecidos.
- **Stack** — motores de base de datos soportados (Oracle, SQL Server, MySQL, MariaDB, PostgreSQL, MongoDB).
- **Resultados** — métricas destacadas y un caso de éxito.
- **Testimonios** — testimonios de clientes con retrato, cita y calificación en estrellas; se recorren de uno en uno, con controles de anterior/siguiente y salto directo.
- **Equipo** — integrantes.
- **Contacto** — formulario que, al enviarse, vuelve a la misma sección con el resultado.

**Flujo:** la navegación desplaza a cada sección · «Herramientas» → instrumento · «Ingresar» → inicio de sesión.

### 2. Instrumento de evaluación — `/herramientas/instrumento-bd` · pública

Documento consultable de solo lectura, dividido en **5 apartados**:

1. **Instrumento** — ficha metodológica.
2. **Cuestionario** — los 75 controles, navegables por cada uno de los 7 dominios.
3. **Tablero** — escala de madurez y criterios de puntuación.
4. **Marco** — marco de referencia normativo.
5. **Referencias** — bibliografía.

**Flujo:** se llega desde el menú «Herramientas» · se sale por la navegación global.

### 3. Ingresar — `/ingresar` · pública

- Correo y contraseña.
- Al fallar, se informa el error y se conserva el correo tecleado.
- Acceso a «Registrarse».

**Flujo:** al entrar → «Mis auditorías». Cualquier página protegida a la que se
intente entrar sin sesión redirige acá.

### 4. Registrarse — `/registrarse` · pública

- Nombre, correo, organización, contraseña y confirmación.
- Toda cuenta creada es `AUDITOR`; el rol no se elige en pantalla.
- Acceso a «Ingresar».

**Flujo:** al registrarse → «Mis auditorías».

### 5. Mis auditorías — `/evaluacion` · `AUDITOR`

Punto de entrada de la aplicación.

- Identificación de quien tiene la sesión: nombre y organización.
- **Listado de auditorías propias**, con: número, organización, área evaluada, fecha, **estado** (en progreso / finalizada) e **índice general de riesgo** (numérico, o sin calcular todavía).
- Acciones: **crear auditoría**, **comparar histórico** y **cerrar sesión**.
- **Sin auditorías:** invitación a crear la primera, indicando cuántos controles tiene el instrumento.

**Flujo:** cada auditoría → su detalle · crear → formulario de alta · comparar → comparación histórica.

### 6. Nueva auditoría — `/evaluacion/nueva` · `AUDITOR`

- Alta de una auditoría: área evaluada, fecha y administrador de BD entrevistado.
- Regreso a «Mis auditorías».

**Flujo:** al crear → detalle de la auditoría recién creada.

### 7. Detalle de auditoría — `/evaluacion/{id}` · `AUDITOR` (solo las propias)

Centro de operaciones de una auditoría.

- Identificación: número de auditoría, estado, organización y entrevistado.
- Acciones: **ver resultados**, **remediaciones**, y **finalizar** o **reabrir** según el estado.
- Si está finalizada, se advierte que ya no admite cambios.
- **Avance**: cuántos de los 75 controles están respondidos.
- **Edición del encabezado** de la auditoría (área, fecha, entrevistado), secundaria respecto al trabajo principal.
- **Listado de los controles del instrumento**, con: código, proceso al que pertenece, enunciado, respuesta registrada (Sí / No / No aplica) y nivel de madurez.

**Flujo:** cada control → su ficha · resultados → tablero · remediaciones → seguimiento de plazos · regreso a «Mis auditorías».

### 8. Ficha de control — `/evaluacion/{id}/controles/{codigo}` · `AUDITOR`

La pantalla donde se hace el trabajo de auditoría. Captura, para un control:

- **Pregunta de auditoría** — el auditor puede reescribirla solo para esta evaluación — y **evidencia esperada**.
- **Respuesta**: Sí / No / No aplica.
- **Nivel de madurez** (puede quedar sin calificar).
- **Criterios de comprobación**, varios a la vez: documentado, repetible, con evidencia.
- **Evidencia verificada** — texto largo, **obligatorio cuando la respuesta es «Sí»**. Necesita una explicación al alcance de la mano de por qué se exige: ISO/IEC 27007 determina la conformidad contra evidencia, no contra la afirmación del auditado.
- **Calidad de la evidencia** — también obligatoria si la respuesta es «Sí»: bien implementado / requiere mejora / declarativo (sin evidencia real).
- **Qué compromete**, varios a la vez: confidencialidad, integridad, disponibilidad.
- **Impacto** y **probabilidad**, de 1 a 5. El **nivel de riesgo** se muestra calculado como promedio de ambos.
- **Hallazgo** y **recomendación**, texto largo.
- Dos formas de guardar: **guardar** y **guardar y pasar al siguiente control**, que encadena el trabajo sin volver al listado.

**Flujo:** se llega desde el listado del detalle, o desde los accesos directos de la pantalla de remediaciones · «guardar y siguiente» encadena controles.

### 9. Resultados — `/evaluacion/{id}/resultados` · `AUDITOR`

Tablero analítico de una auditoría.

- Advertencia destacada cuando el riesgo resulta crítico.
- **Indicadores principales**: cumplimiento general, madurez promedio, índice general de riesgo y controles respondidos, desglosados en sí / no / no aplica.
- **Exposición de riesgo** por dimensión: confidencialidad, integridad, disponibilidad.
- **Matriz de riesgo** impacto × probabilidad, con los controles ubicados en la cuadrícula y tres zonas de severidad.
- **Cumplimiento por dominio**: por cada uno de los 7 dominios, conteo de respuestas, porcentaje de cumplimiento y madurez.
- **Menor madurez** y **mayor riesgo**: los controles peor evaluados.
- Cada bloque puede quedarse sin datos por separado y debe decirlo.
- Acceso al **reporte ejecutivo**.

**Flujo:** se llega desde el detalle · sale al reporte.

### 10. Reporte ejecutivo — `/evaluacion/{id}/reporte` · `AUDITOR`

- **Pensado para imprimirse o guardarse como PDF**: sin la navegación del sitio.
- Datos de portada: organización, área, auditor, administrador entrevistado, fecha, estado y fecha de generación.
- Recoge, en formato de documento, los mismos indicadores, exposición por dimensión, matriz de riesgo, cumplimiento por dominio, menor madurez y mayor riesgo.

**Flujo:** se llega desde Resultados.

### 11. Remediaciones de una auditoría — `/evaluacion/{id}/remediaciones` · `AUDITOR`

Seguimiento de los plazos de corrección de los hallazgos, según el ciclo
Planificar-Hacer-Verificar-Actuar (ISO 9001 §8.5.2 / ISO/IEC 27001 cl. 10).

- **Accesos directos a los controles pendientes de re-auditar**: los que tienen un plazo abierto, indicando la auditoría de seguimiento cuando ya está programada.
- **Por cada remediación**: control al que corresponde y su enunciado, **estado** (pendiente / en proceso / cumplido / vencido), fecha límite y responsable.
  - **Enlazar una re-auditoría**: elegir entre las otras auditorías propias del auditor. Si no hay ninguna disponible, hay que decirlo y ofrecer crear una auditoría de seguimiento. Si ya está enlazada, se accede a esa auditoría y al control por revisar.
  - **Cambiar el estado** entre pendiente, en proceso y cumplido.
- **Crear un plazo**: elegir un control ya evaluado con hallazgo («No»), fecha límite y responsable. Si todavía no hay ninguno, explicar que primero hay que completar el cuestionario.
- **Sin plazos registrados:** decirlo y explicar cómo crear el primero.

**Flujo:** se llega desde el detalle de la auditoría · los accesos directos llevan a la ficha del control · regreso a la auditoría.

### 12. Remediaciones vencidas — `/remediaciones/vencidas` · `ADMIN_BD`

- Panel **global**: hallazgos con el plazo ya cumplido, en todas las organizaciones auditadas.
- Por cada uno: organización, auditoría, control, fecha límite y responsable. La fecha vencida es el dato que hay que hacer saltar a la vista.
- **Sin vencidas:** decirlo explícitamente.

**Flujo:** cada auditoría → su detalle.

### 13. Comparación histórica — `/evaluacion/comparar` · `AUDITOR`

- Auditorías **agrupadas por organización**, para ver cómo evoluciona el índice de riesgo de una a otra.
- Comparación por grupo, con acceso a cada auditoría.
- Dos situaciones sin datos, distintas entre sí: no hay auditorías, o hay una sola y no hay nada que comparar.

**Flujo:** se llega desde «Mis auditorías» · cada auditoría → su detalle.

### 14. Catálogo maestro — `/catalogo` · `ADMIN_BD`

Administración del instrumento: **dominios**, **procesos** y **controles**.

- Las tres entidades listadas, cada una con la opción de crear, y por elemento editar y eliminar.
- Acceso a la **matriz de procesos vs C-I-D** y regreso a «Mis auditorías».

**Flujo:** crear o editar → formulario de la entidad · eliminar pide confirmación y vuelve al catálogo.

### 15. Matriz de procesos vs C-I-D — `/catalogo/matriz` · `ADMIN_BD`

- Reproduce la tabla del **Apéndice II de COBIT 4.1**: cada proceso frente a cada criterio de información.
- Los 25 procesos, agrupados por dominio, cruzados con confidencialidad, integridad y disponibilidad.
- Cada cruce tiene tres valores posibles que hay que distinguir con claridad: **relación primaria**, **relación secundaria** y **sin relación**.
- Debe aclararse que es la relación *declarada* en el catálogo, no lo que el auditor marcó en una evaluación concreta.

**Flujo:** se llega desde el catálogo.

### 16–18. Formularios del catálogo · `ADMIN_BD`

La misma pantalla sirve para crear y para editar, en las tres entidades.

- **Dominio** — `/catalogo/dominios/{nuevo|clave}`: clave, nombre, descripción y orden de presentación.
- **Proceso** — `/catalogo/procesos/{nuevo|numero}`: número, dominio al que pertenece, nombre, referencia normativa, orden y **relación con cada criterio C-I-D**, elegible por separado entre primaria, secundaria o ninguna.
- **Control** — `/catalogo/controles/{nuevo|codigo}`: código, proceso al que pertenece, enunciado, evidencia esperada, pregunta de auditoría y **peso** (alta / media / baja), que es la importancia relativa con que el control pondera el cálculo de riesgo.

**Flujo:** al guardar → catálogo, con confirmación · al fallar la validación se vuelve al formulario con los errores y lo ya tecleado.

### 19. Páginas de error — 403, 404, 500

Acceso denegado, página no encontrada y error del servidor. Con salida de regreso al sitio.

---

## Requisitos funcionales que el diseño debe soportar

1. **Dos contextos de uso distintos.** El sitio público persuade a un visitante; la aplicación de auditoría sostiene sesiones largas de trabajo sobre listados densos. La navegación global es lo que las une.
2. **Estados vacíos en casi todo listado.** Están señalados página por página: son parte del producto, no un caso límite.
3. **Estados de cumplimiento consistentes.** «Cumple», «brecha» y «requiere atención» aparecen en el estado de auditorías, en las remediaciones, en la matriz de riesgo y en el cumplimiento por dominio: deben leerse igual en todos lados. No pueden distinguirse solo por color.
4. **Datos tabulares extensos** — 75 controles, cumplimiento por dominio, matriz de 25 procesos × 3 criterios — tienen que ser legibles y recorribles sin romper la página en pantallas angostas.
5. **Formularios largos con validación.** La ficha de control tiene ~15 campos y reglas condicionales (la evidencia es obligatoria solo si la respuesta es «Sí»). Al fallar, se vuelve al formulario conservando lo tecleado y mostrando los errores.
6. **Bilingüe.** Los rótulos cambian de longitud entre español e inglés — hasta cerca de un 30 % —, así que nada puede depender de que un texto mida lo que mide hoy.
7. **Accesibilidad**: foco visible, contraste suficiente y estados que no se transmitan únicamente por color.
