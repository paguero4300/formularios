<?php
/**
 * Script para corregir el error en las pruebas de envío de formularios
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
    <title>Corrección de Pruebas de Envío - Dr Security</title>
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
    
    <h1>Corrección de Pruebas de Envío de Formularios</h1>
    
    <div class="info">
        <p>Este script corrige el error en las pruebas de envío de formularios.</p>
    </div>
    
    <?php
    // Verificar si el archivo existe
    $testFile = 'tests/test_form_submission.php';
    
    if (file_exists($testFile)) {
        echo "<h2>Archivo de Prueba Encontrado</h2>";
        
        // Leer el contenido del archivo
        $content = file_get_contents($testFile);
        
        // Verificar si contiene la función delete()
        if (strpos($content, 'delete($sql, [$this->testUserId]);') !== false) {
            echo "<p class='error'>Se encontró el error: Llamada a la función inexistente <code>delete()</code></p>";
            
            // Reemplazar la función delete() por update()
            $newContent = str_replace(
                'delete($sql, [$this->testUserId]);',
                'update($sql, [$this->testUserId]);',
                $content
            );
            
            // Guardar el archivo corregido
            if (file_put_contents($testFile, $newContent)) {
                echo "<p class='success'>Archivo corregido correctamente</p>";
                echo "<p>Se ha reemplazado la función <code>delete()</code> por <code>update()</code> en la línea 63.</p>";
            } else {
                echo "<p class='error'>No se pudo escribir en el archivo. Verifica los permisos.</p>";
            }
        } else {
            echo "<p class='warning'>No se encontró el error específico en el archivo. Es posible que ya haya sido corregido o que el error sea diferente.</p>";
        }
    } else {
        echo "<p class='error'>No se encontró el archivo de prueba: {$testFile}</p>";
    }
    ?>
    
    <h2>Próximos Pasos</h2>
    
    <p>Después de ejecutar este script, debes ejecutar los siguientes scripts para verificar que todo funciona correctamente:</p>
    
    <ol>
        <li><a href="run_form_submission_tests.php">Prueba de Envío de Formularios</a></li>
        <li><a href="test_api_submission.php">Prueba de API</a></li>
    </ol>
    
    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
