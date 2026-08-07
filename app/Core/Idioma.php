<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Qué idioma ve el visitante y de dónde saca los textos fijos del sitio.
 *
 * Se guarda en una cookie propia y no en la sesión a propósito: el sitio
 * público no debe abrir sesión solo por mostrarse en pantalla (ver el
 * comentario de Sesion::existePrevia()), y el idioma es justo eso, algo que
 * cualquier visitante anónimo puede cambiar sin necesidad de cuenta.
 */
final class Idioma
{
    private const COOKIE = 'becajo_idioma';
    private const DEFECTO = 'es';

    /** @var array<string, string> */
    public const DISPONIBLES = [
        'es' => 'ES',
        'en' => 'EN',
    ];

    private string $actual;

    /** @var array<string, string>|null */
    private ?array $diccionario = null;

    public function __construct(private readonly string $directorioDiccionarios)
    {
        $desdeCookie = $_COOKIE[self::COOKIE] ?? self::DEFECTO;

        $this->actual = isset(self::DISPONIBLES[$desdeCookie]) ? $desdeCookie : self::DEFECTO;
    }

    public function actual(): string
    {
        return $this->actual;
    }

    public function cambiar(string $codigo): void
    {
        if (!isset(self::DISPONIBLES[$codigo])) {
            return;
        }

        $this->actual = $codigo;
        $this->diccionario = null;

        // Un año: es una preferencia de visualización, no algo que deba
        // caducar con la sesión del navegador.
        setcookie(self::COOKIE, $codigo, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Traduce una clave. Si no existe en el diccionario del idioma actual,
     * devuelve la clave misma: así un texto sin traducir se nota en pantalla
     * en vez de desaparecer en silencio.
     */
    public function t(string $clave): string
    {
        $this->diccionario ??= $this->cargar();

        return $this->diccionario[$clave] ?? $clave;
    }

    /** @return array<string, string> */
    private function cargar(): array
    {
        $archivo = $this->directorioDiccionarios . '/' . $this->actual . '.php';

        if (!is_file($archivo)) {
            return [];
        }

        /** @var array<string, string> $datos */
        $datos = require $archivo;

        return $datos;
    }
}
