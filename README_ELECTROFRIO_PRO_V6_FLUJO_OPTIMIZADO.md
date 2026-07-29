# Electro Frío PRO V6 — Flujo optimizado sin repetir datos

Esta versión corrige el problema de rellenar datos repetidos. El sistema mantiene el flujo profesional, pero ahora carga y reutiliza información desde la atención.

## Cambios principales

### Clientes
- El formulario de cliente queda enfocado en contacto: nombre, teléfono, dirección y observación.
- La referencia de equipo/marca queda dentro de una sección opcional, para no confundir con el módulo Equipos.
- La tabla de clientes ya no fuerza columnas de equipo/marca, porque el historial real debe vivir en Equipos.

### Citas / Atenciones
- Al seleccionar cliente, si tiene un solo equipo registrado, se selecciona automáticamente.
- Al seleccionar servicio, se cargan automáticamente precio y descripción base.
- Se muestra una tarjeta de contexto con cliente, equipo y cantidad de equipos del cliente.
- Se agregó el botón “Guardar y detalle” para pasar directo al detalle técnico sin volver a la tabla.
- Se agregó el botón “Guardar y abrir detalle técnico” en el resumen.

### Detalle técnico
- Cliente, equipo, servicio, técnico y precio vienen desde la atención.
- Se agregaron plantillas rápidas: Mantenimiento, Reparación, Instalación y Diagnóstico.
- La nota/proforma se genera automáticamente con el servicio principal.
- La edición de ítems queda oculta por defecto y solo se abre si se necesitan materiales extras o cambios manuales.
- Botón “Usar servicio” para reconstruir el ítem principal desde la atención.

### Pagos
- El cliente ya no se vuelve a seleccionar manualmente cuando hay una atención seleccionada.
- Al elegir la atención, se muestra una tarjeta de resumen y se sugiere el monto automáticamente.
- El monto sigue editable para pagos parciales.

## Instalación

Backend:
```bash
cd bkelectrofrio
composer install
php artisan migrate
php artisan optimize:clear
php artisan serve
```

Frontend:
```bash
cd ftelectrofrio
npm install
npm run dev
```

## Nota técnica
- No se agregaron migraciones nuevas en esta versión.
- Se revisó sintaxis JavaScript de los componentes modificados.
- Se parsearon los componentes Vue modificados correctamente.
- El build completo no se pudo ejecutar en este entorno porque Quasar exige Node >= 22.22.0 y aquí hay Node 22.16.0.
