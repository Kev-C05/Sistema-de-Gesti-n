<?php
// Modifica un objetivo si vez que ya no pudiste o equivocación. 

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var(valor_texto($_POST, 'id'), FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Objetivo no válido.');
    redirigir('objetivos.php');
}

// Verificacion de propiedad.
if (obtener_objetivo($id, $usuario_id) === null) {
    agregar_mensaje('danger', 'Objetivo no encontrado.');
    redirigir('objetivos.php');
}

$validacion = validar_datos_objetivo($_POST, false);

if (!empty($validacion['errores'])) {
    guardar_formulario_objetivo($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir('objetivos.php?editar=' . $id);
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'UPDATE objetivos
         SET nombre = ?, descripcion = ?, monto_objetivo = ?, fecha_limite = ?
         WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param(
        'ssssii',
        $d['nombre'],
        $d['descripcion'],
        $d['monto_objetivo'],
        $d['fecha_limite'],
        $id,
        $usuario_id
    );
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Objetivo actualizado correctamente.');
    redirigir('objetivos.php');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al actualizar objetivo: ' . $e->getMessage());
    guardar_formulario_objetivo($_POST);
    agregar_mensaje('danger', 'No fue posible actualizar el objetivo.');
    redirigir('objetivos.php?editar=' . $id);
}