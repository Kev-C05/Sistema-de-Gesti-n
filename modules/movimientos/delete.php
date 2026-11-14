<?php
// Elimina un movimiento del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Movimiento no válido.');
    redirigir('movimientos.php');
}

// Verificacion de propiedad. 
if (obtener_movimiento($id, $usuario_id) === null) {
    agregar_mensaje('danger', 'Movimiento no encontrado.');
    redirigir('movimientos.php');
}

try {
    $stmt = obtener_conexion()->prepare(
        'DELETE FROM movimientos WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Movimiento eliminado correctamente.');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al eliminar movimiento: ' . $e->getMessage());
    agregar_mensaje('danger', 'No fue posible eliminar el movimiento.');
}

redirigir('movimientos.php');