@echo off
chcp 65001 >nul
title Electro Frio API - Preparar y ejecutar en Laragon

cd /d "%~dp0"

echo =====================================================
echo   ELECTRO FRIO API V10 - LARAGON + MYSQL
echo =====================================================
echo.

where php >nul 2>nul
if errorlevel 1 (
  echo ERROR: PHP no esta disponible.
  echo Abre Laragon, pulsa Terminal y ejecuta este archivo desde esa terminal.
  pause
  exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
  echo ERROR: Composer no esta disponible.
  echo Abre Laragon, pulsa Terminal y ejecuta este archivo desde esa terminal.
  pause
  exit /b 1
)

if not exist .env (
  copy .env.example .env >nul
  echo Archivo .env creado.
)

echo Instalando dependencias de Laravel...
call composer install --no-interaction
if errorlevel 1 goto :error

findstr /B /C:"APP_KEY=" .env | findstr /X /C:"APP_KEY=" >nul
if not errorlevel 1 (
  php artisan key:generate
  if errorlevel 1 goto :error
)

where mysql >nul 2>nul
if not errorlevel 1 (
  echo Creando la base electro_frio si todavia no existe...
  mysql -u root -e "CREATE DATABASE IF NOT EXISTS electro_frio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  if errorlevel 1 goto :error
) else (
  echo AVISO: no se encontro el comando mysql.
  echo Crea manualmente la base electro_frio desde HeidiSQL antes de continuar.
  pause
)

echo Ejecutando migraciones...
php artisan migrate --force
if errorlevel 1 goto :error

echo Creando o actualizando el administrador...
php artisan db:seed --class=AdminUserSeeder --force
if errorlevel 1 goto :error

php artisan optimize:clear
if errorlevel 1 goto :error

echo.
echo =====================================================
echo   BACKEND PREPARADO CORRECTAMENTE
echo =====================================================
echo Usa el correo o usuario configurado en el archivo .env
echo La clave permanece privada y no se muestra aqui.
echo API:     http://127.0.0.1:8000/api/health
echo.
echo No cierres esta ventana mientras uses el sistema.
echo.

php artisan serve --host=127.0.0.1 --port=8000
exit /b 0

:error
echo.
echo ERROR: no se pudo completar la preparacion del backend.
echo Copia o toma una foto del mensaje mostrado arriba.
pause
exit /b 1
