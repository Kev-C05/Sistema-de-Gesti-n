<?php
// Registra un movimiento del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();

$validacion = validar_datos_movimiento($_POST, $usuario_id);

if (!empty($validacion['errores'])) {
    guardar_formulario_movimiento($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir('movimientos.php');
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'INSERT INTO movimientos
            (usuario_id, categoria_id, tipo, descripcion, importe, fecha, metodo_pago)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'iisssss',
        $usuario_id,
        $d['categoria_id'],
        $d['tipo'],
        $d['descripcion'],
        $d['importe'],
        $d['fecha'],
        $d['metodo_pago']
    );
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Movimiento registrado correctamente.');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al crear movimiento: ' . $e->getMessage());
    guardar_formulario_movimiento($_POST);
    agregar_mensaje('danger', 'No fue posible registrar el movimiento.');
}

redirigir('movimientos.php');