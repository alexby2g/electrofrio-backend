ALTER TABLE detalle_tecnicos
  ADD COLUMN estado_equipo VARCHAR(80) NULL AFTER trabajo_realizado,
  ADD COLUMN garantia VARCHAR(160) NULL AFTER estado_equipo,
  ADD COLUMN recomendaciones TEXT NULL AFTER garantia,
  ADD COLUMN fecha_entrega DATE NULL AFTER recomendaciones;
