<?php

// Aportaciones de un objetivo de ahorro: resumen, formulario y tabla.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modules/aportaciones/read.php';
require_once __DIR__ . '/modules/aportaciones/validar.php';

requerir_login();

$usuario_id = usuario_actual_id();

// El objetivo debe existir y ser del usuario.
$objetivo_id = filter_var(valor_texto($_GET, 'objetivo'), FILTER_VALIDATE_INT);
$resumen = ($objetivo_id !== false && $objetivo_id > 0)
    ? obtener_resumen_objetivo($objetivo_id, $usuario_id)
    : null;

if ($resumen === null) {
    agregar_mensaje('danger', 'Objetivo no encontrado.');
    redirigir('objetivos.php');
}

// Modo edicion: aportaciones
$editando = null;
if (isset($_GET['editar'])) {
    $id_editar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $editando = $id_editar ? obtener_aportacion($id_editar, $usuario_id) : null;

    if ($editando === null || (int) $editando['objetivo_id'] !== $objetivo_id) {
        agregar_mensaje('danger', 'Aportación no encontrada.');
        redirigir(ruta_aportaciones($objetivo_id));
    }
}

// Valores del formulario: lo reenviado tras un error, o lo guardado, o los valores por defecto.
$antiguos = $_SESSION['form_aportacion'] ?? [];
unset($_SESSION['form_aportacion']);

$form = [
    'importe' => $antiguos['importe'] ?? ($editando['importe'] ?? ''),
    'fecha'   => $antiguos['fecha']   ?? ($editando['fecha'] ?? date('Y-m-d')),
    'nota'    => $antiguos['nota']    ?? ($editando['nota'] ?? ''),
];

$aportaciones = listar_aportaciones($objetivo_id, $usuario_id);

// Progreso y estado del objetivo.
$meta = (float) $resumen['monto_objetivo'];
$ahorrado = (float) $resumen['ahorrado'];
$completado = (float) $resumen['restante'] <= 0;
$dias = (int) $resumen['dias_restantes'];
$porcentaje = $completado
    ? 100
    : min(99, (int) floor($meta > 0 ? ($ahorrado * 100) / $meta : 0));

if ($completado) {
    $clase_estado = 'text-bg-success';
    $texto_estado = 'Completado';
    $clase_barra = 'bg-success';
    $texto_plazo = '';
} elseif ($dias < 0) {
    $clase_estado = 'text-bg-danger';
    $texto_estado = 'Vencido';
    $clase_barra = 'bg-danger';
    $texto_plazo = 'Venció hace ' . abs($dias) . ($dias === -1 ? ' día' : ' días');
} else {
    $clase_estado = 'text-bg-primary';
    $texto_estado = 'En curso';
    $clase_barra = 'bg-primary';
    if ($dias === 0) {
        $texto_plazo = 'Vence hoy';
    } elseif ($dias === 1) {
        $texto_plazo = 'Falta 1 día';
    } else {
        $texto_plazo = 'Faltan ' . $dias . ' días';
    }
}

$titulo_pagina = 'Aportaciones';
$pagina_activa = 'objetivos';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-3">
    <a href="<?= e(BASE_URL) ?>objetivos.php">&larr; Volver a objetivos</a>
</div>

<h1 class="h4 mb-4">Aportaciones: <?= e($resumen['nombre']) ?></h1>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <?php if (!empty($resumen['descripcion'])): ?>
                    <div class="small text-muted"><?= e($resumen['descripcion']) ?></div>
                <?php endif; ?>
                <div class="small text-muted">
                    Fecha límite: <?= e(date('d/m/Y', strtotime($resumen['fecha_limite']))) ?>
                    <?php if ($texto_plazo !== ''): ?>
                        · <?= e($texto_plazo) ?>
                    <?php endif; ?>
                </div>
            </div>
            <span class="badge <?= $clase_estado ?>"><?= e($texto_estado) ?></span>
        </div>

        <div class="progress mb-2" role="progressbar" style="height: 12px;"
             aria-valuenow="<?= (int) $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar <?= $clase_barra ?>" style="width: <?= (int) $porcentaje ?>%"></div>
        </div>

        <div class="row g-3 text-center">
            <div class="col-4">
                <div class="text-muted small">Ahorrado</div>
                <div class="fs-5 fw-semibold text-success"><?= e(formato_dinero($resumen['ahorrado'])) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted small">Meta (<?= (int) $porcentaje ?>%)</div>
                <div class="fs-5 fw-semibold"><?= e(formato_dinero($resumen['monto_objetivo'])) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted small">Restante</div>
                <div class="fs-5 fw-semibold"><?= e(formato_dinero($resumen['restante'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $editando ? 'Editar aportación' : 'Nueva aportación' ?></h2>

        <form method="post"
              action="<?= e(BASE_URL) ?>modules/aportaciones/<?= $editando ? 'update.php' : 'create.php' ?>"
              class="needs-validation" novalidate>
            <?= campo_csrf() ?>
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
            <?php else: ?>
                <input type="hidden" name="objetivo_id" value="<?= (int) $objetivo_id ?>">
            <?php endif; ?>

            <div class="row g-3">
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
                           value="<?= e($form['fecha']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                    <div class="invalid-feedback">Selecciona una fecha (hoy o anterior).</div>
                </div>

                <div class="col-12 col-md-6">
                    <label for="nota" class="form-label">Nota <span class="text-muted">(opcional)</span></label>
                    <input type="text" class="form-control" id="nota" name="nota"
                           value="<?= e($form['nota']) ?>" maxlength="255">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <?= $editando ? 'Guardar cambios' : 'Registrar aportación' ?>
                </button>
                <?php if ($editando): ?>
                    <a href="<?= e(BASE_URL . ruta_aportaciones($objetivo_id)) ?>" class="btn btn-outline-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6 mb-3">Historial de aportaciones</h2>

        <?php if (empty($aportaciones)): ?>
            <p class="text-muted mb-0">Aún no hay aportaciones para este objetivo.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Importe</th>
                            <th>Nota</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aportaciones as $fila): ?>
                            <tr>
                                <td class="text-nowrap"><?= e(date('d/m/Y', strtotime($fila['fecha']))) ?></td>
                                <td class="text-end text-success text-nowrap"><?= e(formato_dinero($fila['importe'])) ?></td>
                                <td><?= e($fila['nota'] ?? '') ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?= e(BASE_URL . ruta_aportaciones($objetivo_id) . '&editar=' . (int) $fila['id']) ?>">Editar</a>

                                    <form method="post" action="<?= e(BASE_URL) ?>modules/aportaciones/delete.php"
                                          class="d-inline"
                                          data-confirmar="<?= e('¿Eliminar la aportación de ' . formato_dinero($fila['importe']) . ' del ' . date('d/m/Y', strtotime($fila['fecha'])) . '?') ?>">
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