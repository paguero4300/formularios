<?php
/**
 * API para obtener envíos de formularios
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

// Obtener parámetros
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

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

// Obtener envíos según los permisos del usuario
$result = Form::getSubmissionsForUser($userId, $page, $perPage, $formId);
$submissions = $result['submissions'];
$pagination = $result['pagination'];

// Simplificar la respuesta para la API
$simplifiedSubmissions = [];
foreach ($submissions as $submission) {
    // Decodificar los datos JSON
    $datos = json_decode($submission['datos'], true);
    
    $simplifiedSubmission = [
        'id' => $submission['id'],
        'form_id' => $submission['id_formulario'],
        'form_title' => $submission['formulario_titulo'],
        'user_id' => $submission['id_usuario'],
        'username' => $submission['username'],
        'nombre_completo' => $submission['nombre_completo'],
        'fecha_envio' => $submission['fecha_envio'],
        'datos' => $datos
    ];
    
    $simplifiedSubmissions[] = $simplifiedSubmission;
}

// Preparar respuesta
$response = [
    'success' => true,
    'submissions' => $simplifiedSubmissions,
    'pagination' => $pagination
];

// Registrar actividad
logActivity($userId, 'api_get_submissions', 'Obtención de envíos desde la API');

// Enviar respuesta
jsonResponse($response);
?>
