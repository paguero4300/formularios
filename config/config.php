<?php
/**
 * Configuración general
 * Panel administrativo Dr Security
 */

// Configuración de la aplicación
define('APP_NAME', 'Dr Security');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/formulario'); // Cambia esto según tu configuración

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
session_start();

// Zona horaria
date_default_timezone_set('America/Mexico_City'); // Ajusta según tu ubicación

// Configuración de errores (desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// En producción, cambiar a:
// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Funciones de utilidad

// Función para sanitizar entradas
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para redireccionar
function redirect($url) {
    header("Location: $url");
    exit;
}

// Función para mostrar mensajes de alerta
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Función para obtener y limpiar mensajes de alerta
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Función para proteger páginas que requieren autenticación
function requireAuth() {
    if (!isAuthenticated()) {
        setAlert('danger', 'Debes iniciar sesión para acceder a esta página');
        redirect(APP_URL);
    }
}

// Función para generar tokens CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar tokens CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        setAlert('danger', 'Error de validación del formulario');
        return false;
    }
    return true;
}

// Función para respuestas JSON en la API
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
