<?php
// Validacion compartida por create.php y update.php.

require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../categorias/read.php';
require_once __DIR__ . '/../movimientos/validar.php'; // aporta valor_texto()

function nombres_meses(): array
{
    return [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];
}

// Valida los datos del formulario de presupuestos.

function validar_datos_presupuesto(array $post, int $usuario_id): array
{
    $errores = [];

    $categoria_id = filter_var(valor_texto($post, 'categoria_id'), FILTER_VALIDATE_INT);
    $mes = filter_var(
        valor_texto($post, 'mes'),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 12]]
    );
    $anio = filter_var(
        valor_texto($post, 'anio'),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 2000, 'max_range' => 2100]]
    );
    $monto = validar_importe(valor_texto($post, 'monto_limite'));

    // La categoria debe ser del usuario y de tipo gasto.
    $categoria = null;
    if ($categoria_id !== false && $categoria_id > 0) {
        $categoria = obtener_categoria($categoria_id, $usuario_id);
    }
    if ($categoria === null) {
        $errores[] = 'Selecciona una categoría válida.';
    } elseif ($categoria['tipo'] !== 'gasto') {
        $errores[] = 'Los presupuestos solo se crean para categorías de gasto.';
    }

    if ($mes === false) {
        $errores[] = 'Selecciona un mes válido.';
    }
    if ($anio === false) {
        $errores[] = 'Escribe un año entre 2000 y 2100.';
    }
    if ($monto === null) {
        $errores[] = 'El monto debe ser mayor que cero y tener máximo 2 decimales.';
    }

    $mes_anio = ($mes !== false && $anio !== false) ? sprintf('%04d-%02d-01', $anio, $mes) : null;

    return [
        'errores' => $errores,
        'datos' => [
            'categoria_id' => $categoria === null ? 0 : (int) $categoria['id'],
            'mes_anio'     => $mes_anio,
            'monto_limite' => $monto,
        ],
    ];
}

// Guarda lo escrito en la sesion para repoblar el formulario tras un error.
function guardar_formulario_presupuesto(array $post): void
{
    $campos = ['categoria_id', 'mes', 'anio', 'monto_limite'];
    $guardar = [];
    foreach ($campos as $campo) {
        $guardar[$campo] = (isset($post[$campo]) && is_string($post[$campo])) ? $post[$campo] : '';
    }
    $_SESSION['form_presupuesto'] = $guardar;
}