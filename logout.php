<?php
// Cierre de sesion. 

require_once __DIR__ . '/includes/auth.php';
.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('dashboard.php');
}

exigir_post_csrf();

cerrar_sesion_usuario();

// Se abre una sesion nueva y vacia solo para poder mostrar un mensaje que te verifica si esta bien.
iniciar_sesion_segura();
agregar_mensaje('success', 'Sesión cerrada correctamente.');
redirigir('login.php');