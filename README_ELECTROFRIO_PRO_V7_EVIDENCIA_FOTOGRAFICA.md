# Electro Frío PRO V7 — Evidencia fotográfica

Esta versión agrega carga real de fotografías al expediente técnico.

## Flujo recomendado

1. Crear atención en **Citas / Atenciones**.
2. Abrir menú de tres puntos → **Detalle técnico**.
3. Guardar el detalle técnico una vez.
4. En la sección **Evidencia fotográfica**, subir fotos por tipo:
   - Antes
   - Durante
   - Después
   - Firma / comprobante
   - Otro
5. Abrir **Proforma / Expediente** y elegir la pestaña **Expediente**.
6. Las fotos aparecen automáticamente en el documento.
7. Imprimir o guardar como PDF desde el navegador.

## Backend

Ejecutar:

```bash
cd bkelectrofrio
composer install
php artisan migrate
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

`php artisan storage:link` es importante para que el navegador pueda mostrar las fotos guardadas en `storage/app/public`.

Si prefieres HeidiSQL, ejecuta:

```txt
bkelectrofrio/database/sql/actualizar_evidencias_detalle_tecnico.sql
```

## Frontend

Ejecutar:

```bash
cd ftelectrofrio
npm install
npm run dev
```

## Cambios técnicos

- Nueva columna `detalle_tecnicos.evidencias` tipo JSON.
- Nuevo endpoint para subir fotos:
  - `POST /api/detalle-tecnicos/{detalleTecnico}/evidencias`
- Nuevo endpoint para eliminar fotos:
  - `DELETE /api/detalle-tecnicos/{detalleTecnico}/evidencias/{evidencia}`
- Validación de fotos JPG, JPEG, PNG y WEBP hasta 5 MB.
- Las fotos se guardan en `storage/app/public/evidencias/detalles/{id}`.
- El expediente técnico muestra galería de fotos en vista previa e impresión.

## Nota

La nota/proforma se mantiene limpia para cobro. Las fotos se muestran en el expediente técnico, que es el documento completo del servicio realizado.
