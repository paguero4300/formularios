<?php
/**
 * Tests para validar la creación y gestión de campos de formulario
 * Panel administrativo Dr Security
 */

// Definir la ruta base
$baseDir = dirname(__DIR__); // Obtiene el directorio padre (raíz del proyecto)

// Incluir archivos necesarios
require_once $baseDir . '/config/config.php';
require_once $baseDir . '/config/database.php';
require_once $baseDir . '/includes/functions.php';
require_once $baseDir . '/includes/form.php';

// Clase para ejecutar las pruebas
class FormFieldsTest {
    private $testFormId;
    private $createdFieldIds = [];
    private $totalTests = 0;
    private $passedTests = 0;

    /**
     * Constructor - Crea un formulario de prueba
     */
    public function __construct() {
        echo "<h1>Pruebas de Campos de Formulario</h1>";

        // Crear un formulario de prueba
        $formData = [
            'titulo' => 'Formulario de Prueba - ' . date('Y-m-d H:i:s'),
            'descripcion' => 'Formulario creado automáticamente para pruebas',
            'estado' => 'activo'
        ];

        $this->testFormId = Form::create($formData);

        if ($this->testFormId) {
            $this->logSuccess("Formulario de prueba creado con ID: {$this->testFormId}");
        } else {
            $this->logError("No se pudo crear el formulario de prueba");
            exit;
        }
    }

    /**
     * Destructor - Elimina el formulario de prueba
     */
    public function __destruct() {
        // Eliminar todos los campos creados
        foreach ($this->createdFieldIds as $fieldId) {
            Form::deleteField($fieldId);
        }

        // Eliminar el formulario de prueba
        if ($this->testFormId) {
            Form::delete($this->testFormId);
            $this->logInfo("Formulario de prueba eliminado");
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
     * Ejecuta todas las pruebas
     */
    public function runAllTests() {
        $this->testCreateBasicFields();
        $this->testCreateTextFields();
        $this->testCreateNumberField();
        $this->testCreateSelectField();
        $this->testCreateCheckboxField();
        $this->testCreateRadioField();
        $this->testCreateEmailField();
        $this->testCreatePhoneField();
        $this->testUpdateField();
        $this->testReorderFields();
    }

    /**
     * Prueba la creación de campos básicos (fecha_hora, ubicacion_gps)
     */
    public function testCreateBasicFields() {
        echo "<h2>Prueba: Creación de campos básicos</h2>";

        // Probar campo fecha_hora
        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'fecha_hora',
            'etiqueta' => 'Fecha y Hora de Prueba',
            'requerido' => 1,
            'orden' => 1
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo fecha_hora");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'fecha_hora', "Verificar tipo de campo fecha_hora");
        }

        // Probar campo ubicacion_gps
        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'ubicacion_gps',
            'etiqueta' => 'Ubicación GPS de Prueba',
            'requerido' => 1,
            'orden' => 2
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo ubicacion_gps");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'ubicacion_gps', "Verificar tipo de campo ubicacion_gps");
        }
    }

    /**
     * Prueba la creación de campos de texto (texto, textarea)
     */
    public function testCreateTextFields() {
        echo "<h2>Prueba: Creación de campos de texto</h2>";

        // Probar campo texto con propiedades
        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'texto',
            'etiqueta' => 'Texto de Prueba',
            'requerido' => 1,
            'orden' => 3,
            'propiedades' => [
                'placeholder' => 'Ingrese texto aquí',
                'longitud_maxima' => 100
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo texto con propiedades");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'texto', "Verificar tipo de campo texto");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['placeholder']) &&
                $propiedades['placeholder'] === 'Ingrese texto aquí' &&
                isset($propiedades['longitud_maxima']) &&
                $propiedades['longitud_maxima'] == 100,
                "Verificar propiedades del campo texto"
            );
        }

        // Probar campo textarea
        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'textarea',
            'etiqueta' => 'Textarea de Prueba',
            'requerido' => 0,
            'orden' => 4,
            'propiedades' => [
                'placeholder' => 'Ingrese texto largo aquí',
                'longitud_maxima' => 500
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo textarea");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'textarea', "Verificar tipo de campo textarea");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['placeholder']) &&
                $propiedades['placeholder'] === 'Ingrese texto largo aquí',
                "Verificar propiedades del campo textarea"
            );
        }
    }

    /**
     * Prueba la creación de campo numérico
     */
    public function testCreateNumberField() {
        echo "<h2>Prueba: Creación de campo numérico</h2>";

        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'numero',
            'etiqueta' => 'Número de Prueba',
            'requerido' => 1,
            'orden' => 5,
            'propiedades' => [
                'min' => 0,
                'max' => 100
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo numero");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'numero', "Verificar tipo de campo numero");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['min']) &&
                $propiedades['min'] == 0 &&
                isset($propiedades['max']) &&
                $propiedades['max'] == 100,
                "Verificar propiedades del campo numero"
            );
        }
    }

    /**
     * Prueba la creación de campo select
     */
    public function testCreateSelectField() {
        echo "<h2>Prueba: Creación de campo select</h2>";

        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'select',
            'etiqueta' => 'Select de Prueba',
            'requerido' => 1,
            'orden' => 6,
            'propiedades' => [
                'opciones' => [
                    ['valor' => 'opcion1', 'texto' => 'Opción 1'],
                    ['valor' => 'opcion2', 'texto' => 'Opción 2'],
                    ['valor' => 'opcion3', 'texto' => 'Opción 3']
                ]
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo select");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'select', "Verificar tipo de campo select");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['opciones']) &&
                is_array($propiedades['opciones']) &&
                count($propiedades['opciones']) === 3,
                "Verificar propiedades del campo select"
            );
        }
    }

    /**
     * Prueba la creación de campo checkbox
     */
    public function testCreateCheckboxField() {
        echo "<h2>Prueba: Creación de campo checkbox</h2>";

        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'checkbox',
            'etiqueta' => 'Checkbox de Prueba',
            'requerido' => 0,
            'orden' => 7,
            'propiedades' => [
                'opciones' => [
                    ['valor' => 'check1', 'texto' => 'Opción 1'],
                    ['valor' => 'check2', 'texto' => 'Opción 2']
                ]
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo checkbox");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'checkbox', "Verificar tipo de campo checkbox");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['opciones']) &&
                is_array($propiedades['opciones']) &&
                count($propiedades['opciones']) === 2,
                "Verificar propiedades del campo checkbox"
            );
        }
    }

    /**
     * Prueba la creación de campo radio
     */
    public function testCreateRadioField() {
        echo "<h2>Prueba: Creación de campo radio</h2>";

        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'radio',
            'etiqueta' => 'Radio de Prueba',
            'requerido' => 1,
            'orden' => 8,
            'propiedades' => [
                'opciones' => [
                    ['valor' => 'radio1', 'texto' => 'Opción 1'],
                    ['valor' => 'radio2', 'texto' => 'Opción 2']
                ]
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo radio");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'radio', "Verificar tipo de campo radio");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['opciones']) &&
                is_array($propiedades['opciones']),
                "Verificar propiedades del campo radio"
            );
        }
    }

    /**
     * Prueba la creación de campo email
     */
    public function testCreateEmailField() {
        echo "<h2>Prueba: Creación de campo email</h2>";

        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'email',
            'etiqueta' => 'Email de Prueba',
            'requerido' => 1,
            'orden' => 9,
            'propiedades' => [
                'placeholder' => 'ejemplo@dominio.com',
                'longitud_maxima' => 100
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo email");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'email', "Verificar tipo de campo email");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['placeholder']) &&
                $propiedades['placeholder'] === 'ejemplo@dominio.com',
                "Verificar propiedades del campo email"
            );
        }
    }

    /**
     * Prueba la creación de campo teléfono
     */
    public function testCreatePhoneField() {
        echo "<h2>Prueba: Creación de campo teléfono</h2>";

        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'telefono',
            'etiqueta' => 'Teléfono de Prueba',
            'requerido' => 1,
            'orden' => 10,
            'propiedades' => [
                'placeholder' => '(123) 456-7890'
            ]
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo telefono");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;
            $field = Form::getFieldById($fieldId);
            $this->assert($field && $field['tipo_campo'] === 'telefono', "Verificar tipo de campo telefono");

            // Verificar propiedades
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['placeholder']) &&
                $propiedades['placeholder'] === '(123) 456-7890',
                "Verificar propiedades del campo telefono"
            );
        }
    }

    /**
     * Prueba la actualización de un campo
     */
    public function testUpdateField() {
        echo "<h2>Prueba: Actualización de campo</h2>";

        // Primero crear un campo
        $fieldData = [
            'id_formulario' => $this->testFormId,
            'tipo_campo' => 'texto',
            'etiqueta' => 'Campo para actualizar',
            'requerido' => 0,
            'orden' => 11
        ];

        $fieldId = Form::addField($fieldData);
        $this->assert($fieldId !== false, "Crear campo para actualizar");

        if ($fieldId) {
            $this->createdFieldIds[] = $fieldId;

            // Ahora actualizar el campo
            $updateData = [
                'tipo_campo' => 'textarea',
                'etiqueta' => 'Campo actualizado',
                'requerido' => 1,
                'orden' => 12,
                'propiedades' => [
                    'placeholder' => 'Texto actualizado',
                    'longitud_maxima' => 200
                ]
            ];

            $result = Form::updateField($fieldId, $updateData);
            $this->assert($result, "Actualizar campo");

            // Verificar que se actualizó correctamente
            $field = Form::getFieldById($fieldId);
            $this->assert(
                $field &&
                $field['tipo_campo'] === 'textarea' &&
                $field['etiqueta'] === 'Campo actualizado' &&
                $field['requerido'] == 1 &&
                $field['orden'] == 12,
                "Verificar datos básicos actualizados"
            );

            // Verificar propiedades actualizadas
            $propiedades = json_decode($field['propiedades'], true);
            $this->assert(
                $propiedades &&
                isset($propiedades['placeholder']) &&
                $propiedades['placeholder'] === 'Texto actualizado' &&
                isset($propiedades['longitud_maxima']) &&
                $propiedades['longitud_maxima'] == 200,
                "Verificar propiedades actualizadas"
            );
        }
    }

    /**
     * Prueba el reordenamiento de campos
     */
    public function testReorderFields() {
        echo "<h2>Prueba: Reordenamiento de campos</h2>";

        // Obtener todos los campos creados
        $fields = Form::getFields($this->testFormId);

        if (count($fields) >= 3) {
            // Obtener los IDs de los primeros 3 campos
            $fieldIds = array_map(function($field) {
                return $field['id'];
            }, array_slice($fields, 0, 3));

            // Invertir el orden
            $fieldIds = array_reverse($fieldIds);

            // Reordenar
            $result = Form::reorderFields($this->testFormId, $fieldIds);
            $this->assert($result, "Reordenar campos");

            // Verificar el nuevo orden
            $updatedFields = Form::getFields($this->testFormId);
            $updatedFieldIds = [];

            foreach ($updatedFields as $field) {
                if (in_array($field['id'], $fieldIds)) {
                    $updatedFieldIds[] = $field['id'];
                }
            }

            $this->assert(
                $updatedFieldIds[0] === $fieldIds[0] &&
                $updatedFieldIds[1] === $fieldIds[1] &&
                $updatedFieldIds[2] === $fieldIds[2],
                "Verificar nuevo orden de campos"
            );
        } else {
            $this->logWarning("No hay suficientes campos para probar el reordenamiento");
        }
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

// Crear directorio de tests si no existe
if (!file_exists('../tests')) {
    mkdir('../tests', 0755, true);
}

// Ejecutar las pruebas
$tester = new FormFieldsTest();
$tester->runAllTests();
?>
