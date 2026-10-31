-- Migración heredada de caché de traducciones con IA; ya no se usa.
-- Para las traducciones fijas actuales, aplicar migracion_menu_traducciones_estaticas.sql.
CREATE TABLE IF NOT EXISTS menu_traducciones_cache (
    idioma CHAR(2) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    huella CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    respuesta LONGTEXT NOT NULL,
    actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idioma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
