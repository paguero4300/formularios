<?php
/**
 * Tests para validar el envío y recuperación de formularios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/form.php';

// Clase para ejecutar las pruebas
class FormSubmissionTest {
    private $testFormId;
    private $testUserId;
    private $testFieldIds = [];
    private $testSubmissionId;
    private $totalTests = 0;
    private $passedTests = 0;

    /**
     * Constructor - Prepara el entorno de prueba
     */
    public function __construct() {
        echo "<h1>Pruebas de Envío y Recuperación de Formularios</h1>";

        // Crear un usuario de prueba
        $this->createTestUser();

        // Crear un formulario de prueba
        $this->createTestForm();

        // Crear campos de prueba
        $this->createTestFields();
    }

    /**
     * Destructor - Limpia el entorno de prueba
     */
    public function __destruct() {
        // Eliminar el envío de prueba si existe
        if ($this->testSubmissionId) {
            Form::deleteSubmission($this->testSubmissionId);
            $this->logInfo("Envío de prueba eliminado");
        }

        // Eliminar los campos de prueba
        foreach ($this->testFieldIds as $fieldId) {
            Form::deleteField($fieldId);
        }
        $this->logInfo("Campos de prueba eliminados");

        // Eliminar el formulario de prueba
        if ($this->testFormId) {
            Form::delete($this->testFormId);
            $this->logInfo("Formulario de prueba eliminado");
        }

        // Eliminar el usuario de prueba
        if ($this->testUserId) {
            $sql = "DELETE FROM usuarios WHERE id = ?";
            update($sql, [$this->testUserId]);
            $this->logInfo("Usuario de prueba eliminado");
        }

        // Mostrar resumen
        echo "<h2>Resumen de Pruebas</h2>";
        echo "<p>Total de pruebas: {$this->totalTests}</p>";
        echo "<p>Pruebas exitosas: {$this->passedTests}</p>";
        echo "<p>Pruebas fallidas: " . ($this->totalTests - $this->passedTests) . "</p>";

        if ($this->totalTests == $this->passedTests) {
            echo "<p style='color: green; font-weight: bold;'>¡Todas las pruebas pasaron correctamente!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>Algunas pruebas fallaron. Revisa los detalles arriba.</p>";
        }
    }

    /**
     * Crea un usuario de prueba
     */
    private function createTestUser() {
        // Verificar si ya existe un usuario de prueba
        $sql = "SELECT id FROM usuarios WHERE username = 'test_user' LIMIT 1";
        $existingUser = fetchOne($sql);

        if ($existingUser) {
            $this->testUserId = $existingUser['id'];
            $this->logInfo("Usando usuario de prueba existente con ID: {$this->testUserId}");
            return;
        }

        // Crear un nuevo usuario de prueba
        $hashedPassword = password_hash('test_password', PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (username, password, nombre_completo, estado) VALUES (?, ?, ?, ?)";
        $this->testUserId = insert($sql, ['test_user', $hashedPassword, 'Usuario de Prueba', 'activo']);

        if ($this->testUserId) {
            $this->logSuccess("Usuario de prueba creado con ID: {$this->testUserId}");
        } else {
            $this->logError("No se pudo crear el usuario de prueba");
            exit;
        }
    }

    /**
     * Crea un formulario de prueba
     */
    private function createTestForm() {
        $formData = [
            'titulo' => 'Formulario de Prueba - ' . date('Y-m-d H:i:s'),
            'descripcion' => 'Formulario creado automáticamente para pruebas de envío',
            'estado' => 'activo'
        ];

        $this->testFormId = Form::create($formData);

        if ($this->testFormId) {
            $this->logSuccess("Formulario de prueba creado con ID: {$this->testFormId}");

            // Asignar el formulario al usuario de prueba
            $result = Form::assignFormToUser($this->testFormId, $this->testUserId);
            if ($result) {
                $this->logSuccess("Formulario asignado al usuario de prueba");
            } else {
                $this->logError("No se pudo asignar el formulario al usuario de prueba");
            }
        } else {
            $this->logError("No se pudo crear el formulario de prueba");
            exit;
        }
    }

    /**
     * Crea campos de prueba para el formulario
     */
    private function createTestFields() {
        // Definir los campos de prueba
        $testFields = [
            [
                'tipo_campo' => 'texto',
                'etiqueta' => 'Nombre del cliente',
                'requerido' => 1,
                'propiedades' => [
                    'placeholder' => 'Ingrese el nombre completo',
                    'longitud_maxima' => 100
                ]
            ],
            [
                'tipo_campo' => 'fecha_hora',
                'etiqueta' => 'Fecha y hora de visita',
                'requerido' => 1
            ],
            [
                'tipo_campo' => 'ubicacion_gps',
                'etiqueta' => 'Ubicación del sitio',
                'requerido' => 1
            ],
            [
                'tipo_campo' => 'select',
                'etiqueta' => 'Tipo de servicio',
                'requerido' => 1,
                'propiedades' => [
                    'opciones' => [
                        ['valor' => 'instalacion', 'texto' => 'Instalación'],
                        ['valor' => 'mantenimiento', 'texto' => 'Mantenimiento'],
                        ['valor' => 'reparacion', 'texto' => 'Reparación']
                    ]
                ]
            ],
            [
                'tipo_campo' => 'textarea',
                'etiqueta' => 'Observaciones',
                'requerido' => 0,
                'propiedades' => [
                    'placeholder' => 'Ingrese sus observaciones',
                    'longitud_maxima' => 500
                ]
            ]
        ];

        // Crear los campos
        foreach ($testFields as $index => $fieldData) {
            $fieldData['id_formulario'] = $this->testFormId;
            $fieldData['orden'] = $index + 1;

            $fieldId = Form::addField($fieldData);

            if ($fieldId) {
                $this->testFieldIds[] = $fieldId;
                $this->logSuccess("Campo '{$fieldData['etiqueta']}' creado con ID: {$fieldId}");
            } else {
                $this->logError("No se pudo crear el campo '{$fieldData['etiqueta']}'");
            }
        }

        if (empty($this->testFieldIds)) {
            $this->logError("No se pudieron crear campos para el formulario de prueba");
            exit;
        }
    }

    /**
     * Ejecuta todas las pruebas
     */
    public function runAllTests() {
        $this->testSubmitForm();
        $this->testGetSubmission();
        $this->testGetSubmissionsForUser();
        $this->testSubmitFormWithInvalidData();
    }

    /**
     * Prueba el envío de un formulario
     */
    public function testSubmitForm() {
        echo "<h2>Prueba: Envío de formulario</h2>";

        // Preparar datos de prueba
        $formData = [
            'form_id' => $this->testFormId,
            'user_id' => $this->testUserId,
            'data' => []
        ];

        // Añadir datos para cada campo
        foreach ($this->testFieldIds as $index => $fieldId) {
            switch ($index) {
                case 0: // Texto
                    $formData['data'][$fieldId] = 'Cliente de Prueba';
                    break;
                case 1: // Fecha y hora
                    $formData['data'][$fieldId] = date('Y-m-d H:i:s');
                    break;
                case 2: // Ubicación GPS
                    $formData['data'][$fieldId] = '19.4326,-99.1332';
                    break;
                case 3: // Select
                    $formData['data'][$fieldId] = 'instalacion';
                    break;
                case 4: // Textarea
                    $formData['data'][$fieldId] = 'Observaciones de prueba para el envío del formulario';
                    break;
            }
        }

        // Enviar el formulario
        $this->testSubmissionId = Form::submitForm($formData);

        // Verificar resultado
        $this->assert($this->testSubmissionId !== false, "Envío de formulario");

        if ($this->testSubmissionId) {
            $this->logSuccess("Formulario enviado correctamente con ID: {$this->testSubmissionId}");
        }
    }

    /**
     * Prueba la recuperación de un envío específico
     */
    public function testGetSubmission() {
        echo "<h2>Prueba: Recuperación de envío específico</h2>";

        // Verificar que exista un envío de prueba
        if (!$this->testSubmissionId) {
            $this->logWarning("No hay envío de prueba para recuperar");
            return;
        }

        // Recuperar el envío
        $submission = Form::getSubmissionById($this->testSubmissionId);

        // Verificar que se recuperó correctamente
        $this->assert($submission !== null, "Recuperación de envío por ID");

        if ($submission) {
            $this->assert($submission['id_formulario'] == $this->testFormId, "Verificar ID del formulario");
            $this->assert($submission['id_usuario'] == $this->testUserId, "Verificar ID del usuario");

            // Verificar que los datos estén en formato JSON válido
            $datos = json_decode($submission['datos'], true);
            $this->assert(is_array($datos), "Verificar formato de datos JSON");

            // Verificar que los datos contengan los valores esperados
            if (is_array($datos)) {
                foreach ($this->testFieldIds as $index => $fieldId) {
                    $this->assert(isset($datos[$fieldId]), "Verificar existencia del campo {$fieldId}");

                    switch ($index) {
                        case 0: // Texto
                            $this->assert($datos[$fieldId] === 'Cliente de Prueba', "Verificar valor del campo texto");
                            break;
                        case 3: // Select
                            $this->assert($datos[$fieldId] === 'instalacion', "Verificar valor del campo select");
                            break;
                        case 4: // Textarea
                            $this->assert($datos[$fieldId] === 'Observaciones de prueba para el envío del formulario', "Verificar valor del campo textarea");
                            break;
                    }
                }
            }
        }
    }

    /**
     * Prueba la recuperación de envíos para un usuario
     */
    public function testGetSubmissionsForUser() {
        echo "<h2>Prueba: Recuperación de envíos para un usuario</h2>";

        // Verificar que exista un envío de prueba
        if (!$this->testSubmissionId) {
            $this->logWarning("No hay envío de prueba para recuperar");
            return;
        }

        // Recuperar los envíos para el usuario de prueba
        $result = Form::getSubmissionsForUser($this->testUserId);

        // Verificar que se recuperaron correctamente
        $this->assert(isset($result['submissions']) && is_array($result['submissions']), "Recuperación de envíos para usuario");

        if (isset($result['submissions']) && is_array($result['submissions'])) {
            $found = false;

            foreach ($result['submissions'] as $submission) {
                if ($submission['id'] == $this->testSubmissionId) {
                    $found = true;
                    break;
                }
            }

            $this->assert($found, "Verificar que el envío de prueba está en la lista");
        }

        // Recuperar los envíos filtrados por formulario
        $result = Form::getSubmissionsForUser($this->testUserId, 1, 10, $this->testFormId);

        // Verificar que se recuperaron correctamente
        $this->assert(isset($result['submissions']) && is_array($result['submissions']), "Recuperación de envíos filtrados por formulario");

        if (isset($result['submissions']) && is_array($result['submissions'])) {
            $found = false;

            foreach ($result['submissions'] as $submission) {
                if ($submission['id'] == $this->testSubmissionId) {
                    $found = true;
                    $this->assert($submission['id_formulario'] == $this->testFormId, "Verificar ID del formulario en envío filtrado");
                    break;
                }
            }

            $this->assert($found, "Verificar que el envío de prueba está en la lista filtrada");
        }
    }

    /**
     * Prueba el envío de un formulario con datos inválidos
     */
    public function testSubmitFormWithInvalidData() {
        echo "<h2>Prueba: Envío de formulario con datos inválidos</h2>";

        // Caso 1: Campo requerido faltante
        $formData = [
            'form_id' => $this->testFormId,
            'user_id' => $this->testUserId,
            'data' => []
        ];

        // Añadir datos incompletos (omitir campos requeridos)
        $formData['data'][$this->testFieldIds[4]] = 'Solo observaciones sin campos requeridos';

        // Intentar enviar el formulario
        $result = Form::submitForm($formData);

        // Verificar que el envío falla
        $this->assert($result === false, "Verificar que el envío falla con campos requeridos faltantes");

        // Caso 2: Formato inválido para fecha_hora
        $formData = [
            'form_id' => $this->testFormId,
            'user_id' => $this->testUserId,
            'data' => []
        ];

        // Añadir datos con formato inválido para fecha_hora
        $formData['data'][$this->testFieldIds[0]] = 'Cliente de Prueba';
        $formData['data'][$this->testFieldIds[1]] = 'fecha inválida';
        $formData['data'][$this->testFieldIds[2]] = '19.4326,-99.1332';
        $formData['data'][$this->testFieldIds[3]] = 'instalacion';

        // Intentar enviar el formulario
        $result = Form::submitForm($formData);

        // Verificar que el envío falla
        $this->assert($result === false, "Verificar que el envío falla con formato inválido para fecha_hora");

        // Caso 3: Formato inválido para ubicacion_gps
        $formData = [
            'form_id' => $this->testFormId,
            'user_id' => $this->testUserId,
            'data' => []
        ];

        // Añadir datos con formato inválido para ubicacion_gps
        $formData['data'][$this->testFieldIds[0]] = 'Cliente de Prueba';
        $formData['data'][$this->testFieldIds[1]] = date('Y-m-d H:i:s');
        $formData['data'][$this->testFieldIds[2]] = 'ubicación inválida';
        $formData['data'][$this->testFieldIds[3]] = 'instalacion';

        // Intentar enviar el formulario
        $result = Form::submitForm($formData);

        // Verificar que el envío falla
        $this->assert($result === false, "Verificar que el envío falla con formato inválido para ubicacion_gps");

        // Caso 4: Valor inválido para campo select
        $formData = [
            'form_id' => $this->testFormId,
            'user_id' => $this->testUserId,
            'data' => []
        ];

        // Añadir datos con valor inválido para select
        $formData['data'][$this->testFieldIds[0]] = 'Cliente de Prueba';
        $formData['data'][$this->testFieldIds[1]] = date('Y-m-d H:i:s');
        $formData['data'][$this->testFieldIds[2]] = '19.4326,-99.1332';
        $formData['data'][$this->testFieldIds[3]] = 'valor_inexistente';

        // Intentar enviar el formulario
        $result = Form::submitForm($formData);

        // Verificar que el envío falla
        $this->assert($result === false, "Verificar que el envío falla con valor inválido para campo select");
    }

    /**
     * Verifica una condición y registra el resultado
     */
    private function assert($condition, $message) {
        $this->totalTests++;

        if ($condition) {
            $this->passedTests++;
            $this->logSuccess("PASÓ: $message");
        } else {
            $this->logError("FALLÓ: $message");
        }

        return $condition;
    }

    /**
     * Registra un mensaje de éxito
     */
    private function logSuccess($message) {
        echo "<p style='color: green;'>✓ $message</p>";
    }

    /**
     * Registra un mensaje de error
     */
    private function logError($message) {
        echo "<p style='color: red;'>✗ $message</p>";
    }

    /**
     * Registra un mensaje de advertencia
     */
    private function logWarning($message) {
        echo "<p style='color: orange;'>⚠ $message</p>";
    }

    /**
     * Registra un mensaje informativo
     */
    private function logInfo($message) {
        echo "<p style='color: blue;'>ℹ $message</p>";
    }
}
?>
