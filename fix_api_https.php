<?php
/**
 * Script para corregir los scripts de API para usar HTTPS
 * Panel administrativo Dr Security
 */

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de API para HTTPS - Dr Security</title>
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
    
    <h1>Corrección de API para HTTPS</h1>
    
    <div class="info">
        <p>Este script corrige los scripts de prueba de API para usar HTTPS en lugar de HTTP.</p>
    </div>
    
    <?php
    // Archivos a corregir
    $files = [
        'test_api_submission.php',
        'test_api_v2.php'
    ];
    
    foreach ($files as $file) {
        echo "<h2>Archivo: {$file}</h2>";
        
        if (file_exists($file)) {
            echo "<p class='success'>El archivo existe</p>";
            
            // Leer el contenido del archivo
            $content = file_get_contents($file);
            
            // Verificar si contiene la función simulateApiRequest
            if (strpos($content, 'function simulateApiRequest') !== false) {
                echo "<p class='success'>Se encontró la función simulateApiRequest</p>";
                
                // Buscar la línea que construye la URL
                $pattern = "/\\\$url = 'http:\/\/' \. \\\$_SERVER\['HTTP_HOST'\] \. '\/api\/' \. \\\$endpoint;/";
                
                if (preg_match($pattern, $content)) {
                    echo "<p class='success'>Se encontró la línea que construye la URL con HTTP</p>";
                    
                    // Reemplazar la línea por una versión que use HTTPS
                    $newContent = preg_replace(
                        $pattern,
                        "\$url = 'https://' . \$_SERVER['HTTP_HOST'] . '/api/' . \$endpoint;",
                        $content
                    );
                    
                    // Verificar si tiene las opciones SSL
                    if (strpos($newContent, 'CURLOPT_SSL_VERIFYPEER') === false) {
                        // Añadir opciones SSL después de CURLOPT_FOLLOWLOCATION
                        $newContent = str_replace(
                            "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);",
                            "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);\n    curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL para pruebas\n    curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, false); // Desactivar verificación de host para pruebas",
                            $newContent
                        );
                    }
                    
                    // Guardar el archivo corregido
                    if (file_put_contents($file, $newContent)) {
                        echo "<p class='success'>Archivo corregido correctamente</p>";
                        echo "<p>Se ha modificado la URL para usar HTTPS y se han añadido opciones SSL.</p>";
                    } else {
                        echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
                    }
                } else {
                    // Buscar la línea con HTTPS (ya corregida)
                    $pattern = "/\\\$url = 'https:\/\/' \. \\\$_SERVER\['HTTP_HOST'\] \. '\/api\/' \. \\\$endpoint;/";
                    
                    if (preg_match($pattern, $content)) {
                        echo "<p class='success'>El archivo ya usa HTTPS</p>";
                        
                        // Verificar si tiene las opciones SSL
                        if (strpos($content, 'CURLOPT_SSL_VERIFYPEER') === false) {
                            // Añadir opciones SSL después de CURLOPT_FOLLOWLOCATION
                            $newContent = str_replace(
                                "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);",
                                "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);\n    curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL para pruebas\n    curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, false); // Desactivar verificación de host para pruebas",
                                $content
                            );
                            
                            // Guardar el archivo corregido
                            if (file_put_contents($file, $newContent)) {
                                echo "<p class='success'>Se han añadido opciones SSL al archivo</p>";
                            } else {
                                echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
                            }
                        } else {
                            echo "<p class='success'>El archivo ya tiene las opciones SSL configuradas</p>";
                        }
                    } else {
                        echo "<p class='warning'>No se encontró la línea exacta que construye la URL. Se intentará una corrección manual.</p>";
                        
                        // Reemplazar manualmente la línea
                        $newContent = str_replace(
                            '$url = \'http://\' . $_SERVER[\'HTTP_HOST\'] . \'/api/\' . $endpoint;',
                            '$url = \'https://\' . $_SERVER[\'HTTP_HOST\'] . \'/api/\' . $endpoint;',
                            $content
                        );
                        
                        // Verificar si tiene las opciones SSL
                        if (strpos($newContent, 'CURLOPT_SSL_VERIFYPEER') === false) {
                            // Añadir opciones SSL después de CURLOPT_FOLLOWLOCATION
                            $newContent = str_replace(
                                "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);",
                                "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);\n    curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL para pruebas\n    curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, false); // Desactivar verificación de host para pruebas",
                                $newContent
                            );
                        }
                        
                        // Guardar el archivo corregido
                        if (file_put_contents($file, $newContent)) {
                            echo "<p class='success'>Archivo corregido manualmente</p>";
                            echo "<p>Se ha modificado la URL para usar HTTPS y se han añadido opciones SSL.</p>";
                        } else {
                            echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
                        }
                    }
                }
            } else {
                echo "<p class='error'>No se encontró la función simulateApiRequest en el archivo.</p>";
            }
        } else {
            echo "<p class='error'>El archivo no existe</p>";
        }
    }
    ?>
    
    <h2>Instrucciones para Corregir Manualmente</h2>
    
    <div class="instructions">
        <p>Si no se pudieron corregir los archivos automáticamente debido a problemas de permisos, sigue estas instrucciones para corregirlos manualmente:</p>
        
        <ol>
            <li>Abre el archivo <code>test_api_submission.php</code> en un editor de texto.</li>
            <li>Busca la función <code>simulateApiRequest</code>.</li>
            <li>Dentro de esta función, busca la línea que construye la URL (similar a <code>$url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $endpoint;</code>).</li>
            <li>Cambia <code>http://</code> a <code>https://</code>.</li>
            <li>Después de la línea <code>curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);</code>, añade las siguientes líneas:
                <div class="code">curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL para pruebas
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Desactivar verificación de host para pruebas</div>
            </li>
            <li>Guarda el archivo.</li>
            <li>Repite los mismos pasos para el archivo <code>test_api_v2.php</code> si existe.</li>
        </ol>
    </div>
    
    <h2>Código Corregido</h2>
    
    <p>Aquí está el código corregido que debe usarse en los scripts de prueba:</p>
    
    <div class="code">
// Función para simular una solicitud a la API
function simulateApiRequest($endpoint, $method = 'GET', $data = null) {
    // Construir URL completa con HTTPS
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
    </div>
    
    <h2>Próximos Pasos</h2>
    
    <p>Después de corregir los scripts para usar HTTPS, debes ejecutar los siguientes scripts:</p>
    
    <ol>
        <li><a href="test_api_v2.php">Prueba de API</a></li>
        <li><a href="test_fields_standalone.php">Prueba de campos dinámicos</a></li>
        <li><a href="test_reorder_fields.php">Prueba de reordenamiento de campos</a></li>
        <li><a href="run_form_submission_tests.php">Prueba de envío de formularios</a></li>
    </ol>
    
    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
