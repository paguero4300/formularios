# Dr Security - Panel Administrativo

Panel administrativo para la aplicación Dr Security, que permite la gestión de formularios dinámicos, usuarios y visualización de respuestas.

## Características

- Gestión de usuarios con diferentes niveles de acceso
- Creación y gestión de formularios dinámicos con múltiples tipos de campos
- Asignación de formularios a usuarios específicos
- Visualización de respuestas de formularios con filtrado por usuario
- API completa para integración con aplicación Flutter
- Validación de datos según el tipo de campo

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache, Nginx, etc.)

## Instalación

1. Clonar el repositorio en el directorio del servidor web:
   ```
   git clone https://github.com/tu-usuario/formulario.git
   ```

2. Importar la base de datos:
   ```
   mysql -u usuario -p nombre_base_datos < db/estructura.sql
   ```

3. Configurar la conexión a la base de datos en `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   define('DB_NAME', 'nombre_base_datos');
   ```

4. Acceder al panel administrativo:
   ```
   http://localhost/formulario/admin/
   ```

## Campos Dinámicos

El sistema soporta la creación de formularios con campos dinámicos de diferentes tipos:

### Tipos de Campos Soportados

1. **fecha_hora**: Campo para registrar fecha y hora (formato "YYYY-MM-DD HH:MM:SS")
2. **ubicacion_gps**: Campo para registrar coordenadas GPS (formato "latitud,longitud")
3. **texto**: Campo de texto simple para nombres, descripciones cortas, etc.
4. **numero**: Campo numérico con validación de mínimo y máximo
5. **textarea**: Campo de texto multilínea para comentarios extensos
6. **select**: Lista desplegable con opciones predefinidas
7. **checkbox**: Casillas de verificación para selección múltiple
8. **radio**: Botones de opción para selección única
9. **email**: Campo específico para correos electrónicos con validación
10. **telefono**: Campo para números telefónicos con formato

### Propiedades de los Campos

Cada tipo de campo puede tener propiedades específicas:

- **texto, email, telefono, textarea**:
  ```json
  "propiedades": {
    "placeholder": "Texto de ayuda",
    "longitud_maxima": 100
  }
  ```

- **numero**:
  ```json
  "propiedades": {
    "min": 0,
    "max": 100
  }
  ```

- **select, checkbox, radio**:
  ```json
  "propiedades": {
    "opciones": [
      {"valor": "opcion1", "texto": "Opción 1"},
      {"valor": "opcion2", "texto": "Opción 2"},
      {"valor": "opcion3", "texto": "Opción 3"}
    ]
  }
  ```

## Actualización de la Base de Datos

Para habilitar los campos dinámicos, es necesario ejecutar el script de actualización de la base de datos:

```
http://localhost/formulario/db_update_campos.php
```

Este script modifica la estructura de la tabla `campos_formulario` para soportar los nuevos tipos de campos.

## Pruebas

El sistema incluye varios scripts de prueba para verificar el funcionamiento correcto:

1. **Prueba de Campos Dinámicos**:
   ```
   http://localhost/formulario/test_fields_standalone.php
   ```

2. **Prueba de Reordenamiento de Campos**:
   ```
   http://localhost/formulario/test_reorder_fields.php
   ```

3. **Prueba de Envío de Formularios**:
   ```
   http://localhost/formulario/run_form_submission_tests.php
   ```

4. **Prueba de API**:
   ```
   http://localhost/formulario/test_api_submission.php
   ```

## API para Flutter

La API proporciona endpoints para la integración con la aplicación Flutter:

- **Autenticación**: `/api/login.php`
- **Obtener Formularios**: `/api/get_forms.php`
- **Enviar Formulario**: `/api/submit_form.php`
- **Obtener Envíos**: `/api/get_submissions.php`

Para más detalles sobre la API, consulta la [documentación de la API](API_README.md).

## Estructura de Almacenamiento

Las respuestas de los formularios se almacenan en la tabla `envios_formulario` con la siguiente estructura:
- `id`: Identificador único del envío
- `id_formulario`: ID del formulario al que pertenece el envío
- `id_usuario`: ID del usuario que realizó el envío
- `datos`: Campo JSON que almacena todas las respuestas del formulario
- `fecha_envio`: Fecha y hora en que se realizó el envío

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.
