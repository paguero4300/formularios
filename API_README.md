# API Dr Security - Documentación

Esta documentación describe los endpoints disponibles para la integración de la aplicación Flutter con el panel administrativo Dr Security.

## URL Base

Todos los endpoints están disponibles en:

```
https://formulario.drsecuritygps.com
```

## Autenticación

### Iniciar sesión

Permite a los usuarios autenticarse en el sistema.

**Endpoint:** `/api/login.php`
**Método:** POST
**Content-Type:** application/json

**Parámetros (JSON):**
```json
{
  "username": "nombre_de_usuario",
  "password": "contraseña"
}
```

**Respuesta exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "user": {
    "id": 1,
    "username": "admin",
    "nombre_completo": "Administrador"
  }
}
```

**Respuesta de error (401 Unauthorized):**
```json
{
  "error": "Credenciales inválidas"
}
```

**Ejemplo de uso con cURL:**
```bash
curl -X POST https://formulario.drsecuritygps.com/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

## Formularios

### Obtener formularios asignados

Obtiene los formularios disponibles para un usuario específico.

**Endpoint:** `/api/get_forms.php`
**Método:** GET

**Parámetros (URL):**
- `user_id`: ID del usuario (obtenido en la respuesta de login)

**Respuesta exitosa (200 OK):**
```json
{
  "success": true,
  "forms": [
    {
      "id": 1,
      "titulo": "Formulario de Inspección",
      "descripcion": "Formulario para registrar inspecciones de seguridad",
      "campos": [
        {
          "id": 1,
          "tipo_campo": "texto",
          "etiqueta": "Nombre del cliente",
          "requerido": 1,
          "orden": 1,
          "propiedades": {
            "placeholder": "Ingrese el nombre completo",
            "longitud_maxima": 100
          }
        },
        {
          "id": 2,
          "tipo_campo": "fecha_hora",
          "etiqueta": "Fecha y hora de visita",
          "requerido": 1,
          "orden": 2
        },
        {
          "id": 3,
          "tipo_campo": "ubicacion_gps",
          "etiqueta": "Ubicación del sitio",
          "requerido": 1,
          "orden": 3
        },
        {
          "id": 4,
          "tipo_campo": "select",
          "etiqueta": "Tipo de servicio",
          "requerido": 1,
          "orden": 4,
          "propiedades": {
            "opciones": [
              {"valor": "instalacion", "texto": "Instalación"},
              {"valor": "mantenimiento", "texto": "Mantenimiento"},
              {"valor": "reparacion", "texto": "Reparación"}
            ]
          }
        },
        {
          "id": 5,
          "tipo_campo": "textarea",
          "etiqueta": "Observaciones",
          "requerido": 0,
          "orden": 5,
          "propiedades": {
            "placeholder": "Ingrese sus observaciones",
            "longitud_maxima": 500
          }
        }
      ]
    }
  ]
}
```

**Respuesta de error (404 Not Found):**
```json
{
  "error": "Usuario no encontrado o inactivo"
}
```

**Ejemplo de uso con cURL:**
```bash
curl -X GET "https://formulario.drsecuritygps.com/api/get_forms.php?user_id=1"
```

### Enviar formulario completado

Permite enviar un formulario completado al servidor.

**Endpoint:** `/api/submit_form.php`
**Método:** POST
**Content-Type:** application/json

**Parámetros (JSON):**
```json
{
  "form_id": 1,
  "user_id": 1,
  "data": {
    "1": "Juan Pérez",
    "2": "2023-04-14 15:30:00",
    "3": "19.4326,-99.1332",
    "4": "instalacion",
    "5": "Instalación completada sin incidentes. Cliente satisfecho con el servicio."
  }
}
```

**Notas sobre los datos:**
- Las claves en el objeto `data` corresponden a los IDs de los campos del formulario.
- Los valores deben cumplir con el formato esperado según el tipo de campo:
  - `lugar`: Texto libre
  - `fecha_hora`: Formato "YYYY-MM-DD HH:MM:SS"
  - `ubicacion_gps`: Formato "latitud,longitud" (ej: "19.4326,-99.1332")
  - `comentario`: Texto libre, puede incluir múltiples líneas

**Respuesta exitosa (200 OK):**
```json
{
  "success": true,
  "message": "Formulario enviado correctamente",
  "submission_id": 1
}
```

**Respuesta de error (400 Bad Request):**
```json
{
  "error": "Datos inválidos",
  "errors": [
    "El campo 'Información del lugar' es requerido",
    "El campo 'Ubicación GPS' debe contener coordenadas GPS válidas"
  ]
}
```

**Ejemplo de uso con cURL:**
```bash
curl -X POST https://formulario.drsecuritygps.com/api/submit_form.php \
  -H "Content-Type: application/json" \
  -d '{
    "form_id": 1,
    "user_id": 1,
    "data": {
      "1": "Oficina Central",
      "2": "2023-04-14 15:30:00",
      "3": "19.4326,-99.1332",
      "4": "Inspección de rutina completada sin incidentes."
    }
  }'
```

## Envíos

### Obtener envíos de formularios

Obtiene los envíos de formularios según los permisos del usuario.

**Endpoint:** `/api/get_submissions.php`
**Método:** GET

**Parámetros (URL):**
- `user_id`: ID del usuario (obligatorio)
- `form_id`: ID del formulario (opcional, para filtrar por formulario)
- `page`: Número de página (opcional, por defecto 1)
- `per_page`: Registros por página (opcional, por defecto 10)

**Respuesta exitosa (200 OK):**
```json
{
  "success": true,
  "submissions": [
    {
      "id": 1,
      "form_id": 1,
      "form_title": "Formulario de Inspección",
      "user_id": 2,
      "username": "test",
      "nombre_completo": "Usuario de Prueba",
      "fecha_envio": "2023-04-14 15:35:22",
      "datos": {
        "1": "Oficina Central",
        "2": "2023-04-14 15:30:00",
        "3": "19.4326,-99.1332",
        "4": "Inspección de rutina completada sin incidentes."
      }
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 1
  }
}
```

**Respuesta de error (404 Not Found):**
```json
{
  "error": "Usuario no encontrado o inactivo"
}
```

**Ejemplo de uso con cURL:**
```bash
curl -X GET "https://formulario.drsecuritygps.com/api/get_submissions.php?user_id=1&form_id=1&page=1&per_page=10"
```

## Tipos de campos soportados

El sistema soporta los siguientes tipos de campos para los formularios:

### Tipos esenciales
1. **fecha_hora**: Campo para registrar fecha y hora (formato "YYYY-MM-DD HH:MM:SS")
2. **ubicacion_gps**: Campo para registrar coordenadas GPS (formato "latitud,longitud")

### Tipos dinámicos
3. **texto**: Campo de texto simple para nombres, descripciones cortas, etc.
4. **numero**: Campo numérico con validación de mínimo y máximo
5. **textarea**: Campo de texto multilínea para comentarios extensos
6. **select**: Lista desplegable con opciones predefinidas
7. **checkbox**: Casillas de verificación para selección múltiple
8. **radio**: Botones de opción para selección única
9. **email**: Campo específico para correos electrónicos con validación
10. **telefono**: Campo para números telefónicos con formato

### Tipos heredados (para compatibilidad)
11. **lugar**: Campo de texto para registrar la ubicación o lugar
12. **comentario**: Campo de texto multilínea para observaciones o comentarios

### Propiedades de los campos

Los campos pueden tener propiedades adicionales según su tipo:

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

## Notas importantes

1. **Autenticación**: Todas las APIs excepto `/api/login.php` esperan que el usuario esté autenticado. Debes proporcionar el `user_id` obtenido durante el login.

2. **Asignación de formularios**: Los usuarios solo verán los formularios que les han sido asignados por un administrador. Si un usuario no tiene formularios asignados, la API devolverá un array vacío.

3. **Validación de datos**: El servidor valida que todos los campos marcados como requeridos (`requerido = 1`) tengan un valor y que los valores cumplan con el formato esperado según el tipo de campo.

4. **Paginación**: La API de envíos soporta paginación para manejar grandes cantidades de datos. Puedes especificar la página y la cantidad de registros por página.

5. **Filtrado**: Puedes filtrar los envíos por formulario específico utilizando el parámetro `form_id`.

6. **Seguridad**: Todas las comunicaciones deben realizarse a través de HTTPS para garantizar la seguridad de los datos.

7. **Formato de respuesta**: Todas las APIs devuelven respuestas en formato JSON con un campo `success` que indica si la operación fue exitosa o no.

## Recomendaciones para la implementación en Flutter

1. **Almacenamiento local**: Implementa un sistema de almacenamiento local (SQLite) para guardar formularios y envíos cuando no hay conexión a internet.

2. **Sincronización**: Crea un servicio de sincronización que envíe los formularios almacenados localmente cuando se recupere la conexión a internet.

3. **Manejo de errores**: Implementa un manejo adecuado de errores para informar al usuario cuando ocurran problemas de conectividad o errores del servidor.

4. **Validación**: Aunque el servidor valida los datos, es recomendable implementar validación también en el lado del cliente para mejorar la experiencia del usuario.

5. **Caché**: Considera implementar un sistema de caché para los formularios, de modo que los usuarios puedan acceder a ellos incluso sin conexión a internet.

## Soporte

Para cualquier duda o problema con la API, contacta al administrador del sistema.
