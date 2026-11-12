<?php
// Crea una categoria para el usuario autenticado.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$nombre = trim($_POST['nombre'] ?? '');
$tipo = $_POST['tipo'] ?? '';

$errores = [];
if ($nombre === '' || mb_strlen($nombre) > 80) {
    $errores[] = 'Escribe un nombre de máximo 80 caracteres.';
}
if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
    $errores[] = 'Selecciona un tipo válido.';
}

if (!empty($errores)) {
    agregar_mensaje('danger', implode(' ', $errores));
    redirigir('categorias.php');
}

try {
    $stmt = obtener_conexion()->prepare(
        'INSERT INTO categorias (usuario_id, nombre, tipo) VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iss', $usuario_id, $nombre, $tipo);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Categoría creada correctamente.');
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        agregar_mensaje('danger', 'Ya tienes una categoría con ese nombre y tipo.');
    } else {
        error_log('FinanTrack - error al crear categoria: ' . $e->getMessage());
        agregar_mensaje('danger', 'No fue posible crear la categoría.');
    }
}

redirigir('categorias.php');