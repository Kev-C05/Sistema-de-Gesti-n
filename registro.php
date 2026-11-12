<?php
// Aqui se registra el usuario y si no quiere no la usa.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/usuarios.php';

// Si ya hay sesion, no tiene sentido registrarse pues para que de nuevo.
redirigir_si_autenticado();

$errores = [];
$nombre = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_csrf();

    $nombre = trim($_POST['nombre'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['password_confirmar'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errores[] = 'Escribe tu nombre (máximo 100 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errores[] = 'Escribe un correo electrónico válido.';
    }
    if (mb_strlen($password) < 8 || strlen($password) > 72) {
        $errores[] = 'La contraseña debe tener entre 8 y 72 caracteres.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        try {
            if (buscar_usuario_por_email($email) !== null) {
                $errores[] = 'Ya existe una cuenta con ese correo electrónico.';
            } else {
                crear_usuario($nombre, $email, $password);
                agregar_mensaje('success', 'Cuenta creada correctamente. Ahora puedes iniciar sesión.');
                redirigir('login.php');
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                // Correo duplicado detectado por la restriccion UNIQUE.
                $errores[] = 'Ya existe una cuenta con ese correo electrónico.';
            } else {
                error_log('FinanTrack - error al registrar usuario: ' . $e->getMessage());
                $errores[] = 'No fue posible crear la cuenta. Inténtalo de nuevo.';
            }
        }
    }
}

$titulo_pagina = 'Crear cuenta';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="tarjeta-auth">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Crear cuenta</h1>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(BASE_URL) ?>registro.php" class="needs-validation" novalidate>
                <?= campo_csrf() ?>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           value="<?= e($nombre) ?>" maxlength="100" required>
                    <div class="invalid-feedback">Escribe tu nombre.</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($email) ?>" maxlength="150" required>
                    <div class="invalid-feedback">Escribe un correo válido.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password"
                           minlength="8" maxlength="72" required>
                    <div class="invalid-feedback">Mínimo 8 caracteres.</div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmar" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="password_confirmar"
                           name="password_confirmar" required>
                    <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Registrarme</button>
            </form>

            <p class="mt-3 mb-0 text-center">
                ¿Ya tienes cuenta? <a href="<?= e(BASE_URL) ?>login.php">Iniciar sesión</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>