# Rivendel — Portada pública: decisiones de diseño

Especificación de lo construido en `Rivendel Portada.dc.html`. Deriva de dos
documentos de entrada: la especificación funcional del producto (19 páginas) y la
especificación normativa de color y tipografía del sistema visual de Rivendel.
Este archivo registra únicamente lo decidido para la **portada pública**.

---

## 1. Alcance

| Decisión | Valor |
|---|---|
| Página construida | Portada pública — ruta `/` |
| Idioma resuelto | Español; selector ES/EN presente y funcional como control, sin traducción de contenido |
| Tema resuelto | Ambos temas del sistema, alternados por sección (ver §3) |
| Intensidad del neumorfismo | Equilibrado (prop `relieve`: sutil / equilibrado / pleno) |
| Regla de conflicto | Superficies neumórficas sí; color y tipografía del sistema visual intactos |
| Variantes | Una sola página resuelta, sin opciones paralelas |

Las 18 páginas restantes de la especificación funcional no están construidas.

---

## 2. Nomenclatura

- El sitio y el producto se llaman **Rivendel**. Las referencias a «Becajo» del
  documento funcional quedaron reemplazadas en marca, correo, pie y razón social.
- La aplicación de auditoría se llama **Diagnóstico de Salud** en el menú
  «Herramientas» y en el pie.
- El **Instrumento de evaluación de BD** conserva su nombre como documento
  público de consulta.
- El logotipo es la palabra «Rivendel» en Cinzel 600, caja alta,
  `letter-spacing: 0.09em`, sin subtítulo (removido por revisión).

---

## 3. Ritmo de temas: alternancia por sección

El sistema visual define dos temas. En lugar de elegir uno, la portada los
alterna sección a sección para dar cadencia a una página larga de venta. La
proporción 70 / 20 / 10 se conserva dentro de cada sección.

| Sección | Fondo | Tema |
|---|---|---|
| Barra de navegación (fija) | `#0C1611` a 92 % + desenfoque | Imladris de noche |
| Hero (`#inicio`) | `#0C1611` | Imladris de noche |
| Retos (`#retos`) | `#F5F2E9` | Pergamino élfico |
| Servicios (`#servicios`) | `#0C1611`, fichas en `#FBF9F3` | Mixto (ver §3.1) |
| Stack (`#stack`) | `#F5F2E9` | Pergamino élfico |
| Resultados (`#resultados`) | `#0C1611` | Imladris de noche |
| Testimonios (`#testimonios`) | `#F5F2E9` | Pergamino élfico |
| Equipo (`#equipo`) | `#0C1611` | Imladris de noche |
| Contacto (`#contacto`) | `#F5F2E9` | Pergamino élfico |
| Pie de página | `#0C1611` | Imladris de noche |

Ningún color fuera de los tokens declarados en el sistema visual.

### 3.1 Excepción declarada — fichas de servicios

Las seis fichas de Servicios son **claras** (`#FBF9F3`) sobre la sección oscura,
por decisión de revisión. Consecuencias asumidas: el relieve de esas fichas se
resuelve con sombra proyectada oscura y realce claro tenue
(`11px 11px 24px rgba(0,0,0,.55)` / `-7px -7px 18px rgba(255,253,246,.07)`), y su
tipografía usa los tokens del tema claro (`#1A2B23` / `#4A5D53`). El ícono de
cada ficha usa `#E8F0EA` de fondo con `--rv-gold-text` (`#7A5D00`).

---

## 4. Neumorfismo: cómo se deriva de los tokens

El sistema visual prohíbe gradientes y sombras de color. El neumorfismo se
construye entonces con **pares de sombras neutras** derivadas del lienzo de cada
tema: una sombra en el negro/verde del fondo y un realce en el blanco hueso.
No se introduce ningún color nuevo.

| Registro | Sombra | Realce |
|---|---|---|
| Tema claro | `rgba(26,43,35, var(--nm-o))` — derivado de `--rv-text` | `rgba(255,253,246, var(--nm-h))` |
| Tema oscuro | `rgba(0,0,0, var(--nd-o))` | `rgba(255,255,255, var(--nd-h))` |

Tres niveles de relieve, expuestos como prop `relieve` en el componente:

| Nivel | `--nm-o` | `--nm-h` | `--nm-b` | `--nd-o` | `--nd-h` |
|---|---|---|---|---|---|
| sutil | 0,08 | 0,85 | 0,10 | 0,38 | 0,020 |
| equilibrado (por defecto) | 0,13 | 0,95 | 0,16 | 0,55 | 0,035 |
| pleno | 0,19 | 1,00 | 0,24 | 0,70 | 0,050 |

Convenciones de uso:

- **Extruido** (tarjetas, botones, pills de stack): par de sombras hacia fuera,
  desplazamiento 6–16 px según jerarquía.
- **Hundido** (`inset`): campos de formulario, pistas de barras de progreso,
  selector de idioma, bloque de llamada al instrumento, franja de mensajes de
  sistema. El hundido señala «aquí se recibe algo», no decoración.
- **Radios**: 8–12 px en controles, 14–22 px en tarjetas y paneles, según
  `--rv-radio` / `--rv-radio-lg` del sistema.
- Hover de tarjeta = mismo par de sombras, mayor desplazamiento. Estado activo de
  botón = inversión a `inset`.

---

## 5. Tipografía

Se respetan las cuatro voces excluyentes del sistema.

| Rol | Uso en la portada |
|---|---|
| **Cinzel** 600 | Solo el logotipo y la marca del pie. Caja alta, `0.09em`. |
| **EB Garamond** 400/500 | H1 (56 px), H2 de sección (36 px), párrafos explicativos, citas de testimonios, cuerpo de tarjetas. Nunca por debajo de 16 px. |
| **Inter** 400/500/600 | Navegación, botones, etiquetas, cifras, encabezados de tarjeta, formulario. |
| **JetBrains Mono** 400/500 | IDs de auditoría (`AUD-0042`), badges normativos, numeración de retos, nombres de motores, datos de contacto, etiquetas de placeholder. |

Reglas aplicadas:

- Toda cifra lleva `font-variant-numeric: tabular-nums`.
- Formato numérico español con coma decimal, un decimal y espacio antes del
  signo: `68,0 %`, `100,0 %`, `41,3 %`, `3,4`, `2,6 / 5,0`.
- Escalones tipográficos: 56 / 36 / 26 / 21 / 17 / 15,5 / 13,5 / 12,5 / 11,5 px.
  Ninguna etiqueta por debajo de 11,5 px; ningún contenido por debajo de 12,5 px.
- En pantallas ≤ 760 px, H1 baja a 40 px y los H2 a 29 px.
- Ninguna tipografía propietaria; carga por Google Fonts (aceptable en prototipo).

---

## 6. Color con significado

- **Verde** solo como cumplimiento y como color institucional estructural
  (barra, acciones primarias, barras de progreso).
- **Oro** solo como referencia normativa: badge `ISO/IEC 27002 · 27007 · COBIT 4.1`,
  ID `AUD-0042`, numeración de retos, badge «Caso de éxito · 27002», marco del pie.
  En el tema claro, todo oro usado como texto es `#7A5D00`, nunca `#9A7000`.
- **Escala de estado** con color + ícono + etiqueta, siempre los tres:
  `● Cumple 41`, `▲ No cumple 14`, `– No aplica 6`. «No aplica» en gris neutro.
- **Pills de estado con contorno y texto teñido**, sin relleno sólido, según la
  regla del sistema para tablas densas.
- Las estrellas de calificación **no** usan oro: el oro está reservado a lo
  normativo. Se dibujan en verde institucional (`#1F4D3A`) y van acompañadas del
  valor numérico (`5,0 de 5,0`).
- Los tres puntos del histórico del caso de éxito usan la escala de estado
  (`3,8` rojo, `3,0` ámbar, `2,2` verde) con ícono además del color.

---

## 7. Estructura y secciones

Orden fijado por la especificación funcional, con anclas reales usadas por la
navegación: `#inicio`, `#retos`, `#servicios`, `#stack`, `#resultados`,
`#testimonios`, `#equipo`, `#contacto`, más `#instrumento` y `#ingresar`.

- **Navegación global**: marca, enlaces a secciones, menú «Herramientas» con
  título y descripción por entrada, selector de idioma y acceso a «Ingresar».
  El botón «Hablemos» fue eliminado por revisión; la conversión vive en el hero y
  en la sección de contacto.
- **Hero**: propuesta de valor, dos acciones (primaria llena, secundaria
  extruida) y una tarjeta de índice de riesgo que muestra el producto real —
  índice, estado, tres barras de avance y el desglose sí / no / no aplica.
- **Retos**: cuatro tarjetas numeradas en mono.
- **Servicios**: seis fichas más un bloque hundido que anuncia que el instrumento
  es público y sin registro.
- **Stack**: carrusel continuo de derecha a izquierda, 30 s lineales, pista
  duplicada para bucle sin salto, desvanecido en los bordes con `mask-image`.
  Cada motor lleva un placeholder de logotipo de 52×52 y su clasificación
  (relacional / documental).
- **Resultados**: cuatro indicadores en cifras tabulares y un caso de éxito con
  histórico de tres auditorías.
- **Testimonios**: ver §8.
- **Equipo**: cuatro integrantes con retrato 1:1 en placeholder; nombre y cargo
  centrados.
- **Contacto**: formulario de cuatro campos con relieve hundido; al enviar se
  muestra el aviso de resultado en la franja de mensajes de sistema.
- **Pie**: navegación, herramientas, marco normativo y datos de la empresa.

### 7.1 Alineación

Por revisión, **todos los encabezados de sección** (eyebrow en mayúsculas + H2) y
sus subtítulos están centrados, igual que el bloque de datos de contacto y los
nombres del equipo. El contenido de las tarjetas se mantiene alineado a la
izquierda, salvo las fichas de testimonios, que son centradas por su formato.

### 7.2 Campos de texto largo

Las cajas de texto (`textarea`) tienen **tamaño fijo**: `resize: none` en
`rivendel.css`, para todo el producto y sin excepciones. El tirador de la
esquina permite estirar el campo hasta romper la columna donde vive —el
formulario del pie comparte fila con los datos de la empresa, y las tarjetas de
control llevan dos campos uno sobre otro—, y esa maqueta rota no la ve quien la
provoca. El alto lo declara el atributo `rows` de cada vista; el texto que
excede se recorre con la barra del propio campo.

### 7.3 Mensajes de sistema

Una franja única inmediatamente bajo la navegación, con relieve hundido y borde
izquierdo de 3 px en el color del estado. Reúne los avisos de éxito y de error
que la especificación funcional exige tras cada acción que guarda. Se usa hoy
para el envío del formulario y para advertir que la conmutación a inglés está
prevista pero no resuelta en la maqueta.

---

## 8. Testimonios: ficha vertical con laterales atenuados

Decisión tomada en revisión, reemplazando un panel horizontal único.

- Tres fichas visibles en una rejilla de `0.82fr / 1.25fr / 0.82fr`.
- Formato **vertical**: retrato circular arriba, nombre, cargo, calificación,
  cita y referencia de auditoría, todo centrado.
- La ficha **central** es la enfatizada: más ancha, superficie `#FBF9F3`, relieve
  mayor, cita a 21 px, estrellas y puntaje visibles.
- Las **laterales** están atenuadas (`opacity: .48`, `saturate(.55)`,
  `scale(.94)`) y muestran una cita corta. Son botones: al pulsarlas se navega a
  izquierda o derecha. Hover sube la opacidad a .72.
- Controles adicionales: flechas anterior/siguiente y tres puntos de salto
  directo, con el punto activo en verde extruido y los inactivos hundidos.
- **Animación de movimiento**: al cambiar de testimonio, la ficha central entra
  desde el lado correspondiente al sentido de la navegación
  (`translateX ±46 px`, `scale .95 → 1`, opacidad 0 → 1) en 440 ms con
  `cubic-bezier(.22,.7,.2,1)`; las laterales hacen un fundido desde
  `scale(.88)`. Para reiniciar la animación en cada cambio se alternan dos
  keyframes idénticos (`rv-nx1`/`rv-nx2`, `rv-pv1`/`rv-pv2`, `rv-lat1`/`rv-lat2`).

---

## 9. Movimiento

- Carrusel del stack: 30 s, lineal, infinito, `translate3d` para composición en GPU.
- Testimonios: 440 ms, `cubic-bezier(.22,.7,.2,1)`.
- Hover de tarjetas y botones: cambio de sombra, sin desplazamiento de layout.
- `prefers-reduced-motion: reduce` anula todas las animaciones y transiciones y
  desactiva el desplazamiento suave.

---

## 10. Accesibilidad y responsividad

- Foco visible en todo control: `outline: 2px solid #D9B65F`, `offset: 3px`.
  Se eligió el oro porque es el único acento con contraste suficiente en ambos
  temas.
- Ningún estado se comunica solo por color: siempre color + ícono + etiqueta.
- Contraste: texto normal ≥ 4,5:1 y componentes ≥ 3:1 en ambos temas, usando los
  pares declarados en el sistema visual.
- `aria-live="polite"` en el carrusel de testimonios; `role="status"` en la franja
  de mensajes; `aria-expanded` en el menú «Herramientas»; `aria-pressed` en el
  selector de idioma; `aria-label` en flechas y puntos.
- `box-sizing: border-box` global.
- Puntos de quiebre:
  - ≤ 900 px — el hero pasa a una columna.
  - ≤ 860 px — los testimonios pasan a una columna y las fichas laterales se
    ocultan; permanecen flechas y puntos.
  - ≤ 760 px — la navegación envuelve y los enlaces pasan a una fila propia;
    H1 y H2 bajan de tamaño.
  - Las rejillas de retos, servicios, resultados y equipo usan
    `repeat(auto-fit, minmax(...))`, por lo que colapsan solas.
- Objetivo declarado del sistema: legible y operable hasta 360 px de ancho.

---

## 11. Placeholders pendientes

Todo lo que hoy es placeholder rayado con etiqueta en mono, a la espera de
material real:

- Logotipos de los seis motores del stack (52×52).
- Retratos del equipo (1:1, cuatro).
- Retratos de los testimonios (circular, tres).
- Nombres, cargos y citas de los testimonios y del equipo.
- Datos de contacto: teléfono y cédula jurídica.
- Cifras de Resultados y del caso de éxito.

---

## 12. Pendientes respecto de los criterios de aceptación del sistema visual

- **Conmutación de tema sin recarga**: no implementada. La portada fija el tema
  por sección en lugar de exponer un conmutador global; el atributo `data-tema`
  no se usa. Es la desviación principal frente al criterio de aceptación.
- **Bilingüe**: el selector existe y es operable, pero el contenido está resuelto
  solo en español; al elegir EN se informa en la franja de mensajes. Los rótulos
  se dispusieron con holgura para admitir el ~30 % de crecimiento del inglés.
- **Auto-hospedaje de fuentes** con subconjunto `latin-ext`, `font-display: swap`
  y `preload`: pendiente para producción; el prototipo usa Google Fonts.
