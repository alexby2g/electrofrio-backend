# Electro Frío PRO V3 - Botones dinámicos de servicios

## Qué cambia

Esta versión convierte las tarjetas de referencia de la pantalla **Servicios** en botones reales y útiles:

- Las tarjetas **Diagnóstico**, **Preventivo**, **Correctivo** e **Instalación** ahora filtran la tabla.
- Cada tarjeta muestra cuántos servicios existen en esa categoría.
- Cada tarjeta muestra precio promedio si hay registros.
- Cada tarjeta tiene botón **Crear** para registrar un servicio ya prellenado con nombre, categoría, descripción y precio sugerido.
- El formulario de servicio ahora incluye campo **Categoría**.
- La tabla muestra la categoría de cada servicio.
- El botón **Nuevo servicio** cambia según la categoría filtrada.
- Se puede limpiar el filtro con **Ver todos**.

## Backend

Se agregó la columna `categoria` en la tabla `servicios` mediante migración:

```bash
php artisan migrate
php artisan optimize:clear
```

No uses `migrate:fresh` si quieres conservar tus datos.

## Si prefieres HeidiSQL

Puedes ejecutar el archivo:

```txt
bkelectrofrio/database/sql/actualizar_servicios_categoria.sql
```

## Orden recomendado para probar

1. Ejecuta migraciones en backend.
2. Levanta backend con `php artisan serve`.
3. Levanta frontend con `npm run dev`.
4. Entra a **Servicios**.
5. Haz clic en una tarjeta para filtrar.
6. Haz clic en **Crear** dentro de una tarjeta para registrar un servicio prellenado.
