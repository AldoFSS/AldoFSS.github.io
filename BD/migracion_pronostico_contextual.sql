-- Aplicar despues de migracion_inventario_predictivo.sql en la base cafeteria.
CREATE TABLE IF NOT EXISTS inventario_pronostico_config (
    ID_Config TINYINT UNSIGNED NOT NULL,
    Latitud DECIMAL(9,6) NOT NULL,
    Longitud DECIMAL(9,6) NOT NULL,
    Umbral_Bebida_Fria DECIMAL(5,2) NOT NULL DEFAULT 25.00,
    Ajuste_Bebida_Fria DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    Umbral_Bebida_Caliente DECIMAL(5,2) NOT NULL DEFAULT 18.00,
    Ajuste_Bebida_Caliente DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    Actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Config)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO inventario_pronostico_config
    (ID_Config, Latitud, Longitud, Umbral_Bebida_Fria, Ajuste_Bebida_Fria,
     Umbral_Bebida_Caliente, Ajuste_Bebida_Caliente)
VALUES (1, 20.080550, -98.368310, 25.00, 10.00, 18.00, 10.00);

CREATE TABLE IF NOT EXISTS inventario_pronostico_clima (
    ID_Config TINYINT UNSIGNED NOT NULL,
    Latitud DECIMAL(9,6) NOT NULL,
    Longitud DECIMAL(9,6) NOT NULL,
    Fecha_Clima DATE NULL,
    Temperatura_Maxima DECIMAL(5,2) NULL,
    Probabilidad_Precipitacion TINYINT UNSIGNED NULL,
    Precipitacion_MM DECIMAL(7,2) NULL,
    Codigo_Clima SMALLINT NULL,
    Actualizado DATETIME NULL,
    Intento_Actualizacion DATETIME NULL,
    Ultimo_Error VARCHAR(255) NULL,
    PRIMARY KEY (ID_Config),
    CONSTRAINT inventario_clima_config FOREIGN KEY (ID_Config)
        REFERENCES inventario_pronostico_config(ID_Config) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventario_sensibilidad_clima (
    ID_Producto INT NOT NULL,
    Sensibilidad ENUM('neutra', 'bebida_fria', 'bebida_caliente') NOT NULL DEFAULT 'neutra',
    Actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Producto),
    CONSTRAINT inventario_sensibilidad_producto FOREIGN KEY (ID_Producto)
        REFERENCES menuproductos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO inventario_sensibilidad_clima (ID_Producto, Sensibilidad)
SELECT p.id, IF(c.Categoria = 'Bebidas', 'bebida_fria', 'neutra')
FROM menuproductos p
LEFT JOIN menucategoria c ON c.ID_Cat = p.id_categoria;

CREATE TABLE IF NOT EXISTS inventario_eventos_locales (
    ID_Evento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(120) NOT NULL,
    Descripcion VARCHAR(255) NOT NULL DEFAULT '',
    Inicio DATETIME NOT NULL,
    Fin DATETIME NOT NULL,
    Latitud DECIMAL(9,6) NOT NULL,
    Longitud DECIMAL(9,6) NOT NULL,
    Impacto_Porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Evento),
    KEY inventario_eventos_fecha (Activo, Inicio, Fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
