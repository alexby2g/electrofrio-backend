# Electro Frío PRO V2 - Orden técnico y corrección de base de datos

## 1. Corrección urgente del error `Unknown column 'equipo_id'`

Ese error no es del formulario: significa que el frontend y el backend ya están enviando `equipo_id`, pero tu base de datos todavía no tiene esa columna en la tabla `citas`.

Opción recomendada en consola del backend:

```bash
php artisan migrate
php artisan optimize:clear
```

Si prefieres HeidiSQL, ejecuta:

```sql
ALTER TABLE citas
  ADD COLUMN equipo_id BIGINT UNSIGNED NULL AFTER servicio_id;

ALTER TABLE citas
  ADD CONSTRAINT citas_equipo_id_foreign
  FOREIGN KEY (equipo_id) REFERENCES equipos(id)
  ON DELETE SET NULL;
```

También te dejé el archivo listo en:

`bkelectrofrio/database/sql/actualizar_citas_equipo_id.sql`

No uses `migrate:fresh` si ya tienes datos, porque eso borra las tablas.

## 2. Orden recomendado del sistema

El orden profesional para no confundirse es este:

1. **Clientes**: datos del cliente y contacto.
2. **Equipos**: cada equipo pertenece a un cliente. Aquí se guarda tipo, marca, modelo, serie, ubicación y observación.
3. **Servicios**: catálogo de trabajos y precios base.
4. **Citas / Atenciones**: operación principal. Selecciona cliente, equipo, técnico, servicio, fecha, estado y total.
5. **Pagos**: cobro ligado a una cita o atención.
6. **Detalle técnico**: diagnóstico, solución, repuestos y recomendaciones. Este módulo está en backend, pero falta llevarlo a una pantalla más completa en frontend.
7. **Reportes / PDFs**: orden de trabajo, recibo, historial por equipo, ingresos por fecha.

## 3. Qué se mejoró en esta versión

- Menú lateral ordenado por flujo real: Operación diaria y Catálogos.
- Mejor aspecto visual general: tarjetas, tablas, hover, sidebar y diálogos más pro.
- Mensaje más claro cuando falte actualizar la base de datos con `equipo_id`.
- Validación backend para evitar seleccionar un equipo que no pertenece al cliente.
- En citas se muestra una alerta si el cliente no tiene equipos registrados.
- Botones Anterior / Siguiente en la atención para que el flujo sea más guiado.

## 4. Siguiente evolución ideal

La mejora más fuerte sería convertir `Detalle Técnico` en una pantalla pro:

- Diagnóstico inicial.
- Trabajo realizado.
- Repuestos usados.
- Estado final del equipo: recibido, revisado, reparado, entregado.
- Garantía en días.
- Recomendaciones.
- PDF de orden de trabajo.
- Historial por equipo.
