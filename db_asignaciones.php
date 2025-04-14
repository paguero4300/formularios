<?php
/**
 * Script para crear la tabla de asignaciones de formularios a usuarios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Crear tabla de asignaciones si no existe
$sql = "CREATE TABLE IF NOT EXISTS asignaciones_formulario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_formulario INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_formulario) REFERENCES formularios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (id_usuario, id_formulario)
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'asignaciones_formulario' creada correctamente o ya existente<br>";
} else {
    die("Error al crear la tabla 'asignaciones_formulario': " . $conn->error);
}

// Cerrar conexión
$conn->close();

echo "<br>Tabla de asignaciones creada correctamente.<br>";
echo "<a href='index.php'>Ir al panel de administración</a>";
?>
