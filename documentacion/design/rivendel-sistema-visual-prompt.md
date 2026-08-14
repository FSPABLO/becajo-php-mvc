# Prompt: Sistema visual de Rivendel

> Especificación normativa de color y tipografía para la aplicación de auditoría **Rivendel**.
> Entregar este archivo completo como contexto antes de solicitar cualquier vista, componente u hoja de estilos.

---

## 1. Instrucción principal

Actúa como diseñador de interfaces especializado en aplicaciones de cumplimiento normativo. Vas a construir vistas para **Rivendel**, un instrumento web de auditoría basado en ISO/IEC 27002:2022 que evalúa 25 procesos de administración de bases de datos contra 75 controles.

**Aplica el sistema visual definido en este documento sin desviaciones.** No inventes colores, no sustituyas tipografías, no agregues gradientes, sombras de color ni acentos fuera de la paleta. Si una necesidad de diseño no está cubierta acá, deriva la solución de los tokens existentes y decláralo explícitamente en tu respuesta.

El registro visual es **institucional y sobrio**, no fantástico. El nombre alude a Rivendel (Imladris) como archivo de conocimiento protegido; esa referencia vive en el logotipo y en los nombres de los temas, nunca en ornamentos, texturas ni iconografía de fantasía.

---

## 2. Sistema tipográfico

Cuatro voces, cada una con una función excluyente. Todas bajo SIL Open Font License.

| Rol | Tipografía | Uso permitido | Prohibido |
|---|---|---|---|
| Marca | **Cinzel** 600 | Logotipo, portada, encabezado de informe exportado | Texto corrido, botones, etiquetas |
| Editorial | **EB Garamond** 400/500 | Títulos de sección, texto explicativo, cuerpo de informes | Tablas, cifras, formularios |
| Interfaz y datos | **Inter** 400/500 | Tablas, formularios, botones, etiquetas, cifras | Logotipo |
| Identificadores | **JetBrains Mono** 400 | IDs de control (`A.8.9`), hexadecimales, marcas de tiempo, hashes | Prosa |

### Reglas tipográficas obligatorias

1. **Cinzel es de caja alta.** Aplicar `letter-spacing: 0.09em`. Nunca usar en cadenas de más de tres palabras.
2. **Toda cifra numérica lleva `font-variant-numeric: tabular-nums`.** Sin esto las columnas de porcentaje cambian de ancho fila a fila.
3. **Formato numérico español:** coma decimal, un decimal fijo, espacio antes del signo de porcentaje → `68,0 %`, `100,0 %`, `41,3 %`.
4. **Tamaño mínimo de texto:** 12 px para etiquetas secundarias, 14 px para contenido. EB Garamond nunca por debajo de 16 px (sus trazos finos se degradan).
5. **No usar Times New Roman, Arial, Helvetica ni ninguna tipografía propietaria de Monotype o Linotype** incrustada como webfont. Incrustar Times New Roman requiere licencia comercial de Monotype y constituiría un incumplimiento del control **A.5.32 (derechos de propiedad intelectual)** dentro de una aplicación cuyo propósito es auditar precisamente ese marco.

### Carga de fuentes

- **Prototipo:** `@import` desde Google Fonts es aceptable.
- **Producción:** auto-hospedar `.woff2` con subconjunto `latin-ext` (necesario para tildes y ñ), `font-display: swap` y `<link rel="preload">` para Cinzel e Inter. Reduce la superficie de terceros conforme al control **A.5.19 (seguridad en las relaciones con proveedores)**.

---

## 3. Paleta: distribución 70 / 20 / 10

El reparto es de **superficie ocupada en pantalla**, no de cantidad de tokens.

| Proporción | Tema oscuro | Tema claro |
|---|---|---|
| **70 % dominante** | Verde profundo (fondos y superficies) | Blanco hueso (fondos y superficies) |
| **20 % soporte** | Blanco hueso (texto y contenido) | Verde profundo (navegación, títulos, acciones) |
| **10 % acento** | Oro | Oro |

**El oro se mantiene siempre en 10 %.** Es el elemento escaso del sistema; elevarlo a 20 % lo convierte en ruido y ningún oro saturado alcanza contraste de texto sobre fondo claro.

### Tema oscuro — «Imladris de noche»

| Token | Hex | Función | Contraste vs. lienzo |
|---|---|---|---|
| `--rv-bg` | `#0C1611` | Lienzo de la aplicación | — |
| `--rv-surface` | `#132119` | Tarjetas, paneles | — |
| `--rv-elev` | `#1B2E23` | Superficie elevada, filas alternas | — |
| `--rv-border` | `#2A4436` | Bordes, separadores | 3,0:1 |
| `--rv-primary` | `#3E8E6B` | Acción primaria, barras de progreso | 4,5:1 |
| `--rv-primary-hover` | `#4EA57E` | Estado hover | 5,6:1 |
| `--rv-text` | `#EDE9DC` | Texto principal | 15,4:1 |
| `--rv-text-2` | `#B3AE9E` | Texto secundario, etiquetas | 8,0:1 |
| `--rv-gold` | `#D9B65F` | Acento, referencias normativas | 9,3:1 |
| `--rv-gold-tint` | `#2A2415` | Fondo de badge dorado | — |

### Tema claro — «Pergamino élfico»

| Token | Hex | Función | Contraste vs. lienzo |
|---|---|---|---|
| `--rv-bg` | `#F5F2E9` | Lienzo de la aplicación | — |
| `--rv-surface` | `#FBF9F3` | Tarjetas, paneles | — |
| `--rv-elev` | `#E8F0EA` | Tinte verde, filas destacadas | — |
| `--rv-border` | `#E0DACA` | Bordes, separadores | — |
| `--rv-text` | `#1A2B23` | Texto principal | 14,0:1 |
| `--rv-text-2` | `#4A5D53` | Texto secundario | 7,2:1 |
| `--rv-nav` | `#1F4D3A` | Barra de navegación, títulos | 9,0:1 |
| `--rv-primary` | `#2C6B4E` | Acción primaria, barras de progreso | 5,7:1 |
| `--rv-primary-hover` | `#22563E` | Estado hover | 7,4:1 |
| `--rv-gold` | `#9A7000` | Acento en íconos, bordes, rellenos | 4,0:1 |
| `--rv-gold-text` | `#7A5D00` | Oro **cuando es texto** | 5,3:1 |

> **Advertencia crítica del tema claro:** `#9A7000` cumple el umbral de 3:1 exigido para componentes de interfaz y gráficos, pero **no alcanza 4,5:1 para texto normal**. Todo texto en dorado sobre fondo claro debe usar `--rv-gold-text` (`#7A5D00`).

---

## 4. Escala semántica de estado

Independiente de la paleta de marca. No reutiliza el oro de acento.

| Estado | Tema oscuro | Tema claro | Ícono | Etiqueta |
|---|---|---|---|---|
| Cumple | `#3E8E6B` | `#2C6B4E` | `circle-check` | Cumple |
| Parcial | `#C9B25C` | `#7A5D00` | `alert-circle` | Parcial |
| No cumple | `#E07A72` | `#A32D2D` | `alert-triangle` | No cumple |
| Crítico | `#E07A72` | `#A32D2D` | `alert-octagon` | Crítico |
| No aplica | `#8A897F` | `#6B6A62` | `minus` | No aplica |

**`No aplica` debe verse neutro, nunca verde.** El verde significa cumplimiento; teñir de verde un control excluido inflaría visualmente el resultado y contradiría la lógica de la Declaración de Aplicabilidad, donde esos controles se excluyen del denominador.

---

## 5. Reglas de significado del color

Un color, un significado. Estas reglas no admiten excepción:

1. **Verde = estado de cumplimiento.** No usar verde para navegación decorativa, enlaces genéricos ni realces sin relación con el cumplimiento (salvo el verde estructural del tema claro, que actúa como color institucional de la barra de navegación y los títulos).
2. **Oro = referencia normativa.** IDs de control, badges de norma (`27002`), enlaces a cláusulas, numeración de anexos. Nada más.
3. **La escala de estado no se mezcla con la marca.** Un badge dorado nunca puede significar «riesgo medio».
4. **El color nunca es el único canal.** Todo estado se comunica simultáneamente por color, ícono y etiqueta de texto. Un usuario con daltonismo o una impresión en escala de grises deben leer la misma información.
5. **Los pills de estado usan contorno y texto teñido, no relleno sólido.** Cuatro rellenos saturados compitiendo en una tabla densa destruyen la jerarquía de lectura.

---

## 6. Bloque de tokens listo para usar

```css
:root {
  --rv-font-marca:  'Cinzel', Georgia, serif;
  --rv-font-titulo: 'EB Garamond', Georgia, serif;
  --rv-font-ui:     'Inter', system-ui, 'Segoe UI', sans-serif;
  --rv-font-mono:   'JetBrains Mono', Consolas, monospace;
  --rv-track-marca: 0.09em;
  --rv-radio:       8px;
  --rv-radio-lg:    12px;
}

[data-tema="nocturno"] {
  --rv-bg:#0C1611;  --rv-surface:#132119; --rv-elev:#1B2E23; --rv-border:#2A4436;
  --rv-text:#EDE9DC; --rv-text-2:#B3AE9E;
  --rv-primary:#3E8E6B; --rv-primary-hover:#4EA57E;
  --rv-gold:#D9B65F; --rv-gold-text:#D9B65F; --rv-gold-tint:#2A2415;
  --rv-ok:#3E8E6B; --rv-warn:#C9B25C; --rv-bad:#E07A72; --rv-na:#8A897F;
}

[data-tema="diurno"] {
  --rv-bg:#F5F2E9;  --rv-surface:#FBF9F3; --rv-elev:#E8F0EA; --rv-border:#E0DACA;
  --rv-text:#1A2B23; --rv-text-2:#4A5D53; --rv-nav:#1F4D3A;
  --rv-primary:#2C6B4E; --rv-primary-hover:#22563E;
  --rv-gold:#9A7000; --rv-gold-text:#7A5D00; --rv-gold-tint:#F5EBCF;
  --rv-ok:#2C6B4E; --rv-warn:#7A5D00; --rv-bad:#A32D2D; --rv-na:#6B6A62;
}

.rv-marca { font-family:var(--rv-font-marca); font-weight:600;
            letter-spacing:var(--rv-track-marca); text-transform:uppercase; }
.rv-titulo{ font-family:var(--rv-font-titulo); }
.rv-dato  { font-family:var(--rv-font-ui); font-variant-numeric:tabular-nums; }
.rv-id    { font-family:var(--rv-font-mono); color:var(--rv-gold-text); }
```

Conmutación de tema mediante el atributo `data-tema` en `<html>`. Persistir la preferencia y respetar `prefers-color-scheme` como valor inicial. Respetar también `prefers-reduced-motion` en cualquier transición.

---

## 7. Criterios de aceptación

Toda vista generada debe cumplir, antes de considerarse entregable:

- [ ] Ambos temas implementados y conmutables sin recarga.
- [ ] Ningún color fuera de los tokens declarados.
- [ ] Texto normal ≥ 4,5:1 y componentes de interfaz ≥ 3:1 contra su fondo, en **ambos** temas.
- [ ] Todas las cifras con `tabular-nums` y formato `00,0 %`.
- [ ] Cada estado con color + ícono + etiqueta de texto.
- [ ] Foco de teclado visible en todos los controles interactivos.
- [ ] Diseño responsivo hasta 360 px de ancho.
- [ ] Ninguna tipografía propietaria incrustada.

---

## 8. Referencias normativas

- **ISO/IEC 27002:2022** — controles A.5.13 (etiquetado de información), A.5.19 (seguridad en las relaciones con proveedores), A.5.32 (derechos de propiedad intelectual).
- **ISO/IEC 27001:2022**, cláusula 6.1.3 — Declaración de Aplicabilidad; fundamento de la exclusión de los controles «no aplica» del denominador de cumplimiento.
- **ISO 9241-112:2025** — *Ergonomics of human-system interaction: Principles for the presentation of information*. Segunda edición; cancela y reemplaza ISO 9241-112:2017.
- **W3C, WCAG 2.2** — criterios 1.4.1 (uso del color), 1.4.3 (contraste mínimo, 4,5:1 y 3:1), 1.4.11 (contraste de elementos no textuales, 3:1).
- **SIL Open Font License 1.1** — licencia de Cinzel, EB Garamond, Inter y JetBrains Mono.

---

*Documento de especificación para EIF402 – Administración de Bases de Datos, Universidad Nacional. Grupo 4.*
