<?php
/**
 * Script para verificar que la API funciona correctamente
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
    <title>Verificación de API - Dr Security</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
    
    <h1>Verificación de API</h1>
    
    <div class="info">
        <p>Este script verifica que la API funciona correctamente y muestra información útil para la depuración.</p>
    </div>
    
    <h2>Información del Servidor</h2>
    
    <table>
        <tr>
            <th>Variable</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>HTTP_HOST</td>
            <td><?php echo $_SERVER['HTTP_HOST']; ?></td>
        </tr>
        <tr>
            <td>PHP_SELF</td>
            <td><?php echo $_SERVER['PHP_SELF']; ?></td>
        </tr>
        <tr>
            <td>SCRIPT_NAME</td>
            <td><?php echo $_SERVER['SCRIPT_NAME']; ?></td>
        </tr>
        <tr>
            <td>DOCUMENT_ROOT</td>
            <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
        </tr>
        <tr>
            <td>REQUEST_URI</td>
            <td><?php echo $_SERVER['REQUEST_URI']; ?></td>
        </tr>
        <tr>
            <td>dirname(PHP_SELF)</td>
            <td><?php echo dirname($_SERVER['PHP_SELF']); ?></td>
        </tr>
        <tr>
            <td>Versión de PHP</td>
            <td><?php echo phpversion(); ?></td>
        </tr>
    </table>
    
    <h2>Verificación de Archivos de API</h2>
    
    <?php
    $apiFiles = [
        'api/get_forms.php',
        'api/submit_form.php',
        'api/get_submissions.php'
    ];
    
    echo "<table>";
    echo "<tr><th>Archivo</th><th>Existe</th><th>Tamaño</th><th>Permisos</th><th>Última modificación</th></tr>";
    
    foreach ($apiFiles as $apiFile) {
        echo "<tr>";
        echo "<td>{$apiFile}</td>";
        
        if (file_exists($apiFile)) {
            echo "<td class='success'>Sí</td>";
            echo "<td>" . filesize($apiFile) . " bytes</td>";
            echo "<td>" . substr(sprintf('%o', fileperms($apiFile)), -4) . "</td>";
            echo "<td>" . date("Y-m-d H:i:s", filemtime($apiFile)) . "</td>";
        } else {
            echo "<td class='error'>No</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    ?>
    
    <h2>Prueba de URLs de API</h2>
    
    <?php
    function testApiUrl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        return [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }
    
    echo "<table>";
    echo "<tr><th>URL</th><th>Código HTTP</th><th>Respuesta</th></tr>";
    
    foreach ($apiFiles as $apiFile) {
        $apiEndpoint = basename($apiFile);
        
        // Probar con la ruta /api/
        $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/' . $apiEndpoint;
        $result = testApiUrl($apiUrl);
        
        echo "<tr>";
        echo "<td><a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></td>";
        echo "<td>" . $result['http_code'] . "</td>";
        
        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            echo "<td class='success'>OK</td>";
        } else {
            echo "<td class='error'>" . ($result['error'] ? $result['error'] : "Error HTTP " . $result['http_code']) . "</td>";
        }
        
        echo "</tr>";
        
        // Probar con la ruta /formularios/api/
        $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/formularios/api/' . $apiEndpoint;
        $result = testApiUrl($apiUrl);
        
        echo "<tr>";
        echo "<td><a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></td>";
        echo "<td>" . $result['http_code'] . "</td>";
        
        if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
            echo "<td class='success'>OK</td>";
        } else {
            echo "<td class='error'>" . ($result['error'] ? $result['error'] : "Error HTTP " . $result['http_code']) . "</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    ?>
    
    <h2>Prueba de Conexión a la Base de Datos</h2>
    
    <?php
    // Intentar incluir los archivos de configuración
    $configFiles = [
        'config/config.php',
        'config/database.php'
    ];
    
    $configLoaded = true;
    
    foreach ($configFiles as $configFile) {
        if (file_exists($configFile)) {
            echo "<p class='success'>El archivo {$configFile} existe</p>";
            
            try {
                require_once $configFile;
                echo "<p class='success'>El archivo {$configFile} se cargó correctamente</p>";
            } catch (Exception $e) {
                echo "<p class='error'>Error al cargar {$configFile}: " . $e->getMessage() . "</p>";
                $configLoaded = false;
            }
        } else {
            echo "<p class='error'>El archivo {$configFile} no existe</p>";
            $configLoaded = false;
        }
    }
    
    if ($configLoaded) {
        // Intentar conectar a la base de datos
        try {
            $conn = getDBConnection();
            echo "<p class='success'>Conexión a la base de datos establecida correctamente</p>";
            
            // Verificar si la tabla campos_formulario existe
            $sql = "SHOW TABLES LIKE 'campos_formulario'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                echo "<p class='success'>La tabla campos_formulario existe</p>";
                
                // Verificar la estructura de la tabla
                $sql = "SHOW COLUMNS FROM campos_formulario LIKE 'tipo_campo'";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    $column = $result->fetch_assoc();
                    echo "<p class='success'>La columna tipo_campo existe con tipo: " . $column['Type'] . "</p>";
                    
                    // Verificar si la columna tiene el tipo correcto
                    if (strpos($column['Type'], 'varchar') !== false) {
                        echo "<p class='success'>La columna tipo_campo tiene el tipo correcto (VARCHAR)</p>";
                    } else {
                        echo "<p class='warning'>La columna tipo_campo no tiene el tipo correcto. Tipo actual: " . $column['Type'] . "</p>";
                    }
                } else {
                    echo "<p class='error'>La columna tipo_campo no existe</p>";
                }
            } else {
                echo "<p class='error'>La tabla campos_formulario no existe</p>";
            }
            
            $conn->close();
        } catch (Exception $e) {
            echo "<p class='error'>Error al conectar a la base de datos: " . $e->getMessage() . "</p>";
        }
    }
    ?>
    
    <h2>Próximos Pasos</h2>
    
    <p>Después de verificar que la API funciona correctamente, debes ejecutar los siguientes scripts:</p>
    
    <ol>
        <li><a href="fix_tipo_campo.php">Corregir la estructura de la tabla</a></li>
        <li><a href="fix_form_submission_test.php">Corregir el script de prueba de envío</a></li>
        <li><a href="db_update_campos.php">Actualizar la base de datos para campos dinámicos</a></li>
        <li><a href="test_fields_standalone.php">Prueba de campos dinámicos</a></li>
        <li><a href="test_reorder_fields.php">Prueba de reordenamiento de campos</a></li>
        <li><a href="run_form_submission_tests.php">Prueba de envío de formularios</a></li>
        <li><a href="test_api_v2.php">Prueba de API</a></li>
    </ol>
    
    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
