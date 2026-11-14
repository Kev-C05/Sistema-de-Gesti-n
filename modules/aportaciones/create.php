<?php

// Registra una aportacion a un objetivo del usuario.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$objetivo_id = filter_var(valor_texto($_POST, 'objetivo_id'), FILTER_VALIDATE_INT);

// Verificacion de propiedad: el objetivo debe existir y ser del usuario.
if ($objetivo_id === false || $objetivo_id < 1 || obtener_objetivo($objetivo_id, $usuario_id) === null) {
    agregar_mensaje('danger', 'Objetivo no encontrado.');
    redirigir('objetivos.php');
}

$ruta = ruta_aportaciones($objetivo_id);
$validacion = validar_datos_aportacion($_POST);

if (!empty($validacion['errores'])) {
    guardar_formulario_aportacion($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir($ruta);
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'INSERT INTO aportaciones (usuario_id, objetivo_id, importe, fecha, nota)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iisss', $usuario_id, $objetivo_id, $d['importe'], $d['fecha'], $d['nota']);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Aportación registrada correctamente.');
} catch (mysqli_sql_exception $e) {
    error_log('FinanTrack - error al crear aportacion: ' . $e->getMessage());
    guardar_formulario_aportacion($_POST);
    agregar_mensaje('danger', 'No fue posible registrar la aportación.');
}

redirigir($ruta);