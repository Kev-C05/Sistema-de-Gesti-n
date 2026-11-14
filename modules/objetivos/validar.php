<?php
// Validacion compartida por create.php y update.php.

require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../movimientos/validar.php'; // aporta valor_texto()

// Valida los datos del formulario de objetivos.
// $es_nuevo = true al crear (la fecha limite no puede ser pasada).

function validar_datos_objetivo(array $post, bool $es_nuevo): array
{
    $errores = [];

    $nombre = valor_texto($post, 'nombre');
    $descripcion = valor_texto($post, 'descripcion');
    $monto = validar_importe(valor_texto($post, 'monto_objetivo'));
    $fecha = validar_fecha(valor_texto($post, 'fecha_limite'));

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
    }
    if (mb_strlen($descripcion) > 255) {
        $errores[] = 'La descripción admite máximo 255 caracteres.';
    }
    if ($monto === null) {
        $errores[] = 'La cantidad objetivo debe ser mayor que cero y tener máximo 2 decimales.';
    }
    if ($fecha === null) {
        $errores[] = 'Escribe una fecha límite válida.';
    } elseif ($es_nuevo && $fecha < date('Y-m-d')) {
        $errores[] = 'La fecha límite no puede ser anterior a hoy.';
    }

    return [
        'errores' => $errores,
        'datos' => [
            'nombre'         => $nombre,
            'descripcion'    => $descripcion === '' ? null : $descripcion,
            'monto_objetivo' => $monto,
            'fecha_limite'   => $fecha,
        ],
    ];
}

// Guarda lo escrito en la sesion para repoblar el formulario tras un error.
function guardar_formulario_objetivo(array $post): void
{
    $campos = ['nombre', 'descripcion', 'monto_objetivo', 'fecha_limite'];
    $guardar = [];
    foreach ($campos as $campo) {
        $guardar[$campo] = (isset($post[$campo]) && is_string($post[$campo])) ? $post[$campo] : '';
    }
    $_SESSION['form_objetivo'] = $guardar;
}