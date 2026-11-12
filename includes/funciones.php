<?php

// Ruta base de esta wea
define('BASE_URL', '/FinanTrack/');

// Sesion segura y si no balasos 

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}


// Escape de datos, como ladron para mostrarlos en HTML

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Redireccion

function redirigir(string $ruta): void
{
    // Si hay una conexion abierta, se cierra la puerta antes de salir.
    if (function_exists('cerrar_conexion')) {
        cerrar_conexion();
    }
    header('Location: ' . BASE_URL . ltrim($ruta, '/'));
    exit;
}

// Mensajes temporales (se deberían de quitar despues)

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

// Proteccion 

function token_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
// Devuelve un campo escondido, uuuuuh.
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

// Valiendo si no valida
// Devuelve el importe como texto, deberían de ser dos máximo.
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

// Devuelve la fecha como texto. O yo que se
function validar_fecha($valor): ?string
{
    $valor = trim((string) $valor);
    $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
    if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
        return null;
    }
    return $valor;
}

// La sesion se inicia
iniciar_sesion_segura();