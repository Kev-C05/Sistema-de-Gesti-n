<?php
// Objetivos de ahorro: formulario y tarjetas con progreso.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/modules/objetivos/read.php';

requerir_login();

$usuario_id = usuario_actual_id();

// Modo edición para los objetivos (solo si el objetivo es del usuario).
$editando = null;
if (isset($_GET['editar'])) {
    $id_editar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);
    $editando = $id_editar ? obtener_objetivo($id_editar, $usuario_id) : null;

    if ($editando === null) {
        agregar_mensaje('danger', 'Objetivo no encontrado.');
        redirigir('objetivos.php');
    }
}

// Valores del formulario: lo reenviado tras un error, o lo guardado, o vacio.
$antiguos = $_SESSION['form_objetivo'] ?? [];
unset($_SESSION['form_objetivo']);

$form = [
    'nombre'         => $antiguos['nombre']         ?? ($editando['nombre'] ?? ''),
    'descripcion'    => $antiguos['descripcion']    ?? ($editando['descripcion'] ?? ''),
    'monto_objetivo' => $antiguos['monto_objetivo'] ?? ($editando['monto_objetivo'] ?? ''),
    'fecha_limite'   => $antiguos['fecha_limite']   ?? ($editando['fecha_limite'] ?? ''),
];

$objetivos = listar_objetivos($usuario_id);

$titulo_pagina = 'Objetivos de ahorro';
$pagina_activa = 'objetivos';
$scripts_extra = ['assets/js/validaciones.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4">Objetivos de ahorro</h1>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $editando ? 'Editar objetivo' : 'Nuevo objetivo' ?></h2>

        <form method="post"
              action="<?= e(BASE_URL) ?>modules/objetivos/<?= $editando ? 'update.php' : 'create.php' ?>"
              class="needs-validation" novalidate>
            <?= campo_csrf() ?>
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           value="<?= e($form['nombre']) ?>" maxlength="100" required>
                    <div class="invalid-feedback">Escribe un nombre.</div>
                </div>

                <div class="col-12 col-md-3">
                    <label for="monto_objetivo" class="form-label">Cantidad objetivo</label>
                    <input type="number" class="form-control" id="monto_objetivo" name="monto_objetivo"
                           value="<?= e($form['monto_objetivo']) ?>" step="0.01" min="0.01"
                           max="9999999999.99" required>
                    <div class="invalid-feedback">Escribe una cantidad mayor que cero.</div>
                </div>

                <div class="col-12 col-md-4">
                    <label for="fecha_limite" class="form-label">Fecha límite</label>
                    <input type="date" class="form-control" id="fecha_limite" name="fecha_limite"
                           value="<?= e($form['fecha_limite']) ?>"
                           <?= $editando ? '' : 'min="' . e(date('Y-m-d')) . '"' ?> required>
                    <div class="invalid-feedback">Selecciona una fecha límite válida.</div>
                </div>

                <div class="col-12">
                    <label for="descripcion" class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion"
                           value="<?= e($form['descripcion']) ?>" maxlength="255">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <?= $editando ? 'Guardar cambios' : 'Crear objetivo' ?>
                </button>
                <?php if ($editando): ?>
                    <a href="<?= e(BASE_URL) ?>objetivos.php" class="btn btn-outline-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h2 class="h6 text-muted text-uppercase">Mis objetivos</h2>

<?php if (empty($objetivos)): ?>
    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-0">Aún no tienes objetivos de ahorro.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($objetivos as $fila): ?>
            <?php
            $meta = (float) $fila['monto_objetivo'];
            $ahorrado = (float) $fila['ahorrado'];
            $completado = (float) $fila['restante'] <= 0;
            $dias = (int) $fila['dias_restantes'];
            $porcentaje = $completado
                ? 100
                : min(99, (int) floor($meta > 0 ? ($ahorrado * 100) / $meta : 0));

            if ($completado) {
                $clase_estado = 'text-bg-success';
                $texto_estado = 'Completado';
                $texto_plazo = '';
            } elseif ($dias < 0) {
                $clase_estado = 'text-bg-danger';
                $texto_estado = 'Vencido';
                $texto_plazo = 'Venció hace ' . abs($dias) . ($dias === -1 ? ' día' : ' días');
            } else {
                $clase_estado = 'text-bg-primary';
                $texto_estado = 'En curso';
                if ($dias === 0) {
                    $texto_plazo = 'Vence hoy';
                } elseif ($dias === 1) {
                    $texto_plazo = 'Falta 1 día';
                } else {
                    $texto_plazo = 'Faltan ' . $dias . ' días';
                }
            }

            $mensaje_confirmar = '¿Eliminar el objetivo "' . $fila['nombre'] . '"?';
            if ((int) $fila['num_aportaciones'] > 0) {
                $mensaje_confirmar .= ' Se eliminarán también sus ' . (int) $fila['num_aportaciones']
                    . ' aportación(es) por ' . formato_dinero($fila['ahorrado']) . '.';
            }
            $mensaje_confirmar .= ' Tus ingresos, gastos y balance no cambian.';
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h3 class="h6 mb-0"><?= e($fila['nombre']) ?></h3>
                            <span class="badge <?= $clase_estado ?>"><?= e($texto_estado) ?></span>
                        </div>

                        <?php if (!empty($fila['descripcion'])): ?>
                            <p class="small text-muted mb-2"><?= e($fila['descripcion']) ?></p>
                        <?php endif; ?>

                        <div class="small mb-2">
                            <strong><?= e(formato_dinero($fila['ahorrado'])) ?></strong>
                            de <?= e(formato_dinero($fila['monto_objetivo'])) ?>
                            (<?= (int) $porcentaje ?>%)
                        </div>

                        <div class="small text-muted">Restante: <?= e(formato_dinero($fila['restante'])) ?></div>
                        <div class="small text-muted">
                            Fecha límite: <?= e(date('d/m/Y', strtotime($fila['fecha_limite']))) ?>
                            <?php if ($texto_plazo !== ''): ?>
                                · <?= e($texto_plazo) ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 pt-2 border-top d-flex gap-2">
                            <a class="btn btn-sm btn-primary"
                               href="<?= e(BASE_URL) ?>aportaciones.php?objetivo=<?= (int) $fila['id'] ?>">Aportaciones</a>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= e(BASE_URL) ?>objetivos.php?editar=<?= (int) $fila['id'] ?>">Editar</a>

                            <form method="post" action="<?= e(BASE_URL) ?>modules/objetivos/delete.php"
                                  data-confirmar="<?= e($mensaje_confirmar) ?>">
                                <?= campo_csrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
