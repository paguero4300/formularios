<?php
/**
 * Script para crear un usuario de prueba
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Verificar si ya existe el usuario de prueba
$sql = "SELECT id FROM usuarios WHERE username = 'test'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p>El usuario de prueba ya existe.</p>";
} else {
    // Crear usuario de prueba
    $username = 'test';
    $password = password_hash('test123', PASSWORD_DEFAULT);
    $nombreCompleto = 'Usuario de Prueba';
    
    $sql = "INSERT INTO usuarios (username, password, nombre_completo, estado) VALUES (?, ?, ?, 'activo')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $nombreCompleto);
    
    if ($stmt->execute()) {
        echo "<p>Usuario de prueba creado correctamente.</p>";
        echo "<p>Username: test</p>";
        echo "<p>Password: test123</p>";
    } else {
        echo "<p>Error al crear el usuario de prueba: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

echo "<p><a href='index.php'>Ir a la página de inicio de sesión</a></p>";

// Cerrar conexión
$conn->close();
?>
