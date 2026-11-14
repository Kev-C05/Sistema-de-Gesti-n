<?php
// Consultas de lectura de objetivos de ahorro. Todas filtran por usuario_id.

require_once __DIR__ . '/../../config/database.php';

// Lista los objetivos del usuario con:
//   ahorrado          = suma de sus aportaciones, aunque sea un misero peso
//   num_aportaciones  = cantidad de aportaciones, aunque no hagas.
//   restante          = meta - ahorrado (minimo 0, tampoco quieras endeudarte con la aplicación)
//   dias_restantes    = dias hasta la fecha limite (negativo si fracasastes)
function listar_objetivos(int $usuario_id): array
{
    $sql = "SELECT t.*, GREATEST(t.monto_objetivo - t.ahorrado, 0) AS restante
            FROM (
                SELECT o.id, o.nombre, o.descripcion, o.monto_objetivo, o.fecha_limite,
                       COALESCE((SELECT SUM(a.importe)
                                   FROM aportaciones a
                                  WHERE a.objetivo_id = o.id
                                    AND a.usuario_id = o.usuario_id), 0) AS ahorrado,
                       (SELECT COUNT(*)
                          FROM aportaciones a2
                         WHERE a2.objetivo_id = o.id
                           AND a2.usuario_id = o.usuario_id) AS num_aportaciones,
                       DATEDIFF(o.fecha_limite, CURDATE()) AS dias_restantes
                  FROM objetivos o
                 WHERE o.usuario_id = ?
            ) t
            ORDER BY t.fecha_limite ASC, t.id ASC";

    $stmt = obtener_conexion()->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}

// Devuelve un objetivo SOLO si pertenece al usuario, fin, no hay mensaje esta vez.
function obtener_objetivo(int $id, int $usuario_id): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, nombre, descripcion, monto_objetivo, fecha_limite
         FROM objetivos
         WHERE id = ? AND usuario_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $objetivo = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $objetivo ?: null;
}