<?php
// Resumen financiero del usuario autenticado.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/resumen.php';

requerir_login();

$usuario_id = usuario_actual_id();

$general = totales_movimientos($usuario_id);
$mes = totales_movimientos($usuario_id, true);
$categorias = gastos_por_categoria_mes($usuario_id);

// Datos para la grafica y la tabla de categorias.
$etiquetas = [];
$valores = [];
foreach ($categorias as $fila) {
    $etiquetas[] = $fila['nombre'];
    $valores[] = (float) $fila['total'];
}
$gastos_mes = (float) $mes['gastos'];

$clase_balance = (float) $general['balance'] >= 0 ? 'text-success' : 'text-danger';
$clase_balance_mes = (float) $mes['balance'] >= 0 ? 'text-success' : 'text-danger';

$titulo_pagina = 'Dashboard';
$pagina_activa = 'dashboard';
$scripts_extra = ['assets/js/dashboard.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4">Dashboard</h1>

<h2 class="h6 text-muted text-uppercase">Totales generales</h2>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Balance general</div>
                <div class="fs-3 fw-semibold <?= $clase_balance ?>"><?= e(formato_dinero($general['balance'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Total de ingresos</div>
                <div class="fs-3 fw-semibold text-success"><?= e(formato_dinero($general['ingresos'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Total de gastos</div>
                <div class="fs-3 fw-semibold text-danger"><?= e(formato_dinero($general['gastos'])) ?></div>
            </div>
        </div>
    </div>
</div>

<h2 class="h6 text-muted text-uppercase">Resumen del mes actual</h2>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Balance del mes</div>
                <div class="fs-4 fw-semibold <?= $clase_balance_mes ?>"><?= e(formato_dinero($mes['balance'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Ingresos del mes</div>
                <div class="fs-4 fw-semibold text-success"><?= e(formato_dinero($mes['ingresos'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Gastos del mes</div>
                <div class="fs-4 fw-semibold text-danger"><?= e(formato_dinero($mes['gastos'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Gastos del mes por categoría</h2>
                <?php if (empty($categorias)): ?>
                    <p class="text-muted mb-0">Aún no hay gastos registrados este mes.</p>
                <?php else: ?>
                    <div class="ratio ratio-4x3 mb-3">
                        <canvas id="grafica-categorias"
                                data-etiquetas="<?= e(json_encode($etiquetas, JSON_UNESCAPED_UNICODE)) ?>"
                                data-valores="<?= e(json_encode($valores)) ?>"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $fila): ?>
                                    <?php $porcentaje = $gastos_mes > 0 ? round((float) $fila['total'] / $gastos_mes * 100, 1) : 0; ?>
                                    <tr>
                                        <td><?= e($fila['nombre']) ?></td>
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
                <h2 class="h6 mb-3">Ingresos vs. gastos del mes</h2>
                <div class="ratio ratio-4x3">
                    <canvas id="grafica-comparacion"
                            data-valores="<?= e(json_encode([(float) $mes['ingresos'], (float) $mes['gastos']])) ?>"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>