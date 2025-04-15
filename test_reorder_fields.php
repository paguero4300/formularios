<?php
/**
 * Script para probar la función reorderFields
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/form.php';

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Reordenamiento de Campos - Dr Security</title>
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
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
        }
        .info {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 10px;
            border-radius: 4px;
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
    
    <h1>Prueba de Reordenamiento de Campos</h1>
    
    <?php
    // Crear un formulario de prueba
    $formData = [
        'titulo' => 'Formulario de Prueba Reordenamiento - ' . date('Y-m-d H:i:s'),
        'descripcion' => 'Formulario creado automáticamente para probar reordenamiento',
        'estado' => 'activo'
    ];
    
    $testFormId = Form::create($formData);
    
    if (!$testFormId) {
        echo '<div class="error">No se pudo crear el formulario de prueba</div>';
        exit;
    }
    
    echo '<div class="success">Formulario de prueba creado con ID: ' . $testFormId . '</div>';
    
    // Crear campos de prueba
    $fieldTypes = [
        ['tipo' => 'texto', 'etiqueta' => 'Nombre'],
        ['tipo' => 'email', 'etiqueta' => 'Correo electrónico'],
        ['tipo' => 'numero', 'etiqueta' => 'Edad'],
        ['tipo' => 'fecha_hora', 'etiqueta' => 'Fecha y hora'],
        ['tipo' => 'ubicacion_gps', 'etiqueta' => 'Ubicación']
    ];
    
    $fieldIds = [];
    
    foreach ($fieldTypes as $index => $field) {
        $fieldData = [
            'id_formulario' => $testFormId,
            'tipo_campo' => $field['tipo'],
            'etiqueta' => $field['etiqueta'],
            'requerido' => 1,
            'orden' => $index + 1
        ];
        
        $fieldId = Form::addField($fieldData);
        
        if ($fieldId) {
            $fieldIds[] = $fieldId;
            echo '<div class="info">Campo "' . $field['etiqueta'] . '" creado con ID: ' . $fieldId . '</div>';
        } else {
            echo '<div class="error">Error al crear el campo "' . $field['etiqueta'] . '"</div>';
        }
    }
    
    // Mostrar campos en orden original
    echo '<h2>Campos en orden original</h2>';
    $fields = Form::getFields($testFormId);
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Tipo</th><th>Etiqueta</th><th>Orden</th></tr>';
    
    foreach ($fields as $field) {
        echo '<tr>';
        echo '<td>' . $field['id'] . '</td>';
        echo '<td>' . $field['tipo_campo'] . '</td>';
        echo '<td>' . $field['etiqueta'] . '</td>';
        echo '<td>' . $field['orden'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Invertir el orden de los campos
    $reversedFieldIds = array_reverse($fieldIds);
    
    echo '<h2>Reordenando campos (orden inverso)</h2>';
    
    $reorderResult = Form::reorderFields($testFormId, $reversedFieldIds);
    
    if ($reorderResult) {
        echo '<div class="success">Campos reordenados correctamente</div>';
    } else {
        echo '<div class="error">Error al reordenar los campos</div>';
    }
    
    // Mostrar campos en nuevo orden
    echo '<h2>Campos en nuevo orden</h2>';
    $fields = Form::getFields($testFormId);
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Tipo</th><th>Etiqueta</th><th>Orden</th></tr>';
    
    foreach ($fields as $field) {
        echo '<tr>';
        echo '<td>' . $field['id'] . '</td>';
        echo '<td>' . $field['tipo_campo'] . '</td>';
        echo '<td>' . $field['etiqueta'] . '</td>';
        echo '<td>' . $field['orden'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Verificar que el orden se ha invertido correctamente
    $success = true;
    foreach ($fields as $index => $field) {
        if ($field['id'] != $reversedFieldIds[$index]) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        echo '<div class="success">Verificación exitosa: Los campos se han reordenado correctamente</div>';
    } else {
        echo '<div class="error">Verificación fallida: Los campos no se han reordenado correctamente</div>';
    }
    
    // Limpiar: eliminar los campos creados
    foreach ($fieldIds as $fieldId) {
        Form::deleteField($fieldId);
    }
    
    // Limpiar: eliminar el formulario de prueba
    Form::delete($testFormId);
    echo '<div class="info">Limpieza completada: formulario y campos de prueba eliminados</div>';
    ?>
    
    <div class="nav" style="margin-top: 20px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
