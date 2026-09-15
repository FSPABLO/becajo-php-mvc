<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Contratos\RepositorioCatalogo;
use App\Models\Contratos\RepositorioContenido;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Usuario;

/**
 * Clase base de todos los controladores.
 *
 * Un controlador NO consulta datos por su cuenta ni imprime HTML: pide datos al
 * modelo y se los pasa a una vista. Si algún día un controlador empieza a
 * contener SQL o etiquetas HTML, es señal de que la separación se rompió.
 */
abstract class Controlador
{
    public function __construct(protected readonly Contenedor $contenedor)
    {
    }

    protected function repositorio(): RepositorioContenido
    {
        return $this->contenedor->repositorio();
    }

    protected function instrumento(): RepositorioInstrumento
    {
        return $this->contenedor->instrumento();
    }

    protected function auditorias(): RepositorioAuditorias
    {
        return $this->contenedor->auditorias();
    }

    protected function catalogo(): RepositorioCatalogo
    {
        return $this->contenedor->catalogo();
    }

    protected function peticion(): Peticion
    {
        return $this->contenedor->peticion();
    }

    protected function sesion(): Sesion
    {
        return $this->contenedor->sesion();
    }

    protected function idioma(): Idioma
    {
        return $this->contenedor->idioma();
    }

    /**
     * Traduce una clave, igual que Vista::t().
     *
     * Casi todo el texto de pantalla se traduce en la vista, que es donde
     * pertenece. La excepción son los rótulos que el controlador tiene que
     * ARMAR porque solo él conoce el dato —«Auditoría 152» para la miga de
     * pan—: sin esto habría que pasarle a la vista la clave y sus argumentos
     * sueltos, o dejar el rótulo escrito en español dentro del controlador y
     * que la barra superior dijera «Audits / My audits / Auditoría 152».
     */
    protected function t(string $clave, string ...$argumentos): string
    {
        $texto = $this->idioma()->t($clave);

        return $argumentos === [] ? $texto : vsprintf($texto, $argumentos);
    }

    /**
     * Pasa un texto a minúsculas y sin tildes, para comparar.
     *
     * Sin quitar las tildes, buscar «produccion» no encontraría «producción», y
     * es exactamente lo que se escribe con prisa. El mapa es explícito y no
     * iconv //TRANSLIT: ese depende de la configuración regional del servidor y
     * devuelve cosas distintas en la máquina de cada quien.
     *
     * Vive aquí y no en AuditoriaController porque buscan con él las dos
     * antesalas con buscador —empresas e instancias vigiladas—, y dos copias de
     * «qué cuenta como la misma palabra» acaban encontrando cosas distintas.
     */
    protected function normalizar(string $texto): string
    {
        return strtr(mb_strtolower(trim($texto), 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'ç' => 'c',
        ]);
    }

    protected function autenticacion(): Autenticacion
    {
        return $this->contenedor->autenticacion();
    }

    /**
     * Exige que haya alguien autenticado y devuelve quién es.
     *
     * Primera línea de toda acción del módulo de auditorías. Si no hay sesión,
     * corta aquí y manda al formulario de ingreso: la acción protegida ni
     * siquiera empieza a ejecutarse.
     */
    protected function exigirUsuario(): Usuario
    {
        // Sin esto, el botón Atrás del navegador puede mostrar una página con
        // datos de auditoría desde su caché después de haber cerrado sesión.
        header('Cache-Control: no-store, must-revalidate');

        $usuario = $this->autenticacion()->usuario();

        if ($usuario === null) {
            $this->sesion()->destello('error', 'Inicie sesión para continuar.');
            $this->redirigir('/ingresar');
        }

        return $usuario;
    }

    /**
     * Exige además el rol de administrador de base de datos.
     *
     * Lo usará el CRUD del catálogo maestro: que un auditor pueda responder
     * cuestionarios no significa que pueda reescribir los controles que todos
     * los demás evalúan.
     */
    protected function exigirAdministrador(): Usuario
    {
        $usuario = $this->exigirUsuario();

        if (!$usuario->esAdministrador()) {
            $this->sesion()->destello('error', 'No tiene permisos para esa sección.');
            $this->redirigir('/evaluacion');
        }

        return $usuario;
    }

    /** Atajo para leer un parámetro de la URL: /auditorias/{id} -> parametro('id'). */
    protected function parametro(string $clave, ?string $porDefecto = null): ?string
    {
        return $this->peticion()->parametro($clave, $porDefecto);
    }

    /**
     * Renderiza una vista dentro del diseño principal y la envía al navegador.
     *
     * @param array<string, mixed> $datos
     */
    protected function ver(string $vista, array $datos = [], string $plantilla = 'principal'): void
    {
        $datos['mensajes'] = $datos['mensajes'] ?? $this->mensajesPendientes();

        echo $this->contenedor->vista()->renderizarConPlantilla($vista, $datos, $plantilla);
    }

    /**
     * Igual que ver(), pero dentro del marco del módulo interno.
     *
     * El sitio público y el módulo de auditorías ya no comparten diseño: fuera
     * la navegación es una barra superior sobre una lectura vertical, y dentro
     * es una barra lateral fija, porque trabajar una auditoría es saltar entre
     * secciones y no recorrer una página. Existe como método propio —y no como
     * un tercer argumento repetido en cada llamada— para que añadir una
     * pantalla al módulo no dependa de acordarse del nombre de la plantilla.
     *
     * @param array<string, mixed> $datos
     */
    protected function verPanel(string $vista, array $datos = []): void
    {
        /*
         * Si el auditor dejó la barra lateral plegada, el marco tiene que nacer
         * plegado. Se decide en el servidor —a partir de la cookie que escribe
         * el guion al pulsar el botón— y no al cargar la página: hacerlo con
         * JavaScript significa pintar la barra desplegada y encogerla a la
         * vista en cada navegación.
         *
         * La cookie es texto del cliente, así que se compara contra el único
         * valor que significa algo. Cualquier otra cosa es "desplegada".
         */
        $datos['lateralOculta'] = $datos['lateralOculta']
            ?? $this->peticion()->cookie('becajo_lateral') === 'oculta';

        $this->ver($vista, $datos, 'panel');
    }

    /**
     * Datos que el diseño principal necesita en toda página.
     *
     * @return array<string, mixed>
     */
    protected function contexto(): array
    {
        return [
            'empresa'       => $this->repositorio()->empresa(),
            'navegacion'    => $this->repositorio()->navegacion(),
            // Los dos desplegables del encabezado. Llegan como listas aparte
            // porque son menús distintos, no dos secciones de uno solo.
            'nosotros'      => $this->repositorio()->nosotros(),
            'herramientas'  => $this->repositorio()->herramientas(),
            // El selector de idioma son enlaces, no un <select> con guion, así
            // que necesita saber a dónde volver después de cambiar de idioma.
            'rutaActual'    => $this->peticion()->ruta(),
            // Null si no hay módulo de auditorías o si nadie inició sesión.
            // Así el encabezado sabe si mostrar "Ingresar" o "Mis auditorías".
            'usuarioActual' => $this->contenedor->hayAuditorias() ? $this->autenticacion()->usuario() : null,
            /*
             * Su fotografía de perfil, para el retrato de la barra lateral. Es
             * la FICHA, sin el binario: una consulta corta más por pantalla, y
             * la imagen solo se pide cuando el navegador va a por el <img>.
             *
             * Se resuelve aquí y no en cada controlador porque el retrato lo
             * pinta el MARCO, que está en todas las pantallas del módulo.
             */
            'fotoUsuarioActual' => $this->fotoDelUsuarioActual(),
            // La barra lateral oculta la entrada "Monitor" en vez de suponer
            // que /monitoreo responde — mismo criterio que hayAuditorias().
            'hayMonitor'    => $this->contenedor->hayMonitor(),
        ];
    }

    /**
     * La foto del usuario de la sesión, o null.
     *
     * Aparte de contexto() para que ese arreglo siga leyéndose de un vistazo, y
     * porque son tres condiciones: que haya módulo de auditorías, que haya
     * sesión, y que esa cuenta tenga foto — casi ninguna la tiene.
     */
    private function fotoDelUsuarioActual(): ?\App\Models\Entidades\FotoPerfil
    {
        if (!$this->contenedor->hayAuditorias()) {
            return null;
        }

        $usuario = $this->autenticacion()->usuario();

        return $usuario === null ? null : $this->auditorias()->fotoUsuario($usuario->id);
    }

    /** @return array{titulo: string, descripcion: string} */
    protected function meta(string $titulo, string $descripcion = ''): array
    {
        return [
            'titulo'      => $titulo . ' | ' . $this->repositorio()->empresa()['nombre'],
            'descripcion' => $descripcion !== '' ? $descripcion
                : 'Módulo de evaluación de riesgo en la administración de bases '
                . 'de datos según ISO/IEC 27002.',
        ];
    }

    // ── El intento fallido de un formulario ──────────────────────────────────
    //
    // Al fallar la validación, el POST guarda lo enviado y sus errores y
    // redirige al GET (patrón PRG); el GET los recupera y los pinta. Viven en
    // la sesión porque la redirección es lo que hay entre los dos.
    //
    // LA MARCA DEL FORMULARIO NO ES DECORATIVA. Los destellos son UN par de
    // claves para todo el sistema, así que sin ella el intento fallido de una
    // pantalla se pinta en la siguiente que pregunte. Con valores de texto
    // libre en juego —el entrevistado escrito a mano de una auditoría— eso
    // llegaba a rellenar el encabezado de OTRA auditoría con datos ajenos, a un
    // clic de guardarse. Quien lee dice de qué formulario viene; una marca que
    // no casa se descarta y el destello se consume igual, porque es el intento
    // de una pantalla que el usuario decidió no volver a abrir.
    //
    // Esto estaba COPIADO en AuditoriaController y CatalogoController, y la
    // copia del catálogo se había quedado sin la marca —o sea, con el error que
    // la marca existe para evitar—. Es la razón de subirlo: dos implementaciones
    // del mismo mecanismo son dos, pero solo una se arregla.

    /** @var array{de: string, errores: mixed, valores: mixed}|null */
    private ?array $intentoLeido = null;

    /**
     * @param array<string, string> $errores
     * @param array<string, mixed>  $valores
     */
    protected function guardarIntento(array $errores, array $valores, string $formulario): void
    {
        $this->sesion()->poner('form.errores', $errores);
        $this->sesion()->poner('form.valores', $valores);
        $this->sesion()->poner('form.de', $formulario);
    }

    /** @return array<string, string> */
    protected function erroresGuardados(string $formulario): array
    {
        $errores = $this->intentoGuardado($formulario)['errores'];

        return is_array($errores) ? $errores : [];
    }

    /** @return array<string, mixed> */
    protected function valoresGuardados(string $formulario): array
    {
        $valores = $this->intentoGuardado($formulario)['valores'];

        return is_array($valores) ? $valores : [];
    }

    /**
     * Lee el intento UNA vez por petición y lo borra de la sesión.
     *
     * En memoria porque quien pinta pide errores y valores por separado, y el
     * primero que llegara se llevaría el destello dejando al segundo vacío.
     *
     * @return array{errores: mixed, valores: mixed}
     */
    private function intentoGuardado(string $formulario): array
    {
        if ($this->intentoLeido === null) {
            $de = $this->sesion()->obtener('form.de');

            $this->intentoLeido = [
                'de'      => is_string($de) ? $de : '',
                'errores' => $this->sesion()->obtener('form.errores', []),
                'valores' => $this->sesion()->obtener('form.valores', []),
            ];

            $this->sesion()->olvidar('form.errores');
            $this->sesion()->olvidar('form.valores');
            $this->sesion()->olvidar('form.de');
        }

        return $this->intentoLeido['de'] === $formulario
            ? ['errores' => $this->intentoLeido['errores'], 'valores' => $this->intentoLeido['valores']]
            : ['errores' => [], 'valores' => []];
    }

    /**
     * Corta la petición si el token CSRF no cuadra.
     *
     * Primera línea de todo POST que escribe. Vive aquí y no en cada
     * controlador porque estaba COPIADO en dos —AuditoriaController y
     * CatalogoController— y el tercero que escribe (PerfilController) habría
     * sido la tercera copia. Un control de seguridad repetido es un control que
     * un día se arregla en dos sitios de tres.
     *
     * Redirige en vez de responder 403: quien pierde el token es casi siempre
     * alguien cuya sesión caducó con el formulario abierto, y devolverle su
     * pantalla con un mensaje es más útil que una página de error.
     */
    protected function exigirToken(string $destino): void
    {
        if (!$this->autenticacion()->tokenValido($this->peticion()->entrada('_token'))) {
            $this->sesion()->destello('error', 'La sesión expiró. Intente de nuevo.');
            $this->redirigir($destino);
        }
    }

    // ── Archivos subidos ─────────────────────────────────────────────────────
    //
    // Dos piezas mecánicas que necesita CUALQUIER pantalla que reciba un
    // archivo: hoy el adjunto de la evidencia y la fotografía de perfil. Viven
    // aquí y no en cada controlador porque son exactamente el tipo de código
    // que se copia una vez y se corrige en un solo sitio — y el que se corrige
    // en un solo sitio es el que deja una vulnerabilidad en el otro.
    //
    // Lo que NO sube aquí es la validación: los topes, la lista blanca y los
    // mensajes son de cada pantalla. Un PDF vale como evidencia y no vale como
    // foto de perfil.

    /**
     * El tipo MIME según el CONTENIDO del archivo, no según su nombre.
     *
     * La extensión y el Content-Type que manda el navegador los escribe el
     * cliente: `politica.pdf` puede ser cualquier cosa. Quien decide es finfo.
     */
    protected function tipoRealDe(string $ruta): ?string
    {
        $finfo = finfo_open(\FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return null;
        }

        try {
            $tipo = finfo_file($finfo, $ruta);
        } finally {
            finfo_close($finfo);
        }

        return is_string($tipo) && $tipo !== '' ? $tipo : null;
    }

    /**
     * Deja el nombre de un archivo subido en algo que se pueda guardar y volver
     * a servir.
     *
     * El nombre lo escribe el cliente y viaja después en una cabecera
     * Content-Disposition, así que se le quita la ruta (basename), los saltos
     * de línea —que partirían la cabecera en dos— y se recorta a los 255 de la
     * columna. La extensión se REESCRIBE a la del tipo real: si el contenido es
     * un PNG, el archivo se llama .png aunque llegara como .pdf.
     *
     * @param array<string, string> $extensiones  tipo MIME => extensión.
     */
    protected function nombreSeguroDe(
        string $nombre,
        string $tipoMime,
        array $extensiones,
        string $porDefecto,
    ): string {
        // Dos separadores: el cliente puede ser Windows y mandar la ruta entera.
        $base = basename(str_replace('\\', '/', $nombre));
        $base = preg_replace('/[\x00-\x1F\x7F"]+/u', '', $base) ?? '';
        $base = pathinfo($base, PATHINFO_FILENAME);
        $base = trim($base);

        if ($base === '') {
            $base = $porDefecto;
        }

        $extension = $extensiones[$tipoMime] ?? 'bin';
        $base = mb_substr($base, 0, 250 - mb_strlen($extension));

        return $base . '.' . $extension;
    }

    /**
     * Recoge los avisos que dejó la petición anterior antes de redirigir.
     *
     * Se leen aquí, en un solo sitio, para que toda vista los reciba sin que
     * cada controlador tenga que acordarse de pasarlos. Leerlos los consume:
     * es justo lo que se busca, porque ya se están mostrando.
     *
     * Si el visitante no traía sesión no puede haber nada pendiente, así que
     * ni siquiera se abre una. Es lo que evita que la portada mande una cookie
     * a quien solo pasaba por ahí.
     *
     * @return array{aviso: string|null, error: string|null}
     */
    private function mensajesPendientes(): array
    {
        if (!$this->sesion()->existePrevia()) {
            return ['aviso' => null, 'error' => null];
        }

        return [
            'aviso' => $this->sesion()->destello('aviso'),
            'error' => $this->sesion()->destello('error'),
        ];
    }

    /**
     * Envía al navegador a otra ruta del sitio y corta la ejecución.
     *
     * Todo POST que modifica datos termina aquí (patrón PRG: post, redirect,
     * get). Sin el redireccionamiento, recargar la página después de guardar
     * volvería a enviar el formulario y duplicaría el registro.
     *
     * La ruta se antepone con la ruta base para que los enlaces sigan siendo
     * correctos si el sitio vive en una subcarpeta.
     */
    protected function redirigir(string $ruta): never
    {
        header('Location: ' . $this->peticion()->rutaBase() . $ruta);

        exit;
    }

    /**
     * Responde en JSON y corta la ejecución. La gemela de redirigir(), para
     * cuando quien pregunta es un guion y no el navegador.
     *
     * No sustituye al patrón PRG: el mismo POST sigue redirigiendo cuando
     * llega de un formulario. Esto es la otra mitad, y por eso vive al lado.
     *
     * JSON_UNESCAPED_UNICODE porque el proyecto está en español y una tilde
     * escapada a \u00e9 se lee peor al depurar, sin ganar nada: la respuesta
     * se declara UTF-8 en la misma cabecera.
     *
     * @param array<string, mixed> $datos
     */
    protected function json(array $datos, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * Responde 404 desde un controlador.
     *
     * El enrutador ya cubre la URL que no existe; esto cubre el caso distinto
     * de una URL bien formada que apunta a un registro inexistente
     * (/auditorias/9999).
     */
    protected function noEncontrado(): never
    {
        http_response_code(404);

        // Sin plantilla envolvente: errores/404 ya es un documento HTML
        // completo, igual que cuando lo sirve el enrutador.
        echo $this->contenedor->vista()->renderizar('errores/404', [
            'ruta'     => $this->peticion()->ruta(),
            'empresa'  => $this->repositorio()->empresa(),
            'rutaBase' => $this->peticion()->rutaBase(),
        ]);

        exit;
    }
}
