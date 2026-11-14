<?php

// Consultas de los reportes.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../movimientos/validar.php'; // aporta valor_texto() y validar_fecha()

// Lee el periodo (desde, hasta) recibido por GET.
// Por defecto: desde el dia 1 de hace 5 meses hasta hoy (6 meses).
function leer_periodo_reporte(array $get): array
{
    $desde = validar_fecha(valor_texto($get, 'desde')) ?? '';
    $hasta = validar_fecha(valor_texto($get, 'hasta')) ?? '';

    if ($desde === '') {
        $desde = (new DateTime('first day of this month'))->modify('-5 months')->format('Y-m-d');
    }
    if ($hasta === '') {
        $hasta = date('Y-m-d');
    }
    if ($desde > $hasta) {
        $intercambio = $desde;
        $desde = $hasta;
        $hasta = $intercambio;
    }

    return ['desde' => $desde, 'hasta' => $hasta];
}

// Convierte '2026-11' en 'Nov 2026'.
function etiqueta_periodo(string $periodo): string
{
    $meses = [
        '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
        '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
        '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
    ];
    $anio = substr($periodo, 0, 4);
    $mes = substr($periodo, 5, 2);

    return ($meses[$mes] ?? $mes) . ' ' . $anio;
}

// Ejecuta una consulta preparada y devuelve todas las filas.
function consultar_filas_reporte(string $sql, string $tipos, array $parametros): array
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

// Ingresos, gastos y balance del periodo.
function totales_periodo(int $usuario_id, string $desde, string $hasta): array
{
    $filas = consultar_filas_reporte(
        "SELECT
            COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN importe END), 0) AS ingresos,
            COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN importe END), 0) AS gastos,
            COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN importe END), 0)
            - COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN importe END), 0) AS balance
         FROM movimientos
         WHERE usuario_id = ? AND fecha BETWEEN ? AND ?",
        'iss',
        [$usuario_id, $desde, $hasta]
    );

    return $filas[0];
}

// Ingresos, gastos y balance de cada mes del periodo (meses con movimientos).
// Devuelve filas: periodo (AAAA-MM), ingresos, gastos, balance.
function resumen_mensual(int $usuario_id, string $desde, string $hasta): array
{
    return consultar_filas_reporte(
        "SELECT DATE_FORMAT(fecha, '%Y-%m') AS periodo,
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN importe END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN importe END), 0) AS gastos,
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN importe END), 0)
                - COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN importe END), 0) AS balance
         FROM movimientos
         WHERE usuario_id = ? AND fecha BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(fecha, '%Y-%m')
         ORDER BY periodo ASC",
        'iss',
        [$usuario_id, $desde, $hasta]
    );
}

// Total por categoria de un tipo ('ingreso' o 'gasto') en el periodo.
// Devuelve filas: nombre, total, cantidad (de mayor a menor total).
function total_por_categoria(int $usuario_id, string $tipo, string $desde, string $hasta): array
{
    return consultar_filas_reporte(
        "SELECT c.nombre, SUM(m.importe) AS total, COUNT(*) AS cantidad
         FROM movimientos m
         INNER JOIN categorias c
             ON c.id = m.categoria_id AND c.usuario_id = m.usuario_id
         WHERE m.usuario_id = ? AND m.tipo = ? AND m.fecha BETWEEN ? AND ?
         GROUP BY c.id, c.nombre
         ORDER BY total DESC",
        'isss',
        [$usuario_id, $tipo, $desde, $hasta]
    );
}