<?php
// Crea un objetivo de ahorro del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();

$validacion = validar_datos_objetivo($_POST, true);

if (!empty($validacion['errores'])) {
    guardar_formulario_objetivo($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir('objetivos.php');
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'INSERT INTO objetivos (usuario_id, nombre, descripcion, monto_objetivo, fecha_limite)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issss',
        $usuario_id,
        $d['nombre'],
        $d['descripcion'],
        $d['monto_objetivo'],
        $d['fecha_limite']
    );
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Objetivo creado correctamente.');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al crear objetivo: ' . $e->getMessage());
    guardar_formulario_objetivo($_POST);
    agregar_mensaje('danger', 'No fue posible crear el objetivo.');
}

redirigir('objetivos.php');