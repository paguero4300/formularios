<?php
/**
 * Script para mostrar los cambios necesarios en las pruebas de API
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instrucciones para Corregir API - Dr Security</title>
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
        p {
            margin: 10px 0;
            padding: 5px;
            border-radius: 4px;
        }
        .success {
            color: green;
            background-color: #e8f5e9;
        }
        .error {
            color: red;
            background-color: #ffebee;
        }
        .warning {
            color: orange;
            background-color: #fff8e1;
        }
        .info {
            color: blue;
            background-color: #e3f2fd;
        }
        .code {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
            white-space: pre-wrap;
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
        .instructions {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .instructions ol {
            margin-left: 20px;
            padding-left: 20px;
        }
        .instructions li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
    
    <h1>Instrucciones para Corregir la API</h1>
    
    <div class="info">
        <p>Este script muestra los cambios necesarios para corregir las pruebas de API sin intentar modificar archivos.</p>
    </div>
    
    <?php
    // Verificar si el archivo existe
    $testFile = 'test_api_submission.php';
    
    if (file_exists($testFile)) {
        echo "<h2>Archivo de Prueba Encontrado</h2>";
        
        // Leer el contenido del archivo
        $content = file_get_contents($testFile);
        
        // Verificar si contiene la función simulateApiRequest
        if (strpos($content, 'function simulateApiRequest') !== false) {
            echo "<p class='success'>Se encontró la función simulateApiRequest</p>";
            
            // Extraer la línea que construye la URL
            preg_match('/\$url = \'http:\/\/\' \. \$_SERVER\[\'HTTP_HOST\'\] \. \'.*\/api\/\' \. \$endpoint;/', $content, $matches);
            
            if (!empty($matches)) {
                echo "<p class='success'>Se encontró la línea que construye la URL: <code>" . htmlspecialchars($matches[0]) . "</code></p>";
                
                // Mostrar la línea corregida
                $correctedLine = '$url = \'http://\' . $_SERVER[\'HTTP_HOST\'] . \'/api/\' . $endpoint;';
                echo "<p class='info'>La línea debe cambiarse a: <code>" . htmlspecialchars($correctedLine) . "</code></p>";
            } else {
                echo "<p class='warning'>No se pudo encontrar la línea exacta que construye la URL.</p>";
            }
            
            // Verificar si tiene la opción para seguir redirecciones
            if (strpos($content, 'CURLOPT_FOLLOWLOCATION') !== false) {
                echo "<p class='success'>El archivo ya tiene configurada la opción para seguir redirecciones.</p>";
            } else {
                echo "<p class='warning'>El archivo no tiene configurada la opción para seguir redirecciones.</p>";
                echo "<p class='info'>Debe añadirse la siguiente línea después de <code>curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);</code>:</p>";
                echo "<p class='code'>curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);</p>";
            }
        } else {
            echo "<p class='error'>No se encontró la función simulateApiRequest en el archivo.</p>";
        }
    } else {
        echo "<p class='error'>No se encontró el archivo de prueba: {$testFile}</p>";
    }
    
    // Verificar si los archivos de API existen
    echo "<h2>Verificación de Archivos de API</h2>";
    
    $apiFiles = [
        'api/get_forms.php',
        'api/submit_form.php',
        'api/get_submissions.php'
    ];
    
    $allFilesExist = true;
    
    foreach ($apiFiles as $apiFile) {
        if (file_exists($apiFile)) {
            echo "<p class='success'>El archivo {$apiFile} existe</p>";
        } else {
            echo "<p class='error'>El archivo {$apiFile} no existe</p>";
            $allFilesExist = false;
        }
    }
    
    if ($allFilesExist) {
        echo "<p class='success'>Todos los archivos de API existen</p>";
    } else {
        echo "<p class='error'>Faltan algunos archivos de API. Asegúrate de que todos los archivos necesarios estén presentes.</p>";
    }
    
    // Verificar la configuración del servidor
    echo "<h2>Verificación de Configuración del Servidor</h2>";
    
    echo "<p>URL del servidor: " . $_SERVER['HTTP_HOST'] . "</p>";
    echo "<p>Ruta del script: " . $_SERVER['PHP_SELF'] . "</p>";
    echo "<p>Directorio del script: " . dirname($_SERVER['PHP_SELF']) . "</p>";
    
    // Construir y probar las URLs de API
    echo "<h2>Prueba de URLs de API</h2>";
    
    foreach ($apiFiles as $apiFile) {
        $apiEndpoint = basename($apiFile);
        $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $apiEndpoint;
        
        echo "<p>URL de API: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";
        
        // Probar la URL con cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "<p class='success'>La URL {$apiUrl} es accesible (Código HTTP: {$httpCode})</p>";
        } else {
            echo "<p class='error'>La URL {$apiUrl} no es accesible (Código HTTP: {$httpCode})</p>";
        }
    }
    ?>
    
    <h2>Instrucciones para Corregir Manualmente</h2>
    
    <div class="instructions">
        <p>Debido a problemas de permisos, no podemos modificar automáticamente el archivo. Sigue estas instrucciones para corregirlo manualmente:</p>
        
        <ol>
            <li>Abre el archivo <code>test_api_submission.php</code> en un editor de texto.</li>
            <li>Busca la función <code>simulateApiRequest</code> (alrededor de la línea 190).</li>
            <li>Dentro de esta función, busca la línea que construye la URL (similar a <code>$url = 'http://' . $_SERVER['HTTP_HOST'] . '/formularios/api/' . $endpoint;</code>).</li>
            <li>Cambia esta línea a: <code>$url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $endpoint;</code></li>
            <li>Asegúrate de que después de la línea <code>curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);</code> exista la siguiente línea: <code>curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);</code></li>
            <li>Si no existe, añádela.</li>
            <li>Guarda el archivo.</li>
        </ol>
        
        <p>Después de realizar estos cambios, ejecuta los siguientes scripts para verificar que todo funciona correctamente:</p>
        
        <ol>
            <li><a href="test_api_submission.php">Prueba de API</a></li>
        </ol>
    </div>
    
    <h2>Solución Alternativa: Crear un Nuevo Archivo de Prueba</h2>
    
    <div class="instructions">
        <p>Si no puedes modificar el archivo original, puedes crear un nuevo archivo de prueba con la configuración correcta:</p>
        
        <ol>
            <li>Crea un nuevo archivo llamado <code>test_api_v2.php</code> con el siguiente contenido:</li>
        </ol>
        
        <div class="code">&lt;?php
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
    echo "&lt;p style='color: {$color};'>";
    
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
    
    echo $message . "&lt;/p>";
}

// Función para simular una solicitud a la API
function simulateApiRequest($endpoint, $method = 'GET', $data = null) {
    // Construir URL completa con la ruta correcta
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

// Prueba simple de la API
function testApi() {
    echo "&lt;h2>Prueba de API&lt;/h2>";
    
    // Probar get_forms.php
    echo "&lt;h3>Prueba de get_forms.php&lt;/h3>";
    $result = simulateApiRequest("get_forms.php?user_id=1");
    echo "&lt;p>Código HTTP: " . $result['http_code'] . "&lt;/p>";
    echo "&lt;pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "&lt;/pre>";
    
    // Probar submit_form.php
    echo "&lt;h3>Prueba de submit_form.php&lt;/h3>";
    $formData = [
        'form_id' => 1,
        'user_id' => 1,
        'data' => [
            '1' => 'Valor de prueba'
        ]
    ];
    $result = simulateApiRequest("submit_form.php", "POST", $formData);
    echo "&lt;p>Código HTTP: " . $result['http_code'] . "&lt;/p>";
    echo "&lt;pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "&lt;/pre>";
    
    // Probar get_submissions.php
    echo "&lt;h3>Prueba de get_submissions.php&lt;/h3>";
    $result = simulateApiRequest("get_submissions.php?user_id=1");
    echo "&lt;p>Código HTTP: " . $result['http_code'] . "&lt;/p>";
    echo "&lt;pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "&lt;/pre>";
}
?>
&lt;!DOCTYPE html>
&lt;html lang="es">
&lt;head>
    &lt;meta charset="UTF-8">
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0">
    &lt;title>Prueba de API v2 - Dr Security&lt;/title>
    &lt;style>
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
    &lt;/style>
&lt;/head>
&lt;body>
    &lt;div class="nav">
        &lt;a href="admin/index.php">Volver al Panel&lt;/a>
    &lt;/div>
    
    &lt;h1>Prueba de API v2&lt;/h1>
    
    &lt;div style="background-color: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        &lt;p>Esta prueba verifica que la API funciona correctamente con la configuración corregida.&lt;/p>
    &lt;/div>
    
    &lt;?php
    // Ejecutar prueba de API
    testApi();
    ?>
    
    &lt;div class="nav" style="margin-top: 30px;">
        &lt;a href="admin/index.php">Volver al Panel&lt;/a>
    &lt;/div>
&lt;/body>
&lt;/html></div>
    </div>
    
    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
