<?php
/**
 * API para enviar formulario completado
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
if (empty($input['form_id']) || empty($input['user_id']) || empty($input['data'])) {
    jsonResponse(['error' => 'Datos incompletos. Se requiere form_id, user_id y data'], 400);
}

$formId = (int)$input['form_id'];
$userId = (int)$input['user_id'];
$formData = $input['data'];

// Verificar si el formulario existe y está activo
$sql = "SELECT * FROM formularios WHERE id = ? AND estado = 'activo' LIMIT 1";
$form = fetchOne($sql, [$formId]);

if (!$form) {
    jsonResponse(['error' => 'Formulario no encontrado o inactivo'], 404);
}

// Verificar si el usuario existe y está activo
$sql = "SELECT * FROM usuarios WHERE id = ? AND estado = 'activo' LIMIT 1";
$user = fetchOne($sql, [$userId]);

if (!$user) {
    jsonResponse(['error' => 'Usuario no encontrado o inactivo'], 404);
}

// Obtener campos del formulario para validación
$sql = "SELECT id, tipo_campo, etiqueta, requerido
        FROM campos_formulario
        WHERE id_formulario = ?
        ORDER BY orden ASC";
$fields = fetchAll($sql, [$formId]);

// Validar datos del formulario
$errors = [];
$validatedData = [];

foreach ($fields as $field) {
    $fieldId = $field['id'];
    $fieldValue = $formData[$fieldId] ?? null;
    
    // Verificar campos requeridos
    if ($field['requerido'] && empty($fieldValue)) {
        $errors[] = "El campo '{$field['etiqueta']}' es requerido";
        continue;
    }
    
    // Validar según el tipo de campo
    switch ($field['tipo_campo']) {
        case 'fecha_hora':
            if (!empty($fieldValue) && !isValidDate($fieldValue)) {
                $errors[] = "El campo '{$field['etiqueta']}' debe ser una fecha y hora válida";
            }
            break;
            
        case 'ubicacion_gps':
            if (!empty($fieldValue)) {
                $coords = explode(',', $fieldValue);
                if (count($coords) !== 2 || !isValidGPSCoordinates($coords[0], $coords[1])) {
                    $errors[] = "El campo '{$field['etiqueta']}' debe contener coordenadas GPS válidas";
                }
            }
            break;
    }
    
    // Añadir a datos validados
    $validatedData[$fieldId] = $fieldValue;
}

// Si hay errores, devolver respuesta de error
if (!empty($errors)) {
    jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
}

// Guardar envío en la base de datos
$sql = "INSERT INTO envios_formulario (id_formulario, id_usuario, datos) VALUES (?, ?, ?)";
$result = insert($sql, [$formId, $userId, json_encode($validatedData)]);

if ($result) {
    // Registrar actividad
    logActivity($userId, 'api_submit_form', "Envío de formulario ID: $formId");
    
    // Enviar respuesta exitosa
    jsonResponse([
        'success' => true,
        'message' => 'Formulario enviado correctamente',
        'submission_id' => $result
    ]);
} else {
    // Enviar respuesta de error
    jsonResponse(['error' => 'Error al guardar el formulario'], 500);
}
?>
