<?php
// Consultas de lectura de movimientos con filtros y paginacion.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/validar.php';

// Lee y normaliza los filtros recibidos por GET. Valores invalidos se ignoran, como ella a ti.
function leer_filtros_movimientos(array $get): array
{
    $q = valor_texto($get, 'q');
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }

    $tipo = valor_texto($get, 'tipo');
    if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
        $tipo = '';
    }

    $categoria_id = filter_var(valor_texto($get, 'categoria'), FILTER_VALIDATE_INT);
    $categoria_id = ($categoria_id !== false && $categoria_id > 0) ? $categoria_id : 0;

    $desde = validar_fecha(valor_texto($get, 'desde')) ?? '';
    $hasta = validar_fecha(valor_texto($get, 'hasta')) ?? '';
    if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
        $intercambio = $desde;
        $desde = $hasta;
        $hasta = $intercambio;
    }

    $orden = valor_texto($get, 'orden') === 'asc' ? 'asc' : 'desc';

    $pagina = filter_var(valor_texto($get, 'pagina'), FILTER_VALIDATE_INT);
    $pagina = ($pagina !== false && $pagina > 0) ? $pagina : 1;

    return [
        'q'            => $q,
        'tipo'         => $tipo,
        'categoria_id' => $categoria_id,
        'desde'        => $desde,
        'hasta'        => $hasta,
        'orden'        => $orden,
        'pagina'       => $pagina,
    ];
}

// Construye la direccion de movimientos.php conservando los filtros.
function url_movimientos(array $filtros, array $extra = []): string
{
    $parametros = [];
    if ($filtros['q'] !== '') {
        $parametros['q'] = $filtros['q'];
    }
    if ($filtros['tipo'] !== '') {
        $parametros['tipo'] = $filtros['tipo'];
    }
    if ($filtros['categoria_id'] > 0) {
        $parametros['categoria'] = $filtros['categoria_id'];
    }
    if ($filtros['desde'] !== '') {
        $parametros['desde'] = $filtros['desde'];
    }
    if ($filtros['hasta'] !== '') {
        $parametros['hasta'] = $filtros['hasta'];
    }
    if ($filtros['orden'] === 'asc') {
        $parametros['orden'] = 'asc';
    }
    if ($filtros['pagina'] > 1) {
        $parametros['pagina'] = $filtros['pagina'];
    }

    $parametros = array_merge($parametros, $extra);
    $consulta = http_build_query($parametros);

    return BASE_URL . 'movimientos.php' . ($consulta !== '' ? '?' . $consulta : '');
}

// Los fragmentos de SQL son fijos; los valores del usuario van como parametros.
function condiciones_movimientos(int $usuario_id, array $filtros): array
{
    $sql = ' WHERE m.usuario_id = ?';
    $tipos = 'i';
    $parametros = [$usuario_id];

    if ($filtros['q'] !== '') {
        $sql .= ' AND m.descripcion LIKE ?';
        $tipos .= 's';
        // Se escapan % _ \ para que se busquen como texto normal.
        $parametros[] = '%' . addcslashes($filtros['q'], '%_\\') . '%';
    }
    if ($filtros['tipo'] !== '') {
        $sql .= ' AND m.tipo = ?';
        $tipos .= 's';
        $parametros[] = $filtros['tipo'];
    }
    if ($filtros['categoria_id'] > 0) {
        $sql .= ' AND m.categoria_id = ?';
        $tipos .= 'i';
        $parametros[] = $filtros['categoria_id'];
    }
    if ($filtros['desde'] !== '') {
        $sql .= ' AND m.fecha >= ?';
        $tipos .= 's';
        $parametros[] = $filtros['desde'];
    }
    if ($filtros['hasta'] !== '') {
        $sql .= ' AND m.fecha <= ?';
        $tipos .= 's';
        $parametros[] = $filtros['hasta'];
    }

    return [$sql, $tipos, $parametros];
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

// Cantidad de movimientos que cumplen los filtros.
function contar_movimientos(int $usuario_id, array $filtros): int
{
    [$donde, $tipos, $parametros] = condiciones_movimientos($usuario_id, $filtros);
    $filas = consultar_filas_movimientos(
        'SELECT COUNT(*) AS total FROM movimientos m' . $donde,
        $tipos,
        $parametros
    );

    return (int) $filas[0]['total'];
}

// Ingresos, gastos y balance de los movimientos que cumplen los filtros.
function totales_movimientos_filtrados(int $usuario_id, array $filtros): array
{
    [$donde, $tipos, $parametros] = condiciones_movimientos($usuario_id, $filtros);
    $filas = consultar_filas_movimientos(
        "SELECT
            COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.importe END), 0) AS ingresos,
            COALESCE(SUM(CASE WHEN m.tipo = 'gasto' THEN m.importe END), 0) AS gastos,
            COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.importe END), 0)
            - COALESCE(SUM(CASE WHEN m.tipo = 'gasto' THEN m.importe END), 0) AS balance
         FROM movimientos m" . $donde,
        $tipos,
        $parametros
    );

    return $filas[0];
}

// Una pagina de movimientos que cumplen los filtros.
function listar_movimientos(int $usuario_id, array $filtros, int $por_pagina): array
{
    [$donde, $tipos, $parametros] = condiciones_movimientos($usuario_id, $filtros);

    // La direccion sale de una lista fija, nunca directamente del usuario.
    $direccion = $filtros['orden'] === 'asc' ? 'ASC' : 'DESC';
    $desplazamiento = ($filtros['pagina'] - 1) * $por_pagina;

    $sql = "SELECT m.id, m.tipo, m.descripcion, m.importe, m.fecha,
                   m.metodo_pago, m.observaciones, c.nombre AS categoria
            FROM movimientos m
            INNER JOIN categorias c
                ON c.id = m.categoria_id AND c.usuario_id = m.usuario_id"
         . $donde
         . " ORDER BY m.fecha $direccion, m.id $direccion LIMIT ? OFFSET ?";

    $tipos .= 'ii';
    $parametros[] = $por_pagina;
    $parametros[] = $desplazamiento;

    return consultar_filas_movimientos($sql, $tipos, $parametros);
}

// Devuelve un movimiento SOLO si pertenece al usuario, si no te trato de ladron.
function obtener_movimiento(int $id, int $usuario_id): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, categoria_id, tipo, descripcion, importe, fecha, metodo_pago, observaciones
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