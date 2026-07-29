# Electro Frío — versión mejorada profesional

Esta versión toma el proyecto funcional y lo sube a un flujo más profesional para una empresa técnica:

## Mejoras principales

### Backend Laravel
- Las citas ahora pueden vincularse a un equipo específico mediante `equipo_id`.
- Se agregó relación `Cita -> Equipo` y `Equipo -> Citas`.
- El listado de citas ahora carga cliente, técnico, servicio, equipo y pagos.
- La búsqueda de citas también encuentra por tipo, marca, modelo, serie o ubicación del equipo.
- Dashboard y pagos cargan información del equipo asociado a la cita.
- Se agregó migración nueva sin borrar datos existentes:
  - `2026_06_30_160000_add_equipo_id_to_citas_table.php`

### Frontend Quasar
- Flujo de citas convertido en “atenciones técnicas”.
- Formulario de cita con pestañas internas en el mismo navegador:
  - Datos
  - Trabajo
  - Resumen
- Selección guiada de cliente, equipo, técnico y servicio.
- Al seleccionar servicio, el precio se carga automáticamente.
- En pagos, al seleccionar una cita se carga automáticamente cliente y monto.
- Equipos ahora funcionan como catálogo/referencia técnica con tipos y marcas sugeridas.
- Servicios ahora funcionan como catálogo de precios con referencias rápidas.
- Los botones de Ver/Editar/Eliminar se reemplazaron por menú de tres puntos más ordenado.
- Mejoras visuales: chips de precio, tarjetas de referencia, diálogos más limpios y tablas menos cargadas.

## Cómo probar

### Backend
```bash
cd bkelectrofrio
composer install
php artisan migrate
php artisan serve
```

La migración nueva agrega `equipo_id` a la tabla `citas`. No debería borrar tus datos actuales.

### Frontend
```bash
cd ftelectrofrio
npm install
npm run dev
```

Importante: este proyecto usa una versión de Quasar/App Vite que pide Node 22.22.0 o superior. Si `npm run dev` reclama Node, instala Node 22.22+ y luego vuelve a ejecutar `npm install`.

## Flujo recomendado de uso
1. Registrar cliente.
2. Registrar uno o más equipos del cliente en Equipos.
3. Registrar servicios con precios en Servicios.
4. Crear una atención en Citas seleccionando cliente, equipo y servicio.
5. Al terminar, registrar el pago seleccionando esa cita.

## Ideas para la siguiente fase
- Generar PDF de orden de trabajo.
- Generar PDF de recibo de pago.
- Historial técnico por equipo.
- Garantía por servicio realizado.
- Estado del equipo: pendiente, reparado, entregado, en garantía.
- Usuarios/roles: administrador, técnico, cajero.
- Reportes por fecha, técnico, servicio y cliente.
- WhatsApp con mensaje prearmado para confirmar cita o enviar recibo.
