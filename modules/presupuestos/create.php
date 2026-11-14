<?php
// Crea un presupuesto mensual del usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/read.php';
require_once __DIR__ . '/validar.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();

$validacion = validar_datos_presupuesto($_POST, $usuario_id);

if (!empty($validacion['errores'])) {
    guardar_formulario_presupuesto($_POST);
    agregar_mensaje('danger', implode(' ', $validacion['errores']));
    redirigir('presupuestos.php');
}

$d = $validacion['datos'];

try {
    $stmt = obtener_conexion()->prepare(
        'INSERT INTO presupuestos (usuario_id, categoria_id, mes_anio, monto_limite)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('iiss', $usuario_id, $d['categoria_id'], $d['mes_anio'], $d['monto_limite']);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Presupuesto creado correctamente.');
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        agregar_mensaje('danger', 'Ya existe un presupuesto para esa categoría en ese mes.');
    } else {
        error_log('FinanTrack - error al crear presupuesto: ' . $e->getMessage());
        agregar_mensaje('danger', 'No fue posible crear el presupuesto.');
    }
    guardar_formulario_presupuesto($_POST);
}

// Se vuelve al mes del presupuesto para verlo de inmediato.
redirigir(ruta_presupuestos($d['mes_anio']));