<?php
/**
 * Script para asignar formularios al usuario de prueba
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/form.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Obtener ID del usuario de prueba
$sql = "SELECT id FROM usuarios WHERE username = 'test'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $userId = $result->fetch_assoc()['id'];
    
    // Obtener formularios
    $sql = "SELECT id FROM formularios LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $formId = $result->fetch_assoc()['id'];
        
        // Limpiar asignaciones existentes para el usuario
        $sql = "DELETE FROM asignaciones_formulario WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Asignar formulario al usuario
        $result = Form::assignFormToUser($formId, $userId);
        
        if ($result) {
            echo "<p>Formulario ID $formId asignado al usuario de prueba (ID $userId).</p>";
        } else {
            echo "<p>Error al asignar el formulario al usuario de prueba.</p>";
        }
    } else {
        echo "<p>No hay formularios disponibles.</p>";
    }
} else {
    echo "<p>El usuario de prueba no existe.</p>";
}

echo "<p><a href='check_assignments.php'>Verificar asignaciones</a></p>";
echo "<p><a href='index.php'>Ir a la página de inicio de sesión</a></p>";

// Cerrar conexión
$conn->close();
?>
