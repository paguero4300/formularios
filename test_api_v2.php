<?php
/**
 * Script para probar la API con la configuración correcta
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/form.php';

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');

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

// Función para simular una solicitud a la API
function simulateApiRequest($endpoint, $method = 'GET', $data = null) {
    // Construir URL completa con la ruta correcta y HTTPS
    $url = 'https://' . $_SERVER['HTTP_HOST'] . '/api/' . $endpoint;

    // Inicializar cURL
    $ch = curl_init();

    // Configurar opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL para pruebas
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Desactivar verificación de host para pruebas

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

// Prueba simple de la API
function testApi() {
    echo "<h2>Prueba de API</h2>";

    // Probar get_forms.php
    echo "<h3>Prueba de get_forms.php</h3>";
    $result = simulateApiRequest("get_forms.php?user_id=1");
    echo "<p>Código HTTP: " . $result['http_code'] . "</p>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";

    // Probar submit_form.php
    echo "<h3>Prueba de submit_form.php</h3>";
    $formData = [
        'form_id' => 1,
        'user_id' => 1,
        'data' => [
            '1' => 'Valor de prueba'
        ]
    ];
    $result = simulateApiRequest("submit_form.php", "POST", $formData);
    echo "<p>Código HTTP: " . $result['http_code'] . "</p>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";

    // Probar get_submissions.php
    echo "<h3>Prueba de get_submissions.php</h3>";
    $result = simulateApiRequest("get_submissions.php?user_id=1");
    echo "<p>Código HTTP: " . $result['http_code'] . "</p>";
    echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de API v2 - Dr Security</title>
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
    </div>

    <h1>Prueba de API v2</h1>

    <div style="background-color: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        <p>Esta prueba verifica que la API funciona correctamente con la configuración corregida.</p>
    </div>

    <?php
    // Ejecutar prueba de API
    testApi();
    ?>

    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
