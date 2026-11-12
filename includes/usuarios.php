<?php

require_once __DIR__ . '/../config/database.php';

// Categorias que recibe cada usuario nuevo 
function categorias_iniciales(): array
{
    return [
        ['Alimentación', 'gasto'],
        ['Transporte', 'gasto'],
        ['Vivienda', 'gasto'],
        ['Servicios', 'gasto'],
        ['Salud', 'gasto'],
        ['Educación', 'gasto'],
        ['Entretenimiento', 'gasto'],
        ['Otros gastos', 'gasto'],
        ['Sueldo', 'ingreso'],
        ['Ventas', 'ingreso'],
        ['Otros ingresos', 'ingreso'],
    ];
}

// Devuelve los datos del usuario con ese correo. O no.
function buscar_usuario_por_email(string $email): ?array
{
    $stmt = obtener_conexion()->prepare(
        'SELECT id, nombre, email, password_hash FROM usuarios WHERE email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return $usuario ?: null;
}

// Crea el usuario y sus categorias iniciales en una sola transaccion. Todo rápido papi.
// Devuelve el id del usuario nuevo. Si algo falla, no se guarda nada. De valiste.
function crear_usuario(string $nombre, string $email, string $password): int
{
    $conexion = obtener_conexion();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('sss', $nombre, $email, $hash);
        $stmt->execute();
        $usuario_id = (int) $conexion->insert_id;
        $stmt->close();

        $stmt = $conexion->prepare(
            'INSERT INTO categorias (usuario_id, nombre, tipo) VALUES (?, ?, ?)'
        );
        foreach (categorias_iniciales() as $categoria) {
            $nombre_categoria = $categoria[0];
            $tipo_categoria = $categoria[1];
            $stmt->bind_param('iss', $usuario_id, $nombre_categoria, $tipo_categoria);
            $stmt->execute();
        }
        $stmt->close();

        $conexion->commit();
        return $usuario_id;
    } catch (Throwable $e) {
        $conexion->rollback();
        throw $e;
    }
}