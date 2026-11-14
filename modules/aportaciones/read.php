<?php
// Consultas de lectura de aportaciones.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../objetivos/read.php';

// Aportaciones de un objetivo del usuario, las mas recientes primero.
function listar_aportaciones(int $objetivo_id, int $usuario_id): array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, importe, fecha, nota
         FROM aportaciones
         WHERE objetivo_id = ? AND usuario_id = ?
         ORDER BY fecha DESC, id DESC'
    );
    $stmt->bind_param('ii', $objetivo_id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}

// Devuelve una aportacion SOLO si pertenece al patrón.
function obtener_aportacion(int $id, int $usuario_id): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, objetivo_id, importe, fecha, nota
         FROM aportaciones
         WHERE id = ? AND usuario_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $aportacion = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $aportacion ?: null;
}

// Objetivo del usuario con su progreso (ahorrado, restante, dias).

function obtener_resumen_objetivo(int $objetivo_id, int $usuario_id): ?array
{
    foreach (listar_objetivos($usuario_id) as $objetivo) {
        if ((int) $objetivo['id'] === $objetivo_id) {
            return $objetivo;
        }
    }

    return null;
}

// Ruta de la pagina de aportaciones de un objetivo.
function ruta_aportaciones(int $objetivo_id): string
{
    return 'aportaciones.php?objetivo=' . $objetivo_id;
}