#!/bin/bash

# Nombre del script PHP
PHP_SCRIPT="run.php"

# Comprobar si el script PHP está corriendo
if ! pgrep -f "php $PHP_SCRIPT" > /dev/null; then
    echo "El script PHP no está corriendo. Iniciando el script..."
    # Ejecutar el script PHP en segundo plano
    nohup php $PHP_SCRIPT > /dev/null 2>&1 &
else
    echo "El script PHP ya está corriendo."
fi

