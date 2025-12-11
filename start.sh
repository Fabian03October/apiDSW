#!/bin/sh

# 1. Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate:fresh --seed --force

# 2. Iniciar el servidor de aplicaciones RoadRunner
# RoadRunner es el proceso que se queda corriendo y atiende el puerto.
echo "Iniciando RoadRunner en el puerto $PORT..."
./vendor/bin/rr serve --port $PORT