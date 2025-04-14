#!/bin/bash
# Script para configurar los parámetros importantes de la aplicación Dr Security
# Autor: Augment Agent
# Fecha: 2023

# Colores para mejor visualización
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar mensajes de cabecera
header() {
    echo -e "\n${BLUE}=== $1 ===${NC}\n"
}

# Función para mostrar mensajes de éxito
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Función para mostrar mensajes de error
error() {
    echo -e "${RED}✗ $1${NC}"
    exit 1
}

# Función para mostrar mensajes de advertencia
warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Función para mostrar mensajes informativos
info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Función para obtener el valor actual de una constante en un archivo
get_current_value() {
    local file=$1
    local constant=$2
    grep -oP "define\\('$constant',\\s*'\\K[^']*" "$file" 2>/dev/null || echo "No encontrado"
}

# Función para actualizar una constante en un archivo
update_constant() {
    local file=$1
    local constant=$2
    local value=$3
    local current_value=$(get_current_value "$file" "$constant")
    
    if [ "$current_value" == "No encontrado" ]; then
        error "No se pudo encontrar la constante $constant en $file"
        return 1
    fi
    
    # Escapar caracteres especiales en el valor actual y el nuevo valor para sed
    local escaped_current=$(echo "$current_value" | sed 's/[\/&]/\\&/g')
    local escaped_new=$(echo "$value" | sed 's/[\/&]/\\&/g')
    
    # Realizar la sustitución
    sed -i "s/define('$constant', '$escaped_current')/define('$constant', '$escaped_new')/g" "$file"
    
    if [ $? -eq 0 ]; then
        success "Actualizado $constant de '$current_value' a '$value' en $file"
        return 0
    else
        error "Error al actualizar $constant en $file"
        return 1
    fi
}

# Mostrar cabecera
clear
header "Configuración de Dr Security - Panel Administrativo"
echo "Este script te ayudará a configurar los parámetros importantes de la aplicación."
echo "Presiona Ctrl+C en cualquier momento para cancelar."
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "config/config.php" ] || [ ! -f "config/database.php" ]; then
    error "No se encontraron los archivos de configuración. Asegúrate de ejecutar este script desde el directorio raíz de la aplicación."
fi

# Mostrar configuración actual
header "Configuración Actual"

# Configuración de la aplicación
APP_URL=$(get_current_value "config/config.php" "APP_URL")
echo "URL de la aplicación (APP_URL): $APP_URL"

# Configuración de la base de datos
DB_HOST=$(get_current_value "config/database.php" "DB_HOST")
DB_PORT=$(get_current_value "config/database.php" "DB_PORT")
DB_USER=$(get_current_value "config/database.php" "DB_USER")
DB_PASS=$(get_current_value "config/database.php" "DB_PASS")
DB_NAME=$(get_current_value "config/database.php" "DB_NAME")

echo "Host de la base de datos (DB_HOST): $DB_HOST"
echo "Puerto de la base de datos (DB_PORT): $DB_PORT"
echo "Usuario de la base de datos (DB_USER): $DB_USER"
echo "Contraseña de la base de datos (DB_PASS): $DB_PASS"
echo "Nombre de la base de datos (DB_NAME): $DB_NAME"

# Preguntar si se desea modificar la configuración
header "Modificar Configuración"
echo "¿Deseas modificar la configuración? (s/n)"
read -r response
if [[ "$response" =~ ^([nN][oO]|[nN])$ ]]; then
    info "No se realizarán cambios. Saliendo..."
    exit 0
fi

# Modificar URL de la aplicación
echo ""
echo "URL de la aplicación (actual: $APP_URL)"
echo "Ingresa la nueva URL (o presiona Enter para mantener el valor actual):"
read -r new_app_url
if [ -n "$new_app_url" ]; then
    update_constant "config/config.php" "APP_URL" "$new_app_url"
else
    info "Se mantiene el valor actual de APP_URL: $APP_URL"
fi

# Modificar configuración de la base de datos
echo ""
echo "Host de la base de datos (actual: $DB_HOST)"
echo "Ingresa el nuevo host (o presiona Enter para mantener el valor actual):"
read -r new_db_host
if [ -n "$new_db_host" ]; then
    update_constant "config/database.php" "DB_HOST" "$new_db_host"
else
    info "Se mantiene el valor actual de DB_HOST: $DB_HOST"
fi

echo ""
echo "Puerto de la base de datos (actual: $DB_PORT)"
echo "Ingresa el nuevo puerto (o presiona Enter para mantener el valor actual):"
read -r new_db_port
if [ -n "$new_db_port" ]; then
    update_constant "config/database.php" "DB_PORT" "$new_db_port"
else
    info "Se mantiene el valor actual de DB_PORT: $DB_PORT"
fi

echo ""
echo "Usuario de la base de datos (actual: $DB_USER)"
echo "Ingresa el nuevo usuario (o presiona Enter para mantener el valor actual):"
read -r new_db_user
if [ -n "$new_db_user" ]; then
    update_constant "config/database.php" "DB_USER" "$new_db_user"
else
    info "Se mantiene el valor actual de DB_USER: $DB_USER"
fi

echo ""
echo "Contraseña de la base de datos (actual: $DB_PASS)"
echo "Ingresa la nueva contraseña (o presiona Enter para mantener el valor actual):"
read -r new_db_pass
if [ -n "$new_db_pass" ]; then
    update_constant "config/database.php" "DB_PASS" "$new_db_pass"
else
    info "Se mantiene el valor actual de DB_PASS: $DB_PASS"
fi

echo ""
echo "Nombre de la base de datos (actual: $DB_NAME)"
echo "Ingresa el nuevo nombre (o presiona Enter para mantener el valor actual):"
read -r new_db_name
if [ -n "$new_db_name" ]; then
    update_constant "config/database.php" "DB_NAME" "$new_db_name"
else
    info "Se mantiene el valor actual de DB_NAME: $DB_NAME"
fi

# Mostrar resumen de cambios
header "Resumen de Cambios"
echo "URL de la aplicación (APP_URL): $(get_current_value "config/config.php" "APP_URL")"
echo "Host de la base de datos (DB_HOST): $(get_current_value "config/database.php" "DB_HOST")"
echo "Puerto de la base de datos (DB_PORT): $(get_current_value "config/database.php" "DB_PORT")"
echo "Usuario de la base de datos (DB_USER): $(get_current_value "config/database.php" "DB_USER")"
echo "Contraseña de la base de datos (DB_PASS): $(get_current_value "config/database.php" "DB_PASS")"
echo "Nombre de la base de datos (DB_NAME): $(get_current_value "config/database.php" "DB_NAME")"

# Verificar la conexión a la base de datos
header "Verificando Conexión a la Base de Datos"
echo "<?php
require_once 'config/database.php';
\$conn = getDBConnection();
if (\$conn->connect_error) {
    echo \"Error: \" . \$conn->connect_error;
    exit(1);
} else {
    echo \"Conexión exitosa a la base de datos.\";
    \$conn->close();
}
?>" > test_db_connection.php

# Ejecutar el script PHP para probar la conexión
php test_db_connection.php
if [ $? -eq 0 ]; then
    success "La configuración de la base de datos es correcta."
else
    warning "Hubo un problema con la conexión a la base de datos. Verifica los parámetros."
fi

# Eliminar el archivo de prueba
rm test_db_connection.php

# Mensaje final
header "Configuración Completada"
echo "La configuración ha sido actualizada correctamente."
echo "Recuerda reiniciar tu servidor web si es necesario para que los cambios surtan efecto."
echo ""
echo "Para ejecutar este script nuevamente, usa:"
echo "  bash config_setup.sh"
echo ""
success "¡Listo! Tu aplicación Dr Security está configurada."
