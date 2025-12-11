#!/bin/sh

# 1. Ejecutar migraciones
php artisan migrate:fresh --seed --force

# 2. Iniciar el servidor de forma que Railway lo entienda (usando FPM)
# Reemplaza con el comando que inicia tu servidor de producción (PHP-FPM)
# Este comando DEBE ser el que se queda corriendo.
php-fpm # Ejemplo si FPM está disponible.