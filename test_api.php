<?php
/**
 * Script para probar las API del panel administrativo Dr Security
 */

// URL base de la API
$baseUrl = 'http://localhost/formulario/api';

echo "<h1>Prueba de API Dr Security</h1>";

// Función para realizar solicitudes HTTP
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data))
            ]);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "Error cURL: " . curl_error($ch);
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => $response ? json_decode($response, true) : null
    ];
}

// 1. Probar el endpoint de login
echo "<h2>1. Prueba de login</h2>";
$loginData = [
    'username' => 'admin',
    'password' => 'admin123'
];

$loginResult = makeRequest("$baseUrl/login.php", 'POST', $loginData);

echo "<p>Código de respuesta: " . $loginResult['code'] . "</p>";
echo "<pre>" . json_encode($loginResult['response'], JSON_PRETTY_PRINT) . "</pre>";

// Verificar si el login fue exitoso
if ($loginResult['code'] === 200 && isset($loginResult['response']['success']) && $loginResult['response']['success']) {
    $userId = $loginResult['response']['user']['id'];
    
    // 2. Probar el endpoint para obtener formularios
    echo "<h2>2. Prueba de obtención de formularios</h2>";
    $formsResult = makeRequest("$baseUrl/get_forms.php?user_id=$userId");
    
    echo "<p>Código de respuesta: " . $formsResult['code'] . "</p>";
    echo "<pre>" . json_encode($formsResult['response'], JSON_PRETTY_PRINT) . "</pre>";
    
    // Verificar si se obtuvieron formularios
    if ($formsResult['code'] === 200 && isset($formsResult['response']['success']) && $formsResult['response']['success']) {
        if (count($formsResult['response']['forms']) > 0) {
            $formId = $formsResult['response']['forms'][0]['id'];
            $campos = $formsResult['response']['forms'][0]['campos'];
            
            // Preparar datos para enviar un formulario
            $formData = [
                'form_id' => $formId,
                'user_id' => $userId,
                'data' => []
            ];
            
            // Generar datos de ejemplo para cada campo
            foreach ($campos as $campo) {
                switch ($campo['tipo_campo']) {
                    case 'lugar':
                        $formData['data'][$campo['id']] = 'Oficina Central';
                        break;
                    case 'fecha_hora':
                        $formData['data'][$campo['id']] = date('Y-m-d H:i:s');
                        break;
                    case 'ubicacion_gps':
                        $formData['data'][$campo['id']] = '19.4326,-99.1332';
                        break;
                    case 'comentario':
                        $formData['data'][$campo['id']] = 'Inspección de rutina completada sin incidentes.';
                        break;
                }
            }
            
            // 3. Probar el endpoint para enviar formularios
            echo "<h2>3. Prueba de envío de formulario</h2>";
            $submitResult = makeRequest("$baseUrl/submit_form.php", 'POST', $formData);
            
            echo "<p>Código de respuesta: " . $submitResult['code'] . "</p>";
            echo "<pre>" . json_encode($submitResult['response'], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>No se encontraron formularios para probar el envío.</p>";
        }
    } else {
        echo "<p>Error al obtener formularios.</p>";
    }
} else {
    echo "<p>Error en la autenticación. No se pueden probar los demás endpoints.</p>";
}
?>
