<?php
/**
 * Clase para gestión de formularios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Clase para manejar operaciones relacionadas con formularios
 */
class Form {
    /**
     * Obtiene todos los formularios
     *
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @return array Arreglo con los formularios y metadatos de paginación
     */
    public static function getAll($page = 1, $perPage = 10) {
        // Calcular offset para paginación
        $offset = ($page - 1) * $perPage;

        // Obtener total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM formularios";
        $result = fetchOne($sqlCount);
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = ceil($total / $perPage);

        // Obtener formularios para la página actual
        $sql = "SELECT * FROM formularios ORDER BY id DESC LIMIT ? OFFSET ?";
        $forms = fetchAll($sql, [$perPage, $offset]);

        return [
            'forms' => $forms,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Busca formularios por término de búsqueda
     *
     * @param string $searchTerm Término de búsqueda
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @return array Arreglo con los formularios y metadatos de paginación
     */
    public static function search($searchTerm, $page = 1, $perPage = 10) {
        // Sanitizar término de búsqueda
        $searchTerm = '%' . sanitize($searchTerm) . '%';

        // Calcular offset para paginación
        $offset = ($page - 1) * $perPage;

        // Obtener total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM formularios
                    WHERE titulo LIKE ? OR descripcion LIKE ?";
        $result = fetchOne($sqlCount, [$searchTerm, $searchTerm]);
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = ceil($total / $perPage);

        // Obtener formularios para la página actual
        $sql = "SELECT * FROM formularios
                WHERE titulo LIKE ? OR descripcion LIKE ?
                ORDER BY id DESC LIMIT ? OFFSET ?";
        $forms = fetchAll($sql, [$searchTerm, $searchTerm, $perPage, $offset]);

        return [
            'forms' => $forms,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Obtiene un formulario por su ID
     *
     * @param int $id ID del formulario
     * @return array|null Datos del formulario o null si no existe
     */
    public static function getById($id) {
        $sql = "SELECT * FROM formularios WHERE id = ? LIMIT 1";
        return fetchOne($sql, [$id]);
    }

    /**
     * Obtiene los campos de un formulario
     *
     * @param int $formId ID del formulario
     * @return array Arreglo con los campos del formulario
     */
    public static function getFields($formId) {
        $sql = "SELECT * FROM campos_formulario WHERE id_formulario = ? ORDER BY orden ASC";
        return fetchAll($sql, [$formId]);
    }

    /**
     * Obtiene un campo de formulario por su ID
     *
     * @param int $fieldId ID del campo
     * @return array|null Datos del campo o null si no existe
     */
    public static function getFieldById($fieldId) {
        $sql = "SELECT * FROM campos_formulario WHERE id = ? LIMIT 1";
        return fetchOne($sql, [$fieldId]);
    }

    /**
     * Crea un nuevo formulario
     *
     * @param array $data Datos del formulario
     * @return int|bool ID del formulario creado o false si hubo un error
     */
    public static function create($data) {
        // Insertar formulario en la base de datos
        $sql = "INSERT INTO formularios (titulo, descripcion, estado)
                VALUES (?, ?, ?)";

        return insert($sql, [
            $data['titulo'],
            $data['descripcion'],
            $data['estado']
        ]);
    }

    /**
     * Actualiza un formulario existente
     *
     * @param int $id ID del formulario
     * @param array $data Datos del formulario
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public static function update($id, $data) {
        // Verificar si el formulario existe
        $form = self::getById($id);
        if (!$form) {
            return false;
        }

        // Actualizar formulario en la base de datos
        $sql = "UPDATE formularios SET titulo = ?, descripcion = ?, estado = ? WHERE id = ?";

        $result = update($sql, [
            $data['titulo'],
            $data['descripcion'],
            $data['estado'],
            $id
        ]);

        // Consideramos la actualización exitosa si se afectaron filas o si no hubo cambios
        // (affected_rows = 0 cuando los valores son los mismos)
        return $result >= 0;
    }

    /**
     * Elimina un formulario
     *
     * @param int $id ID del formulario
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public static function delete($id) {
        // Verificar si el formulario existe
        $form = self::getById($id);
        if (!$form) {
            return false;
        }

        // Eliminar formulario de la base de datos
        // Las claves foráneas con ON DELETE CASCADE eliminarán automáticamente los campos y envíos
        $sql = "DELETE FROM formularios WHERE id = ?";

        return update($sql, [$id]) > 0;
    }

    /**
     * Cambia el estado de un formulario (activo/inactivo)
     *
     * @param int $id ID del formulario
     * @param string $estado Nuevo estado ('activo' o 'inactivo')
     * @return bool True si el cambio fue exitoso, false en caso contrario
     */
    public static function changeStatus($id, $estado) {
        // Verificar si el formulario existe
        $form = self::getById($id);
        if (!$form) {
            return false;
        }

        // Actualizar estado del formulario
        $sql = "UPDATE formularios SET estado = ? WHERE id = ?";

        $result = update($sql, [$estado, $id]);

        // Consideramos la actualización exitosa si se afectaron filas o si no hubo cambios
        return $result >= 0;
    }

    /**
     * Añade un campo a un formulario
     *
     * @param array $data Datos del campo
     * @return int|bool ID del campo creado o false si hubo un error
     */
    public static function addField($data) {
        // Verificar si el formulario existe
        $form = self::getById($data['id_formulario']);
        if (!$form) {
            return false;
        }

        // Obtener el orden máximo actual
        $sql = "SELECT MAX(orden) as max_orden FROM campos_formulario WHERE id_formulario = ?";
        $result = fetchOne($sql, [$data['id_formulario']]);
        $orden = ($result && isset($result['max_orden'])) ? $result['max_orden'] + 1 : 1;

        // Insertar campo en la base de datos
        $sql = "INSERT INTO campos_formulario (id_formulario, tipo_campo, etiqueta, requerido, orden)
                VALUES (?, ?, ?, ?, ?)";

        return insert($sql, [
            $data['id_formulario'],
            $data['tipo_campo'],
            $data['etiqueta'],
            $data['requerido'] ? 1 : 0,
            $data['orden'] ?? $orden
        ]);
    }

    /**
     * Actualiza un campo de formulario
     *
     * @param int $fieldId ID del campo
     * @param array $data Datos del campo
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public static function updateField($fieldId, $data) {
        // Verificar si el campo existe
        $field = self::getFieldById($fieldId);
        if (!$field) {
            return false;
        }

        // Actualizar campo en la base de datos
        $sql = "UPDATE campos_formulario SET tipo_campo = ?, etiqueta = ?, requerido = ?, orden = ? WHERE id = ?";

        $result = update($sql, [
            $data['tipo_campo'],
            $data['etiqueta'],
            $data['requerido'] ? 1 : 0,
            $data['orden'],
            $fieldId
        ]);

        // Consideramos la actualización exitosa si se afectaron filas o si no hubo cambios
        return $result >= 0;
    }

    /**
     * Elimina un campo de formulario
     *
     * @param int $fieldId ID del campo
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public static function deleteField($fieldId) {
        // Verificar si el campo existe
        $field = self::getFieldById($fieldId);
        if (!$field) {
            return false;
        }

        // Eliminar campo de la base de datos
        $sql = "DELETE FROM campos_formulario WHERE id = ?";

        return update($sql, [$fieldId]) > 0;
    }

    /**
     * Reordena los campos de un formulario
     *
     * @param int $formId ID del formulario
     * @param array $fieldOrder Arreglo con los IDs de los campos en el nuevo orden
     * @return bool True si la reordenación fue exitosa, false en caso contrario
     */
    public static function reorderFields($formId, $fieldOrder) {
        // Verificar si el formulario existe
        $form = self::getById($formId);
        if (!$form) {
            return false;
        }

        // Iniciar transacción
        $conn = getDBConnection();
        $conn->begin_transaction();

        try {
            // Actualizar el orden de cada campo
            foreach ($fieldOrder as $index => $fieldId) {
                $orden = $index + 1;
                $sql = "UPDATE campos_formulario SET orden = ? WHERE id = ? AND id_formulario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iii', $orden, $fieldId, $formId);
                $stmt->execute();
            }

            // Confirmar transacción
            $conn->commit();
            $conn->close();

            return true;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conn->rollback();
            $conn->close();

            return false;
        }
    }

    /**
     * Obtiene los envíos de un formulario
     *
     * @param int $formId ID del formulario
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @return array Arreglo con los envíos y metadatos de paginación
     */
    public static function getSubmissions($formId, $page = 1, $perPage = 10) {
        // Calcular offset para paginación
        $offset = ($page - 1) * $perPage;

        // Obtener total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM envios_formulario WHERE id_formulario = ?";
        $result = fetchOne($sqlCount, [$formId]);
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = ceil($total / $perPage);

        // Obtener envíos para la página actual
        $sql = "SELECT ef.*, u.username, u.nombre_completo
                FROM envios_formulario ef
                JOIN usuarios u ON ef.id_usuario = u.id
                WHERE ef.id_formulario = ?
                ORDER BY ef.fecha_envio DESC
                LIMIT ? OFFSET ?";
        $submissions = fetchAll($sql, [$formId, $perPage, $offset]);

        return [
            'submissions' => $submissions,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Obtiene los envíos de formularios según los permisos del usuario
     *
     * @param int $userId ID del usuario
     * @param int $page Número de página
     * @param int $perPage Registros por página
     * @param int $formId ID del formulario (opcional, para filtrar por formulario)
     * @return array Arreglo con los envíos y metadatos de paginación
     */
    public static function getSubmissionsForUser($userId, $page = 1, $perPage = 10, $formId = null) {
        // Calcular offset para paginación
        $offset = ($page - 1) * $perPage;

        // Verificar si el usuario es administrador (username = 'admin')
        $sqlAdmin = "SELECT username FROM usuarios WHERE id = ? LIMIT 1";
        $userResult = fetchOne($sqlAdmin, [$userId]);
        $isAdmin = ($userResult && $userResult['username'] === 'admin');

        // Verificar si existen asignaciones para el usuario
        $sqlCheck = "SELECT COUNT(*) as total FROM asignaciones_formulario WHERE id_usuario = ?";
        $result = fetchOne($sqlCheck, [$userId]);
        $hasAssignments = ($result && $result['total'] > 0);

        // Preparar condiciones de la consulta
        $conditions = [];
        $params = [];

        // Si no es admin y tiene asignaciones, filtrar por formularios asignados
        if (!$isAdmin && $hasAssignments) {
            $conditions[] = "ef.id_formulario IN (SELECT id_formulario FROM asignaciones_formulario WHERE id_usuario = ?)";
            $params[] = $userId;
        }

        // Si se especificó un formulario, filtrar por ese formulario
        if ($formId) {
            $conditions[] = "ef.id_formulario = ?";
            $params[] = $formId;

            // Si no es admin, verificar que tenga acceso a este formulario
            if (!$isAdmin) {
                // Verificar si el formulario está asignado al usuario o si no hay asignaciones
                $sqlFormCheck = "SELECT COUNT(*) as total FROM asignaciones_formulario WHERE id_formulario = ?";
                $formCheckResult = fetchOne($sqlFormCheck, [$formId]);
                $formHasAssignments = ($formCheckResult && $formCheckResult['total'] > 0);

                if ($formHasAssignments) {
                    $sqlUserAccess = "SELECT COUNT(*) as total FROM asignaciones_formulario WHERE id_formulario = ? AND id_usuario = ?";
                    $userAccessResult = fetchOne($sqlUserAccess, [$formId, $userId]);
                    $userHasAccess = ($userAccessResult && $userAccessResult['total'] > 0);

                    if (!$userHasAccess) {
                        // El usuario no tiene acceso a este formulario
                        return [
                            'submissions' => [],
                            'pagination' => [
                                'total' => 0,
                                'per_page' => $perPage,
                                'current_page' => $page,
                                'total_pages' => 0
                            ]
                        ];
                    }
                }
            }
        }

        // Construir la cláusula WHERE
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Obtener total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM envios_formulario ef $whereClause";
        $result = fetchOne($sqlCount, $params);
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = ceil($total / $perPage);

        // Obtener envíos para la página actual
        $sql = "SELECT ef.*, u.username, u.nombre_completo, f.titulo as formulario_titulo
                FROM envios_formulario ef
                JOIN usuarios u ON ef.id_usuario = u.id
                JOIN formularios f ON ef.id_formulario = f.id
                $whereClause
                ORDER BY ef.fecha_envio DESC
                LIMIT ? OFFSET ?";

        // Añadir parámetros de paginación
        $params[] = $perPage;
        $params[] = $offset;

        $submissions = fetchAll($sql, $params);

        return [
            'submissions' => $submissions,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Obtiene un envío de formulario por su ID
     *
     * @param int $submissionId ID del envío
     * @return array|null Datos del envío o null si no existe
     */
    public static function getSubmissionById($submissionId) {
        $sql = "SELECT ef.*, u.username, u.nombre_completo, f.titulo as formulario_titulo
                FROM envios_formulario ef
                JOIN usuarios u ON ef.id_usuario = u.id
                JOIN formularios f ON ef.id_formulario = f.id
                WHERE ef.id = ?
                LIMIT 1";
        return fetchOne($sql, [$submissionId]);
    }

    /**
     * Guarda un envío de formulario
     *
     * @param array $data Datos del envío
     * @return int|bool ID del envío creado o false si hubo un error
     */
    public static function saveSubmission($data) {
        // Verificar si el formulario existe
        $form = self::getById($data['id_formulario']);
        if (!$form) {
            return false;
        }

        // Convertir datos a formato JSON
        $jsonData = json_encode($data['datos']);

        // Insertar envío en la base de datos
        $sql = "INSERT INTO envios_formulario (id_formulario, id_usuario, datos)
                VALUES (?, ?, ?)";

        return insert($sql, [
            $data['id_formulario'],
            $data['id_usuario'],
            $jsonData
        ]);
    }

    /**
     * Elimina un envío de formulario
     *
     * @param int $submissionId ID del envío
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public static function deleteSubmission($submissionId) {
        // Verificar si el envío existe
        $submission = self::getSubmissionById($submissionId);
        if (!$submission) {
            return false;
        }

        // Eliminar envío de la base de datos
        $sql = "DELETE FROM envios_formulario WHERE id = ?";

        return update($sql, [$submissionId]) > 0;
    }

    /**
     * Obtiene los formularios activos para un usuario
     *
     * @param int $userId ID del usuario
     * @return array Arreglo con los formularios activos
     */
    public static function getActiveFormsForUser($userId) {
        // Verificar si existen asignaciones para el usuario
        $sqlCheck = "SELECT COUNT(*) as total FROM asignaciones_formulario WHERE id_usuario = ?";
        $result = fetchOne($sqlCheck, [$userId]);
        $hasAssignments = ($result && $result['total'] > 0);

        if ($hasAssignments) {
            // Si hay asignaciones, mostrar solo los formularios asignados
            $sql = "SELECT f.*,
                    (SELECT COUNT(*) FROM campos_formulario WHERE id_formulario = f.id) as total_campos
                    FROM formularios f
                    INNER JOIN asignaciones_formulario af ON f.id = af.id_formulario
                    WHERE f.estado = 'activo' AND af.id_usuario = ?
                    ORDER BY f.id DESC";

            $forms = fetchAll($sql, [$userId]);
        } else {
            // Si no hay asignaciones, mostrar todos los formularios activos (comportamiento anterior)
            $sql = "SELECT f.*,
                    (SELECT COUNT(*) FROM campos_formulario WHERE id_formulario = f.id) as total_campos
                    FROM formularios f
                    WHERE f.estado = 'activo'
                    ORDER BY f.id DESC";

            $forms = fetchAll($sql);
        }

        // Para cada formulario, obtener sus campos
        foreach ($forms as &$form) {
            $form['campos'] = self::getFields($form['id']);
        }

        return $forms;
    }

    /**
     * Asigna un formulario a un usuario
     *
     * @param int $formId ID del formulario
     * @param int $userId ID del usuario
     * @return bool True si la asignación fue exitosa, false en caso contrario
     */
    public static function assignFormToUser($formId, $userId) {
        // Verificar si el formulario existe
        $form = self::getById($formId);
        if (!$form) {
            return false;
        }

        // Verificar si el usuario existe
        $sql = "SELECT id FROM usuarios WHERE id = ? LIMIT 1";
        $user = fetchOne($sql, [$userId]);
        if (!$user) {
            return false;
        }

        // Verificar si ya existe la asignación
        $sqlCheck = "SELECT id FROM asignaciones_formulario WHERE id_usuario = ? AND id_formulario = ? LIMIT 1";
        $existing = fetchOne($sqlCheck, [$userId, $formId]);

        if ($existing) {
            // La asignación ya existe, se considera exitoso
            return true;
        }

        // Crear la asignación
        $sql = "INSERT INTO asignaciones_formulario (id_usuario, id_formulario) VALUES (?, ?)";
        $result = insert($sql, [$userId, $formId]);

        return $result > 0;
    }

    /**
     * Elimina la asignación de un formulario a un usuario
     *
     * @param int $formId ID del formulario
     * @param int $userId ID del usuario
     * @return bool True si la eliminación fue exitosa, false en caso contrario
     */
    public static function unassignFormFromUser($formId, $userId) {
        $sql = "DELETE FROM asignaciones_formulario WHERE id_usuario = ? AND id_formulario = ?";
        $result = update($sql, [$userId, $formId]);

        return $result >= 0;
    }

    /**
     * Obtiene los usuarios asignados a un formulario
     *
     * @param int $formId ID del formulario
     * @return array Arreglo con los usuarios asignados
     */
    public static function getAssignedUsers($formId) {
        $sql = "SELECT u.id, u.username, u.nombre_completo, u.estado
                FROM usuarios u
                INNER JOIN asignaciones_formulario af ON u.id = af.id_usuario
                WHERE af.id_formulario = ?
                ORDER BY u.nombre_completo";

        return fetchAll($sql, [$formId]);
    }
}
?>
