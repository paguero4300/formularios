<?php
/**
 * Script para verificar la estructura de la tabla envios_formulario
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Verificar la estructura de la tabla
$sql = "SHOW COLUMNS FROM envios_formulario";
$result = $conn->query($sql);

echo "<h2>Estructura de la tabla envios_formulario</h2>";
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

// Mostrar algunos registros de ejemplo
$sql = "SELECT * FROM envios_formulario LIMIT 5";
$result = $conn->query($sql);

echo "<h2>Registros de ejemplo en envios_formulario</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>ID Formulario</th><th>ID Usuario</th><th>Datos</th><th>Fecha Envío</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['id_formulario'] . "</td>";
        echo "<td>" . $row['id_usuario'] . "</td>";
        echo "<td><pre>" . htmlspecialchars(substr($row['datos'], 0, 100)) . (strlen($row['datos']) > 100 ? '...' : '') . "</pre></td>";
        echo "<td>" . $row['fecha_envio'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay registros en la tabla envios_formulario</p>";
}

// Cerrar conexión
$conn->close();
?>
