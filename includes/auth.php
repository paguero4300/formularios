<?php
/**
 * Funciones de autenticación
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Clase para manejar la autenticación de usuarios
 */
class Auth {
    /**
     * Intenta autenticar a un usuario con las credenciales proporcionadas
     * 
     * @param string $username Nombre de usuario
     * @param string $password Contraseña
     * @return bool|array Datos del usuario si la autenticación es exitosa, false en caso contrario
     */
    public static function login($username, $password) {
        // Sanitizar entradas
        $username = sanitize($username);
        
        // Buscar usuario en la base de datos
        $sql = "SELECT * FROM usuarios WHERE username = ? AND estado = 'activo' LIMIT 1";
        $user = fetchOne($sql, [$username]);
        
        // Verificar si el usuario existe y la contraseña es correcta
        if ($user && password_verify($password, $user['password'])) {
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            
            // Registrar actividad
            logActivity($user['id'], 'login', 'Inicio de sesión exitoso');
            
            return $user;
        }
        
        // Registrar intento fallido (opcional)
        // logActivity(0, 'login_failed', 'Intento fallido para el usuario: ' . $username);
        
        return false;
    }
    
    /**
     * Cierra la sesión del usuario actual
     */
    public static function logout() {
        // Registrar actividad antes de cerrar sesión
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'logout', 'Cierre de sesión');
        }
        
        // Destruir todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    /**
     * Verifica si el usuario actual está autenticado
     * 
     * @return bool True si el usuario está autenticado, false en caso contrario
     */
    public static function check() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obtiene el ID del usuario actual
     * 
     * @return int|null ID del usuario o null si no está autenticado
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtiene el nombre de usuario actual
     * 
     * @return string|null Nombre de usuario o null si no está autenticado
     */
    public static function username() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Obtiene el nombre completo del usuario actual
     * 
     * @return string|null Nombre completo o null si no está autenticado
     */
    public static function fullName() {
        return $_SESSION['nombre_completo'] ?? null;
    }
    
    /**
     * Obtiene los datos completos del usuario actual desde la base de datos
     * 
     * @return array|null Datos del usuario o null si no está autenticado
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        $sql = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
        return fetchOne($sql, [self::id()]);
    }
    
    /**
     * Cambia la contraseña del usuario
     * 
     * @param int $userId ID del usuario
     * @param string $currentPassword Contraseña actual
     * @param string $newPassword Nueva contraseña
     * @return bool True si el cambio fue exitoso, false en caso contrario
     */
    public static function changePassword($userId, $currentPassword, $newPassword) {
        // Obtener datos del usuario
        $sql = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
        $user = fetchOne($sql, [$userId]);
        
        // Verificar si el usuario existe y la contraseña actual es correcta
        if ($user && password_verify($currentPassword, $user['password'])) {
            // Generar hash de la nueva contraseña
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Actualizar contraseña en la base de datos
            $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
            $result = update($sql, [$passwordHash, $userId]);
            
            // Registrar actividad
            if ($result) {
                logActivity($userId, 'change_password', 'Cambio de contraseña exitoso');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Restablece la contraseña de un usuario (para administradores)
     * 
     * @param int $userId ID del usuario
     * @param string $newPassword Nueva contraseña
     * @return bool True si el restablecimiento fue exitoso, false en caso contrario
     */
    public static function resetPassword($userId, $newPassword) {
        // Verificar si el usuario actual es administrador
        if (!self::check()) {
            return false;
        }
        
        // Generar hash de la nueva contraseña
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Actualizar contraseña en la base de datos
        $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
        $result = update($sql, [$passwordHash, $userId]);
        
        // Registrar actividad
        if ($result) {
            logActivity(self::id(), 'reset_password', 'Restablecimiento de contraseña para el usuario ID: ' . $userId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica si la API key proporcionada es válida
     * 
     * @param string $apiKey API key a verificar
     * @return bool|array Datos del usuario si la API key es válida, false en caso contrario
     */
    public static function verifyApiKey($apiKey) {
        // Esta función podría implementarse si se decide usar API keys
        // Por ahora, usamos autenticación básica para la API
        return false;
    }
}
?>
