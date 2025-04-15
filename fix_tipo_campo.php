<?php
/**
 * Script para corregir la columna tipo_campo en la tabla campos_formulario
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
    <title>Corrección de Estructura de Base de Datos - Dr Security</title>
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
    
    <h1>Corrección de Estructura de Base de Datos</h1>
    
    <div class="info">
        <p>Este script corrige la estructura de la tabla <code>campos_formulario</code> para permitir los nuevos tipos de campos.</p>
    </div>
    
    <?php
    // Obtener conexión a la base de datos
    $conn = getDBConnection();
    
    // Verificar la estructura actual de la tabla
    echo "<h2>Estructura Actual de la Tabla</h2>";
    
    $sql = "SHOW COLUMNS FROM campos_formulario LIKE 'tipo_campo'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $column = $result->fetch_assoc();
        echo "<p>Columna <code>tipo_campo</code> encontrada con tipo: <strong>{$column['Type']}</strong></p>";
        
        // Verificar si la columna ya tiene el tipo correcto
        if (strpos($column['Type'], 'varchar') !== false) {
            echo "<p class='success'>La columna ya tiene el tipo correcto (VARCHAR). No es necesario modificarla.</p>";
        } else {
            // Modificar la columna para aceptar los nuevos tipos de campos
            echo "<h2>Modificando la Estructura de la Tabla</h2>";
            
            // Crear una copia de seguridad de la tabla
            echo "<p>Creando copia de seguridad de la tabla...</p>";
            
            $backupTable = "campos_formulario_backup_" . date("Ymd_His");
            $sqlBackup = "CREATE TABLE {$backupTable} LIKE campos_formulario";
            
            if ($conn->query($sqlBackup)) {
                echo "<p class='success'>Tabla de respaldo creada: <code>{$backupTable}</code></p>";
                
                $sqlCopyData = "INSERT INTO {$backupTable} SELECT * FROM campos_formulario";
                if ($conn->query($sqlCopyData)) {
                    echo "<p class='success'>Datos copiados a la tabla de respaldo</p>";
                } else {
                    echo "<p class='error'>Error al copiar datos: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='error'>Error al crear tabla de respaldo: " . $conn->error . "</p>";
            }
            
            // Modificar la columna tipo_campo
            echo "<p>Modificando la columna <code>tipo_campo</code>...</p>";
            
            $sqlAlter = "ALTER TABLE campos_formulario MODIFY COLUMN tipo_campo VARCHAR(50) NOT NULL";
            
            if ($conn->query($sqlAlter)) {
                echo "<p class='success'>Columna modificada correctamente</p>";
                
                // Verificar la nueva estructura
                $sqlVerify = "SHOW COLUMNS FROM campos_formulario LIKE 'tipo_campo'";
                $resultVerify = $conn->query($sqlVerify);
                
                if ($resultVerify && $resultVerify->num_rows > 0) {
                    $columnVerify = $resultVerify->fetch_assoc();
                    echo "<p class='success'>Nueva estructura de la columna: <strong>{$columnVerify['Type']}</strong></p>";
                }
            } else {
                echo "<p class='error'>Error al modificar la columna: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p class='error'>No se pudo obtener información de la columna: " . $conn->error . "</p>";
    }
    
    // Verificar los tipos de campos existentes
    echo "<h2>Tipos de Campos Existentes</h2>";
    
    $sqlTypes = "SELECT DISTINCT tipo_campo FROM campos_formulario";
    $resultTypes = $conn->query($sqlTypes);
    
    if ($resultTypes && $resultTypes->num_rows > 0) {
        echo "<ul>";
        while ($row = $resultTypes->fetch_assoc()) {
            echo "<li>{$row['tipo_campo']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron campos en la tabla</p>";
    }
    
    // Cerrar conexión
    $conn->close();
    ?>
    
    <h2>Próximos Pasos</h2>
    
    <p>Después de ejecutar este script, debes ejecutar los siguientes scripts para verificar que todo funciona correctamente:</p>
    
    <ol>
        <li><a href="test_fields_standalone.php">Prueba de Campos Dinámicos</a></li>
        <li><a href="test_reorder_fields.php">Prueba de Reordenamiento de Campos</a></li>
        <li><a href="run_form_submission_tests.php">Prueba de Envío de Formularios</a></li>
        <li><a href="test_api_submission.php">Prueba de API</a></li>
    </ol>
    
    <div class="nav" style="margin-top: 30px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
