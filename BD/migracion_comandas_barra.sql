-- Aplicar una sola vez, despues de respaldar la base de datos.
-- Los pedidos existentes quedan como entregados; las compras nuevas se crean pendientes.
ALTER TABLE ordenes
    ADD COLUMN Estado_KDS ENUM('pendiente', 'preparando', 'listo', 'entregado')
        NOT NULL DEFAULT 'entregado',
    ADD COLUMN Creado_En DATETIME NULL DEFAULT NULL,
    ADD KEY idx_ordenes_estado_kds_fecha (Estado_KDS, Creado_En);
