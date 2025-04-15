<?php
/**
 * Script para probar el envío de formularios a través de la API
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/form.php';

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');

// Variables para almacenar IDs de prueba
$testFormId = null;
$testUserId = null;
$testFieldIds = [];
$testSubmissionId = null;

// Función para registrar mensajes
function logMessage($message, $type = 'info') {
    $colors = [
        'success' => 'green',
        'error' => 'red',
        'warning' => 'orange',
        'info' => 'blue'
    ];

    $color = isset($colors[$type]) ? $colors[$type] : 'black';
    echo "<p style='color: {$color};'>";

    switch ($type) {
        case 'success':
            echo "✓ ";
            break;
        case 'error':
            echo "✗ ";
            break;
        case 'warning':
            echo "⚠ ";
            break;
        case 'info':
            echo "ℹ ";
            break;
    }

    echo $message . "</p>";
}

// Función para crear entorno de prueba
function setupTestEnvironment() {
    global $testFormId, $testUserId, $testFieldIds;

    // Crear usuario de prueba
    $sql = "SELECT id FROM usuarios WHERE username = 'api_test_user' LIMIT 1";
    $existingUser = fetchOne($sql);

    if ($existingUser) {
        $testUserId = $existingUser['id'];
        logMessage("Usando usuario de prueba existente con ID: {$testUserId}", 'info');
    } else {
        $hashedPassword = password_hash('test_password', PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (username, password, nombre_completo, estado) VALUES (?, ?, ?, ?)";
        $testUserId = insert($sql, ['api_test_user', $hashedPassword, 'Usuario API de Prueba', 'activo']);

        if ($testUserId) {
            logMessage("Usuario de prueba creado con ID: {$testUserId}", 'success');
        } else {
            logMessage("No se pudo crear el usuario de prueba", 'error');
            return false;
        }
    }

    // Crear formulario de prueba
    $formData = [
        'titulo' => 'Formulario API de Prueba - ' . date('Y-m-d H:i:s'),
        'descripcion' => 'Formulario creado automáticamente para pruebas de API',
        'estado' => 'activo'
    ];

    $testFormId = Form::create($formData);

    if ($testFormId) {
        logMessage("Formulario de prueba creado con ID: {$testFormId}", 'success');

        // Asignar el formulario al usuario de prueba
        $result = Form::assignFormToUser($testFormId, $testUserId);
        if ($result) {
            logMessage("Formulario asignado al usuario de prueba", 'success');
        } else {
            logMessage("No se pudo asignar el formulario al usuario de prueba", 'error');
        }
    } else {
        logMessage("No se pudo crear el formulario de prueba", 'error');
        return false;
    }

    // Crear campos de prueba
    $testFields = [
        [
            'tipo_campo' => 'texto',
            'etiqueta' => 'Nombre del cliente',
            'requerido' => 1,
            'propiedades' => [
                'placeholder' => 'Ingrese el nombre completo',
                'longitud_maxima' => 100
            ]
        ],
        [
            'tipo_campo' => 'fecha_hora',
            'etiqueta' => 'Fecha y hora de visita',
            'requerido' => 1
        ],
        [
            'tipo_campo' => 'ubicacion_gps',
            'etiqueta' => 'Ubicación del sitio',
            'requerido' => 1
        ],
        [
            'tipo_campo' => 'select',
            'etiqueta' => 'Tipo de servicio',
            'requerido' => 1,
            'propiedades' => [
                'opciones' => [
                    ['valor' => 'instalacion', 'texto' => 'Instalación'],
                    ['valor' => 'mantenimiento', 'texto' => 'Mantenimiento'],
                    ['valor' => 'reparacion', 'texto' => 'Reparación']
                ]
            ]
        ],
        [
            'tipo_campo' => 'textarea',
            'etiqueta' => 'Observaciones',
            'requerido' => 0,
            'propiedades' => [
                'placeholder' => 'Ingrese sus observaciones',
                'longitud_maxima' => 500
            ]
        ]
    ];

    foreach ($testFields as $index => $fieldData) {
        $fieldData['id_formulario'] = $testFormId;
        $fieldData['orden'] = $index + 1;

        $fieldId = Form::addField($fieldData);

        if ($fieldId) {
            $testFieldIds[] = $fieldId;
            logMessage("Campo '{$fieldData['etiqueta']}' creado con ID: {$fieldId}", 'success');
        } else {
            logMessage("No se pudo crear el campo '{$fieldData['etiqueta']}'", 'error');
        }
    }

    if (empty($testFieldIds)) {
        logMessage("No se pudieron crear campos para el formulario de prueba", 'error');
        return false;
    }

    return true;
}

// Función para limpiar entorno de prueba
function cleanupTestEnvironment() {
    global $testFormId, $testUserId, $testFieldIds, $testSubmissionId;

    // Eliminar el envío de prueba si existe
    if ($testSubmissionId) {
        Form::deleteSubmission($testSubmissionId);
        logMessage("Envío de prueba eliminado", 'info');
    }

    // Eliminar los campos de prueba
    foreach ($testFieldIds as $fieldId) {
        Form::deleteField($fieldId);
    }
    logMessage("Campos de prueba eliminados", 'info');

    // Eliminar el formulario de prueba
    if ($testFormId) {
        Form::delete($testFormId);
        logMessage("Formulario de prueba eliminado", 'info');
    }

    // No eliminamos el usuario de prueba para reutilizarlo en futuras pruebas
}

// Función para simular una solicitud a la API
function simulateApiRequest($endpoint, $method = 'GET', $data = null) {
    // Construir URL completa
    $url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $endpoint;

    // Inicializar cURL
    $ch = curl_init();

    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);

        if ($data !== null) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ]);
        }
    }

    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Cerrar cURL
    curl_close($ch);

    // Decodificar respuesta JSON
    $decodedResponse = json_decode($response, true);

    return [
        'http_code' => $httpCode,
        'response' => $decodedResponse,
        'raw_response' => $response
    ];
}

// Función para probar el envío de formulario a través de la API
function testApiSubmitForm() {
    global $testFormId, $testUserId, $testFieldIds, $testSubmissionId;

    echo "<h2>Prueba: Envío de formulario a través de la API</h2>";

    // Preparar datos de prueba
    $formData = [
        'form_id' => $testFormId,
        'user_id' => $testUserId,
        'data' => []
    ];

    // Añadir datos para cada campo
    foreach ($testFieldIds as $index => $fieldId) {
        switch ($index) {
            case 0: // Texto
                $formData['data'][$fieldId] = 'Cliente API de Prueba';
                break;
            case 1: // Fecha y hora
                $formData['data'][$fieldId] = date('Y-m-d H:i:s');
                break;
            case 2: // Ubicación GPS
                $formData['data'][$fieldId] = '19.4326,-99.1332';
                break;
            case 3: // Select
                $formData['data'][$fieldId] = 'instalacion';
                break;
            case 4: // Textarea
                $formData['data'][$fieldId] = 'Observaciones de prueba para el envío del formulario a través de la API';
                break;
        }
    }

    // Enviar solicitud a la API
    $result = simulateApiRequest('submit_form.php', 'POST', $formData);

    // Mostrar resultado
    echo "<h3>Solicitud enviada a la API</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($formData, JSON_PRETTY_PRINT)) . "</pre>";

    echo "<h3>Respuesta de la API</h3>";
    echo "<p>Código HTTP: " . $result['http_code'] . "</p>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";

    // Verificar resultado
    if ($result['http_code'] === 200 && isset($result['response']['success']) && $result['response']['success'] === true) {
        logMessage("Formulario enviado correctamente a través de la API", 'success');

        // Guardar ID del envío para limpieza posterior
        if (isset($result['response']['submission_id'])) {
            $testSubmissionId = $result['response']['submission_id'];
            logMessage("ID del envío: {$testSubmissionId}", 'info');
        }

        return true;
    } else {
        logMessage("Error al enviar formulario a través de la API", 'error');
        return false;
    }
}

// Función para probar la recuperación de envíos a través de la API
function testApiGetSubmissions() {
    global $testUserId, $testFormId;

    echo "<h2>Prueba: Recuperación de envíos a través de la API</h2>";

    // Enviar solicitud a la API
    $result = simulateApiRequest("get_submissions.php?user_id={$testUserId}");

    // Mostrar resultado
    echo "<h3>Respuesta de la API</h3>";
    echo "<p>Código HTTP: " . $result['http_code'] . "</p>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";

    // Verificar resultado
    if ($result['http_code'] === 200 && isset($result['response']['success']) && $result['response']['success'] === true) {
        logMessage("Envíos recuperados correctamente a través de la API", 'success');

        // Verificar que hay envíos
        if (isset($result['response']['submissions']) && !empty($result['response']['submissions'])) {
            logMessage("Se encontraron " . count($result['response']['submissions']) . " envíos", 'info');
        } else {
            logMessage("No se encontraron envíos", 'warning');
        }

        return true;
    } else {
        logMessage("Error al recuperar envíos a través de la API", 'error');
        return false;
    }
}

// Función para probar la recuperación de formularios a través de la API
function testApiGetForms() {
    global $testUserId, $testFormId;

    echo "<h2>Prueba: Recuperación de formularios a través de la API</h2>";

    // Enviar solicitud a la API
    $result = simulateApiRequest("get_forms.php?user_id={$testUserId}");

    // Mostrar resultado
    echo "<h3>Respuesta de la API</h3>";
    echo "<p>Código HTTP: " . $result['http_code'] . "</p>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";

    // Verificar resultado
    if ($result['http_code'] === 200 && isset($result['response']['success']) && $result['response']['success'] === true) {
        logMessage("Formularios recuperados correctamente a través de la API", 'success');

        // Verificar que hay formularios
        if (isset($result['response']['forms']) && !empty($result['response']['forms'])) {
            logMessage("Se encontraron " . count($result['response']['forms']) . " formularios", 'info');

            // Verificar que el formulario de prueba está en la lista
            $found = false;
            foreach ($result['response']['forms'] as $form) {
                if ($form['id'] == $testFormId) {
                    $found = true;
                    logMessage("El formulario de prueba está en la lista", 'success');
                    break;
                }
            }

            if (!$found) {
                logMessage("El formulario de prueba no está en la lista", 'warning');
            }
        } else {
            logMessage("No se encontraron formularios", 'warning');
        }

        return true;
    } else {
        logMessage("Error al recuperar formularios a través de la API", 'error');
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de API de Formularios - Dr Security</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #009688;
            border-bottom: 2px solid #009688;
            padding-bottom: 10px;
        }
        h2 {
            color: #00796b;
            margin-top: 30px;
            border-left: 4px solid #009688;
            padding-left: 10px;
        }
        h3 {
            color: #00695c;
            margin-top: 20px;
        }
        p {
            margin: 10px 0;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .nav {
            margin-bottom: 20px;
        }
        .nav a {
            display: inline-block;
            padding: 8px 16px;
            background-color: #009688;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .nav a:hover {
            background-color: #00796b;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="admin/index.php">Volver al Panel</a>
        <a href="run_form_submission_tests.php">Pruebas de Envío</a>
    </div>

    <h1>Prueba de API de Formularios</h1>

    <div style="background-color: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        <p>Esta prueba simula el uso de la API desde una aplicación externa (como Flutter) para enviar y recuperar formularios.</p>
    </div>

    <?php
    // Configurar entorno de prueba
    if (setupTestEnvironment()) {
        // Ejecutar pruebas
        testApiGetForms();
        testApiSubmitForm();
        testApiGetSubmissions();

        // Limpiar entorno de prueba
        cleanupTestEnvironment();
    } else {
        echo "<p style='color: red; font-weight: bold;'>No se pudo configurar el entorno de prueba</p>";
    }
    ?>

    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
