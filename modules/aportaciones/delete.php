<?php

// Elimina una aportacion del usuario. 

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Aportación no válida.');
    redirigir('objetivos.php');
}

// Verificacion de propiedad.
$aportacion = obtener_aportacion($id, $usuario_id);
if ($aportacion === null) {
    agregar_mensaje('danger', 'Aportación no encontrada.');
    redirigir('objetivos.php');
}

try {
    $stmt = obtener_conexion()->prepare(
        'DELETE FROM aportaciones WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Aportación eliminada correctamente.');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al eliminar aportacion: ' . $e->getMessage());
    agregar_mensaje('danger', 'No fue posible eliminar la aportación.');
}

redirigir(ruta_aportaciones((int) $aportacion['objetivo_id']));