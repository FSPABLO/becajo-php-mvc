# Prompt para Claude Code — Instrumento de consultoría de bases de datos (ISO/IEC 27000–27011)

> Cópielo completo y péguelo como primer mensaje en Claude Code, dentro del repositorio donde quiere que viva el instrumento.

---

## Contexto y objetivo

Construye dentro de este repositorio un **instrumento de consultoría para la administración de bases de datos**, basado en la familia de normas ISO/IEC 27000 a 27011\. Es una herramienta interna de auditoría: un consultor la abre, entrevista al DBA, marca lo que pudo verificar y obtiene un tablero de cumplimiento.

**Antes de escribir una sola línea de código, adáptate a este proyecto.** No traigas una paleta propia ni un sistema de diseño nuevo: el instrumento debe parecer parte de este producto, no un widget injertado.

## Fase 1 — Descubrimiento (obligatoria, antes de codificar)

Explora el repositorio y **repórtame por escrito** lo que encontraste antes de continuar:

1. **Framework y estructura.** ¿Next.js, Vite, Astro, Rails, Django, HTML estático? ¿Dónde viven las páginas o rutas? ¿Hay router? Sigue la convención existente; no introduzcas una nueva.  
2. **Paleta y tokens.** Busca en este orden y usa el primero que exista:  
   - `tailwind.config.{js,ts}` → `theme.extend.colors`  
   - variables CSS en `globals.css`, `app.css`, `:root`, `@theme`, o archivos `tokens.*`  
   - un design system propio (`components/ui/`, `packages/ui/`, Storybook)  
   - variables SCSS/LESS, o `styled-components` theme  
   - si no hay nada de lo anterior: extrae los colores dominantes del CSS existente y del logo, y **pregúntame antes de asumir**.  
3. **Tipografía.** Qué familias ya carga el proyecto y con qué pesos. Reúsalas.  
4. **Componentes reutilizables.** Si ya existen `Button`, `Card`, `Select`, `Tabs`, `Table`, `Input`, **úsalos**. Solo crea componentes nuevos para lo que no exista.  
5. **Convenciones.** TypeScript o JavaScript, gestor de estado, alias de importación, linter, formato, idioma de la interfaz (si el resto del producto está en inglés, tradúcelo).

**Regla dura sobre color:** ningún hexadecimal literal en el código nuevo. Todo color sale de los tokens del proyecto. Si necesitas un matiz que no existe (por ejemplo, tres estados semánticos), decláralo **una sola vez** junto a los tokens existentes, derivándolo de la paleta del proyecto, y explícame por qué lo agregaste.

Si el proyecto tiene tema claro y oscuro, el instrumento debe funcionar en ambos.

## Fase 2 — Contenido

Crea un archivo de datos separado del componente (por ejemplo `data/instrumento-bd.ts`), tipado, con esta estructura:

- **25 procesos** típicos de administración de bases de datos, agrupados en **7 dominios**.  
- **3 controles por proceso \= 75 controles**. Cada control lleva: `id` (`C-001`…`C-075`), número de proceso, enunciado del control en positivo (*lo que la organización debe tener*), referencia al control ISO/IEC 27002:2022 correspondiente, y la evidencia que el consultor debe solicitar.  
- **1 pregunta de auditoría por control**, con el mismo `id`, redactada para hacerse en voz alta durante una entrevista (ISO/IEC 27007:2020).  
- **Escala de madurez 0 a 5**: 0 inexistente, 1 inicial, 2 repetible, 3 documentado, 4 gestionado y medido, 5 optimizado.  
- **Marco normativo**: qué aporta cada norma de 27000 a 27011 al instrumento.  
- **Referencias** con enlace.

Los 25 procesos y sus anclas normativas, agrupados por dominio:

| Dominio | \# | Proceso | Ancla ISO/IEC |
| :---- | :---- | :---- | :---- |
| Gobierno y riesgo | 1 | Gobierno y control interno del SGBD | 27001 cl. 5; A.5.1, A.5.2, A.5.3 |
|  | 2 | Gestión de riesgos inherentes al SGBD | 27005; 27001 cl. 6.1 |
|  | 25 | Competencia, documentación e intercambio de información | 27001 cl. 7.2; A.5.37, A.6.3, A.5.14; 27010 |
| Configuración y cambios | 3 | Instalación y aseguramiento (hardening) del SGBD | A.8.9, A.8.19, A.5.21 |
|  | 4 | Gestión de cambios y de la configuración | A.8.32, A.8.31 |
|  | 5 | Gestión de vulnerabilidades técnicas y parches | A.8.8; 27008 |
|  | 6 | Creación de bases de datos y diseño físico | A.8.27, A.8.9 |
| Memoria y almacenamiento | 7 | Administración de memoria y procesos de soporte | A.8.6, A.8.16 |
|  | 8 | Gestión de estructuras de almacenamiento | A.8.6, A.8.13 |
|  | 9 | Administración del registro de transacciones | A.8.13, A.8.15 |
|  | 10 | Gestión de capacidad | A.8.6 |
| Accesos y privilegios | 11 | Gestión de cuentas de usuario | A.5.16, A.5.18 |
|  | 12 | Gestión de privilegios y roles | A.8.2, A.5.15 |
|  | 13 | Cuentas administrativas y accesos privilegiados | A.8.2, A.5.17, A.8.15 |
|  | 14 | Autenticación y política de contraseñas | A.5.17, A.8.5, A.8.28 |
| Protección del dato | 15 | Criptografía y gestión de llaves | A.8.24, A.8.20 |
|  | 16 | Enmascaramiento y ambientes no productivos | A.8.11, A.8.33, A.8.31 |
|  | 23 | Retención, archivado y eliminación segura | A.5.33, A.5.34, A.8.10, A.7.14 |
| Continuidad | 17 | Respaldo de bases de datos | A.8.13, A.8.24 |
|  | 18 | Recuperación y continuidad del servicio | A.8.13, A.5.29, A.5.30 |
|  | 19 | Alta disponibilidad y replicación | A.8.14, A.8.16 |
| Vigilancia y terceros | 20 | Monitoreo y gestión del desempeño | A.8.16, A.8.6; 27004 |
|  | 21 | Auditoría de la base de datos y registros | A.8.15, A.8.17; 27007 |
|  | 22 | Gestión de incidentes de seguridad en la base de datos | A.5.24 a A.5.28 |
|  | 24 | Proveedores y bases de datos gestionadas o en la nube | A.5.19 a A.5.23; 27011 |

Redacta los 75 controles y las 75 preguntas tú mismo a partir de esta tabla. Criterios de redacción:

- El control describe **un estado verificable**, no una intención. «Existe una política de respaldo que define alcance, frecuencia, retención, RPO y RTO por base de datos», no «se debe respaldar la información».  
- Un control, un hecho. Si el enunciado necesita una «y» que une dos cosas auditables por separado, son dos controles.  
- La pregunta nunca se responde con sí o no a secas: pide el cómo, el quién o el cuándo. «¿Cuándo fue la última prueba real de restauración y cuál fue el tiempo de recuperación obtenido?», no «¿Prueban los respaldos?».  
- Español formal, tercera persona, sin jerga de consultoría vacía.

## Fase 3 — Interfaz

Una sola vista con **cinco pestañas**: Instrumento · Cuestionario · Tablero · Marco ISO · Referencias.

**Pestañas principales.** Estilo pastilla con ícono y etiqueta; la activa se rellena con el color de énfasis del proyecto. Barra adherida al borde superior. Si el proyecto ya tiene un componente de pestañas, úsalo y solo ajusta el ícono.

**Encabezado.** Una franja con el título del instrumento y los campos de identificación de la consultoría: organización, base de datos e instancia, motor y versión, consultor responsable, fecha. Si el producto tiene un patrón de encabezado de página, respétalo.

**Pestaña Instrumento.**

- Fila de **7 pastillas de dominio** con contador de avance (`7/12`); la que llega a su total se marca como completa.  
- Buscador que atraviesa los siete dominios (cuando hay texto de búsqueda se ignora el dominio activo y se avisa en pantalla) y filtro por estado: existe, no existe, no aplica, sin evaluar.  
- Dentro del dominio, los controles van agrupados bajo el encabezado de su proceso.  
- Cada control es una tarjeta con: identificador, referencia ISO, enunciado, evidencia solicitada y, en una fila de captura: tres casillas de riesgo (**integridad, confidencialidad, disponibilidad**), selector de madurez 0 a 5, selector de criterio (documentado / repetible / evidencia), y un control segmentado de tres opciones excluyentes (**sí / no / no aplica**). Debajo, dos campos de texto: hallazgo y recomendación.  
- El borde izquierdo de la tarjeta se tiñe según el estado, para que al desplazarse se lea el avance sin leer texto.  
- Navegación **anterior / siguiente dominio** al pie, con el nombre del dominio destino.

**Pestaña Cuestionario.** Mismos dominios y misma agrupación, pero muestra la pregunta de auditoría con: respuesta (sí / parcial / no / no aplica), persona entrevistada, evidencia aportada y notas. Filtro por respuesta, incluida «sin responder».

**Pestaña Tablero.** Todo calculado en vivo:

- Tarjetas de resumen: controles evaluados, porcentaje de cumplimiento, madurez promedio, brechas abiertas, pendientes de evaluar.  
- Barra apilada de proporción entre los cuatro estados.  
- Tabla de los 25 procesos con: controles, sí, no, no aplica, cumplimiento (barra \+ porcentaje), madurez promedio, nivel alcanzado y cobertura de integridad, confidencialidad y disponibilidad. Fila de total general.

**Pestañas Marco ISO y Referencias.** Contenido estático: las doce normas con su aporte al instrumento, la escala de madurez explicada, y las fuentes con enlace.

**Acciones.** Exportar a CSV los 75 controles con sus respuestas (delimitador `;` y BOM UTF-8, para que Excel en español lo abra bien), cargar datos de ejemplo para demostración, limpiar todo con confirmación previa, e imprimir el informe con una hoja de estilos que oculte controles de navegación y filtros.

## Fase 4 — Reglas de cálculo

Impleméntalas exactamente así; son el corazón del instrumento y son fáciles de equivocar.

- **Cumplimiento \= sí / (sí \+ no).** Los controles marcados «no aplica» **salen del denominador**, igual que una exclusión justificada en la Declaración de Aplicabilidad (ISO/IEC 27001, cl. 6.1.3). Los controles sin evaluar tampoco cuentan en ninguno de los dos lados.  
- Si el denominador es cero, muestra un guion, nunca `NaN`, `0%` ni `Infinity`.  
- **Madurez promedio** solo sobre los controles con madurez registrada; un control sin calificar no vale cero.  
- «No aplica» **exige justificación escrita** en el campo de hallazgo: si el consultor lo marca y deja el campo vacío, señálalo visiblemente. Sin esa fricción, «no aplica» se convierte en la salida fácil que infla el resultado.  
- Redondea todo número que llegue a pantalla; nada de `0.30000000000000004`.  
- Estado y cálculos viven en un solo lugar: la pestaña Instrumento y la pestaña Cuestionario operan sobre **el mismo objeto por identificador**. Responder en una debe moverse en la otra y en el tablero.

## Fase 5 — Calidad

- Persistencia local del avance, si el proyecto ya usa algún mecanismo de almacenamiento; si no, deja al menos exportar e importar el estado como JSON. **Nunca pierdas dos horas de entrevista por un refresco de página.**  
- Responsivo hasta 375 px: en móvil la fila de captura se apila.  
- Accesible: `role="tab"` y `aria-selected` en las pestañas, navegación por teclado con foco visible, etiquetas asociadas a cada control de formulario, contraste mínimo AA sobre el fondo del proyecto.  
- Sin dependencias nuevas salvo que este repositorio ya las tenga.  
- Tipa el modelo de datos y evita `any`.

## Fase 6 — Verificación

Antes de darlo por terminado:

1. Levanta el proyecto y confirma que la vista carga sin errores en consola.  
2. Comprueba con datos de ejemplo que cumplimiento, madurez y cobertura de riesgo cuadran a mano en al menos dos procesos.  
3. Verifica el caso límite: un proceso con sus tres controles en «no aplica» debe mostrar guion, no cero por ciento.  
4. Exporta el CSV y ábrelo para confirmar que las tildes y la eñe se ven bien.  
5. Toma una captura y compárala con otra pantalla del producto: si el instrumento se distingue como pieza ajena, corrige el uso de tokens y vuelve a comparar.  
6. Ejecuta el linter y el formateador del proyecto.

Entrégame al final un resumen de qué archivos creaste, qué tokens de color reutilizaste y qué decisiones tomaste donde el repositorio no te daba una convención clara.  
