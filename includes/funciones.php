<?php
// Funciones comunes: sesion, escape HTML, CSRF, mensajes, validaciones.

// Ruta base del proyecto dentro de htdocs.
define('BASE_URL', '/FinanTrack/');

// Segundos de inactividad antes de cerrar la sesion (30 minutos).
define('TIEMPO_INACTIVIDAD', 1800);

// true: muestra los errores de PHP en pantalla.
// false (recomendado): los errores se guardan en el registro del servidor

define('MODO_DEPURACION', false);

ini_set('display_errors', MODO_DEPURACION ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Zona horaria usada.
date_default_timezone_set('America/Mexico_City');

// Cabeceras de seguridad.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header_remove('X-Powered-By');
}

// Sesion segura

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Cierra la sesion si hubo demasiado tiempo de inactividad.
    if (isset($_SESSION['ultima_actividad'])
        && (time() - (int) $_SESSION['ultima_actividad']) > TIEMPO_INACTIVIDAD) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['mensajes'][] = [
            'tipo'  => 'warning',
            'texto' => 'Tu sesión expiró por inactividad. Inicia sesión de nuevo.',
        ];
    }
    $_SESSION['ultima_actividad'] = time();
}

// Escape de datos para mostrarlos en HTML
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Redireccion

function redirigir(string $ruta): void
{
    // Si hay una conexion abierta, se cierra antes de salir.
    if (function_exists('cerrar_conexion')) {
        cerrar_conexion();
    }
    header('Location: ' . BASE_URL . ltrim($ruta, '/'));
    exit;
}

// Mensajes temporales (se muestran una sola vez)
// Tipos: success, danger, warning, info
function agregar_mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensajes'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function obtener_mensajes(): array
{
    $mensajes = $_SESSION['mensajes'] ?? [];
    unset($_SESSION['mensajes']);
    return $mensajes;
}

// Proteccion CSRF
function token_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Devuelve el campo oculto que debe ir dentro de cada formulario.
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(token_csrf()) . '">';
}

function validar_token_csrf($token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Se llama al inicio de create.php, update.php y delete.php.
function exigir_post_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Metodo no permitido.');
    }
    if (!validar_token_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Solicitud no valida. Regresa e intentalo de nuevo.');
    }
}

// Lectura segura de datos enviados

// Si el valor no existe o no es texto.
function texto_enviado(array $datos, string $campo): string
{
    return (isset($datos[$campo]) && is_string($datos[$campo])) ? trim($datos[$campo]) : '';
}

// Validaciones del servidor

// Importe positivo con hasta 2 decimales (punto decimal).
// Devuelve el importe como texto.
function validar_importe($valor): ?string
{
    $valor = trim((string) $valor);
    if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $valor)) {
        return null;
    }
    if ((float) $valor <= 0) {
        return null;
    }
    return $valor;
}

// Fecha real en formato AAAA-MM-DD.
// Devuelve la fecha como texto.
function validar_fecha($valor): ?string
{
    $valor = trim((string) $valor);
    $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
    if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
        return null;
    }
    return $valor;
}

// Formato de dinero a mostrar. (Si tienes)

function formato_dinero($valor): string
{
    $numero = (float) $valor;
    $signo = $numero < 0 ? '-' : '';
    return $signo . '$' . number_format(abs($numero), 2, '.', ',');
}

// La sesion se inicia al cargar este archivo.
iniciar_sesion_segura();