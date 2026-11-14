<?php
// Presupuestos mensuales: formulario, selector de mes, resumen y tabla.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modules/categorias/read.php';
require_once __DIR__ . '/modules/presupuestos/read.php';
require_once __DIR__ . '/modules/presupuestos/validar.php';

requerir_login();

$usuario_id = usuario_actual_id();
$meses = nombres_meses();

// Mes que se esta viendo (por defecto, el actual).
$mes_sel = filter_var(
    valor_texto($_GET, 'mes'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 12]]
);
$anio_sel = filter_var(
    valor_texto($_GET, 'anio'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 2000, 'max_range' => 2100]]
);
$mes_sel = $mes_sel !== false ? $mes_sel : (int) date('n');
$anio_sel = $anio_sel !== false ? $anio_sel : (int) date('Y');
$mes_anio_sel = sprintf('%04d-%02d-01', $anio_sel, $mes_sel);

// Modo edicion (solo si es del usuario).
$editando = null;
if (isset($_GET['editar'])) {
    $id_editar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $editando = $id_editar ? obtener_presupuesto($id_editar, $usuario_id) : null;

    if ($editando === null) {
        agregar_mensaje('danger', 'Presupuesto no encontrado.');
        redirigir('presupuestos.php');
    }
}

// Valores del formulario: lo reenviado tras un error, o lo guardado, o los valores por defecto.
$antiguos = $_SESSION['form_presupuesto'] ?? [];
unset($_SESSION['form_presupuesto']);

$form = [
    'categoria_id' => $antiguos['categoria_id'] ?? ($editando['categoria_id'] ?? ''),
    'mes'          => $antiguos['mes'] ?? ($editando ? (int) substr($editando['mes_anio'], 5, 2) : $mes_sel),
    'anio'         => $antiguos['anio'] ?? ($editando ? (int) substr($editando['mes_anio'], 0, 4) : $anio_sel),
    'monto_limite' => $antiguos['monto_limite'] ?? ($editando['monto_limite'] ?? ''),
];

$categorias_gasto = listar_categorias($usuario_id, 'gasto');

$presupuestos = listar_presupuestos_mes($usuario_id, $mes_anio_sel);
$totales = totales_presupuestos_mes($usuario_id, $mes_anio_sel);

$excedidos = 0;
foreach ($presupuestos as $fila) {
    if ((float) $fila['disponible'] < 0) {
        $excedidos++;
    }
}

$url_vista = BASE_URL . 'presupuestos.php?mes=' . $mes_sel . '&anio=' . $anio_sel;
$clase_disponible = (float) $totales['disponible'] >= 0 ? 'text-success' : 'text-danger';

$titulo_pagina = 'Presupuestos';
$pagina_activa = 'presupuestos';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4">Presupuestos mensuales</h1>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $editando ? 'Editar presupuesto' : 'Nuevo presupuesto' ?></h2>

        <?php if (empty($categorias_gasto)): ?>
            <div class="alert alert-warning mb-0">
                Necesitas al menos una categoría de gasto.
                <a href="<?= e(BASE_URL) ?>categorias.php">Crear categoría</a>
            </div>
        <?php else: ?>
            <form method="post"
                  action="<?= e(BASE_URL) ?>modules/presupuestos/<?= $editando ? 'update.php' : 'create.php' ?>"
                  class="needs-validation" novalidate>
                <?= campo_csrf() ?>
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label for="categoria_id" class="form-label">Categoría de gasto</label>
                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($categorias_gasto as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>"
                                    <?= (int) $form['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Selecciona una categoría.</div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="mes" class="form-label">Mes</label>
                        <select class="form-select" id="mes" name="mes" required>
                            <?php foreach ($meses as $numero => $nombre_mes): ?>
                                <option value="<?= (int) $numero ?>"
                                    <?= (int) $form['mes'] === $numero ? 'selected' : '' ?>>
                                    <?= e($nombre_mes) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label for="anio" class="form-label">Año</label>
                        <input type="number" class="form-control" id="anio" name="anio"
                               value="<?= e($form['anio']) ?>" min="2000" max="2100" required>
                        <div class="invalid-feedback">Año entre 2000 y 2100.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="monto_limite" class="form-label">Monto límite</label>
                        <input type="number" class="form-control" id="monto_limite" name="monto_limite"
                               value="<?= e($form['monto_limite']) ?>" step="0.01" min="0.01"
                               max="9999999999.99" required>
                        <div class="invalid-feedback">Escribe un monto mayor que cero.</div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <?= $editando ? 'Guardar cambios' : 'Crear presupuesto' ?>
                    </button>
                    <?php if ($editando): ?>
                        <a href="<?= e($url_vista) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="<?= e(BASE_URL) ?>presupuestos.php" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="ver_mes" class="form-label">Ver mes</label>
                <select class="form-select" id="ver_mes" name="mes">
                    <?php foreach ($meses as $numero => $nombre_mes): ?>
                        <option value="<?= (int) $numero ?>" <?= $mes_sel === $numero ? 'selected' : '' ?>>
                            <?= e($nombre_mes) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="ver_anio" class="form-label">Año</label>
                <input type="number" class="form-control" id="ver_anio" name="anio"
                       value="<?= (int) $anio_sel ?>" min="2000" max="2100">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-outline-primary">Ver</button>
            </div>
        </form>
    </div>
</div>

<h2 class="h6 text-muted text-uppercase">
    Resumen de <?= e($meses[$mes_sel]) ?> <?= (int) $anio_sel ?>
</h2>

<?php if ($excedidos > 0): ?>
    <div class="alert alert-danger" role="alert">
        Superaste el presupuesto en <?= (int) $excedidos ?> categoría(s) este mes.
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Presupuestado</div>
                <div class="fs-4 fw-semibold"><?= e(formato_dinero($totales['presupuestado'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Gastado</div>
                <div class="fs-4 fw-semibold text-danger"><?= e(formato_dinero($totales['gastado'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Disponible</div>
                <div class="fs-4 fw-semibold <?= $clase_disponible ?>"><?= e(formato_dinero($totales['disponible'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($presupuestos)): ?>
            <p class="text-muted mb-0">No hay presupuestos para este mes.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th class="text-end">Presupuesto</th>
                            <th class="text-end">Gastado</th>
                            <th class="text-end">Disponible</th>
                            <th style="min-width: 130px;">Avance</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presupuestos as $fila): ?>
                            <?php
                            $limite = (float) $fila['monto_limite'];
                            $gastado = (float) $fila['gastado'];
                            $porcentaje = $limite > 0 ? ($gastado / $limite) * 100 : 0;
                            $ancho = (int) min(100, round($porcentaje));
                            $excedido = (float) $fila['disponible'] < 0;

                            if ($excedido) {
                                $clase_barra = 'bg-danger';
                                $clase_estado = 'text-bg-danger';
                                $texto_estado = 'Excedido';
                            } elseif ($porcentaje >= 80) {
                                $clase_barra = 'bg-warning';
                                $clase_estado = 'text-bg-warning';
                                $texto_estado = 'Cerca del límite';
                            } else {
                                $clase_barra = 'bg-success';
                                $clase_estado = 'text-bg-success';
                                $texto_estado = 'Dentro del límite';
                            }
                            ?>
                            <tr>
                                <td><?= e($fila['categoria']) ?></td>
                                <td class="text-end"><?= e(formato_dinero($fila['monto_limite'])) ?></td>
                                <td class="text-end"><?= e(formato_dinero($fila['gastado'])) ?></td>
                                <td class="text-end <?= $excedido ? 'text-danger' : '' ?>">
                                    <?= e(formato_dinero($fila['disponible'])) ?>
                                </td>
                                <td>
                                    <div class="progress" role="progressbar" style="height: 8px;"
                                         aria-valuenow="<?= $ancho ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar <?= $clase_barra ?>" style="width: <?= $ancho ?>%"></div>
                                    </div>
                                    <div class="small text-muted"><?= (int) round($porcentaje) ?>%</div>
                                </td>
                                <td><span class="badge <?= $clase_estado ?>"><?= e($texto_estado) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?= e($url_vista . '&editar=' . (int) $fila['id']) ?>">Editar</a>

                                    <form method="post" action="<?= e(BASE_URL) ?>modules/presupuestos/delete.php"
                                          class="d-inline"
                                          data-confirmar="<?= e('¿Eliminar el presupuesto de "' . $fila['categoria'] . '" de ' . $meses[$mes_sel] . ' ' . $anio_sel . '?') ?>">
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>