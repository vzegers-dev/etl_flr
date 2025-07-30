#!/bin/bash

# Ruta completa al script PHP que quieres detener
PHP_SCRIPT="run.php"

# (Opcional) Ruta al archivo .pid si lo usas
PID_FILE="/tmp/run_php.pid"

# Buscar el proceso usando pgrep
PID=$(pgrep -f "php $PHP_SCRIPT")

if [ -z "$PID" ]; then
    echo "No se encontró el proceso ejecutando '$PHP_SCRIPT'."
else
    echo "Deteniendo proceso (PID $PID)..."
    kill -9 $PID

    if [ $? -eq 0 ]; then
        echo "Proceso detenido con éxito."
        # Eliminar archivo PID si existe
        if [ -f "$PID_FILE" ]; then
            rm "$PID_FILE"
            echo "Archivo PID eliminado."
        fi
    else
        echo "No se pudo detener el proceso."
    fi
fi
