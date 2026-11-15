<?php
// Consultas de lectura de movimientos con paginacion.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/validar.php';

// Lee el numero de pagina recibido por GET (minimo 1).
function leer_pagina_movimientos(array $get): int
{
    $pagina = filter_var(valor_texto($get, 'pagina'), FILTER_VALIDATE_INT);
    return ($pagina !== false && $pagina > 0) ? $pagina : 1;
}

// Construye la direccion de movimientos.php con parametros extra (pagina, editar).
function url_movimientos(array $extra = []): string
{
    $consulta = http_build_query($extra);

    return BASE_URL . 'movimientos.php' . ($consulta !== '' ? '?' . $consulta : '');
}

// Ejecuta una consulta preparada y devuelve todas las filas.
function consultar_filas_movimientos(string $sql, string $tipos, array $parametros): array
{
    $stmt = obtener_conexion()->prepare($sql);
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}

// Cantidad de movimientos del usuario.
function contar_movimientos(int $usuario_id): int
{
    $filas = consultar_filas_movimientos(
        'SELECT COUNT(*) AS total FROM movimientos WHERE usuario_id = ?',
        'i',
        [$usuario_id]
    );

    return (int) $filas[0]['total'];
}

// Una pagina de movimientos del usuario, los mas recientes primero.
function listar_movimientos(int $usuario_id, int $pagina, int $por_pagina): array
{
    $desplazamiento = ($pagina - 1) * $por_pagina;

    return consultar_filas_movimientos(
        'SELECT m.id, m.tipo, m.descripcion, m.importe, m.fecha,
                m.metodo_pago, c.nombre AS categoria
         FROM movimientos m
         INNER JOIN categorias c
             ON c.id = m.categoria_id AND c.usuario_id = m.usuario_id
         WHERE m.usuario_id = ?
         ORDER BY m.fecha DESC, m.id DESC
         LIMIT ? OFFSET ?',
        'iii',
        [$usuario_id, $por_pagina, $desplazamiento]
    );
}

// Devuelve un movimiento SOLO si pertenece al usuario.
function obtener_movimiento(int $id, int $usuario_id): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, categoria_id, tipo, descripcion, importe, fecha, metodo_pago
         FROM movimientos
         WHERE id = ? AND usuario_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $movimiento = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $movimiento ?: null;
}
