<?php

// Modifica importe, fecha y nota de una aportacion del usuario.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var(valor_texto($_POST, 'id'), FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Aportación no válida.');
    redirigir('objetivos.php');
}

// Verificacion de propiedad.
$existente = obtener_aportacion($id, $usuario_id);
if ($existente === null) {
    agregar_mensaje('danger', 'Aportación no encontrada.');
    redirigir('objetivos.php');
}

$ruta = ruta_aportaciones((int) $existente['objetivo_id']);

$validacion = validar_datos_aportacion($_POST);

if (!empty($validacion['errores'])) {
    guardar_formulario_aportacion($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir($ruta . '&editar=' . $id);
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'UPDATE aportaciones
         SET importe = ?, fecha = ?, nota = ?
         WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('sssii', $d['importe'], $d['fecha'], $d['nota'], $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Aportación actualizada correctamente.');
    redirigir($ruta);
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al actualizar aportacion: ' . $e->getMessage());
    guardar_formulario_aportacion($_POST);
    agregar_mensaje('danger', 'No fue posible actualizar la aportación.');
    redirigir($ruta . '&editar=' . $id);
}