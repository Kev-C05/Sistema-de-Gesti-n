<?php
// Lista de categorias y formulario para crear o editar.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modules/categorias/read.php';

requerir_login();

$usuario_id = usuario_actual_id();

// Modo edicion
$editando = null;
if (isset($_GET['editar'])) {
    $id_editar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $editando = $id_editar ? obtener_categoria($id_editar, $usuario_id) : null;

    if ($editando === null) {
        agregar_mensaje('danger', 'Categoría no encontrada.');
        redirigir('categorias.php');
    }
}

$categorias = listar_categorias($usuario_id);

$titulo_pagina = 'Categorías';
$pagina_activa = 'categorias';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4">Categorías</h1>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></h2>

                <?php if ($editando): ?>
                    <form method="post" action="<?= e(BASE_URL) ?>modules/categorias/update.php"
                          class="needs-validation" novalidate>
                        <?= campo_csrf() ?>
                        <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   value="<?= e($editando['nombre']) ?>" maxlength="80" required>
                            <div class="invalid-feedback">Escribe un nombre.</div>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" disabled>
                                <option><?= e(ucfirst($editando['tipo'])) ?></option>
                            </select>
                            <div class="form-text">El tipo no se puede cambiar.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a href="<?= e(BASE_URL) ?>categorias.php" class="btn btn-outline-secondary">Cancelar</a>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e(BASE_URL) ?>modules/categorias/create.php"
                          class="needs-validation" novalidate>
                        <?= campo_csrf() ?>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   maxlength="80" required>
                            <div class="invalid-feedback">Escribe un nombre.</div>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecciona...</option>
                                <option value="gasto">Gasto</option>
                                <option value="ingreso">Ingreso</option>
                            </select>
                            <div class="invalid-feedback">Selecciona un tipo.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Crear categoría</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3">Mis categorías</h2>

                <?php if (empty($categorias)): ?>
                    <p class="text-muted mb-0">Aún no tienes categorías.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Usos</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $fila): ?>
                                    <tr>
                                        <td><?= e($fila['nombre']) ?></td>
                                        <td>
                                            <span class="badge <?= $fila['tipo'] === 'gasto' ? 'text-bg-danger' : 'text-bg-success' ?>">
                                                <?= e(ucfirst($fila['tipo'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= (int) $fila['usos'] ?></td>
                                        <td class="text-end text-nowrap">
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="<?= e(BASE_URL) ?>categorias.php?editar=<?= (int) $fila['id'] ?>">Editar</a>

                                            <form method="post" action="<?= e(BASE_URL) ?>modules/categorias/delete.php"
                                                  class="d-inline"
                                                  data-confirmar="<?= e('¿Eliminar la categoría "' . $fila['nombre'] . '"?') ?>">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    <?= (int) $fila['usos'] > 0 ? 'disabled title="Tiene registros asociados"' : '' ?>>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>