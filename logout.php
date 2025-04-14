<?php
/**
 * Página de cierre de sesión
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'includes/auth.php';

// Cerrar sesión
Auth::logout();

// Redireccionar a la página de inicio
setAlert('success', 'Has cerrado sesión correctamente');
redirect(APP_URL);
?>
