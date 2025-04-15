<?php
/**
 * Script para verificar la estructura de la tabla campos_formulario
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Verificar la estructura de la tabla
$sql = "SHOW COLUMNS FROM campos_formulario";
$result = $conn->query($sql);

echo "<h2>Estructura de la tabla campos_formulario</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Cerrar conexión
$conn->close();
?>
