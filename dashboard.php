<?php
// Pagina privada minimo para comprobar el inicio de sesion.

require_once __DIR__ . '/includes/auth.php';

requerir_login();

$titulo_pagina = 'Panel';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Hola, <?= e(usuario_actual_nombre()) ?></h1>

    <form method="post" action="<?= e(BASE_URL) ?>logout.php">
        <?= campo_csrf() ?>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Cerrar sesión</button>
    </form>
</div>

<p>Sesión activa. Tu id de usuario es: <?= usuario_actual_id() ?></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>