<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_HOST', 'localhost');
define('DB_USUARIO', 'root');
define('DB_PASSWORD', '');
define('DB_NOMBRE', 'finantrack');
define('DB_CHARSET', 'utf8mb4');

function obtener_conexion(): mysqli
{
    if (isset($GLOBALS['db_conexion']) && $GLOBALS['db_conexion'] instanceof mysqli) {
        return $GLOBALS['db_conexion'];
    }

    try {
        $conexion = new mysqli(DB_HOST, DB_USUARIO, DB_PASSWORD, DB_NOMBRE);
        $conexion->set_charset(DB_CHARSET);
    } catch (mysqli_sql_exception $e) {
        // El detalle tecnico solo va al registro del servidor, no al usuario.
        error_log('FinanTrack - error de conexion: ' . $e->getMessage());
        http_response_code(500);
        exit('No fue posible conectar con la base de datos. Intenta de nuevo mas tarde.');
    }

    $GLOBALS['db_conexion'] = $conexion;
    return $conexion;
}

function cerrar_conexion(): void
{
    if (isset($GLOBALS['db_conexion']) && $GLOBALS['db_conexion'] instanceof mysqli) {
        $GLOBALS['db_conexion']->close();
    }
    $GLOBALS['db_conexion'] = null;
}