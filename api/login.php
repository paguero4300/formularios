<?php
/**
 * API de autenticación
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Configurar cabeceras para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

// Obtener datos de la solicitud
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Si no hay datos JSON, intentar obtener de POST
if (empty($input)) {
    $input = $_POST;
}

// Verificar datos requeridos
if (empty($input['username']) || empty($input['password'])) {
    jsonResponse(['error' => 'Nombre de usuario y contraseña son requeridos'], 400);
}

// Intentar autenticar al usuario
$username = sanitize($input['username']);
$password = $input['password'];

// Buscar usuario en la base de datos
$sql = "SELECT * FROM usuarios WHERE username = ? AND estado = 'activo' LIMIT 1";
$user = fetchOne($sql, [$username]);

// Verificar si el usuario existe y la contraseña es correcta
if ($user && password_verify($password, $user['password'])) {
    // Autenticación exitosa
    $response = [
        'success' => true,
        'message' => 'Autenticación exitosa',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nombre_completo' => $user['nombre_completo']
        ]
    ];
    
    // Registrar actividad
    logActivity($user['id'], 'api_login', 'Inicio de sesión desde la API');
    
    jsonResponse($response);
} else {
    // Autenticación fallida
    jsonResponse(['error' => 'Credenciales inválidas'], 401);
}
?>
