<?php
// Modifica un movimiento del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var(valor_texto($_POST, 'id'), FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Movimiento no válido.');
    redirigir('movimientos.php');
}

// Verificacion de propiedad.
if (obtener_movimiento($id, $usuario_id) === null) {
    agregar_mensaje('danger', 'Movimiento no encontrado.');
    redirigir('movimientos.php');
}

$validacion = validar_datos_movimiento($_POST, $usuario_id);

if (!empty($validacion['errores'])) {
    guardar_formulario_movimiento($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir('movimientos.php?editar=' . $id);
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'UPDATE movimientos
         SET categoria_id = ?, tipo = ?, descripcion = ?, importe = ?,
             fecha = ?, metodo_pago = ?
         WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param(
        'isssssii',
        $d['categoria_id'],
        $d['tipo'],
        $d['descripcion'],
        $d['importe'],
        $d['fecha'],
        $d['metodo_pago'],
        $id,
        $usuario_id
    );
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Movimiento actualizado correctamente.');
    redirigir('movimientos.php');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al actualizar movimiento: ' . $e->getMessage());
    guardar_formulario_movimiento($_POST);
    agregar_mensaje('danger', 'No fue posible actualizar el movimiento.');
    redirigir('movimientos.php?editar=' . $id);
}