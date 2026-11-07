-- Aplicar una vez después de migracion_fidelidad_verde.sql y migracion_precios_historicos.sql.
-- Los cupones existentes sin vencer reciben 14 días desde la aplicación de esta migración.
ALTER TABLE eco_cupones
    ADD COLUMN Expira DATETIME NULL,
    ADD COLUMN Recordatorio_Intentado DATETIME NULL,
    ADD COLUMN Recordatorio_Enviado DATETIME NULL,
    ADD COLUMN Recordatorio_Intentos TINYINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE eco_cupones
SET Expira = CASE
    WHEN Canjeado IS NULL THEN DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 14 DAY)
    ELSE DATE_ADD(Creado, INTERVAL 14 DAY)
END
WHERE Expira IS NULL;

ALTER TABLE eco_cupones
    MODIFY COLUMN Expira DATETIME NOT NULL,
    ADD KEY eco_cupon_estado_vencimiento (Canjeado, Expira, Recordatorio_Enviado);

CREATE TABLE IF NOT EXISTS ordenes_cupones (
    ID_Orden INT NOT NULL,
    ID_Cupon BIGINT NOT NULL,
    ID_Producto INT NOT NULL,
    Descuento DECIMAL(10,2) NOT NULL,
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Orden),
    UNIQUE KEY ordenes_cupon_cupon_unico (ID_Cupon),
    CONSTRAINT ordenes_cupon_orden FOREIGN KEY (ID_Orden) REFERENCES ordenes(ID_Ordenes),
    CONSTRAINT ordenes_cupon_cupon FOREIGN KEY (ID_Cupon) REFERENCES eco_cupones(ID_Cupon),
    CONSTRAINT ordenes_cupon_producto FOREIGN KEY (ID_Producto) REFERENCES menuproductos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
