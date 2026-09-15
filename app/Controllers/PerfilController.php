<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\FotoPerfil;

/**
 * La ficha del propio usuario: quién es, qué ha hecho y cuándo.
 *
 * SIEMPRE es el usuario de la sesión. No hay `/perfil/{id}`, y no es un olvido:
 * la única razón para mirar la ficha de otro sería administrar cuentas, que hoy
 * no existe como función. Mientras no exista, una ruta con id sería una URL
 * adivinable que enseña el nombre y la foto de cualquiera, y habría que
 * defenderla — el mismo razonamiento de `auditoriaPropia()`, pero resuelto
 * quitando el parámetro en vez de comprobándolo.
 *
 * Las tres piezas de la pantalla salen de datos que el sistema ya tenía:
 * la cuenta (`usuario`), su cartera (`auditoriasDe`) y, lo único nuevo, la
 * descripción y la foto. El calendario y la tabla son la MISMA lista mirada de
 * dos maneras —por día del mes y en orden—, no dos consultas.
 */
final class PerfilController extends Controlador
{
    /** Filas por página en la tabla de auditorías. */
    private const POR_PAGINA = 10;

    public function mostrar(): void
    {
        $usuario = $this->exigirUsuario();
        $auditorias = $this->auditorias()->auditoriasDe($usuario->id);

        /*
         * El mes del calendario. Llega como YYYY-MM y se comprueba antes de
         * tocarlo: lo que viene de la URL nunca se pasa a una función de fecha
         * tal cual. Cualquier cosa que no case cae en el mes actual, que es un
         * accidente corriente —un enlace viejo en el historial— y no un intento
         * de romper nada.
         */
        $mes = $this->mesValido($this->peticion()->entrada('mes')) ?? date('Y-m');

        $paginas = max(1, (int) ceil(count($auditorias) / self::POR_PAGINA));
        $pagina = max(1, min($paginas, (int) ($this->peticion()->entrada('pagina') ?? '1')));

        $this->verPanel('perfil/mostrar', [
            ...$this->contexto(),
            'meta'        => $this->meta('Mi perfil'),
            'migaPagina'  => [['etiqueta' => $this->t('perfil.titulo')]],
            'usuario'     => $usuario,
            'foto'        => $this->auditorias()->fotoUsuario($usuario->id),
            'limiteFoto'  => $this->limiteFoto(),

            // El calendario y la tabla, de la MISMA lista.
            'mes'         => $mes,
            'porDia'      => $this->auditoriasPorDia($auditorias, $mes),
            'mesAnterior' => $this->mesDesplazado($mes, -1),
            'mesSiguiente' => $this->mesDesplazado($mes, +1),

            'auditorias'  => $auditorias,
            'visibles'    => array_slice($auditorias, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA),
            'pagina'      => $pagina,
            'paginas'     => $paginas,
            'porPagina'   => self::POR_PAGINA,

            'errores'     => $this->erroresGuardados('perfil'),
            'valores'     => $this->valoresGuardados('perfil'),
        ]);
    }

    /** Guarda la descripción que el usuario escribe sobre sí mismo. */
    public function guardarDescripcion(): void
    {
        $usuario = $this->exigirUsuario();
        $this->exigirToken('/perfil');

        $descripcion = $this->peticion()->entrada('descripcion');

        /*
         * El tope es el de la columna (VARCHAR2 500). Se comprueba aquí porque
         * un ORA-12899 en pantalla no es un mensaje de error — y porque el
         * repositorio recorta en silencio, que para un texto que el usuario
         * acaba de escribir sería peor que decírselo.
         */
        if ($descripcion !== null && mb_strlen($descripcion) > 500) {
            $this->guardarIntento(
                ['descripcion' => 'La descripción no puede pasar de 500 caracteres.'],
                ['descripcion' => $descripcion],
                'perfil',
            );
            $this->redirigir('/perfil');
        }

        $this->auditorias()->actualizarDescripcionUsuario($usuario->id, $descripcion);
        $this->sesion()->destello('aviso', 'Descripción actualizada.');
        $this->redirigir('/perfil');
    }

    /** Sube o quita la fotografía de perfil. */
    public function guardarFoto(): void
    {
        $usuario = $this->exigirUsuario();

        /*
         * Una foto que se pasa de post_max_size deja $_POST vacío —token
         * incluido—, así que esto va ANTES de comprobarlo: si no, el usuario
         * leería «la sesión expiró» cuando lo que pasó es que la imagen no
         * cabía. Ver docker/php.ini.
         */
        if ($this->peticion()->excedioLimitePost()) {
            $this->sesion()->destello('error', 'La imagen pesa demasiado. Elija una más pequeña.');
            $this->redirigir('/perfil');
        }

        $this->exigirToken('/perfil');

        if ($this->peticion()->marcada('quitar_foto')) {
            $this->auditorias()->eliminarFotoUsuario($usuario->id);
            $this->sesion()->destello('aviso', 'Fotografía retirada.');
            $this->redirigir('/perfil');
        }

        $archivo = $this->peticion()->archivo('foto');
        $error = $this->validarFoto($archivo);

        if ($error !== null) {
            $this->guardarIntento(['foto' => $error], [], 'perfil');
            $this->redirigir('/perfil');
        }

        // validarFoto() ya garantizó que hay archivo y que se puede leer.
        $contenido = file_get_contents($archivo['ruta']);

        if ($contenido === false || $contenido === '') {
            $this->guardarIntento(['foto' => 'No se pudo leer la imagen. Vuelva a intentarlo.'], [], 'perfil');
            $this->redirigir('/perfil');
        }

        $tipo = (string) $this->tipoRealDe($archivo['ruta']);

        $this->auditorias()->guardarFotoUsuario(
            $usuario->id,
            $this->nombreSeguroDe($archivo['nombre'], $tipo, FotoPerfil::TIPOS, 'foto'),
            $tipo,
            $contenido,
        );

        $this->sesion()->destello('aviso', 'Fotografía actualizada.');
        $this->redirigir('/perfil');
    }

    /**
     * Sirve la fotografía del usuario de la sesión.
     *
     * Mismas tres cabeceras que el adjunto de la evidencia, y por lo mismo:
     * `nosniff` impide que el navegador adivine un tipo distinto del declarado,
     * y el CSP con `sandbox` deja la imagen sin permisos. Aunque la validación
     * solo deje pasar imágenes comprobadas por su contenido, esto lo sube un
     * usuario y se sirve desde NUESTRO dominio.
     *
     * `Cache-Control: private` y nada más: la barra lateral la pide en cada
     * pantalla, pero cachearla dejaría al usuario mirando su foto anterior
     * después de cambiarla.
     */
    public function foto(): never
    {
        $usuario = $this->exigirUsuario();

        $ficha = $this->auditorias()->fotoUsuario($usuario->id);
        $contenido = $ficha === null
            ? null
            : $this->auditorias()->contenidoFotoUsuario($usuario->id);

        if ($ficha === null || $contenido === null) {
            $this->noEncontrado();
        }

        header('Content-Type: ' . $ficha->tipoMime);
        header('Content-Length: ' . strlen($contenido));
        header('Content-Disposition: inline; filename="' . $ficha->nombre . '"');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; sandbox");
        header('Cache-Control: private, no-cache');

        echo $contenido;
        exit;
    }

    // ── Ayudantes ────────────────────────────────────────────────────────────

    /**
     * Las auditorías del mes, agrupadas por día.
     *
     * Se agrupa en PHP y no con una consulta nueva porque la lista completa ya
     * está en memoria —la tabla de abajo la necesita entera— y pedirle a Oracle
     * lo que se acaba de traer sería un viaje de más. Es el mismo criterio con
     * el que `panel()` saca de ahí la lista de empresas.
     *
     * @param list<Auditoria> $auditorias
     * @return array<int, list<Auditoria>>  día del mes => auditorías de ese día
     */
    private function auditoriasPorDia(array $auditorias, string $mes): array
    {
        $porDia = [];

        foreach ($auditorias as $auditoria) {
            if (!str_starts_with($auditoria->fecha, $mes . '-')) {
                continue;
            }

            $dia = (int) substr($auditoria->fecha, 8, 2);
            $porDia[$dia][] = $auditoria;
        }

        return $porDia;
    }

    /** Un mes en formato YYYY-MM, o null si lo recibido no lo es. */
    private function mesValido(?string $mes): ?string
    {
        if ($mes === null || preg_match('/^\d{4}-\d{2}$/', $mes) !== 1) {
            return null;
        }

        [$anio, $numero] = array_map('intval', explode('-', $mes));

        // Un mes 13 pasa el patrón pero no existe, y el rango de años evita que
        // ?mes=0000-01 mande a date() a recorrer dos milenios de calendario.
        return ($numero >= 1 && $numero <= 12 && $anio >= 2000 && $anio <= 2100) ? $mes : null;
    }

    /**
     * El mes de al lado. Se cuenta con el día 1 a propósito: sumar un mes al 31
     * de enero da el 3 de marzo, que es la clase de error que solo aparece en
     * los meses cortos y por eso nadie lo ve al probar.
     */
    private function mesDesplazado(string $mes, int $meses): string
    {
        return date('Y-m', (int) strtotime($mes . '-01 ' . $meses . ' month'));
    }

    /**
     * El tope de subida que se va a respetar de VERDAD, en bytes: el máximo de
     * la aplicación cruzado con lo que este PHP acepta. Los dos pueden
     * discrepar —docker/php.ini sube los límites de fábrica, pero un contenedor
     * sin reconstruir sigue con los suyos— y manda el menor, para que el rótulo
     * de la pantalla no prometa lo que el servidor va a rechazar.
     */
    private function limiteFoto(): int
    {
        $dePhp = $this->peticion()->limiteSubidaBytes();

        return $dePhp > 0 ? min(FotoPerfil::MAXIMO_BYTES, $dePhp) : FotoPerfil::MAXIMO_BYTES;
    }

    /**
     * Comprueba la imagen. Devuelve el mensaje de error, o null.
     *
     * El tipo se decide por el CONTENIDO (finfo), no por la extensión ni por el
     * Content-Type que declara el navegador: los dos los escribe el cliente. El
     * `accept` del campo es una comodidad del diálogo de archivos.
     *
     * @param array{nombre: string, tipo: string, tamano: int, ruta: string, error: int}|null $archivo
     */
    private function validarFoto(?array $archivo): ?string
    {
        if ($archivo === null) {
            return 'Elija una imagen.';
        }

        $limite = $this->limiteFoto();
        $limiteMb = number_format($limite / (1024 * 1024), 1, ',', '');

        if ($archivo['error'] === \UPLOAD_ERR_INI_SIZE || $archivo['error'] === \UPLOAD_ERR_FORM_SIZE) {
            return 'La imagen pesa demasiado. El máximo son ' . $limiteMb . ' MB.';
        }

        if ($archivo['error'] === \UPLOAD_ERR_PARTIAL) {
            return 'La imagen llegó incompleta. Vuelva a subirla.';
        }

        if ($archivo['error'] !== \UPLOAD_ERR_OK || $archivo['ruta'] === '') {
            return 'No se pudo recibir la imagen. Vuelva a intentarlo.';
        }

        if ($archivo['tamano'] <= 0) {
            return 'El archivo está vacío.';
        }

        if ($archivo['tamano'] > $limite) {
            return 'La imagen pesa demasiado. El máximo son ' . $limiteMb . ' MB.';
        }

        $tipo = $this->tipoRealDe($archivo['ruta']);

        if ($tipo === null || !isset(FotoPerfil::TIPOS[$tipo])) {
            return 'La fotografía debe ser una imagen PNG, JPG, WEBP o GIF.';
        }

        return null;
    }

    /*
     * El intento fallido lo guarda y lo lee Controlador, con la marca del
     * formulario. Aquí solo hay uno —la ficha entera comparte pantalla— y por
     * eso la marca es constante: la descripción y la foto se envían por
     * separado pero vuelven al mismo sitio, así que un error de una no puede
     * pisar el formulario de la otra.
     */
}
