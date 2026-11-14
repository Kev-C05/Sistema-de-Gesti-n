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

// Limite de intentos de inicio de sesion, solo tienes 5, no la rieges tanto.

define('LOGIN_MAX_INTENTOS', 5);
define('LOGIN_MINUTOS_BLOQUEO', 15);

// Cuenta los intentos fallidos recientes de este correo desde esta IP.
function intentos_fallidos_recientes(string $email, string $ip): int
{
    $minutos = LOGIN_MINUTOS_BLOQUEO;

    $stmt = obtener_conexion()->prepare(
        'SELECT COUNT(*) AS total
         FROM intentos_login
         WHERE email = ? AND ip = ? AND creado_en > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->bind_param('ssi', $email, $ip, $minutos);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $resultado->free();
    $stmt->close();

    return (int) $fila['total'];
}

// Registra un intento fallido y elimina registros viejos (mas de 1 dia).
function registrar_intento_fallido(string $email, string $ip): void
{
    $conexion = obtener_conexion();

    $stmt = $conexion->prepare('INSERT INTO intentos_login (email, ip) VALUES (?, ?)');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare('DELETE FROM intentos_login WHERE creado_en < (NOW() - INTERVAL 1 DAY)');
    $stmt->execute();
    $stmt->close();
}

// Borra los intentos fallidos tras un inicio de sesion correcto.
function limpiar_intentos_login(string $email, string $ip): void
{
    $stmt = obtener_conexion()->prepare('DELETE FROM intentos_login WHERE email = ? AND ip = ?');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $stmt->close();
}