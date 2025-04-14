<?php
/**
 * Script de inicialización de la base de datos
 * Panel administrativo Dr Security
 */

// Parámetros de conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);       // Puerto de MySQL
define('DB_USER', 'root');     // Cambia esto por tu usuario de MySQL
define('DB_PASS', '123456');         // Cambia esto por tu contraseña de MySQL

// Conectar a MySQL sin seleccionar base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS dr_security CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Base de datos creada correctamente o ya existente<br>";
} else {
    die("Error al crear la base de datos: " . $conn->error);
}

// Seleccionar la base de datos
$conn->select_db("dr_security");

// Crear tabla de usuarios
$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'usuarios' creada correctamente o ya existente<br>";
} else {
    die("Error al crear la tabla 'usuarios': " . $conn->error);
}

// Crear tabla de formularios
$sql = "CREATE TABLE IF NOT EXISTS formularios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'formularios' creada correctamente o ya existente<br>";
} else {
    die("Error al crear la tabla 'formularios': " . $conn->error);
}

// Crear tabla de campos de formulario
$sql = "CREATE TABLE IF NOT EXISTS campos_formulario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_formulario INT NOT NULL,
    tipo_campo ENUM('lugar', 'fecha_hora', 'ubicacion_gps', 'comentario') NOT NULL,
    etiqueta VARCHAR(100) NOT NULL,
    requerido BOOLEAN NOT NULL DEFAULT 1,
    orden INT NOT NULL,
    FOREIGN KEY (id_formulario) REFERENCES formularios(id) ON DELETE CASCADE
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'campos_formulario' creada correctamente o ya existente<br>";
} else {
    die("Error al crear la tabla 'campos_formulario': " . $conn->error);
}

// Crear tabla de envíos de formulario
$sql = "CREATE TABLE IF NOT EXISTS envios_formulario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_formulario INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datos JSON NOT NULL,
    FOREIGN KEY (id_formulario) REFERENCES formularios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'envios_formulario' creada correctamente o ya existente<br>";
} else {
    die("Error al crear la tabla 'envios_formulario': " . $conn->error);
}

// Crear tabla de asignaciones de formularios a usuarios
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

// Crear usuario administrador por defecto si no existe
$checkAdmin = "SELECT * FROM usuarios WHERE username = 'admin'";
$result = $conn->query($checkAdmin);

if ($result->num_rows == 0) {
    // Crear usuario administrador
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (username, password, nombre_completo, estado)
            VALUES ('admin', '$password', 'Administrador', 'activo')";

    if ($conn->query($sql) === TRUE) {
        echo "Usuario administrador creado correctamente<br>";
        echo "Usuario: admin<br>";
        echo "Contraseña: admin123<br>";
        echo "<strong>¡Importante! Cambia esta contraseña después de iniciar sesión por primera vez.</strong><br>";
    } else {
        echo "Error al crear el usuario administrador: " . $conn->error . "<br>";
    }
} else {
    echo "El usuario administrador ya existe<br>";
}

// Crear formulario de ejemplo si no existe
$checkForm = "SELECT * FROM formularios";
$result = $conn->query($checkForm);

if ($result->num_rows == 0) {
    // Crear formulario de ejemplo
    $sql = "INSERT INTO formularios (titulo, descripcion, estado)
            VALUES ('Formulario de Inspección', 'Formulario para registrar inspecciones de seguridad', 'activo')";

    if ($conn->query($sql) === TRUE) {
        $formId = $conn->insert_id;
        echo "Formulario de ejemplo creado correctamente<br>";

        // Crear campos para el formulario de ejemplo
        $campos = [
            ['lugar', 'Información del lugar', 1, 1],
            ['fecha_hora', 'Fecha y hora', 1, 2],
            ['ubicacion_gps', 'Ubicación GPS', 1, 3],
            ['comentario', 'Comentario', 1, 4]
        ];

        foreach ($campos as $index => $campo) {
            $sql = "INSERT INTO campos_formulario (id_formulario, tipo_campo, etiqueta, requerido, orden)
                    VALUES ($formId, '{$campo[0]}', '{$campo[1]}', {$campo[2]}, {$campo[3]})";

            if ($conn->query($sql) === TRUE) {
                echo "Campo '{$campo[1]}' creado correctamente<br>";
            } else {
                echo "Error al crear el campo '{$campo[1]}': " . $conn->error . "<br>";
            }
        }
    } else {
        echo "Error al crear el formulario de ejemplo: " . $conn->error . "<br>";
    }
} else {
    echo "Ya existen formularios en la base de datos<br>";
}

// Cerrar conexión
$conn->close();

echo "<br>Inicialización de la base de datos completada.<br>";
echo "<a href='index.php'>Ir al panel de administración</a>";
?>
