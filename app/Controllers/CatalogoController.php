<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Proceso;

/**
 * Administración del catálogo maestro: dominios, procesos y controles.
 *
 * TODAS las acciones exigen rol ADMIN_BD. Que un auditor pueda responder
 * cuestionarios no significa que pueda reescribir los controles que evalúan
 * todos los demás: cambiar un enunciado altera el significado de las
 * respuestas ya guardadas en otras auditorías.
 *
 * Dos reglas que atraviesan el archivo:
 *
 * - Las claves (clave de dominio, número de proceso, código de control) no se
 *   editan. Identifican la fila y viajan a las evaluaciones ya respondidas;
 *   cambiarlas reescribiría el contenido de auditorías entregadas. Para
 *   corregir una clave se borra y se vuelve a crear, y borrar solo se permite
 *   si nadie la usa.
 *
 * - Antes de borrar se cuenta qué depende de la fila. Las llaves foráneas ya
 *   lo impedirían, pero devolviendo un ORA-02292 en pantalla; aquí se explica
 *   qué hay que quitar primero.
 */
final class CatalogoController extends Controlador
{
    // ── Índice ───────────────────────────────────────────────────────────────

    public function indice(): void
    {
        $this->exigirAdministrador();
        $catalogo = $this->catalogo();

        $this->ver('catalogo/indice', [
            ...$this->contexto(),
            'meta'      => $this->meta('Catálogo de controles'),
            'dominios'  => $catalogo->dominios(),
            'procesos'  => $catalogo->procesos(),
            'controles' => $catalogo->controles(),
            'usosControl' => $catalogo->evaluacionesPorControl(),
        ]);
    }

    // ── Dominios ─────────────────────────────────────────────────────────────

    public function dominioFormulario(): void
    {
        $this->exigirAdministrador();

        $clave = $this->parametro('clave');
        $dominio = $clave === null ? null : $this->catalogo()->dominio($clave);

        if ($clave !== null && $dominio === null) {
            $this->noEncontrado();
        }

        $this->ver('catalogo/dominio', [
            ...$this->contexto(),
            'meta'     => $this->meta($dominio === null ? 'Nuevo dominio' : 'Dominio ' . $dominio->clave),
            'dominio'  => $dominio,
            'errores'  => $this->erroresGuardados(),
            'valores'  => $this->valoresGuardados(),
            'siguienteOrden' => $this->catalogo()->siguienteOrdenDominio(),
        ]);
    }

    public function guardarDominio(): void
    {
        $this->exigirAdministrador();

        $clave = $this->parametro('clave');
        $existente = $clave === null ? null : $this->catalogo()->dominio($clave);
        $destino = '/catalogo/dominios/' . ($clave ?? 'nuevo');

        $this->exigirToken($destino);

        $datos = [
            'clave'       => (string) $this->peticion()->entrada('clave', ''),
            'nombre'      => (string) $this->peticion()->entrada('nombre', ''),
            'corto'       => (string) $this->peticion()->entrada('corto', ''),
            'descripcion' => (string) $this->peticion()->entrada('descripcion', ''),
            'orden'       => (string) $this->peticion()->entrada('orden', ''),
        ];

        // Al editar, la clave viene de la URL y se ignora lo que llegue en el
        // formulario: es lo que impide renombrarla enviando otro valor a mano.
        if ($existente !== null) {
            $datos['clave'] = $existente->clave;
        }

        $errores = $this->validarDominio($datos, $existente === null);

        if ($errores !== []) {
            $this->guardarIntento($errores, $datos);
            $this->redirigir($destino);
        }

        $dominio = new Dominio(
            clave:       $datos['clave'],
            nombre:      $datos['nombre'],
            corto:       $datos['corto'],
            descripcion: $datos['descripcion'],
            orden:       (int) $datos['orden'],
        );

        if ($existente === null) {
            $this->catalogo()->crearDominio($dominio);
            $this->sesion()->destello('aviso', 'Dominio ' . $dominio->clave . ' creado.');
        } else {
            $this->catalogo()->actualizarDominio($dominio);
            $this->sesion()->destello('aviso', 'Dominio ' . $dominio->clave . ' actualizado.');
        }

        $this->redirigir('/catalogo');
    }

    public function eliminarDominio(): void
    {
        $this->exigirAdministrador();
        $this->exigirToken('/catalogo');

        $clave = (string) $this->parametro('clave', '');
        $dominio = $this->catalogo()->dominio($clave);

        if ($dominio === null) {
            $this->noEncontrado();
        }

        $procesos = $this->catalogo()->procesosDeDominio($clave);

        if ($procesos > 0) {
            $this->sesion()->destello('error', sprintf(
                'No se puede eliminar %s: todavía tiene %d proceso(s). Reasígnelos o elimínelos primero.',
                $clave,
                $procesos,
            ));
            $this->redirigir('/catalogo');
        }

        $this->catalogo()->eliminarDominio($clave);
        $this->sesion()->destello('aviso', 'Dominio ' . $clave . ' eliminado.');
        $this->redirigir('/catalogo');
    }

    // ── Procesos ─────────────────────────────────────────────────────────────

    public function procesoFormulario(): void
    {
        $this->exigirAdministrador();

        $numero = $this->parametro('numero');
        $proceso = $numero === null ? null : $this->catalogo()->proceso((int) $numero);

        if ($numero !== null && $proceso === null) {
            $this->noEncontrado();
        }

        $this->ver('catalogo/proceso', [
            ...$this->contexto(),
            'meta'     => $this->meta($proceso === null ? 'Nuevo proceso' : 'Proceso ' . $proceso->numero),
            'proceso'  => $proceso,
            'dominios' => $this->catalogo()->dominios(),
            'errores'  => $this->erroresGuardados(),
            'valores'  => $this->valoresGuardados(),
            'siguienteOrden' => $this->catalogo()->siguienteOrdenProceso(),
        ]);
    }

    public function guardarProceso(): void
    {
        $this->exigirAdministrador();

        $numero = $this->parametro('numero');
        $existente = $numero === null ? null : $this->catalogo()->proceso((int) $numero);
        $destino = '/catalogo/procesos/' . ($numero ?? 'nuevo');

        $this->exigirToken($destino);

        $datos = [
            'numero'  => (string) $this->peticion()->entrada('numero', ''),
            'dominio' => (string) $this->peticion()->entrada('dominio', ''),
            'nombre'  => (string) $this->peticion()->entrada('nombre', ''),
            'ancla'   => (string) $this->peticion()->entrada('ancla', ''),
            'orden'   => (string) $this->peticion()->entrada('orden', ''),
        ];

        if ($existente !== null) {
            $datos['numero'] = (string) $existente->numero;
        }

        $errores = $this->validarProceso($datos, $existente === null);

        if ($errores !== []) {
            $this->guardarIntento($errores, $datos);
            $this->redirigir($destino);
        }

        $proceso = new Proceso(
            numero:  (int) $datos['numero'],
            dominio: $datos['dominio'],
            nombre:  $datos['nombre'],
            ancla:   $datos['ancla'],
            orden:   (int) $datos['orden'],
        );

        if ($existente === null) {
            $this->catalogo()->crearProceso($proceso);
            $this->sesion()->destello('aviso', 'Proceso ' . $proceso->numero . ' creado.');
        } else {
            $this->catalogo()->actualizarProceso($proceso);
            $this->sesion()->destello('aviso', 'Proceso ' . $proceso->numero . ' actualizado.');
        }

        $this->redirigir('/catalogo');
    }

    public function eliminarProceso(): void
    {
        $this->exigirAdministrador();
        $this->exigirToken('/catalogo');

        $numero = (int) $this->parametro('numero', '0');
        $proceso = $this->catalogo()->proceso($numero);

        if ($proceso === null) {
            $this->noEncontrado();
        }

        $controles = $this->catalogo()->controlesDeProceso($numero);

        if ($controles > 0) {
            $this->sesion()->destello('error', sprintf(
                'No se puede eliminar el proceso %d: todavía tiene %d control(es).',
                $numero,
                $controles,
            ));
            $this->redirigir('/catalogo');
        }

        $this->catalogo()->eliminarProceso($numero);
        $this->sesion()->destello('aviso', 'Proceso ' . $numero . ' eliminado.');
        $this->redirigir('/catalogo');
    }

    // ── Controles ────────────────────────────────────────────────────────────

    public function controlFormulario(): void
    {
        $this->exigirAdministrador();

        $codigo = $this->parametro('codigo');
        $control = $codigo === null ? null : $this->catalogo()->control($codigo);

        if ($codigo !== null && $control === null) {
            $this->noEncontrado();
        }

        $this->ver('catalogo/control', [
            ...$this->contexto(),
            'meta'        => $this->meta($control === null ? 'Nuevo control' : 'Control ' . $control->id),
            'control'     => $control,
            'procesos'    => $this->catalogo()->procesos(),
            'dominios'    => $this->indexarDominios(),
            'errores'     => $this->erroresGuardados(),
            'valores'     => $this->valoresGuardados(),
            'evaluaciones' => $control === null ? 0 : $this->catalogo()->evaluacionesDeControl($control->id),
        ]);
    }

    public function guardarControl(): void
    {
        $this->exigirAdministrador();

        $codigo = $this->parametro('codigo');
        $existente = $codigo === null ? null : $this->catalogo()->control($codigo);
        $destino = '/catalogo/controles/' . ($codigo ?? 'nuevo');

        $this->exigirToken($destino);

        $datos = [
            'codigo'    => (string) $this->peticion()->entrada('codigo', ''),
            'proceso'   => (string) $this->peticion()->entrada('proceso', ''),
            'iso'       => (string) $this->peticion()->entrada('iso', ''),
            'enunciado' => (string) $this->peticion()->entrada('enunciado', ''),
            'evidencia' => (string) $this->peticion()->entrada('evidencia', ''),
            'pregunta'  => (string) $this->peticion()->entrada('pregunta', ''),
        ];

        if ($existente !== null) {
            $datos['codigo'] = $existente->id;
        }

        $errores = $this->validarControl($datos, $existente === null);

        if ($errores !== []) {
            $this->guardarIntento($errores, $datos);
            $this->redirigir($destino);
        }

        $control = new Control(
            id:        $datos['codigo'],
            proceso:   (int) $datos['proceso'],
            iso:       $datos['iso'],
            enunciado: $datos['enunciado'],
            evidencia: $datos['evidencia'],
            pregunta:  $datos['pregunta'],
        );

        if ($existente === null) {
            $this->catalogo()->crearControl($control);
            $this->sesion()->destello('aviso', 'Control ' . $control->id . ' creado.');
        } else {
            $this->catalogo()->actualizarControl($control);

            $usos = $this->catalogo()->evaluacionesDeControl($control->id);

            $this->sesion()->destello('aviso', $usos > 0
                ? sprintf(
                    'Control %s actualizado. Atención: %d evaluación(es) ya respondieron su versión anterior.',
                    $control->id,
                    $usos,
                )
                : 'Control ' . $control->id . ' actualizado.');
        }

        $this->redirigir('/catalogo');
    }

    public function eliminarControl(): void
    {
        $this->exigirAdministrador();
        $this->exigirToken('/catalogo');

        $codigo = (string) $this->parametro('codigo', '');
        $control = $this->catalogo()->control($codigo);

        if ($control === null) {
            $this->noEncontrado();
        }

        $usos = $this->catalogo()->evaluacionesDeControl($codigo);

        if ($usos > 0) {
            $this->sesion()->destello('error', sprintf(
                'No se puede eliminar %s: %d evaluación(es) de auditoría ya lo respondieron. '
                . 'Borrarlo destruiría el resultado de esas auditorías.',
                $codigo,
                $usos,
            ));
            $this->redirigir('/catalogo');
        }

        $this->catalogo()->eliminarControl($codigo);
        $this->sesion()->destello('aviso', 'Control ' . $codigo . ' eliminado.');
        $this->redirigir('/catalogo');
    }

    // ── Validación ───────────────────────────────────────────────────────────

    /**
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function validarDominio(array $datos, bool $esNuevo): array
    {
        $errores = [];

        if ($esNuevo) {
            if ($datos['clave'] === '') {
                $errores['clave'] = 'La clave es obligatoria.';
            } elseif (!preg_match('/^[a-z][a-z0-9_]{1,19}$/', $datos['clave'])) {
                // Minúsculas sin acentos: la clave viaja en atributos data- y en
                // las URL de las pestañas del instrumento.
                $errores['clave'] = 'Use de 2 a 20 caracteres: minúsculas sin acentos, dígitos o guion bajo.';
            } elseif ($this->catalogo()->dominio($datos['clave']) !== null) {
                $errores['clave'] = 'Ya existe un dominio con esa clave.';
            }
        }

        $errores += $this->validarTexto($datos, [
            'nombre' => ['El nombre', 100, true],
            'corto'  => ['El nombre corto', 30, true],
            'descripcion' => ['La descripción', 500, false],
        ]);

        $errores += $this->validarOrden($datos['orden']);

        return $errores;
    }

    /**
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function validarProceso(array $datos, bool $esNuevo): array
    {
        $errores = [];

        if ($esNuevo) {
            if (!ctype_digit($datos['numero']) || (int) $datos['numero'] < 1 || (int) $datos['numero'] > 999) {
                $errores['numero'] = 'El número de proceso va de 1 a 999.';
            } elseif ($this->catalogo()->proceso((int) $datos['numero']) !== null) {
                $errores['numero'] = 'Ya existe un proceso con ese número.';
            }
        }

        if ($datos['dominio'] === '' || $this->catalogo()->dominio($datos['dominio']) === null) {
            $errores['dominio'] = 'Seleccione un dominio existente.';
        }

        $errores += $this->validarTexto($datos, [
            'nombre' => ['El nombre', 200, true],
            'ancla'  => ['El anclaje normativo', 300, false],
        ]);

        $errores += $this->validarOrden($datos['orden']);

        return $errores;
    }

    /**
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function validarControl(array $datos, bool $esNuevo): array
    {
        $errores = [];

        if ($esNuevo) {
            if ($datos['codigo'] === '') {
                $errores['codigo'] = 'El código es obligatorio.';
            } elseif (mb_strlen($datos['codigo']) > 6) {
                // La columna es VARCHAR2(6): C-001 ocupa cinco.
                $errores['codigo'] = 'El código no puede pasar de 6 caracteres.';
            } elseif ($this->catalogo()->control($datos['codigo']) !== null) {
                $errores['codigo'] = 'Ya existe un control con ese código.';
            }
        }

        if ($datos['proceso'] === '' || !ctype_digit($datos['proceso'])
            || $this->catalogo()->proceso((int) $datos['proceso']) === null
        ) {
            $errores['proceso'] = 'Seleccione un proceso existente.';
        }

        $errores += $this->validarTexto($datos, [
            'iso' => ['La referencia ISO', 100, true],
        ]);

        // enunciado, evidencia y pregunta son CLOB: no tienen tope práctico,
        // pero enunciado y pregunta sí son obligatorios (NOT NULL en la tabla).
        foreach (['enunciado' => 'El enunciado', 'pregunta' => 'La pregunta'] as $campo => $etiqueta) {
            if (trim($datos[$campo]) === '') {
                $errores[$campo] = $etiqueta . ' es obligatorio.';
            }
        }

        return $errores;
    }

    /**
     * @param array<string, string> $datos
     * @param array<string, array{0: string, 1: int, 2: bool}> $reglas campo => [etiqueta, máximo, obligatorio]
     * @return array<string, string>
     */
    private function validarTexto(array $datos, array $reglas): array
    {
        $errores = [];

        foreach ($reglas as $campo => [$etiqueta, $maximo, $obligatorio]) {
            $valor = trim($datos[$campo] ?? '');

            if ($obligatorio && $valor === '') {
                $errores[$campo] = $etiqueta . ' es obligatorio.';
            } elseif (mb_strlen($valor) > $maximo) {
                $errores[$campo] = $etiqueta . " no puede pasar de {$maximo} caracteres.";
            }
        }

        return $errores;
    }

    /** @return array<string, string> */
    private function validarOrden(string $orden): array
    {
        if (!ctype_digit($orden) || (int) $orden < 1 || (int) $orden > 999) {
            return ['orden' => 'El orden de presentación va de 1 a 999.'];
        }

        return [];
    }

    // ── Apoyo ────────────────────────────────────────────────────────────────

    /** @return array<string, \App\Models\Entidades\Dominio> */
    private function indexarDominios(): array
    {
        $indice = [];

        foreach ($this->catalogo()->dominios() as $dominio) {
            $indice[$dominio->clave] = $dominio;
        }

        return $indice;
    }

    private function exigirToken(string $destino): void
    {
        if (!$this->autenticacion()->tokenValido($this->peticion()->entrada('_token'))) {
            $this->sesion()->destello('error', 'La sesión expiró. Intente de nuevo.');
            $this->redirigir($destino);
        }
    }

    /** @param array<string, string> $errores @param array<string, mixed> $valores */
    private function guardarIntento(array $errores, array $valores): void
    {
        $this->sesion()->poner('form.errores', $errores);
        $this->sesion()->poner('form.valores', $valores);
    }

    /** @return array<string, string> */
    private function erroresGuardados(): array
    {
        $errores = $this->sesion()->obtener('form.errores', []);
        $this->sesion()->olvidar('form.errores');

        return is_array($errores) ? $errores : [];
    }

    /** @return array<string, mixed> */
    private function valoresGuardados(): array
    {
        $valores = $this->sesion()->obtener('form.valores', []);
        $this->sesion()->olvidar('form.valores');

        return is_array($valores) ? $valores : [];
    }
}
