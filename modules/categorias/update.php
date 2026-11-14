<?php
// Cambia el nombre de una categoria del usuario.

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/read.php';

requerir_login();
exigir_post_csrf();

$usuario_id = usuario_actual_id();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
$nombre = texto_enviado($_POST, 'nombre');

if ($id === false || $id < 1) {
    agregar_mensaje('danger', 'Categoría no válida.');
    redirigir('categorias.php');
}

// Verificacion de propiedad: si no es del usuario, se trata como ladron.
$categoria = obtener_categoria($id, $usuario_id);
if ($categoria === null) {
    agregar_mensaje('danger', 'Categoría no encontrada.');
    redirigir('categorias.php');
}

if ($nombre === '' || mb_strlen($nombre) > 80) {
    agregar_mensaje('danger', 'Escribe un nombre de máximo 80 caracteres.');
    redirigir('categorias.php?editar=' . $id);
}

try {
    $stmt = obtener_conexion()->prepare(
        'UPDATE categorias SET nombre = ? WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('sii', $nombre, $id, $usuario_id);
    $stmt->execute();
    $stmt->close();

    agregar_mensaje('success', 'Categoría actualizada correctamente.');
    redirigir('categorias.php');
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        agregar_mensaje('danger', 'Ya tienes una categoría con ese nombre y tipo.');
    } else {
        error_log('FinanTrack - error al actualizar categoria: ' . $e->getMessage());
        agregar_mensaje('danger', 'No fue posible actualizar la categoría.');
    }
    redirigir('categorias.php?editar=' . $id);
}