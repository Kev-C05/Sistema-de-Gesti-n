<?php
// Elimina una categoria del usuario si no tiene registros asociados.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/read.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Categoría no válida.');
    redirigir('categorias.php');
}

// Verificacion de propiedad, antes de que te roben.
$categoria = obtener_categoria($id, $usuario_id);
if ($categoria === null) {
    agregar_mensaje('danger', 'Categoría no encontrada.');
    redirigir('categorias.php');
}

// Regla de seguridad, no se elimina si esta en uso.
$usos = contar_usos_categoria($id, $usuario_id);
if ($usos > 0) {
    agregar_mensaje(
        'danger',
        'No se puede eliminar la categoría "' . $categoria['nombre'] . '": tiene '
        . $usos . ' registro(s) asociado(s) (movimientos o presupuestos).'
    );
    redirigir('categorias.php');
}

try {
    $stmt = obtener_conexion()->prepare(
        'DELETE FROM categorias WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Categoría eliminada correctamente.');
} catch (mysqli_sql_exception $e) {
    // 1451: la clave foranea (ON DELETE RESTRICT) lo impidio.
    if ($e->getCode() === 1451) {
        agregar_mensaje('danger', 'No se puede eliminar: la categoría tiene registros asociados.');
    } else {
        error_log('FinanTrack - error al eliminar categoria: ' . $e->getMessage());
        agregar_mensaje('danger', 'No fue posible eliminar la categoría.');
    }
}

redirigir('categorias.php');