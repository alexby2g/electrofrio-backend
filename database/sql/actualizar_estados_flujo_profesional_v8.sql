ALTER TABLE citas
  MODIFY estado ENUM(
    'pendiente',
    'revision',
    'en_proceso',
    'esperando_repuesto',
    'terminado',
    'entregado',
    'concluida',
    'cancelada'
  ) NOT NULL DEFAULT 'pendiente';
