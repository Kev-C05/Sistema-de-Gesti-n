<?php
require_once __DIR__ . '/config/database.php';

$conexion = obtener_conexion();

$stmt = $conexion->prepare(
    'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = ?'
);
$nombre_bd = DB_NOMBRE;
$stmt->bind_param('s', $nombre_bd);
$stmt->execute();
$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();
$resultado->free();
$stmt->close();

echo 'Conexion correcta. Tablas en la base de datos: ' . (int) $fila['total'];

cerrar_conexion();