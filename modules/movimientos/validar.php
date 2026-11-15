<?php
// Validacion compartida de los datos de movimientos.

require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../categorias/read.php';

// Metodos de pago permitidos.
function metodos_pago(): array
{
    return ['Efectivo', 'Tarjeta de débito', 'Tarjeta de crédito', 'Transferencia', 'Otro'];
}

function valor_texto(array $post, string $campo): string
{
    return (isset($post[$campo]) && is_string($post[$campo])) ? trim($post[$campo]) : '';
}

// Valida los datos del formulario de movimientos.

function validar_datos_movimiento(array $post, int $usuario_id): array
{
    $errores = [];

    $descripcion = valor_texto($post, 'descripcion');
    $metodo_pago = valor_texto($post, 'metodo_pago');
    $categoria_id = filter_var(valor_texto($post, 'categoria_id'), FILTER_VALIDATE_INT);
    $importe = validar_importe(valor_texto($post, 'importe'));
    $fecha = validar_fecha(valor_texto($post, 'fecha'));

    if ($descripcion === '' || mb_strlen($descripcion) > 150) {
        $errores[] = 'La descripción es obligatoria (máximo 150 caracteres).';
    }
    if ($importe === null) {
        $errores[] = 'El importe debe ser mayor que cero y tener máximo 2 decimales.';
    }
    if ($fecha === null) {
        $errores[] = 'Escribe una fecha válida.';
    }
    if ($metodo_pago !== '' && !in_array($metodo_pago, metodos_pago(), true)) {
        $errores[] = 'El método de pago no es válido.';
    }

    // La categoria debe existir y ser del usuario. De ella sale el tipo.
    $tipo = null;
    $categoria = null;
    if ($categoria_id !== false && $categoria_id > 0) {
        $categoria = obtener_categoria($categoria_id, $usuario_id);
    }
    if ($categoria === null) {
        $errores[] = 'Selecciona una categoría válida.';
    } else {
        $tipo = $categoria['tipo'];
    }

    return [
        'errores' => $errores,
        'datos' => [
            'categoria_id'  => $categoria === null ? 0 : (int) $categoria['id'],
            'tipo'          => $tipo,
            'descripcion'   => $descripcion,
            'importe'       => $importe,
            'fecha'         => $fecha,
            'metodo_pago'   => $metodo_pago === '' ? null : $metodo_pago,
        ],
    ];
}

// Guarda lo escrito en la sesion para repoblar el formulario tras un error.
function guardar_formulario_movimiento(array $post): void
{
    $campos = ['categoria_id', 'descripcion', 'importe', 'fecha', 'metodo_pago'];
    $guardar = [];
    foreach ($campos as $campo) {
        $guardar[$campo] = (isset($post[$campo]) && is_string($post[$campo])) ? $post[$campo] : '';
    }
    $_SESSION['form_movimiento'] = $guardar;
}