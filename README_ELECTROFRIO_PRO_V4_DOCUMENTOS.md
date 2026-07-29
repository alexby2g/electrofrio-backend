# Electro Frío PRO V4 — Detalle técnico, expediente, proforma y WhatsApp

Esta versión agrega el flujo profesional después de crear una atención:

1. Crear atención: cliente, equipo, servicio, técnico, fecha, estado y total.
2. Abrir menú de tres puntos en la atención.
3. Entrar a **Detalle técnico**.
4. Guardar diagnóstico, trabajo realizado, repuestos, estado del equipo, garantía, recomendaciones y fecha de entrega.
5. Abrir **Proforma / expediente**.
6. Imprimir o guardar como PDF usando el diálogo de impresión del navegador.
7. Abrir WhatsApp con mensaje preparado para el cliente.

## Cambios backend

- Nueva migración: `2026_06_30_162000_add_documento_fields_to_detalle_tecnicos_table.php`
- Nuevos campos en `detalle_tecnicos`:
  - `estado_equipo`
  - `garantia`
  - `recomendaciones`
  - `fecha_entrega`
- Endpoint nuevo:
  - `GET /api/citas/{cita}/documento`

## Cambios frontend

- `CitasPage.vue` ahora incluye:
  - Menú de tres puntos con Detalle técnico, Proforma/Expediente y WhatsApp.
  - Diálogo para completar expediente técnico.
  - Vista previa de nota/proforma y expediente.
  - Botón **Imprimir / Guardar PDF**.
  - Botón **Enviar WhatsApp**.

## Comandos recomendados

Backend:

```bash
cd bkelectrofrio
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

## Si usas HeidiSQL

Si `php artisan migrate` no se puede ejecutar, corre manualmente:

```txt
bkelectrofrio/database/sql/actualizar_detalle_tecnico_documentos.sql
```

> Ojo: ese SQL es para una base que aún no tenga esos campos. Si ya existen, HeidiSQL marcará duplicado.
