<?php
// Barra de navegacion

$pagina_activa = $pagina_activa ?? '';

$enlaces_menu = [
    'dashboard'    => ['Dashboard', 'dashboard.php'],
    'movimientos'  => ['Movimientos', 'movimientos.php'],
    'categorias'   => ['Categorías', 'categorias.php'],
    'presupuestos' => ['Presupuestos', 'presupuestos.php'],
    'objetivos'    => ['Objetivos', 'objetivos.php'],
    'reportes'     => ['Reportes', 'reportes.php'],
];
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= e(BASE_URL) ?>dashboard.php">FinanTrack</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menu-principal" aria-controls="menu-principal"
                aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu-principal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($enlaces_menu as $clave => $enlace): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $clave === $pagina_activa ? ' active' : '' ?>"
                           href="<?= e(BASE_URL . $enlace[1]) ?>"><?= e($enlace[0]) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form method="post" action="<?= e(BASE_URL) ?>logout.php" class="d-flex align-items-center gap-2">
                <?= campo_csrf() ?>
                <span class="navbar-text"><?= e(usuario_actual_nombre()) ?></span>
                <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>