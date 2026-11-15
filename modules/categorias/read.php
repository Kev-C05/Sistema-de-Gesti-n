<?php
// Consultas de lectura de categorias. Todas filtran por usuario_id.

require_once __DIR__ . '/../../config/database.php';

// Lista las categorias del usuario con el numero de usos.
function listar_categorias(int $usuario_id, ?string $tipo = null): array
{
    $sql = "SELECT c.id, c.nombre, c.tipo,
                   (SELECT COUNT(*) FROM movimientos m
                     WHERE m.categoria_id = c.id AND m.usuario_id = c.usuario_id)
                 + (SELECT COUNT(*) FROM presupuestos p
                     WHERE p.categoria_id = c.id AND p.usuario_id = c.usuario_id) AS usos
            FROM categorias c
            WHERE c.usuario_id = ?";

    if ($tipo === null) {
        $sql .= " ORDER BY c.tipo, c.nombre";
        $stmt = obtener_conexion()->prepare($sql);
        $stmt->bind_param('i', $usuario_id);
    } else {
        $sql .= " AND c.tipo = ? ORDER BY c.nombre";
        $stmt = obtener_conexion()->prepare($sql);
        $stmt->bind_param('is', $usuario_id, $tipo);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}

// Devuelve una categoria SOLO si pertenece al usuario; si no, devuelve null.
function obtener_categoria(int $id, int $usuario_id): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, nombre, tipo FROM categorias WHERE id = ? AND usuario_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $categoria = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $categoria ?: null;
}

// Cuenta cuantos movimientos y presupuestos usan la categoria.
function contar_usos_categoria(int $id, int $usuario_id): int
{
    $stmt = obtener_conexion()->prepare(
        'SELECT
            (SELECT COUNT(*) FROM movimientos WHERE categoria_id = ? AND usuario_id = ?)
          + (SELECT COUNT(*) FROM presupuestos WHERE categoria_id = ? AND usuario_id = ?) AS usos'
    );
    $stmt->bind_param('iiii', $id, $usuario_id, $id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return (int) $fila['usos'];
}