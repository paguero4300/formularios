<?php
/**
 * Script para actualizar la tabla de campos de formulario
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Verificar si la columna propiedades ya existe
$checkColumn = "SHOW COLUMNS FROM campos_formulario LIKE 'propiedades'";
$result = $conn->query($checkColumn);

if ($result->num_rows == 0) {
    // Agregar columna propiedades de tipo JSON
    $sql = "ALTER TABLE campos_formulario ADD COLUMN propiedades JSON NULL AFTER requerido";
    
    if ($conn->query($sql) === TRUE) {
        echo "Columna 'propiedades' agregada correctamente a la tabla 'campos_formulario'<br>";
    } else {
        die("Error al agregar la columna 'propiedades': " . $conn->error);
    }
} else {
    echo "La columna 'propiedades' ya existe en la tabla 'campos_formulario'<br>";
}

// Actualizar los campos existentes para establecer propiedades por defecto
$sql = "UPDATE campos_formulario SET propiedades = JSON_OBJECT() WHERE propiedades IS NULL";
if ($conn->query($sql) === TRUE) {
    echo "Propiedades por defecto establecidas para campos existentes<br>";
} else {
    echo "Error al establecer propiedades por defecto: " . $conn->error . "<br>";
}

// Cerrar conexión
$conn->close();

echo "<br>Actualización de la tabla de campos completada.<br>";
echo "<a href='index.php'>Ir al panel de administración</a>";
?>
