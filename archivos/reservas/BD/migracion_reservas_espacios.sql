-- Aplicar una vez en la base cafeteria para habilitar las solicitudes de coworking.
-- Los espacios de ejemplo y sus atributos deben validarse contra el plano real del local.
CREATE TABLE IF NOT EXISTS espacios_reserva (
    ID_Espacio INT NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(80) NOT NULL,
    Silla_Ruedas TINYINT(1) NOT NULL DEFAULT 0,
    Zurdo TINYINT(1) NOT NULL DEFAULT 0,
    Conexiones VARCHAR(80) NOT NULL DEFAULT 'Contacto eléctrico',
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (ID_Espacio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO espacios_reserva (ID_Espacio, Nombre, Silla_Ruedas, Zurdo, Conexiones)
VALUES
    (1, 'Ventana 1', 1, 1, 'Contacto AC y USB-C'),
    (2, 'Ventana 2', 0, 1, 'Contacto AC'),
    (3, 'Centro 1', 0, 0, 'Contacto AC'),
    (4, 'Centro 2', 1, 0, 'Contacto AC y USB-C'),
    (5, 'Pared 1', 0, 1, 'Contacto AC'),
    (6, 'Pared 2', 0, 0, 'Contacto AC')
ON DUPLICATE KEY UPDATE Nombre=VALUES(Nombre);

CREATE TABLE IF NOT EXISTS reservas_espacios (
    ID_Reserva INT NOT NULL AUTO_INCREMENT,
    ID_Usuario INT NOT NULL,
    ID_Espacio INT NOT NULL,
    Paquete VARCHAR(30) NOT NULL,
    Inicio DATETIME NOT NULL,
    Fin DATETIME NOT NULL,
    Precio DECIMAL(8,2) NOT NULL,
    Requisito ENUM('silla_ruedas', 'zurdo') NULL,
    Estado ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    Creada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Reserva),
    KEY reserva_espacio_horario (ID_Espacio, Estado, Inicio, Fin),
    KEY reserva_usuario (ID_Usuario, Inicio),
    CONSTRAINT reservas_espacios_usuario FOREIGN KEY (ID_Usuario) REFERENCES usuarios(id),
    CONSTRAINT reservas_espacios_espacio FOREIGN KEY (ID_Espacio) REFERENCES espacios_reserva(ID_Espacio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
