# Electro Frío PRO V7.2 — Fix definitivo de fotos en expediente

Esta versión corrige el problema donde las fotos subían correctamente pero en el expediente aparecía solo el nombre del archivo o el icono roto.

## Qué cambió

- Las fotos ya no dependen de `/storage` ni del enlace simbólico de Laravel para verse.
- Se agregó una ruta API para servir cada evidencia directamente:
  `GET /api/detalle-tecnicos/{detalle}/evidencias/{evidencia}/archivo`
- El frontend usa esa ruta para mostrar las imágenes en:
  - Galería del detalle técnico.
  - Vista del expediente.
  - Impresión / Guardar PDF.
- Funciona con JPG, JPEG, PNG, WEBP, GIF y BMP.
- No requiere migración nueva.

## Pasos recomendados

Backend:
```bash
cd bkelectrofrio
php artisan optimize:clear
php artisan serve
```

Frontend:
```bash
cd ftelectrofrio
npm install
npm run dev
```

`php artisan storage:link` ya no debería ser obligatorio para ver las fotos dentro del sistema, porque ahora se sirven por API. Si alguna foto antigua no aparece, elimina esa evidencia y vuelve a subirla.
