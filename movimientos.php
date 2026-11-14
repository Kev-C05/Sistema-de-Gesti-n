<?php
// Formulario para registrar/editar movimientos e historial con filtros.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modules/categorias/read.php';
require_once __DIR__ . '/modules/movimientos/read.php';
require_once __DIR__ . '/modules/movimientos/validar.php';

requerir_login();

$usuario_id = usuario_actual_id();
$por_pagina = 10;

$filtros = leer_filtros_movimientos($_GET);

// Modo edicion (solo si el movimiento es del usuario).
$editando = null;
if (isset($_GET['editar'])) {
    $id_editar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $editando = $id_editar ? obtener_movimiento($id_editar, $usuario_id) : null;

    if ($editando === null) {
        agregar_mensaje('danger', 'Movimiento no encontrado.');
        redirigir('movimientos.php');
    }
}

// Valores del formulario: lo reenviado tras un error, o lo guardado, o los valores por defecto.
$antiguos = $_SESSION['form_movimiento'] ?? [];
unset($_SESSION['form_movimiento']);

$form = [
    'categoria_id'  => $antiguos['categoria_id']  ?? ($editando['categoria_id'] ?? ''),
    'descripcion'   => $antiguos['descripcion']   ?? ($editando['descripcion'] ?? ''),
    'importe'       => $antiguos['importe']       ?? ($editando['importe'] ?? ''),
    'fecha'         => $antiguos['fecha']         ?? ($editando['fecha'] ?? date('Y-m-d')),
    'metodo_pago'   => $antiguos['metodo_pago']   ?? ($editando['metodo_pago'] ?? ''),
    'observaciones' => $antiguos['observaciones'] ?? ($editando['observaciones'] ?? ''),
];

$categorias_gasto = listar_categorias($usuario_id, 'gasto');
$categorias_ingreso = listar_categorias($usuario_id, 'ingreso');
$sin_categorias = empty($categorias_gasto) && empty($categorias_ingreso);

// Historial: conteo, pagina valida, totales y filas.
$total = contar_movimientos($usuario_id, $filtros);
$total_paginas = max(1, (int) ceil($total / $por_pagina));
if ($filtros['pagina'] > $total_paginas) {
    $filtros['pagina'] = $total_paginas;
}

$totales = totales_movimientos_filtrados($usuario_id, $filtros);
$movimientos = listar_movimientos($usuario_id, $filtros, $por_pagina);

$hay_filtros = $filtros['q'] !== '' || $filtros['tipo'] !== '' || $filtros['categoria_id'] > 0
    || $filtros['desde'] !== '' || $filtros['hasta'] !== '';
$primero = $total > 0 ? ($filtros['pagina'] - 1) * $por_pagina + 1 : 0;
$ultimo = min($total, $filtros['pagina'] * $por_pagina);
$clase_balance = (float) $totales['balance'] >= 0 ? 'text-success' : 'text-danger';

$titulo_pagina = 'Movimientos';
$pagina_activa = 'movimientos';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4">Ingresos y gastos</h1>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $editando ? 'Editar movimiento' : 'Nuevo movimiento' ?></h2>

        <?php if ($sin_categorias): ?>
            <div class="alert alert-warning mb-0">
                Primero necesitas una categoría.
                <a href="<?= e(BASE_URL) ?>categorias.php">Crear categoría</a>
            </div>
        <?php else: ?>
            <form method="post"
                  action="<?= e(BASE_URL) ?>modules/movimientos/<?= $editando ? 'update.php' : 'create.php' ?>"
                  class="needs-validation" novalidate>
                <?= campo_csrf() ?>
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="categoria_id" class="form-label">Categoría</label>
                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                            <option value="">Selecciona...</option>
                            <?php if (!empty($categorias_gasto)): ?>
                                <optgroup label="Gastos">
                                    <?php foreach ($categorias_gasto as $cat): ?>
                                        <option value="<?= (int) $cat['id'] ?>"
                                            <?= (int) $form['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                            <?= e($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($categorias_ingreso)): ?>
                                <optgroup label="Ingresos">
                                    <?php foreach ($categorias_ingreso as $cat): ?>
                                        <option value="<?= (int) $cat['id'] ?>"
                                            <?= (int) $form['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                            <?= e($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">Selecciona una categoría.</div>
                        <div class="form-text">El tipo (gasto o ingreso) lo define la categoría.</div>
                    </div>

                    <div class="col-12 col-md-8">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion"
                               value="<?= e($form['descripcion']) ?>" maxlength="150" required>
                        <div class="invalid-feedback">Escribe una descripción.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="importe" class="form-label">Importe</label>
                        <input type="number" class="form-control" id="importe" name="importe"
                               value="<?= e($form['importe']) ?>" step="0.01" min="0.01"
                               max="9999999999.99" required>
                        <div class="invalid-feedback">Escribe un importe mayor que cero.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha"
                               value="<?= e($form['fecha']) ?>" required>
                        <div class="invalid-feedback">Selecciona una fecha.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="metodo_pago" class="form-label">Método de pago <span class="text-muted">(opcional)</span></label>
                        <select class="form-select" id="metodo_pago" name="metodo_pago">
                            <option value="">Sin especificar</option>
                            <?php foreach (metodos_pago() as $metodo): ?>
                                <option value="<?= e($metodo) ?>"
                                    <?= $form['metodo_pago'] === $metodo ? 'selected' : '' ?>>
                                    <?= e($metodo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="observaciones" class="form-label">Observaciones <span class="text-muted">(opcional)</span></label>
                        <input type="text" class="form-control" id="observaciones" name="observaciones"
                               value="<?= e($form['observaciones']) ?>" maxlength="255">
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <?= $editando ? 'Guardar cambios' : 'Registrar movimiento' ?>
                    </button>
                    <?php if ($editando): ?>
                        <a href="<?= e(url_movimientos($filtros)) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Historial y filtros</h2>

        <form method="get" action="<?= e(BASE_URL) ?>movimientos.php">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="filtro_q" class="form-label">Buscar en la descripción</label>
                    <input type="text" class="form-control" id="filtro_q" name="q"
                           value="<?= e($filtros['q']) ?>" maxlength="100">
                </div>

                <div class="col-6 col-md-2">
                    <label for="filtro_tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="filtro_tipo" name="tipo">
                        <option value="">Todos</option>
                        <option value="gasto" <?= $filtros['tipo'] === 'gasto' ? 'selected' : '' ?>>Gastos</option>
                        <option value="ingreso" <?= $filtros['tipo'] === 'ingreso' ? 'selected' : '' ?>>Ingresos</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="filtro_categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="filtro_categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php if (!empty($categorias_gasto)): ?>
                            <optgroup label="Gastos">
                                <?php foreach ($categorias_gasto as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>"
                                        <?= $filtros['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($categorias_ingreso)): ?>
                            <optgroup label="Ingresos">
                                <?php foreach ($categorias_ingreso as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>"
                                        <?= $filtros['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="filtro_orden" class="form-label">Orden por fecha</label>
                    <select class="form-select" id="filtro_orden" name="orden">
                        <option value="desc" <?= $filtros['orden'] === 'desc' ? 'selected' : '' ?>>Más recientes primero</option>
                        <option value="asc" <?= $filtros['orden'] === 'asc' ? 'selected' : '' ?>>Más antiguos primero</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="filtro_desde" class="form-label">Desde</label>
                    <input type="date" class="form-control" id="filtro_desde" name="desde"
                           value="<?= e($filtros['desde']) ?>">
                </div>

                <div class="col-6 col-md-3">
                    <label for="filtro_hasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="filtro_hasta" name="hasta"
                           value="<?= e($filtros['hasta']) ?>">
                </div>

                <div class="col-12 col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?= e(BASE_URL) ?>movimientos.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<h2 class="h6 text-muted text-uppercase">Balance de los resultados<?= $hay_filtros ? ' filtrados' : '' ?></h2>
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ingresos</div>
                <div class="fs-4 fw-semibold text-success"><?= e(formato_dinero($totales['ingresos'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Gastos</div>
                <div class="fs-4 fw-semibold text-danger"><?= e(formato_dinero($totales['gastos'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Balance</div>
                <div class="fs-4 fw-semibold <?= $clase_balance ?>"><?= e(formato_dinero($totales['balance'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($movimientos)): ?>
            <p class="text-muted mb-0">
                <?= $hay_filtros ? 'No se encontraron movimientos con esos filtros.' : 'Aún no tienes movimientos registrados.' ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Método</th>
                            <th class="text-end">Importe</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $fila): ?>
                            <?php $es_ingreso = $fila['tipo'] === 'ingreso'; ?>
                            <tr>
                                <td class="text-nowrap"><?= e(date('d/m/Y', strtotime($fila['fecha']))) ?></td>
                                <td>
                                    <?= e($fila['descripcion']) ?>
                                    <?php if (!empty($fila['observaciones'])): ?>
                                        <div class="small text-muted"><?= e($fila['observaciones']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $es_ingreso ? 'text-bg-success' : 'text-bg-danger' ?>">
                                        <?= e($fila['categoria']) ?>
                                    </span>
                                </td>
                                <td><?= e($fila['metodo_pago'] ?? '') ?></td>
                                <td class="text-end text-nowrap <?= $es_ingreso ? 'text-success' : 'text-danger' ?>">
                                    <?= $es_ingreso ? '+' : '-' ?><?= e(formato_dinero($fila['importe'])) ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?= e(url_movimientos($filtros, ['editar' => (int) $fila['id']])) ?>">Editar</a>

                                    <form method="post" action="<?= e(BASE_URL) ?>modules/movimientos/delete.php"
                                          class="d-inline"
                                          data-confirmar="<?= e('¿Eliminar el movimiento "' . $fila['descripcion'] . '" por ' . formato_dinero($fila['importe']) . '?') ?>">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2" aria-label="Paginación">
                <span class="small text-muted">
                    Mostrando <?= (int) $primero ?>–<?= (int) $ultimo ?> de <?= (int) $total ?> movimientos
                </span>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $filtros['pagina'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= e(url_movimientos($filtros, ['pagina' => max(1, $filtros['pagina'] - 1)])) ?>">Anterior</a>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">Página <?= (int) $filtros['pagina'] ?> de <?= (int) $total_paginas ?></span>
                    </li>
                    <li class="page-item <?= $filtros['pagina'] >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="<?= e(url_movimientos($filtros, ['pagina' => min($total_paginas, $filtros['pagina'] + 1)])) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>