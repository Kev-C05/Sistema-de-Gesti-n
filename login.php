<?php

// Formulario y verificacion de inicio de sesion, con limite de intentos.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/usuarios.php';

// Si ya hay sesion, se va directo al dashboard.
redirigir_si_autenticado();

$errores = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_csrf();

    $email = strtolower(texto_enviado($_POST, 'email'));
    $password = (isset($_POST['password']) && is_string($_POST['password'])) ? $_POST['password'] : '';
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    if ($email === '' || $password === '') {
        $errores[] = 'Escribe tu correo y tu contraseña.';
    } else {
        try {
            $clave_email = mb_substr($email, 0, 150);

            if (intentos_fallidos_recientes($clave_email, $ip) >= LOGIN_MAX_INTENTOS) {
                // Bloqueado temporalmente: no se verifica la contrasena.
                $errores[] = 'Demasiados intentos fallidos. Espera '
                    . LOGIN_MINUTOS_BLOQUEO . ' minutos e inténtalo de nuevo.';
            } else {
                $usuario = buscar_usuario_por_email($email);

                if ($usuario !== null && password_verify($password, $usuario['password_hash'])) {
                    limpiar_intentos_login($clave_email, $ip);
                    iniciar_sesion_usuario((int) $usuario['id'], $usuario['nombre']);
                    redirigir('dashboard.php');
                }

                // Mismo mensaje si el correo no existe o la contrasena es incorrecta.
                registrar_intento_fallido($clave_email, $ip);
                $errores[] = 'Correo o contraseña incorrectos.';
            }
        } catch (mysqli_sql_exception $e) {
            error_log('FinanTrack - error al iniciar sesion: ' . $e->getMessage());
            $errores[] = 'No fue posible iniciar sesión. Inténtalo de nuevo.';
        }
    }
}

$titulo_pagina = 'Iniciar sesión';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="tarjeta-auth">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Iniciar sesión</h1>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(BASE_URL) ?>login.php" class="needs-validation" novalidate>
                <?= campo_csrf() ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($email) ?>" maxlength="150" required>
                    <div class="invalid-feedback">Escribe un correo válido.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">Escribe tu contraseña.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>

            <p class="mt-3 mb-0 text-center">
                ¿No tienes cuenta? <a href="<?= e(BASE_URL) ?>registro.php">Crear cuenta</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>