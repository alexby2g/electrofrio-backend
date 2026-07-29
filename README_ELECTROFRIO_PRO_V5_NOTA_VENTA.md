# Electro Frío PRO V5 - Nota de Venta / Proforma con formato real

## Qué cambia esta versión

Esta versión adapta el documento de atención al formato de nota de venta/proforma usado como referencia:

- Encabezado con marca Electro Frío, slogan y contacto.
- Número de documento.
- Empresa, fecha y cliente.
- Tabla con cantidad, descripción, precio unitario y subtotal.
- Total en bolivianos.
- Total literal: `SON: ... 00/100 BOLIVIANOS`.
- Firma del cliente y firma técnico/sello.
- Botón para imprimir o guardar como PDF desde el navegador.
- Botón de WhatsApp con mensaje listo.

## Nuevo campo técnico

Se agregó `items` al detalle técnico para guardar los ítems de la nota:

```json
[
  {
    "cantidad": 1,
    "unidad": "serv.",
    "descripcion": "Mantenimiento preventivo",
    "precio_unitario": 150,
    "subtotal": 150
  }
]
```

## Actualizar base de datos

Ejecuta en el backend:

```bash
php artisan migrate
php artisan optimize:clear
php artisan serve
```

También queda un SQL manual en:

```txt
bkelectrofrio/database/sql/actualizar_items_proforma_detalle_tecnico.sql
```

## Dónde se usa

En **Citas / Atenciones**:

1. Crea o edita una atención.
2. En el menú de tres puntos abre **Detalle técnico**.
3. Llena diagnóstico, trabajo realizado y los ítems de la nota.
4. Abre **Proforma / expediente**.
5. Usa **Imprimir / Guardar PDF** o **Enviar WhatsApp**.

## Archivos principales modificados

### Backend

- `app/Models/DetalleTecnico.php`
- `app/Http/Controllers/Api/DetalleTecnicoController.php`
- `database/migrations/2026_06_30_172500_add_items_to_detalle_tecnicos_table.php`
- `database/sql/actualizar_items_proforma_detalle_tecnico.sql`

### Frontend

- `src/pages/CitasPage.vue`
- `src/layouts/MainLayout.vue`
- `src/css/app.scss`
- `src/assets/electrofrio-logo.png`
- `src/assets/electrofrio-tecnico-trabajo.png`
