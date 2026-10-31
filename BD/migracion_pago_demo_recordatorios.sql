-- Aplicar despues de BD/migracion_reservas_espacios.sql.
-- Estos pagos son solo registros de demostracion; no se procesan cargos reales.
CREATE TABLE IF NOT EXISTS pagos_demo_reserva (
    ID_Pago BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ID_Reserva INT NOT NULL,
    Referencia CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    Monto DECIMAL(8,2) NOT NULL,
    Moneda CHAR(3) CHARACTER SET ascii NOT NULL DEFAULT 'MXN',
    Resultado ENUM('aprobado') NOT NULL DEFAULT 'aprobado',
    Creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Pago),
    UNIQUE KEY pago_demo_reserva_unico (ID_Reserva),
    UNIQUE KEY pago_demo_referencia_unica (Referencia),
    CONSTRAINT pago_demo_reserva_fk
        FOREIGN KEY (ID_Reserva) REFERENCES reservas_espacios(ID_Reserva) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recordatorios_reserva (
    ID_Recordatorio BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ID_Reserva INT NOT NULL,
    Enviar_En DATETIME NOT NULL,
    Estado ENUM('pendiente', 'procesando', 'enviada', 'omitida', 'fallida')
        NOT NULL DEFAULT 'pendiente',
    Intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    Intentada DATETIME NULL,
    Enviada DATETIME NULL,
    Creada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Recordatorio),
    UNIQUE KEY recordatorio_reserva_unico (ID_Reserva),
    KEY recordatorio_pendiente (Estado, Enviar_En, Intentada),
    CONSTRAINT recordatorio_reserva_fk
        FOREIGN KEY (ID_Reserva) REFERENCES reservas_espacios(ID_Reserva) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
