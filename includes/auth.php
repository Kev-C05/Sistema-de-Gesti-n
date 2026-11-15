<?php
// Control de autenticación.

require_once __DIR__ . '/funciones.php';

function usuario_autenticado(): bool
{
    return isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
}

// para filtrar por usuario_id.
function usuario_actual_id(): int
{
    return (int) ($_SESSION['usuario_id'] ?? 0);
}

function usuario_actual_nombre(): string
{
    return (string) ($_SESSION['usuario_nombre'] ?? '');
}

// Se llama al inicio de cada pagina protegida.
function requerir_login(): void
{
    if (!usuario_autenticado()) {
        agregar_mensaje('danger', 'Debes iniciar sesion para continuar.');
        redirigir('login.php');
    }
}

// Si ya hay sesion, manda al dashboard.
function redirigir_si_autenticado(): void
{
    if (usuario_autenticado()) {
        redirigir('dashboard.php');
    }
}

// Inicia la sesion del usuario tras un login correcto.
function iniciar_sesion_usuario(int $id, string $nombre): void
{
    // Nuevo id de sesion para evitar la fijacion de sesion.
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['usuario_id'] = $id;
    $_SESSION['usuario_nombre'] = $nombre;
}

// Cierra la sesion por completo.
function cerrar_sesion_usuario(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}