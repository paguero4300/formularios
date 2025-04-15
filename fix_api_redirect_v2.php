<?php
/**
 * Script para corregir el problema de redirección en las pruebas de API (versión 2)
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
    <title>Corrección de Redirección de API (v2) - Dr Security</title>
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
    
    <h1>Corrección de Redirección de API (v2)</h1>
    
    <div class="info">
        <p>Este script corrige el problema de redirección en las pruebas de API con la URL correcta.</p>
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
            
            // Buscar la línea que construye la URL
            $pattern = "/\\\$url = 'http:\/\/' \. \\\$_SERVER\['HTTP_HOST'\] \. '\/formularios\/api\/' \. \\\$endpoint;/";
            
            if (preg_match($pattern, $content)) {
                echo "<p class='success'>Se encontró la línea que construye la URL con la ruta incorrecta</p>";
                
                // Reemplazar la línea por una versión que use la URL absoluta correcta
                $newContent = preg_replace(
                    $pattern,
                    "\$url = 'http://' . \$_SERVER['HTTP_HOST'] . '/api/' . \$endpoint;",
                    $content
                );
                
                // Guardar el archivo corregido
                if (file_put_contents($testFile, $newContent)) {
                    echo "<p class='success'>Archivo corregido correctamente</p>";
                    echo "<p>Se ha modificado la construcción de la URL para usar la ruta correcta: <code>/api/</code></p>";
                } else {
                    echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
                }
            } else {
                // Buscar la línea con la URL incorrecta (versión alternativa)
                $pattern = "/\\\$url = 'http:\/\/' \. \\\$_SERVER\['HTTP_HOST'\] \. '.*\/api\/' \. \\\$endpoint;/";
                
                if (preg_match($pattern, $content, $matches)) {
                    echo "<p class='success'>Se encontró la línea que construye la URL con formato alternativo: <code>" . htmlspecialchars($matches[0]) . "</code></p>";
                    
                    // Reemplazar la línea por una versión que use la URL absoluta correcta
                    $newContent = preg_replace(
                        $pattern,
                        "\$url = 'http://' . \$_SERVER['HTTP_HOST'] . '/api/' . \$endpoint;",
                        $content
                    );
                    
                    // Guardar el archivo corregido
                    if (file_put_contents($testFile, $newContent)) {
                        echo "<p class='success'>Archivo corregido correctamente</p>";
                        echo "<p>Se ha modificado la construcción de la URL para usar la ruta correcta: <code>/api/</code></p>";
                    } else {
                        echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
                    }
                } else {
                    echo "<p class='warning'>No se encontró la línea exacta que construye la URL. Se intentará una corrección manual.</p>";
                    
                    // Reemplazar manualmente la línea
                    $newContent = str_replace(
                        '$url = \'http://\' . $_SERVER[\'HTTP_HOST\'] . \'/formularios/api/\' . $endpoint;',
                        '$url = \'http://\' . $_SERVER[\'HTTP_HOST\'] . \'/api/\' . $endpoint;',
                        $content
                    );
                    
                    // Guardar el archivo corregido
                    if (file_put_contents($testFile, $newContent)) {
                        echo "<p class='success'>Archivo corregido manualmente</p>";
                        echo "<p>Se ha modificado la construcción de la URL para usar la ruta correcta: <code>/api/</code></p>";
                    } else {
                        echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
                    }
                }
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
    
    <h2>Próximos Pasos</h2>
    
    <p>Después de ejecutar este script, debes ejecutar el siguiente script para verificar que las pruebas de API funcionan correctamente:</p>
    
    <ol>
        <li><a href="test_api_submission.php">Prueba de API</a></li>
    </ol>
    
    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
