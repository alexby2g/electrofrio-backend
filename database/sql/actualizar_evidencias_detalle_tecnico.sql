-- Electro Frío PRO V7
-- Agrega espacio para guardar la galería de fotos antes/durante/después del detalle técnico.

ALTER TABLE detalle_tecnicos
  ADD COLUMN evidencias JSON NULL AFTER items;
