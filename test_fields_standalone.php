<?php
/**
 * Script independiente para probar la creación de campos de formulario
 * Panel administrativo Dr Security
 */

// Establecer tiempo máximo de ejecución
set_time_limit(120);

// Establecer cabeceras
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/form.php';

// Función para mostrar mensajes con formato
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

// Función para ejecutar una prueba
function runTest($testName, $callback) {
    echo "<h2>Prueba: {$testName}</h2>";
    
    try {
        $result = $callback();
        if ($result === true) {
            logMessage("La prueba '{$testName}' ha pasado correctamente", 'success');
            return true;
        } else {
            logMessage("La prueba '{$testName}' ha fallado: " . ($result ?: 'Error desconocido'), 'error');
            return false;
        }
    } catch (Exception $e) {
        logMessage("Error en la prueba '{$testName}': " . $e->getMessage(), 'error');
        return false;
    }
}

// Iniciar HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Campos de Formulario - Dr Security</title>
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
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
    
    <h1>Prueba de Campos de Formulario</h1>
    
    <?php
    // Crear un formulario de prueba
    $formData = [
        'titulo' => 'Formulario de Prueba - ' . date('Y-m-d H:i:s'),
        'descripcion' => 'Formulario creado automáticamente para pruebas',
        'estado' => 'activo'
    ];
    
    $testFormId = Form::create($formData);
    
    if (!$testFormId) {
        logMessage("No se pudo crear el formulario de prueba", 'error');
        exit;
    }
    
    logMessage("Formulario de prueba creado con ID: {$testFormId}", 'success');
    
    // Array para almacenar los IDs de los campos creados
    $createdFieldIds = [];
    $totalTests = 0;
    $passedTests = 0;
    
    // Prueba 1: Crear campo de texto
    $totalTests++;
    $result = runTest('Crear campo de texto', function() use ($testFormId, &$createdFieldIds) {
        $fieldData = [
            'id_formulario' => $testFormId,
            'tipo_campo' => 'texto',
            'etiqueta' => 'Nombre completo',
            'requerido' => 1,
            'orden' => 1,
            'propiedades' => [
                'placeholder' => 'Ingrese su nombre completo',
                'longitud_maxima' => 100
            ]
        ];
        
        $fieldId = Form::addField($fieldData);
        
        if (!$fieldId) {
            return "No se pudo crear el campo de texto";
        }
        
        $createdFieldIds[] = $fieldId;
        
        // Verificar que el campo se creó correctamente
        $field = Form::getFieldById($fieldId);
        
        if (!$field || $field['tipo_campo'] !== 'texto') {
            return "El campo creado no tiene el tipo correcto";
        }
        
        // Verificar propiedades
        $propiedades = json_decode($field['propiedades'], true);
        
        if (!$propiedades || 
            !isset($propiedades['placeholder']) || 
            $propiedades['placeholder'] !== 'Ingrese su nombre completo' ||
            !isset($propiedades['longitud_maxima']) || 
            $propiedades['longitud_maxima'] != 100) {
            return "Las propiedades del campo no se guardaron correctamente";
        }
        
        return true;
    });
    
    if ($result) $passedTests++;
    
    // Prueba 2: Crear campo numérico
    $totalTests++;
    $result = runTest('Crear campo numérico', function() use ($testFormId, &$createdFieldIds) {
        $fieldData = [
            'id_formulario' => $testFormId,
            'tipo_campo' => 'numero',
            'etiqueta' => 'Edad',
            'requerido' => 1,
            'orden' => 2,
            'propiedades' => [
                'min' => 18,
                'max' => 99
            ]
        ];
        
        $fieldId = Form::addField($fieldData);
        
        if (!$fieldId) {
            return "No se pudo crear el campo numérico";
        }
        
        $createdFieldIds[] = $fieldId;
        
        // Verificar que el campo se creó correctamente
        $field = Form::getFieldById($fieldId);
        
        if (!$field || $field['tipo_campo'] !== 'numero') {
            return "El campo creado no tiene el tipo correcto";
        }
        
        // Verificar propiedades
        $propiedades = json_decode($field['propiedades'], true);
        
        if (!$propiedades || 
            !isset($propiedades['min']) || 
            $propiedades['min'] != 18 ||
            !isset($propiedades['max']) || 
            $propiedades['max'] != 99) {
            return "Las propiedades del campo no se guardaron correctamente";
        }
        
        return true;
    });
    
    if ($result) $passedTests++;
    
    // Prueba 3: Crear campo select
    $totalTests++;
    $result = runTest('Crear campo select', function() use ($testFormId, &$createdFieldIds) {
        $fieldData = [
            'id_formulario' => $testFormId,
            'tipo_campo' => 'select',
            'etiqueta' => 'País',
            'requerido' => 1,
            'orden' => 3,
            'propiedades' => [
                'opciones' => [
                    ['valor' => 'mx', 'texto' => 'México'],
                    ['valor' => 'us', 'texto' => 'Estados Unidos'],
                    ['valor' => 'ca', 'texto' => 'Canadá']
                ]
            ]
        ];
        
        $fieldId = Form::addField($fieldData);
        
        if (!$fieldId) {
            return "No se pudo crear el campo select";
        }
        
        $createdFieldIds[] = $fieldId;
        
        // Verificar que el campo se creó correctamente
        $field = Form::getFieldById($fieldId);
        
        if (!$field || $field['tipo_campo'] !== 'select') {
            return "El campo creado no tiene el tipo correcto";
        }
        
        // Verificar propiedades
        $propiedades = json_decode($field['propiedades'], true);
        
        if (!$propiedades || 
            !isset($propiedades['opciones']) || 
            !is_array($propiedades['opciones']) ||
            count($propiedades['opciones']) !== 3) {
            return "Las propiedades del campo no se guardaron correctamente";
        }
        
        return true;
    });
    
    if ($result) $passedTests++;
    
    // Prueba 4: Crear campo fecha_hora
    $totalTests++;
    $result = runTest('Crear campo fecha_hora', function() use ($testFormId, &$createdFieldIds) {
        $fieldData = [
            'id_formulario' => $testFormId,
            'tipo_campo' => 'fecha_hora',
            'etiqueta' => 'Fecha y hora de visita',
            'requerido' => 1,
            'orden' => 4
        ];
        
        $fieldId = Form::addField($fieldData);
        
        if (!$fieldId) {
            return "No se pudo crear el campo fecha_hora";
        }
        
        $createdFieldIds[] = $fieldId;
        
        // Verificar que el campo se creó correctamente
        $field = Form::getFieldById($fieldId);
        
        if (!$field || $field['tipo_campo'] !== 'fecha_hora') {
            return "El campo creado no tiene el tipo correcto";
        }
        
        return true;
    });
    
    if ($result) $passedTests++;
    
    // Prueba 5: Crear campo ubicacion_gps
    $totalTests++;
    $result = runTest('Crear campo ubicacion_gps', function() use ($testFormId, &$createdFieldIds) {
        $fieldData = [
            'id_formulario' => $testFormId,
            'tipo_campo' => 'ubicacion_gps',
            'etiqueta' => 'Ubicación del sitio',
            'requerido' => 1,
            'orden' => 5
        ];
        
        $fieldId = Form::addField($fieldData);
        
        if (!$fieldId) {
            return "No se pudo crear el campo ubicacion_gps";
        }
        
        $createdFieldIds[] = $fieldId;
        
        // Verificar que el campo se creó correctamente
        $field = Form::getFieldById($fieldId);
        
        if (!$field || $field['tipo_campo'] !== 'ubicacion_gps') {
            return "El campo creado no tiene el tipo correcto";
        }
        
        return true;
    });
    
    if ($result) $passedTests++;
    
    // Mostrar resumen
    echo "<h2>Resumen de Pruebas</h2>";
    echo "<p>Total de pruebas: {$totalTests}</p>";
    echo "<p>Pruebas exitosas: {$passedTests}</p>";
    echo "<p>Pruebas fallidas: " . ($totalTests - $passedTests) . "</p>";
    
    if ($totalTests == $passedTests) {
        echo "<p style='color: green; font-weight: bold;'>¡Todas las pruebas pasaron correctamente!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>Algunas pruebas fallaron. Revisa los detalles arriba.</p>";
    }
    
    // Mostrar los campos creados
    echo "<h2>Campos Creados</h2>";
    
    $fields = Form::getFields($testFormId);
    
    if (empty($fields)) {
        echo "<p>No se encontraron campos para el formulario de prueba.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Tipo</th><th>Etiqueta</th><th>Requerido</th><th>Orden</th><th>Propiedades</th></tr>";
        
        foreach ($fields as $field) {
            echo "<tr>";
            echo "<td>{$field['id']}</td>";
            echo "<td>{$field['tipo_campo']}</td>";
            echo "<td>{$field['etiqueta']}</td>";
            echo "<td>" . ($field['requerido'] ? 'Sí' : 'No') . "</td>";
            echo "<td>{$field['orden']}</td>";
            echo "<td><pre>" . htmlspecialchars(print_r(json_decode($field['propiedades'], true), true)) . "</pre></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Limpiar: eliminar los campos creados
    foreach ($createdFieldIds as $fieldId) {
        Form::deleteField($fieldId);
    }
    
    // Limpiar: eliminar el formulario de prueba
    Form::delete($testFormId);
    logMessage("Limpieza completada: formulario y campos de prueba eliminados", 'info');
    ?>
    
    <div class="nav" style="margin-top: 20px;">
        <a href="admin/index.php">Volver al Panel</a>
    </div>
</body>
</html>
