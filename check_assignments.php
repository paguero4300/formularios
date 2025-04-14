<?php
/**
 * Script para verificar asignaciones
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Conectar a la base de datos
$conn = getDBConnection();

// Verificar si hay asignaciones
$sql = "SELECT * FROM asignaciones_formulario";
$result = $conn->query($sql);

echo "<h2>Asignaciones de formularios a usuarios</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>ID Usuario</th><th>ID Formulario</th><th>Fecha Asignación</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['id_usuario'] . "</td>";
        echo "<td>" . $row['id_formulario'] . "</td>";
        echo "<td>" . $row['fecha_asignacion'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay asignaciones en la tabla.</p>";
}

// Verificar usuarios
$sql = "SELECT id, username, nombre_completo FROM usuarios";
$result = $conn->query($sql);

echo "<h2>Usuarios</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nombre Completo</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['nombre_completo'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay usuarios en la tabla.</p>";
}

// Verificar formularios
$sql = "SELECT id, titulo FROM formularios";
$result = $conn->query($sql);

echo "<h2>Formularios</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Título</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['titulo'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay formularios en la tabla.</p>";
}

// Cerrar conexión
$conn->close();
?>
