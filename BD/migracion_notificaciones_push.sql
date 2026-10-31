CREATE TABLE IF NOT EXISTS notificaciones_push (
    ID_Suscripcion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ID_Usuario INT NOT NULL,
    Endpoint TEXT NOT NULL,
    Endpoint_Hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    P256DH VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    Auth VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    Creada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Actualizada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Suscripcion),
    UNIQUE KEY notificaciones_push_endpoint (Endpoint_Hash),
    KEY notificaciones_push_usuario (ID_Usuario),
    CONSTRAINT notificaciones_push_usuario_fk
        FOREIGN KEY (ID_Usuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
