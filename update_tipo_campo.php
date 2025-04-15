<?php
/**
 * Script para actualizar la columna tipo_campo de la tabla campos_formulario
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Verificar la estructura actual
$sql = "SHOW COLUMNS FROM campos_formulario LIKE 'tipo_campo'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "<h2>Estructura actual de la columna tipo_campo</h2>";
echo "<pre>";
print_r($row);
echo "</pre>";

// Modificar la columna para permitir los nuevos tipos
$sql = "ALTER TABLE campos_formulario MODIFY COLUMN tipo_campo VARCHAR(20) NOT NULL";

if ($conn->query($sql) === TRUE) {
    echo "<div style='color: green; font-weight: bold;'>La columna tipo_campo ha sido actualizada correctamente a VARCHAR(20).</div>";
} else {
    echo "<div style='color: red; font-weight: bold;'>Error al actualizar la columna: " . $conn->error . "</div>";
}

// Verificar la nueva estructura
$sql = "SHOW COLUMNS FROM campos_formulario LIKE 'tipo_campo'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "<h2>Nueva estructura de la columna tipo_campo</h2>";
echo "<pre>";
print_r($row);
echo "</pre>";

// Cerrar conexión
$conn->close();

echo "<p><a href='admin/form_fields.php?form_id=4'>Volver a la página de campos</a></p>";
?>
