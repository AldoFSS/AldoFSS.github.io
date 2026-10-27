-- Aplicar una vez en la base cafeteria para puntos verdes, QR y cupones.
CREATE TABLE IF NOT EXISTS eco_qr_tokens (
    ID_QR BIGINT NOT NULL AUTO_INCREMENT,
    ID_Usuario INT NOT NULL,
    Huella CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    Expira DATETIME NOT NULL,
    Usado DATETIME NULL,
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_QR),
    UNIQUE KEY eco_qr_huella_unica (Huella),
    KEY eco_qr_usuario_activo (ID_Usuario, Usado, Expira),
    CONSTRAINT eco_qr_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eco_eventos (
    ID_Evento BIGINT NOT NULL AUTO_INCREMENT,
    ID_Usuario INT NOT NULL,
    Huella_QR CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    CO2_g INT NOT NULL DEFAULT 80,
    Residuos_g INT NOT NULL DEFAULT 15,
    Agua_ml INT NOT NULL DEFAULT 250,
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Evento),
    UNIQUE KEY eco_evento_qr_unico (Huella_QR),
    KEY eco_evento_usuario_fecha (ID_Usuario, Creado),
    KEY eco_evento_fecha (Creado),
    CONSTRAINT eco_evento_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eco_cupones (
    ID_Cupon BIGINT NOT NULL AUTO_INCREMENT,
    ID_Usuario INT NOT NULL,
    Codigo CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Canjeado DATETIME NULL,
    PRIMARY KEY (ID_Cupon),
    UNIQUE KEY eco_cupon_codigo_unico (Codigo),
    KEY eco_cupon_usuario_estado (ID_Usuario, Canjeado),
    CONSTRAINT eco_cupon_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
