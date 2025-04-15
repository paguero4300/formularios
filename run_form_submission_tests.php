<?php
/**
 * Script para ejecutar las pruebas de envío y recuperación de formularios
 * Panel administrativo Dr Security
 */

// Establecer tiempo máximo de ejecución
set_time_limit(120);

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas de Envío de Formularios - Dr Security</title>
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
            background-color: #e8f5e9;
        }
        .error {
            background-color: #ffebee;
        }
        .warning {
            background-color: #fff8e1;
        }
        .info {
            background-color: #e3f2fd;
        }
        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        .summary h3 {
            margin-top: 0;
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
        <a href="test_fields_standalone.php">Pruebas de Campos</a>
        <a href="test_reorder_fields.php">Pruebas de Reordenamiento</a>
    </div>
    
    <h1>Pruebas de Envío y Recuperación de Formularios</h1>
    
    <div class="info">
        <p>Las pruebas crearán un formulario temporal, campos y un usuario de prueba para validar el envío y recuperación de formularios. Todo será eliminado automáticamente al finalizar.</p>
    </div>
    
    <?php
    // Incluir y ejecutar las pruebas
    require_once 'tests/test_form_submission.php';
    
    $tester = new FormSubmissionTest();
    $tester->runAllTests();
    ?>
    
    <div class="nav">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
