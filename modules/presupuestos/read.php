<?php
// Consultas de lectura de presupuestos.

require_once __DIR__ . '/../../config/database.php';

// presupuestos del usuario en un mes, con lo gastado en la categoria.
function sql_base_presupuestos(bool $solo_un_mes = true): string
{
    $filtro_mes = $solo_un_mes ? ' AND p.mes_anio = ?' : '';

    return "SELECT p.id, p.categoria_id, c.nombre AS categoria, p.mes_anio, p.monto_limite,
                   COALESCE((SELECT SUM(m.importe)
                               FROM movimientos m
                              WHERE m.usuario_id = p.usuario_id
                                AND m.categoria_id = p.categoria_id
                                AND m.tipo = 'gasto'
                                AND m.fecha BETWEEN p.mes_anio AND LAST_DAY(p.mes_anio)), 0) AS gastado
              FROM presupuestos p
              INNER JOIN categorias c
                  ON c.id = p.categoria_id AND c.usuario_id = p.usuario_id
             WHERE p.usuario_id = ?" . $filtro_mes;
}

// Ejecuta una consulta preparada y devuelve las filas.
function consultar_presupuestos(string $sql, int $usuario_id, string $mes_anio): array
{
    $stmt = obtener_conexion()->prepare($sql);
    $stmt->bind_param('is', $usuario_id, $mes_anio);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}

// Presupuestos del mes con lo gastado y lo disponible.
function listar_presupuestos_mes(int $usuario_id, string $mes_anio): array
{
    $sql = 'SELECT t.*, (t.monto_limite - t.gastado) AS disponible
            FROM (' . sql_base_presupuestos() . ') t
            ORDER BY t.categoria';

    return consultar_presupuestos($sql, $usuario_id, $mes_anio);
}

// Todos los presupuestos del usuario (de todos los meses), del mas reciente al mas antiguo.
function listar_presupuestos_todos(int $usuario_id): array
{
    $sql = 'SELECT t.*, (t.monto_limite - t.gastado) AS disponible
            FROM (' . sql_base_presupuestos(false) . ') t
            ORDER BY t.mes_anio DESC, t.categoria';

    $stmt = obtener_conexion()->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $filas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
    $stmt->close();

    return $filas;
}

// Totales del mes: presupuestado, gastado y disponible.
function totales_presupuestos_mes(int $usuario_id, string $mes_anio): array
{
    $sql = 'SELECT COALESCE(SUM(t.monto_limite), 0) AS presupuestado,
                   COALESCE(SUM(t.gastado), 0) AS gastado,
                   COALESCE(SUM(t.monto_limite), 0) - COALESCE(SUM(t.gastado), 0) AS disponible
            FROM (' . sql_base_presupuestos() . ') t';

    $filas = consultar_presupuestos($sql, $usuario_id, $mes_anio);

    return $filas[0];
}

// Devuelve un presupuesto SOLO si pertenece al usuario.
function obtener_presupuesto(int $id, int $usuario_id): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, categoria_id, mes_anio, monto_limite
         FROM presupuestos
         WHERE id = ? AND usuario_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $id, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $presupuesto = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $presupuesto ?: null;
}

// Ruta de la pagina de presupuestos para el mes de una fecha,
function ruta_presupuestos(string $mes_anio): string
{
    $fecha = DateTime::createFromFormat('Y-m-d', $mes_anio);
    if ($fecha === false) {
        return 'presupuestos.php';
    }

    return 'presupuestos.php?mes=' . (int) $fecha->format('n') . '&anio=' . $fecha->format('Y');
}