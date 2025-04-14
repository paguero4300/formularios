<?php
/**
 * Clase para gestión de usuarios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Clase para manejar operaciones relacionadas con usuarios
 */
class User {
    /**
     * Obtiene todos los usuarios
     * 
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @return array Arreglo con los usuarios y metadatos de paginación
     */
    public static function getAll($page = 1, $perPage = 10) {
        // Calcular offset para paginación
        $offset = ($page - 1) * $perPage;
        
        // Obtener total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM usuarios";
        $result = fetchOne($sqlCount);
        $total = $result['total'];
        
        // Calcular total de páginas
        $totalPages = ceil($total / $perPage);
        
        // Obtener usuarios para la página actual
        $sql = "SELECT * FROM usuarios ORDER BY id DESC LIMIT ? OFFSET ?";
        $users = fetchAll($sql, [$perPage, $offset]);
        
        return [
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Busca usuarios por término de búsqueda
     * 
     * @param string $searchTerm Término de búsqueda
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @return array Arreglo con los usuarios y metadatos de paginación
     */
    public static function search($searchTerm, $page = 1, $perPage = 10) {
        // Sanitizar término de búsqueda
        $searchTerm = '%' . sanitize($searchTerm) . '%';
        
        // Calcular offset para paginación
        $offset = ($page - 1) * $perPage;
        
        // Obtener total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM usuarios 
                    WHERE username LIKE ? OR nombre_completo LIKE ?";
        $result = fetchOne($sqlCount, [$searchTerm, $searchTerm]);
        $total = $result['total'];
        
        // Calcular total de páginas
        $totalPages = ceil($total / $perPage);
        
        // Obtener usuarios para la página actual
        $sql = "SELECT * FROM usuarios 
                WHERE username LIKE ? OR nombre_completo LIKE ? 
                ORDER BY id DESC LIMIT ? OFFSET ?";
        $users = fetchAll($sql, [$searchTerm, $searchTerm, $perPage, $offset]);
        
        return [
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Obtiene un usuario por su ID
     * 
     * @param int $id ID del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public static function getById($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
        return fetchOne($sql, [$id]);
    }
    
    /**
     * Obtiene un usuario por su nombre de usuario
     * 
     * @param string $username Nombre de usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public static function getByUsername($username) {
        $sql = "SELECT * FROM usuarios WHERE username = ? LIMIT 1";
        return fetchOne($sql, [$username]);
    }
    
    /**
     * Crea un nuevo usuario
     * 
     * @param array $data Datos del usuario
     * @return int|bool ID del usuario creado o false si hubo un error
     */
    public static function create($data) {
        // Verificar si el nombre de usuario ya existe
        $existingUser = self::getByUsername($data['username']);
        if ($existingUser) {
            return false;
        }
        
        // Generar hash de la contraseña
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insertar usuario en la base de datos
        $sql = "INSERT INTO usuarios (username, password, nombre_completo, estado) 
                VALUES (?, ?, ?, ?)";
        
        return insert($sql, [
            $data['username'],
            $passwordHash,
            $data['nombre_completo'],
            $data['estado']
        ]);
    }
    
    /**
     * Actualiza un usuario existente
     * 
     * @param int $id ID del usuario
     * @param array $data Datos del usuario
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public static function update($id, $data) {
        // Verificar si el usuario existe
        $user = self::getById($id);
        if (!$user) {
            return false;
        }
        
        // Verificar si el nombre de usuario ya existe (si se está cambiando)
        if ($data['username'] !== $user['username']) {
            $existingUser = self::getByUsername($data['username']);
            if ($existingUser) {
                return false;
            }
        }
        
        // Actualizar usuario en la base de datos
        $sql = "UPDATE usuarios SET username = ?, nombre_completo = ?, estado = ? WHERE id = ?";
        
        return update($sql, [
            $data['username'],
            $data['nombre_completo'],
            $data['estado'],
            $id
        ]) > 0;
    }
    
    /**
     * Elimina un usuario
     * 
     * @param int $id ID del usuario
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public static function delete($id) {
        // Verificar si el usuario existe
        $user = self::getById($id);
        if (!$user) {
            return false;
        }
        
        // Eliminar usuario de la base de datos
        $sql = "DELETE FROM usuarios WHERE id = ?";
        
        return update($sql, [$id]) > 0;
    }
    
    /**
     * Cambia el estado de un usuario (activo/inactivo)
     * 
     * @param int $id ID del usuario
     * @param string $estado Nuevo estado ('activo' o 'inactivo')
     * @return bool True si el cambio fue exitoso, false en caso contrario
     */
    public static function changeStatus($id, $estado) {
        // Verificar si el usuario existe
        $user = self::getById($id);
        if (!$user) {
            return false;
        }
        
        // Actualizar estado del usuario
        $sql = "UPDATE usuarios SET estado = ? WHERE id = ?";
        
        return update($sql, [$estado, $id]) > 0;
    }
}
?>
