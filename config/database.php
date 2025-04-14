<?php
/**
 * Configuración de la base de datos
 * Panel administrativo Dr Security
 */

// Parámetros de conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);       // Puerto de MySQL
define('DB_USER', 'root');     // Cambia esto por tu usuario de MySQL
define('DB_PASS', '123456');         // Cambia esto por tu contraseña de MySQL
define('DB_NAME', 'dr_security');

// Crear conexión a la base de datos
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Establecer charset
    $conn->set_charset("utf8");

    return $conn;
}

// Función para ejecutar consultas y manejar errores
function executeQuery($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    // Vincular parámetros si existen
    if (!empty($params)) {
        $types = '';
        $bindParams = [];

        // Determinar los tipos de datos
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $bindParams[] = $param;
        }

        // Preparar array para bind_param
        $bindParamsRef = [];
        foreach ($bindParams as $key => $value) {
            $bindParamsRef[$key] = &$bindParams[$key];
        }

        // Añadir el string de tipos al inicio del array
        array_unshift($bindParamsRef, $types);

        // Vincular parámetros
        call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
    }

    // Ejecutar consulta
    $stmt->execute();

    // Verificar errores
    if ($stmt->error) {
        die("Error en la ejecución de la consulta: " . $stmt->error);
    }

    // Obtener resultados si es una consulta SELECT
    $result = $stmt->get_result();

    // Cerrar statement
    $stmt->close();

    // Cerrar conexión
    $conn->close();

    return $result;
}

// Función para obtener un solo registro
function fetchOne($sql, $params = []) {
    $result = executeQuery($sql, $params);
    return $result->fetch_assoc();
}

// Función para obtener múltiples registros
function fetchAll($sql, $params = []) {
    $result = executeQuery($sql, $params);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para insertar registros y obtener el ID insertado
function insert($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    // Vincular parámetros si existen
    if (!empty($params)) {
        $types = '';
        $bindParams = [];

        // Determinar los tipos de datos
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $bindParams[] = $param;
        }

        // Preparar array para bind_param
        $bindParamsRef = [];
        foreach ($bindParams as $key => $value) {
            $bindParamsRef[$key] = &$bindParams[$key];
        }

        // Añadir el string de tipos al inicio del array
        array_unshift($bindParamsRef, $types);

        // Vincular parámetros
        call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
    }

    // Ejecutar consulta
    $stmt->execute();

    // Verificar errores
    if ($stmt->error) {
        die("Error en la ejecución de la consulta: " . $stmt->error);
    }

    // Obtener el ID insertado
    $insertId = $conn->insert_id;

    // Cerrar statement
    $stmt->close();

    // Cerrar conexión
    $conn->close();

    return $insertId;
}

// Función para actualizar o eliminar registros y obtener el número de filas afectadas
function update($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    // Vincular parámetros si existen
    if (!empty($params)) {
        $types = '';
        $bindParams = [];

        // Determinar los tipos de datos
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $bindParams[] = $param;
        }

        // Preparar array para bind_param
        $bindParamsRef = [];
        foreach ($bindParams as $key => $value) {
            $bindParamsRef[$key] = &$bindParams[$key];
        }

        // Añadir el string de tipos al inicio del array
        array_unshift($bindParamsRef, $types);

        // Vincular parámetros
        call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
    }

    // Ejecutar consulta
    $stmt->execute();

    // Verificar errores
    if ($stmt->error) {
        die("Error en la ejecución de la consulta: " . $stmt->error);
    }

    // Obtener el número de filas afectadas
    $affectedRows = $stmt->affected_rows;

    // Cerrar statement
    $stmt->close();

    // Cerrar conexión
    $conn->close();

    return $affectedRows;
}
?>
