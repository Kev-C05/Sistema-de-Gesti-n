<?php
// Cabecera HTML comun y corriente como el puerto mas corriente.

$titulo_pagina = $titulo_pagina ?? 'FinanTrack';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo_pagina) ?> - FinanTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(BASE_URL) ?>assets/css/estilos.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
<?php foreach (obtener_mensajes() as $mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?>" role="alert"><?= e($mensaje['texto']) ?></div>
<?php endforeach; ?>