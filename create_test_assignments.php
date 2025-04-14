<?php
/**
 * Script para crear asignaciones de prueba
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/form.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Obtener usuarios que no sean admin
$sql = "SELECT id FROM usuarios WHERE username != 'admin'";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row['id'];
    }
}

// Obtener formularios
$sql = "SELECT id FROM formularios";
$result = $conn->query($sql);

$forms = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $forms[] = $row['id'];
    }
}

// Limpiar asignaciones existentes
$sql = "DELETE FROM asignaciones_formulario";
$conn->query($sql);

echo "<h2>Creando asignaciones de prueba</h2>";

// Crear asignaciones
$count = 0;
if (!empty($users) && !empty($forms)) {
    // Asignar el primer formulario al primer usuario
    if (isset($users[0]) && isset($forms[0])) {
        $userId = $users[0];
        $formId = $forms[0];
        
        $result = Form::assignFormToUser($formId, $userId);
        
        if ($result) {
            echo "<p>Asignado formulario ID $formId a usuario ID $userId</p>";
            $count++;
        }
    }
    
    // Si hay más de un formulario, asignar el segundo formulario al primer usuario también
    if (isset($users[0]) && isset($forms[1])) {
        $userId = $users[0];
        $formId = $forms[1];
        
        $result = Form::assignFormToUser($formId, $userId);
        
        if ($result) {
            echo "<p>Asignado formulario ID $formId a usuario ID $userId</p>";
            $count++;
        }
    }
}

echo "<p>Total de asignaciones creadas: $count</p>";
echo "<p><a href='check_assignments.php'>Verificar asignaciones</a></p>";
echo "<p><a href='admin/forms.php'>Ir al panel de administración</a></p>";

// Cerrar conexión
$conn->close();
?>
