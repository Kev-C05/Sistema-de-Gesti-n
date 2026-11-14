<?php
// Modifica un presupuesto del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var(valor_texto($_POST, 'id'), FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Presupuesto no válido.');
    redirigir('presupuestos.php');
}

// Verificacion de propiedad.
$existente = obtener_presupuesto($id, $usuario_id);
if ($existente === null) {
    agregar_mensaje('danger', 'Presupuesto no encontrado.');
    redirigir('presupuestos.php');
}

$ruta_edicion = ruta_presupuestos($existente['mes_anio']) . '&editar=' . $id;

$validacion = validar_datos_presupuesto($_POST, $usuario_id);

if (!empty($validacion['errores'])) {
    guardar_formulario_presupuesto($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir($ruta_edicion);
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'UPDATE presupuestos
         SET categoria_id = ?, mes_anio = ?, monto_limite = ?
         WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('issii', $d['categoria_id'], $d['mes_anio'], $d['monto_limite'], $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Presupuesto actualizado correctamente.');
    redirigir(ruta_presupuestos($d['mes_anio']));
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        agregar_mensaje('danger', 'Ya existe un presupuesto para esa categoría en ese mes.');
    } else {
        error_log('FinanTrack - error al actualizar presupuesto: ' . $e->getMessage());
        agregar_mensaje('danger', 'No fue posible actualizar el presupuesto.');
    }
    guardar_formulario_presupuesto($_POST);
    redirigir($ruta_edicion);
}