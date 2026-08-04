# EIF402 · Administración de Bases de Datos

## Organización del equipo de trabajo

**Proyecto Integrador (parte 1) — Evaluación del riesgo en la administración de bases de datos basada en ISO/IEC 27002**

Este documento propone la división del trabajo entre 4 integrantes para desarrollar el proyecto solicitado, tomando como base la idea planteada por el equipo: la evaluación de riesgo se integrará como un servicio dentro de la página web existente (sección "Servicios"), con inicio de sesión, donde el auditor puede guardar varias auditorías y, dentro de cada una, agregar controles llenando una plantilla en pantalla (un formulario web, no un archivo que se sube). Al finalizar, el sistema calcula automáticamente los 7 resultados que pide el documento.

El criterio de diseño principal fue **evitar dependencias bloqueantes**: cada persona puede avanzar desde el primer día porque el equipo define, en una fase corta de arranque, los "contratos" (campos del formulario, estructura de datos, endpoints) que todos usarán mientras cada quien construye su parte con datos de ejemplo.

---

## Idea de arquitectura general (flujo confirmado)

- La sección **Servicios** del sitio incorpora una tarjeta/enlace "Evaluación de riesgo ISO/IEC 27002" que lleva a una pantalla de inicio de sesión del auditor.
- Ya autenticado, el auditor ve **"Mis auditorías"**: puede crear una nueva (registrando Organización, Área evaluada, Auditor, Administrador de BD y Fecha) o continuar una que dejó a medias.
- Dentro de una auditoría, el auditor agrega un control eligiéndolo del **catálogo ISO/IEC 27002 ya precargado** en el sistema, y lo califica llenando la plantilla en pantalla: responde cada pregunta del control (Sí / No / No aplica), asigna el nivel de madurez (0–5) y puede anotar observaciones y evidencias. Este formulario es programado por el equipo (no es un archivo que el usuario sube).
- El catálogo maestro de controles (código, nombre, dominio, objetivo, peso, relación con C/I/D y sus preguntas) se administra también en pantalla, dentro del sitio, y queda precargado para que el auditor solo tenga que seleccionarlo y responder.
- Al terminar la auditoría, el sistema calcula automáticamente los 7 puntos del documento: (1) nivel de cumplimiento por control, (2) nivel de madurez por control, (3) nivel de cumplimiento por dominio, (4) exposición al riesgo de Confidencialidad, (5) de Integridad, (6) de Disponibilidad, y (7) el índice general de exposición al riesgo — además de los reportes, gráficos y mapas de calor.

---

## Fase 0 — Arranque conjunto (2 a 3 días, todo el equipo)

Antes de separarse, el equipo completo debe acordar en conjunto tres "contratos" que permitirán que nadie dependa de que otro termine su parte primero. Esto se hace en 1–2 reuniones cortas; no es una fase de desarrollo.

| Contrato | En qué consiste | Lo usan |
|----------|-----------------|---------|
| **Campos de la plantilla en pantalla** | Qué campos tendrá el formulario web con el que el auditor califica un control: pregunta(s), respuesta (Sí/No/No aplica), nivel de madurez (0–5), observaciones y evidencia. Es un formulario programado, no un archivo que se sube. | Personas 1, 3 y 4 |
| **Modelo de datos preliminar (borrador)** | Entidades principales: Organización, Auditoría, Usuario, Control, Pregunta, Respuesta, Evidencia, Dominio. No requiere el contenido final de los controles. | Personas 2, 3 y 4 |
| **Contrato de API / datos de ejemplo (mock)** | Nombres de endpoints y forma del JSON de entrada/salida (aunque aún no existan), más datos ficticios para que el frontend no espere al backend real. | Personas 3 y 4 |

Con esto, desde el día 1: la **Persona 1** investiga y redacta sin depender de nadie; la **Persona 2** modela la base de datos con la estructura genérica acordada; la **Persona 3** monta la arquitectura, autenticación y el manejo de auditorías/controles; la **Persona 4** construye las pantallas (login, auditorías, plantilla) contra los datos de ejemplo. Los ajustes finos se sincronizan en checkpoints semanales cortos (30 min).

---

## División de roles (4 personas)

### PERSONA 1 · Investigación, Controles ISO 27002 y Metodología de Riesgo

*Rol tipo "Analista de seguridad / auditor" — trabajo 100% independiente, no requiere que nadie más avance primero.*

**Qué hace**

- Investiga y redacta el marco teórico: familia ISO/IEC 27000, ISO/IEC 27002, gestión del riesgo, CID, controles internos, auditoría de TI.
- Selecciona y justifica los controles de ISO/IEC 27002 aplicables a administración de bases de datos (dominio, objetivo, descripción, peso/importancia, relación con Confidencialidad, Integridad y Disponibilidad).
- Diseña el instrumento de evaluación: transforma cada control en una o varias preguntas (Sí / No / No aplica) y define qué evidencias se esperan.
- Define el contenido exacto de la plantilla en pantalla: qué campos debe tener el formulario que el auditor llena por cada control (preguntas, respuesta, nivel de madurez, observaciones, evidencia), siguiendo el contrato acordado en la Fase 0, y entrega el catálogo completo de controles/preguntas para precargarlo en el sistema.
- Diseña y justifica la metodología de nivel de madurez (0–5) a partir de las respuestas del cuestionario, y la metodología de cálculo de exposición al riesgo por C, I y D (las fórmulas exactas, con ejemplos numéricos, para que Backend las implemente sin ambigüedad).
- Redacta el documento de análisis del problema y participa en la presentación/defensa.

**Entregables que produce**

1. Documento de análisis del problema · 2. Marco teórico · 4. Justificación de controles ISO/IEC 27002 · 5. Diseño del instrumento de evaluación (contenido de la plantilla en pantalla) · 6. Metodología de madurez · 7. Metodología de exposición al riesgo · catálogo de controles y preguntas para precargar en el sistema.

> No depende de nadie para empezar. Su entrega de la Semana 1–2 (catálogo de controles y preguntas) alimenta a Persona 2 y 3, pero como ellos ya trabajan con la estructura genérica desde el día 1, no se detienen esperándolo.

---

### PERSONA 2 · Modelo de Datos y Base de Datos

*Rol tipo "Arquitecto/a de datos" — construye sobre el modelo genérico acordado en la Fase 0.*

**Qué hace**

- Diseña el Modelo Entidad-Relación completo: Organización, Área evaluada, Auditoría (con estado "en progreso" / "finalizada"), Usuario/Rol, Dominio, Control, Pregunta, Respuesta, Evidencia/Comentario, Nivel de madurez, Resultado de riesgo (C/I/D).
- Convierte el ERD en el Modelo Relacional normalizado y redacta el diccionario de datos.
- Escribe el script SQL de creación de la base de datos (tablas, llaves, restricciones, índices) y datos semilla (seed data) para pruebas.
- Diseña las vistas o consultas necesarias para los indicadores (cumplimiento por dominio, controles con menor madurez, mayor exposición al riesgo, etc.), que Backend consumirá.
- Colabora con Persona 1 para que el catálogo de controles y preguntas quede modelado como datos configurables (tablas Control/Pregunta), no "quemado" en el código, para poder ampliarlo desde pantalla más adelante.
- Apoya a Persona 3 con la definición de tipos de datos y validaciones a nivel de base de datos.

**Entregables que produce**

8. Modelo Entidad-Relación · 9. Modelo Relacional · 10. Diccionario de datos · 11. Script SQL de creación de la base de datos · vistas/consultas de indicadores.

> Empieza de inmediato con la estructura genérica de la Fase 0 (no necesita el catálogo final de controles, solo la forma en que se organizan). Ajusta detalles cuando Persona 1 entregue el catálogo real, sin haber estado bloqueado mientras tanto.

---

### PERSONA 3 · Backend, Lógica de Negocio y Motor de Cálculo

*Rol tipo "Desarrollador/a backend" — trabaja contra el contrato de API y datos de ejemplo.*

**Qué hace**

- Define la arquitectura de la aplicación (framework, capas, estructura de carpetas) y monta el proyecto base.
- Implementa login y manejo de sesiones del auditor (con sus roles).
- Construye el CRUD de auditorías: crear una auditoría nueva (organización, área, auditor, DBA, fecha), listar "mis auditorías", guardarla parcialmente y continuarla después.
- Construye el CRUD del catálogo maestro de controles (código, nombre, dominio, objetivo, peso, relación C/I/D y sus preguntas) para que quede administrado desde el sitio, no en el código.
- Implementa el endpoint para "agregar control a una auditoría": el auditor elige un control del catálogo y guarda las respuestas de la plantilla (Sí/No/No aplica, madurez, observaciones, evidencia) que llenó en pantalla.
- Implementa el motor de cálculo de los 7 puntos que pide el documento — cumplimiento por control, madurez por control, cumplimiento por dominio, riesgo de C, de I, de D, e índice general — siguiendo exactamente las fórmulas que entrega Persona 1.
- Expone la API (endpoints) que consumirá el frontend, siguiendo el contrato acordado en la Fase 0.
- Implementa validación de datos de entrada y manejo de auditorías guardadas parcialmente ("guardar y continuar después").

**Entregables que produce**

Componente principal de: 12. Aplicación web funcional (backend + API) · 14. Manual técnico (arquitectura, endpoints, motor de cálculo).

> Empieza de inmediato con el contrato de API y datos ficticios de la Fase 0 (no espera el script SQL final de Persona 2 ni el catálogo real de Persona 1); conecta cada pieza real a medida que va estando disponible.

---

### PERSONA 4 · Frontend, Experiencia de Usuario y Reportes

*Rol tipo "Desarrollador/a frontend" — construye contra datos de ejemplo (mock).*

**Qué hace**

- Integra el nuevo servicio en la sección "Servicios" del sitio (tarjeta/enlace de acceso al módulo de auditoría), respetando el estilo visual ya existente en la página.
- Construye la pantalla de inicio de sesión del auditor y la vista "Mis auditorías" (crear nueva / continuar una guardada).
- Construye el formulario de encabezado de auditoría: Organización, Área evaluada, Auditor, Administrador de BD, Fecha.
- Construye la plantilla en pantalla para agregar un control: el auditor lo selecciona del catálogo y llena el formulario con las preguntas del control (Sí/No/No aplica), nivel de madurez, observaciones y evidencias, con guardado parcial.
- Construye la pantalla de administración del catálogo de controles (alta/edición de controles y preguntas), consumiendo el CRUD de Persona 3.
- Construye los reportes e indicadores: resultados generales, por dominio, por control, controles con menor madurez, controles con mayor riesgo, gráficos, mapas de calor y el reporte ejecutivo (idealmente exportable a PDF).
- Cuida el diseño responsive (adaptable a distintos tamaños de pantalla) y la usabilidad general.

**Entregables que produce**

Componente principal de: 12. Aplicación web funcional (frontend) · 13. Manual de usuario · 15. Presentación del proyecto (apoya el guion visual) · 16. Video demostrativo (grabación de pantalla).

> Empieza de inmediato con el JSON de ejemplo definido en la Fase 0 (incluye login simulado y controles ficticios); cuando el backend real de Persona 3 esté listo, solo cambia la fuente de datos (de mock a API real) sin rediseñar pantallas.

---

## Resumen: qué hace cada quien y qué evita bloqueos

| Persona | Enfoque | Empieza con... | Cubre del criterio de evaluación |
|:---:|---------|----------------|----------------------------------|
| 1 | Investigación, controles ISO 27002, metodología de madurez y riesgo | Nada, trabajo documental propio | Investigación y marco teórico (10%) · Selección de controles (10%) · Modelo de madurez (10%) · Modelo de riesgo (15%) |
| 2 | Modelo de datos y base de datos | Estructura genérica de entidades (Fase 0) | Modelo de datos y base de datos (10%) · apoya Diseño del instrumento (15%) |
| 3 | Backend: login, auditorías, catálogo de controles, motor de cálculo, API | Contrato de API + datos de ejemplo (Fase 0) | Desarrollo de la aplicación web (20%, mitad) · apoya modelos de madurez/riesgo (25%) |
| 4 | Frontend: login, mis auditorías, plantilla en pantalla, reportes, mapas de calor, integración con "Servicios" | JSON de ejemplo (Fase 0) | Desarrollo de la aplicación web (20%, mitad) · Diseño del instrumento de evaluación (15%) |

Documentación técnica, presentación y defensa (10%) y el video demostrativo son responsabilidad compartida de todo el equipo, integrando el aporte de cada rol.

---

## Cronograma sugerido (6 semanas, ajustable a la fecha de entrega real)

| Semana | Persona 1 | Persona 2 | Persona 3 | Persona 4 |
|:---:|-----------|-----------|-----------|-----------|
| 0 (2–3 días) | Define formato de plantilla junto al equipo | Propone entidades genéricas | Propone contrato de API/mock | Revisa estilo actual del sitio |
| 1–2 | Marco teórico + selección y justificación de controles | ERD + modelo relacional + diccionario de datos (versión inicial) | Arquitectura base + login/sesión + esqueleto de API con mocks | Maquetación: sección Servicios + login + formulario de encabezado de auditoría |
| 3 | Diseño del instrumento (preguntas por control) + campos de la plantilla en pantalla | Script SQL + carga de datos semilla + ajustes según catálogo real | CRUD de catálogo de controles + CRUD de auditorías + "agregar control" | Vista "Mis auditorías" + plantilla en pantalla para llenar un control (con mock) |
| 4 | Metodología de madurez y riesgo con fórmulas y ejemplos numéricos | Vistas/consultas para indicadores | Motor de cálculo de los 7 puntos (madurez, dominio, riesgo C/I/D, índice general) | Conecta plantilla y auditorías a API real; empieza reportes y gráficos |
| 5 | Documento de análisis del problema + revisión de consistencia general | Pruebas de integridad y ajustes finales de BD | Endurecimiento, validaciones, guardado parcial de auditorías | Mapas de calor, reporte ejecutivo, diseño responsive |
| 6 | Manual técnico (metodologías) + apoyo en defensa | Manual técnico (modelo de datos) | Manual técnico (backend/API) + pruebas integrales | Manual de usuario + video demostrativo + presentación |

---

## Cómo evitar bloqueos durante el desarrollo

- **Checkpoint semanal de 30 minutos** (no diario): cada quien muestra su avance y se ajustan los contratos si algo cambió (campos de la plantilla, forma del JSON, nombres de campos).
- **Nadie espera un entregable "terminado" de otra persona:** Persona 2 y 3 trabajan con la estructura genérica y datos ficticios desde el día 1; cuando Persona 1 entrega el catálogo real de controles, simplemente se carga a la base de datos con el mismo CRUD que ya existe (no se reconstruye nada).
- **El catálogo de controles y preguntas vive en datos (base de datos), no en código:** así, aunque Persona 1 siga ajustando controles en las semanas 3–4, Persona 3 y 4 no tienen que cambiar su lógica ni sus pantallas — solo se agregan filas nuevas al catálogo.
- **Persona 4 desarrolla con un JSON de ejemplo** que imita la respuesta real de la API (login, auditorías, catálogo de controles); al final solo cambia la URL/fuente de datos.
- **Persona 2 entrega el script SQL con datos semilla ficticios desde la semana 1** para que Persona 3 pueda probar su backend sin esperar el modelo final.
- **Cualquier decisión de metodología (madurez, riesgo) que afecte a Backend se documenta por escrito** (no solo se conversa), para que Persona 3 la implemente sin depender de reuniones adicionales.

---

## Funcionalidades de valor agregado sugeridas (mínimo 3 requeridas)

El proyecto pide incorporar al menos tres funcionalidades adicionales no contempladas explícitamente en el enunciado. Se sugieren las siguientes:

- **Comparación histórica entre auditorías** de una misma organización/área: evolución del nivel de madurez y del riesgo a lo largo del tiempo (gráfico de tendencia).
- **Exportación del reporte ejecutivo a PDF**, listo para entregar al cliente de la auditoría.
- **Alertas automáticas** para controles con exposición al riesgo crítica al finalizar la evaluación.
- **Panel consolidado multi-organización** para la consultora, con indicadores comparativos entre todas las auditorías realizadas.
- **Duplicar una auditoría anterior** como punto de partida para una auditoría de seguimiento, conservando el catálogo de controles pero reiniciando las respuestas.

---

> **Nota:** este documento es una propuesta de organización de trabajo, no reemplaza los entregables formales del proyecto (documento de análisis, marco teórico, modelos de datos, etc.), que deben redactarse y sustentarse técnicamente según lo solicitado en el enunciado.
