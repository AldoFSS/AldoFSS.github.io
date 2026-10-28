-- Aplicar una vez en la base cafeteria para habilitar inventario y recetas.
CREATE TABLE IF NOT EXISTS inventario_insumos (
    ID_Insumo INT NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Unidad VARCHAR(20) NOT NULL,
    Stock DECIMAL(12,3) NOT NULL DEFAULT 0,
    Stock_Minimo DECIMAL(12,3) NOT NULL DEFAULT 0,
    Caducidad DATE NULL,
    Fecha_Apertura DATE NULL,
    Actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Insumo),
    UNIQUE KEY inventario_nombre_unico (Nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recetas_insumos (
    ID_Producto INT NOT NULL,
    ID_Insumo INT NOT NULL,
    Cantidad DECIMAL(12,3) NOT NULL,
    PRIMARY KEY (ID_Producto, ID_Insumo),
    KEY receta_insumo (ID_Insumo),
    CONSTRAINT recetas_insumos_producto FOREIGN KEY (ID_Producto) REFERENCES menuproductos(id) ON DELETE CASCADE,
    CONSTRAINT recetas_insumos_insumo FOREIGN KEY (ID_Insumo) REFERENCES inventario_insumos(ID_Insumo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimientos_inventario (
    ID_Movimiento BIGINT NOT NULL AUTO_INCREMENT,
    ID_Insumo INT NOT NULL,
    ID_Orden INT NULL,
    Tipo ENUM('inicial', 'entrada', 'consumo', 'merma') NOT NULL,
    Cantidad DECIMAL(12,3) NOT NULL,
    Stock_Anterior DECIMAL(12,3) NOT NULL,
    Stock_Nuevo DECIMAL(12,3) NOT NULL,
    Nota VARCHAR(255) NOT NULL DEFAULT '',
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Movimiento),
    KEY movimiento_insumo_fecha (ID_Insumo, Creado),
    KEY movimiento_orden (ID_Orden),
    CONSTRAINT movimientos_inventario_insumo FOREIGN KEY (ID_Insumo) REFERENCES inventario_insumos(ID_Insumo),
    CONSTRAINT movimientos_inventario_orden FOREIGN KEY (ID_Orden) REFERENCES ordenes(ID_Ordenes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
