<?php
// Elimina un objetivo del usuario y sus aportaciones, en una transaccion.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Objetivo no válido.');
    redirigir('objetivos.php');
}

// Verificacion de propiedad.
if (obtener_objetivo($id, $usuario_id) === null) {
    agregar_mensaje('danger', 'Objetivo no encontrado.');
    redirigir('objetivos.php');
}

$conexion = obtener_conexion();
$conexion->begin_transaction();

try {
    // Primero las aportaciones del objetivo.
    $stmt = $conexion->prepare(
        'DELETE FROM aportaciones WHERE objetivo_id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    // Despues el objetivo.
    $stmt = $conexion->prepare(
        'DELETE FROM objetivos WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    // Si ambas operaciones salieron bien, se confirman juntas.
    $conexion->commit();
    agregar_mensaje('success', 'Objetivo eliminado correctamente.');
} catch (mysqli_sql_exception $e) {
    // Si algo fallo, no se borra nada.
    $conexion->rollback();
    error_log('FinanTrack - error al eliminar objetivo: ' . $e->getMessage());
    agregar_mensaje('danger', 'No fue posible eliminar el objetivo.');
}

redirigir('objetivos.php');