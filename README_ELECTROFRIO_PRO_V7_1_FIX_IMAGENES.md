# Electro Frío PRO V7.1 - Fix evidencia fotográfica

Esta versión corrige la visualización de fotos en el expediente técnico.

## Correcciones

- Las imágenes del expediente ahora se cargan usando el host real del API (`VITE_API_URL`) y el `path` de storage.
- Se evita el error común de Laravel cuando `APP_URL` queda como `http://localhost` sin puerto.
- El botón imprimir ahora espera a que las imágenes terminen de cargar antes de abrir la impresión.
- Se aceptan formatos visibles en navegador: JPG, JPEG, PNG, WEBP, GIF y BMP.
- El tamaño máximo de foto subió a 8 MB.

## Importante

Ejecutar en backend:

```bash
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Si aún no se ve una foto antigua, vuelve a subirla o revisa que exista en:

```txt
bkelectrofrio/storage/app/public/evidencias
```

La Nota / Proforma se mantiene limpia. Las fotos salen en el Expediente Técnico.
