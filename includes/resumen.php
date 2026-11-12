<?php

require_once __DIR__ . '/../config/database.php';

// Totales de ingresos, gastos y balance del usuario.

function totales_movimientos(int $usuario_id, bool $solo_mes_actual = false): array
{
    $filtro_mes = $solo_mes_actual
        ? " AND fecha BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())"
        : '';

    $sql = "SELECT
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN importe END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN importe END), 0) AS gastos,
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN importe END), 0)
                - COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN importe END), 0) AS balance
            FROM movimientos
            WHERE usuario_id = ?" . $filtro_mes;

    $stmt = obtener_conexion()->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $fila;
}


// Devuelve una lista de filas de mayor a menor.
function gastos_por_categoria_mes(int $usuario_id): array
{
    $sql = "SELECT c.nombre, SUM(m.importe) AS total
            FROM movimientos m
            INNER JOIN categorias c
                ON c.id = m.categoria_id AND c.usuario_id = m.usuario_id
            WHERE m.usuario_id = ?
              AND m.tipo = 'gasto'
              AND m.fecha BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())
            GROUP BY c.id, c.nombre
            ORDER BY total DESC";

    $stmt = obtener_conexion()->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}