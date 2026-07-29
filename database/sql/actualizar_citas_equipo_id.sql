-- Electro Frío PRO V2
-- Ejecuta este script en HeidiSQL si al guardar una cita aparece:
-- Unknown column 'equipo_id' in 'field list'

ALTER TABLE citas
  ADD COLUMN equipo_id BIGINT UNSIGNED NULL AFTER servicio_id;

ALTER TABLE citas
  ADD CONSTRAINT citas_equipo_id_foreign
  FOREIGN KEY (equipo_id) REFERENCES equipos(id)
  ON DELETE SET NULL;
