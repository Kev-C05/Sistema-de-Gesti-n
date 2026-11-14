<?php

// Reportes del usuario autenticado: balance del periodo, comparacion
// mensual, gastos por categoria e ingresos por categoria.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modules/reportes/read.php';

requerir_login();

$usuario_id = usuario_actual_id();

$periodo = leer_periodo_reporte($_GET);
$desde = $periodo['desde'];
$hasta = $periodo['hasta'];

$totales = totales_periodo($usuario_id, $desde, $hasta);
$mensual = resumen_mensual($usuario_id, $desde, $hasta);
$gastos_categoria = total_por_categoria($usuario_id, 'gasto', $desde, $hasta);
$ingresos_categoria = total_por_categoria($usuario_id, 'ingreso', $desde, $hasta);

// Datos para la grafica mensual.
$etiquetas_mes = [];
$valores_ingresos = [];
$valores_gastos = [];
$valores_balance = [];
foreach ($mensual as $fila) {
    $etiquetas_mes[] = etiqueta_periodo($fila['periodo']);
    $valores_ingresos[] = (float) $fila['ingresos'];
    $valores_gastos[] = (float) $fila['gastos'];
    $valores_balance[] = (float) $fila['balance'];
}

// Datos para las graficas por categoria.
$etiquetas_gastos = [];
$valores_gastos_cat = [];
foreach ($gastos_categoria as $fila) {
    $etiquetas_gastos[] = $fila['nombre'];
    $valores_gastos_cat[] = (float) $fila['total'];
}
$etiquetas_ingresos = [];
$valores_ingresos_cat = [];
foreach ($ingresos_categoria as $fila) {
    $etiquetas_ingresos[] = $fila['nombre'];
    $valores_ingresos_cat[] = (float) $fila['total'];
}

$total_ingresos = (float) $totales['ingresos'];
$total_gastos = (float) $totales['gastos'];
$clase_balance = (float) $totales['balance'] >= 0 ? 'text-success' : 'text-danger';

// Enlaces al historial de movimientos con los mismos filtros.
$url_gastos = BASE_URL . 'movimientos.php?' . http_build_query(['tipo' => 'gasto', 'desde' => $desde, 'hasta' => $hasta]);
$url_ingresos = BASE_URL . 'movimientos.php?' . http_build_query(['tipo' => 'ingreso', 'desde' => $desde, 'hasta' => $hasta]);

$titulo_pagina = 'Reportes';
$pagina_activa = 'reportes';
$scripts_extra = ['assets/js/reportes.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4">Reportes</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(BASE_URL) ?>reportes.php" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label for="desde" class="form-label">Desde</label>
                <input type="date" class="form-control" id="desde" name="desde" value="<?= e($desde) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label for="hasta" class="form-label">Hasta</label>
                <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($hasta) ?>">
            </div>
            <div class="col-12 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Generar reporte</button>
                <a href="<?= e(BASE_URL) ?>reportes.php" class="btn btn-outline-secondary">Últimos 6 meses</a>
            </div>
        </form>
    </div>
</div>

<h2 class="h6 text-muted text-uppercase">
    Balance del periodo:
    <?= e(date('d/m/Y', strtotime($desde))) ?> – <?= e(date('d/m/Y', strtotime($hasta))) ?>
</h2>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ingresos</div>
                <div class="fs-3 fw-semibold text-success"><?= e(formato_dinero($totales['ingresos'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Gastos</div>
                <div class="fs-3 fw-semibold text-danger"><?= e(formato_dinero($totales['gastos'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Balance</div>
                <div class="fs-3 fw-semibold <?= $clase_balance ?>"><?= e(formato_dinero($totales['balance'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3">Comparación mensual</h2>

        <?php if (empty($mensual)): ?>
            <p class="text-muted mb-0">No hay movimientos en este periodo.</p>
        <?php else: ?>
            <div class="ratio ratio-21x9 mb-3">
                <canvas id="grafica-mensual"
                        data-etiquetas="<?= e(json_encode($etiquetas_mes, JSON_UNESCAPED_UNICODE)) ?>"
                        data-ingresos="<?= e(json_encode($valores_ingresos)) ?>"
                        data-gastos="<?= e(json_encode($valores_gastos)) ?>"
                        data-balance="<?= e(json_encode($valores_balance)) ?>"></canvas>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th class="text-end">Ingresos</th>
                            <th class="text-end">Gastos</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensual as $fila): ?>
                            <tr>
                                <td><?= e(etiqueta_periodo($fila['periodo'])) ?></td>
                                <td class="text-end text-success"><?= e(formato_dinero($fila['ingresos'])) ?></td>
                                <td class="text-end text-danger"><?= e(formato_dinero($fila['gastos'])) ?></td>
                                <td class="text-end <?= (float) $fila['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= e(formato_dinero($fila['balance'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Reporte de gastos por categoría</h2>
                    <a href="<?= e($url_gastos) ?>" class="small">Ver movimientos</a>
                </div>

                <?php if (empty($gastos_categoria)): ?>
                    <p class="text-muted mb-0">No hay gastos en este periodo.</p>
                <?php else: ?>
                    <div class="ratio ratio-4x3 mb-3">
                        <canvas id="grafica-gastos-categoria"
                                data-etiquetas="<?= e(json_encode($etiquetas_gastos, JSON_UNESCAPED_UNICODE)) ?>"
                                data-valores="<?= e(json_encode($valores_gastos_cat)) ?>"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th class="text-end">Movs.</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gastos_categoria as $fila): ?>
                                    <?php $porcentaje = $total_gastos > 0 ? round((float) $fila['total'] / $total_gastos * 100, 1) : 0; ?>
                                    <tr>
                                        <td><?= e($fila['nombre']) ?></td>
                                        <td class="text-end"><?= (int) $fila['cantidad'] ?></td>
                                        <td class="text-end"><?= e(formato_dinero($fila['total'])) ?></td>
                                        <td class="text-end"><?= e($porcentaje) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Reporte de ingresos por categoría</h2>
                    <a href="<?= e($url_ingresos) ?>" class="small">Ver movimientos</a>
                </div>

                <?php if (empty($ingresos_categoria)): ?>
                    <p class="text-muted mb-0">No hay ingresos en este periodo.</p>
                <?php else: ?>
                    <div class="ratio ratio-4x3 mb-3">
                        <canvas id="grafica-ingresos-categoria"
                                data-etiquetas="<?= e(json_encode($etiquetas_ingresos, JSON_UNESCAPED_UNICODE)) ?>"
                                data-valores="<?= e(json_encode($valores_ingresos_cat)) ?>"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th class="text-end">Movs.</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ingresos_categoria as $fila): ?>
                                    <?php $porcentaje = $total_ingresos > 0 ? round((float) $fila['total'] / $total_ingresos * 100, 1) : 0; ?>
                                    <tr>
                                        <td><?= e($fila['nombre']) ?></td>
                                        <td class="text-end"><?= (int) $fila['cantidad'] ?></td>
                                        <td class="text-end"><?= e(formato_dinero($fila['total'])) ?></td>
                                        <td class="text-end"><?= e($porcentaje) ?>%</td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>