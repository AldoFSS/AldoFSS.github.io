-- Aplicar una vez, después de respaldar la base actual. No importar cafeteria.sql.
ALTER TABLE usuarios MODIFY COLUMN contraseña_usuario VARCHAR(255) NOT NULL;
CREATE TABLE IF NOT EXISTS solicitudes_compra (
    ID_Usuario INT NOT NULL,
    clave VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    huella CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    respuesta LONGTEXT NULL,
    creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Usuario, clave),
    CONSTRAINT solicitudes_compra_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
