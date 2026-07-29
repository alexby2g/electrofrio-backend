ALTER TABLE servicios
  ADD COLUMN categoria VARCHAR(80) NULL AFTER descripcion;

CREATE INDEX servicios_categoria_index ON servicios (categoria);

UPDATE servicios
SET categoria = 'diagnostico'
WHERE categoria IS NULL
  AND (
    nombre LIKE '%diagn%'
    OR nombre LIKE '%revisi%'
    OR descripcion LIKE '%diagn%'
    OR descripcion LIKE '%revisi%'
  );

UPDATE servicios
SET categoria = 'preventivo'
WHERE categoria IS NULL
  AND (
    nombre LIKE '%prevent%'
    OR nombre LIKE '%mantenimiento%'
    OR nombre LIKE '%limpieza%'
    OR descripcion LIKE '%prevent%'
    OR descripcion LIKE '%mantenimiento%'
    OR descripcion LIKE '%limpieza%'
  );

UPDATE servicios
SET categoria = 'instalacion'
WHERE categoria IS NULL
  AND (
    nombre LIKE '%instal%'
    OR nombre LIKE '%reubic%'
    OR descripcion LIKE '%instal%'
    OR descripcion LIKE '%reubic%'
  );

UPDATE servicios
SET categoria = 'correctivo'
WHERE categoria IS NULL
  AND (
    nombre LIKE '%correct%'
    OR nombre LIKE '%repar%'
    OR nombre LIKE '%fuga%'
    OR nombre LIKE '%compresor%'
    OR nombre LIKE '%capacitor%'
    OR descripcion LIKE '%correct%'
    OR descripcion LIKE '%repar%'
    OR descripcion LIKE '%fuga%'
    OR descripcion LIKE '%compresor%'
    OR descripcion LIKE '%capacitor%'
  );
