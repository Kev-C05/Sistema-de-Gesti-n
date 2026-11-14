<?php

// Resumen financiero del usuario autenticado.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/resumen.php';
require_once __DIR__ . '/modules/presupuestos/read.php';
require_once __DIR__ . '/modules/objetivos/read.php';

requerir_login();

$usuario_id = usuario_actual_id();

// Totales y gastos por categoria.
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

// Presupuestos del mes actual.
$mes_actual = date('Y-m-01');
$presupuestos = listar_presupuestos_mes($usuario_id, $mes_actual);
$totales_presupuesto = totales_presupuestos_mes($usuario_id, $mes_actual);

$presupuestos_excedidos = 0;
foreach ($presupuestos as $fila) {
    if ((float) $fila['disponible'] < 0) {
        $presupuestos_excedidos++;
    }
}
$clase_disponible = (float) $totales_presupuesto['disponible'] >= 0 ? 'text-success' : 'text-danger';

// Objetivos de ahorro: primero los pendientes (por fecha limite), luego los completados.
$todos_objetivos = listar_objetivos($usuario_id);
$pendientes = [];
$completados = [];
foreach ($todos_objetivos as $objetivo) {
    if ((float) $objetivo['restante'] > 0) {
        $pendientes[] = $objetivo;
    } else {
        $completados[] = $objetivo;
    }
}
$objetivos_resumen = array_merge($pendientes, $completados);
$total_ahorrado = total_ahorrado_objetivos($usuario_id);

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

<div class="row g-3 mb-4">
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

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Presupuestos del mes</h2>
                    <a href="<?= e(BASE_URL) ?>presupuestos.php" class="small">Ver presupuestos</a>
                </div>

                <?php if (empty($presupuestos)): ?>
                    <p class="text-muted mb-0">No hay presupuestos para este mes.</p>
                <?php else: ?>
                    <?php if ($presupuestos_excedidos > 0): ?>
                        <div class="alert alert-danger py-2" role="alert">
                            Superaste el presupuesto en <?= (int) $presupuestos_excedidos ?> categoría(s).
                        </div>
                    <?php endif; ?>

                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="text-muted small">Presupuestado</div>
                            <div class="fw-semibold"><?= e(formato_dinero($totales_presupuesto['presupuestado'])) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Gastado</div>
                            <div class="fw-semibold text-danger"><?= e(formato_dinero($totales_presupuesto['gastado'])) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Disponible</div>
                            <div class="fw-semibold <?= $clase_disponible ?>"><?= e(formato_dinero($totales_presupuesto['disponible'])) ?></div>
                        </div>
                    </div>

                    <?php foreach (array_slice($presupuestos, 0, 5) as $fila): ?>
                        <?php
                        $limite = (float) $fila['monto_limite'];
                        $gastado = (float) $fila['gastado'];
                        $avance = $limite > 0 ? ($gastado / $limite) * 100 : 0;
                        $ancho = (int) min(100, round($avance));
                        $excedido = (float) $fila['disponible'] < 0;

                        if ($excedido) {
                            $clase_barra = 'bg-danger';
                        } elseif ($avance >= 80) {
                            $clase_barra = 'bg-warning';
                        } else {
                            $clase_barra = 'bg-success';
                        }
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small">
                                <span><?= e($fila['categoria']) ?></span>
                                <span class="<?= $excedido ? 'text-danger' : 'text-muted' ?>">
                                    <?= e(formato_dinero($fila['gastado'])) ?> / <?= e(formato_dinero($fila['monto_limite'])) ?>
                                </span>
                            </div>
                            <div class="progress" role="progressbar" style="height: 6px;"
                                 aria-valuenow="<?= $ancho ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar <?= $clase_barra ?>" style="width: <?= $ancho ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($presupuestos) > 5): ?>
                        <div class="small text-muted">
                            y <?= (int) (count($presupuestos) - 5) ?> más en
                            <a href="<?= e(BASE_URL) ?>presupuestos.php">Presupuestos</a>.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Objetivos de ahorro</h2>
                    <a href="<?= e(BASE_URL) ?>objetivos.php" class="small">Ver objetivos</a>
                </div>

                <div class="mb-3">
                    <div class="text-muted small">Total ahorrado en objetivos</div>
                    <div class="fs-4 fw-semibold text-success"><?= e(formato_dinero($total_ahorrado)) ?></div>
                    <div class="small text-muted">Las aportaciones no reducen tu balance general.</div>
                </div>

                <?php if (empty($objetivos_resumen)): ?>
                    <p class="text-muted mb-0">Aún no tienes objetivos de ahorro.</p>
                <?php else: ?>
                    <?php foreach (array_slice($objetivos_resumen, 0, 4) as $fila): ?>
                        <?php
                        $meta = (float) $fila['monto_objetivo'];
                        $ahorrado = (float) $fila['ahorrado'];
                        $completado = (float) $fila['restante'] <= 0;
                        $porcentaje_obj = $completado
                            ? 100
                            : min(99, (int) floor($meta > 0 ? ($ahorrado * 100) / $meta : 0));

                        if ($completado) {
                            $clase_barra_obj = 'bg-success';
                        } elseif ((int) $fila['dias_restantes'] < 0) {
                            $clase_barra_obj = 'bg-danger';
                        } else {
                            $clase_barra_obj = 'bg-primary';
                        }
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small">
                                <span><?= e($fila['nombre']) ?></span>
                                <span class="text-muted">
                                    <?= e(formato_dinero($fila['ahorrado'])) ?> / <?= e(formato_dinero($fila['monto_objetivo'])) ?>
                                    (<?= (int) $porcentaje_obj ?>%)
                                </span>
                            </div>
                            <div class="progress" role="progressbar" style="height: 6px;"
                                 aria-valuenow="<?= (int) $porcentaje_obj ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar <?= $clase_barra_obj ?>" style="width: <?= (int) $porcentaje_obj ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($objetivos_resumen) > 4): ?>
                        <div class="small text-muted">
                            y <?= (int) (count($objetivos_resumen) - 4) ?> más en
                            <a href="<?= e(BASE_URL) ?>objetivos.php">Objetivos</a>.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>