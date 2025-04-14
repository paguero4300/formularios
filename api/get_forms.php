<?php
/**
 * API para obtener formularios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/form.php';

// Configurar cabeceras para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

// Obtener ID del usuario
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Verificar si se proporcionó un ID de usuario
if ($userId <= 0) {
    jsonResponse(['error' => 'ID de usuario inválido'], 400);
}

// Verificar si el usuario existe
$sql = "SELECT * FROM usuarios WHERE id = ? AND estado = 'activo' LIMIT 1";
$user = fetchOne($sql, [$userId]);

if (!$user) {
    jsonResponse(['error' => 'Usuario no encontrado o inactivo'], 404);
}

// Obtener formularios activos
$sql = "SELECT f.id, f.titulo, f.descripcion
        FROM formularios f
        WHERE f.estado = 'activo'
        ORDER BY f.id DESC";

$forms = fetchAll($sql);

// Para cada formulario, obtener sus campos
foreach ($forms as &$form) {
    $sql = "SELECT id, tipo_campo, etiqueta, requerido, orden
            FROM campos_formulario
            WHERE id_formulario = ?
            ORDER BY orden ASC";
    
    $form['campos'] = fetchAll($sql, [$form['id']]);
}

// Preparar respuesta
$response = [
    'success' => true,
    'forms' => $forms
];

// Registrar actividad
logActivity($userId, 'api_get_forms', 'Obtención de formularios desde la API');

// Enviar respuesta
jsonResponse($response);
?>
