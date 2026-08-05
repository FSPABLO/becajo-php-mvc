# Becajo — Sitio corporativo

Página principal de **Becajo**, empresa ficticia de consultoría en administración de
bases de datos. Proyecto de la materia **Administración de Bases de Datos**.

PHP 8 con arquitectura **MVC**, Tailwind CSS y Apache.

> El nombre viene de las iniciales del equipo: **Be**njamín, **Ca**mila, **Jo**sé.

---

## Cómo ejecutarlo

### Docker (recomendado — LAMP: Linux + Apache + MySQL/MariaDB + PHP)

Es el equivalente funcional de WAMP: mismo Apache, mismo PHP, mismo resultado
en el navegador, corriendo sobre Linux en lugar de Windows. Toda la
configuración vive en `Dockerfile` y `docker-compose.yml`, así que cualquier
integrante del equipo levanta exactamente el mismo entorno con un comando.

```bash
cd becajo
docker compose up --build
```

La primera vez tarda unos minutos (descarga la imagen de PHP). Las siguientes
veces arranca en segundos. Abra `http://localhost:8080`.

Para detenerlo: `Ctrl + C`, o en otra terminal `docker compose down`.

Los archivos del proyecto están montados como volumen: cualquier cambio que
haga en su editor se refleja de inmediato, sin reconstruir la imagen.

### Opción rápida — servidor embebido de PHP

```bash
cd becajo
php -S localhost:8000 -t public
```

Abra `http://localhost:8000`. No necesita Apache ni configurar nada.

### WAMP (Windows)

1. Copie la carpeta `becajo` en `C:\wamp64\www\`
2. Active **rewrite_module**: clic en el ícono de Wamp → Apache → Apache modules → `rewrite_module`
3. Abra `http://localhost/becajo/`

### MAMP (macOS)

1. Copie la carpeta `becajo` en `/Applications/MAMP/htdocs/`
2. **Start Servers**
3. Abra `http://localhost:8888/becajo/`

> **No abra los archivos `.php` con doble clic.** El navegador mostraría el código
> en lugar de ejecutarlo: PHP necesita un servidor que lo interprete.

---

## Estructura MVC

```
becajo/
├── public/                     ← ÚNICA carpeta expuesta al navegador
│   ├── index.php               Front controller: toda petición entra aquí
│   ├── .htaccess               Reescritura de URLs
│   └── assets/                 CSS y JavaScript
│
├── app/
│   ├── Core/                   El "mini framework"
│   │   ├── Autoloader.php      Carga clases sin require manual
│   │   ├── Peticion.php        Encapsula la petición HTTP
│   │   ├── Enrutador.php       Decide qué controlador atiende cada URL
│   │   ├── Contenedor.php      Entrega las dependencias compartidas
│   │   ├── Controlador.php     Clase base de los controladores
│   │   ├── Vista.php           Motor de plantillas
│   │   └── funciones.php       e() e icono()
│   │
│   ├── Models/                 ← M — los DATOS
│   │   ├── Contratos/
│   │   │   ├── RepositorioContenido.php    Interfaz: qué datos ofrece el sitio
│   │   │   └── RepositorioInstrumento.php  Interfaz: catálogo del instrumento
│   │   ├── Entidades/
│   │   │   ├── Servicio.php
│   │   │   ├── Integrante.php
│   │   │   ├── Dominio.php     ┐
│   │   │   ├── Proceso.php     ├ Instrumento de consultoría
│   │   │   └── Control.php     ┘
│   │   ├── RepositorioArreglo.php             Implementación actual del sitio
│   │   └── RepositorioInstrumentoArreglo.php  Implementación del instrumento
│   │
│   ├── Controllers/            ← C — la COORDINACIÓN
│   │   ├── HomeController.php
│   │   └── HerramientasController.php
│   │
│   └── Views/                  ← V — la PRESENTACIÓN
│       ├── layouts/principal.php
│       ├── partials/           head, encabezado, pie
│       ├── components/         tarjeta-servicio, tarjeta-control, tarjeta-pregunta
│       ├── home/               index + secciones/
│       ├── herramientas/       instrumento-bd + parciales/
│       └── errores/404.php
│
├── config/
│   ├── contenido.php           TODO el texto editable del sitio
│   ├── instrumento-bd.php      Catálogo del instrumento: 7 dominios, 25 procesos,
│   │                           75 controles con su pregunta de auditoría
│   └── rutas.php               Tabla de rutas
│
├── .htaccess                   Redirige la raíz a /public
├── .gitattributes              Normaliza fines de línea (Windows ↔ Linux)
└── .gitignore
```

---

## Cómo funciona una petición

1. El navegador pide `/`
2. Apache manda todo a `public/index.php` (front controller)
3. `index.php` registra el autoloader y construye tres objetos: la petición,
   el motor de vistas y **el repositorio**
4. El `Enrutador` busca `/` en `config/rutas.php` y encuentra `HomeController::index`
5. El controlador pide los datos al repositorio y se los pasa a la vista
6. La `Vista` renderiza `home/index`, lo mete dentro de `layouts/principal`
   y devuelve el HTML

El controlador nunca toca la base de datos ni imprime HTML. La vista nunca
consulta datos. El modelo no sabe que existe un navegador. Esa es toda la idea
del patrón.

---

## Herramientas

El menú **Herramientas** del encabezado agrupa las utilidades internas del sitio.
Para agregar una se añaden dos líneas: una entrada en `herramientas` dentro de
`config/contenido.php` y su ruta en `config/rutas.php`.

### Instrumento de consultoría — `/herramientas/instrumento-bd`

Evaluación de la administración de bases de datos con anclaje en la familia
ISO/IEC 27000 a 27011: **7 dominios, 25 procesos y 75 controles**, cada uno con su
referencia a ISO/IEC 27002:2022, la evidencia que debe solicitarse y una pregunta
de auditoría redactada conforme a ISO/IEC 27007:2020.

Cinco pestañas: Instrumento · Cuestionario · Tablero · Marco ISO · Referencias.

**Reglas de cálculo** (implementadas en `public/assets/js/instrumento.js`):

| Medida | Fórmula | Nota |
|---|---|---|
| Cumplimiento | `sí ÷ (sí + no)` | «No aplica» sale del denominador, como una exclusión justificada en la Declaración de Aplicabilidad (ISO/IEC 27001, cl. 6.1.3). Los controles sin evaluar no cuentan en ningún lado. |
| Denominador cero | `—` | Nunca `NaN`, `0 %` ni `Infinity`. |
| Madurez promedio | `Σ madurez ÷ n calificados` | Un control sin calificar no vale cero: queda fuera del promedio. |

Marcar un control como «no aplica» sin escribir la justificación en el campo de
hallazgo levanta un aviso visible en la tarjeta. Sin esa fricción, «no aplica»
se convierte en la salida fácil que infla el resultado.

El avance se guarda solo en `localStorage`; además se puede exportar a CSV
(delimitador `;` y BOM UTF-8, para que Excel en español lo abra bien) o guardar y
recargar como JSON.

---

## El punto importante: cambiar a Oracle

`app/Models/Contratos/RepositorioContenido.php` es una **interfaz**: define qué
datos ofrece el modelo, sin decir de dónde salen. Hoy los sirve
`RepositorioArreglo` leyendo `config/contenido.php`.

Para conectar Oracle:

1. Crear `app/Models/RepositorioPdo.php` que implemente la misma interfaz,
   pero con consultas SQL en cada método.
2. Cambiar **una línea** en `public/index.php`:

```php
// Antes
$repositorio = new RepositorioArreglo(RAIZ . '/config/contenido.php');

// Después
$repositorio = new RepositorioPdo(require RAIZ . '/config/base_datos.php');
```

Ni el controlador ni las vistas se tocan. Eso es lo que se gana al programar
contra una interfaz en lugar de contra una implementación concreta. El
instrumento de consultoría sigue el mismo esquema con
`RepositorioInstrumento`.

---

## Por qué `public/` está separado

Es la única carpeta que el navegador puede alcanzar. El código, la configuración
y las credenciales quedan un nivel arriba, fuera de su alcance. Si mañana Apache
deja de interpretar PHP por una mala configuración, nadie podrá pedir
`config/base_datos.php` por URL, porque esa ruta simplemente no existe para el
servidor web.

Es la práctica estándar en Laravel y Symfony, y aquí además es coherente con el
tema del curso: reducir la superficie de exposición de los datos.

---

## Convenciones del equipo

1. **Nombres de archivo en minúsculas** para vistas y recursos; **PascalCase**
   solo para clases (es la convención PSR-4 y coincide con el nombre de la clase).
   Linux distingue mayúsculas y Windows no: `Header.php` funciona en WAMP y falla
   en LAMP.
2. **Rutas siempre con `/`**, nunca con `\`.
3. **Todo dato impreso pasa por `e()`**. Sin excepciones.
4. **UTF-8 sin BOM** en todos los archivos.
5. **Un commit por cambio con sentido propio**, mensaje en imperativo
   (`Agrega sección de equipo`, no `cambios`).

---

## Producción

Tailwind se carga por CDN para que el proyecto corra sin instalar Node. En un
sitio real se compila una hoja con solo las clases usadas:

```bash
npm install -D tailwindcss
npx tailwindcss -i ./public/assets/css/entrada.css -o ./public/assets/css/tailwind.css --minify
```

Después se sustituye el `<script>` de `app/Views/partials/head.php` por un
`<link>` a la hoja generada. El peso pasa de unos 3 MB a menos de 15 KB.

---

## Equipo

| Integrante | Rol |
|---|---|
| Benjamín Alexander Solano Ortega | Seguridad y cumplimiento |
| Camila Fallas Jiménez | Rendimiento y continuidad |
| José Pablo Fernández Sandoval | Consultor líder |
| Minor Brenes | Migración y monitoreo |

---

## Licencia

[PolyForm Noncommercial 1.0.0](https://polyformproject.org/licenses/noncommercial/1.0.0).

Puede usar, estudiar, modificar y redistribuir este código libremente para
cualquier **propósito no comercial**: estudio personal, docencia, investigación
y proyectos de instituciones educativas o sin fines de lucro. El uso comercial
requiere permiso de los autores.

## Aviso

Becajo es una **empresa ficticia**. Las métricas, el caso de éxito y los datos de
contacto son ilustrativos y fueron creados con fines académicos.
