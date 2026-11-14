<?php

// Validacion compartida por create.php y update.php.

require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../movimientos/validar.php'; // aporta valor_texto()

// Valida importe, fecha y nota de una aportacion.
// (La propiedad del objetivo se comprueba aparte, en create.php.)

function validar_datos_aportacion(array $post): array
{
    $errores = [];

    $importe = validar_importe(valor_texto($post, 'importe'));
    $fecha = validar_fecha(valor_texto($post, 'fecha'));
    $nota = valor_texto($post, 'nota');

    if ($importe === null) {
        $errores[] = 'El importe debe ser mayor que cero y tener máximo 2 decimales.';
    }
    if ($fecha === null) {
        $errores[] = 'Escribe una fecha válida.';
    } elseif ($fecha > date('Y-m-d')) {
        $errores[] = 'La fecha de la aportación no puede ser posterior a hoy.';
    }
    if (mb_strlen($nota) > 255) {
        $errores[] = 'La nota admite máximo 255 caracteres.';
    }

    return [
        'errores' => $errores,
        'datos' => [
            'importe' => $importe,
            'fecha'   => $fecha,
            'nota'    => $nota === '' ? null : $nota,
        ],
    ];
}

// Guarda lo escrito en la sesion para repoblar el formulario tras un error.
function guardar_formulario_aportacion(array $post): void
{
    $campos = ['importe', 'fecha', 'nota'];
    $guardar = [];
    foreach ($campos as $campo) {
        $guardar[$campo] = (isset($post[$campo]) && is_string($post[$campo])) ? $post[$campo] : '';
    }
    $_SESSION['form_aportacion'] = $guardar;
}