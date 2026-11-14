<?php
// Elimina un presupuesto del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Presupuesto no válido.');
    redirigir('presupuestos.php');
}

// Verificacion de propiedad.
$presupuesto = obtener_presupuesto($id, $usuario_id);
if ($presupuesto === null) {
    agregar_mensaje('danger', 'Presupuesto no encontrado.');
    redirigir('presupuestos.php');
}

try {
    $stmt = obtener_conexion()->prepare(
        'DELETE FROM presupuestos WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Presupuesto eliminado correctamente.');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al eliminar presupuesto: ' . $e->getMessage());
    agregar_mensaje('danger', 'No fue posible eliminar el presupuesto.');
}

redirigir(ruta_presupuestos($presupuesto['mes_anio']));