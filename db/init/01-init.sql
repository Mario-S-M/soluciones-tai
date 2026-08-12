-- Esquema inicial de la aplicacion.
-- Se ejecuta una unica vez, cuando el volumen pgdata se inicializa vacio.
-- Para reejecutarlo: docker compose down -v && docker compose up -d

-- La secuencia se declara aparte en lugar de usar SERIAL: asi queda como un
-- objeto propio del esquema, visible con \ds y manejable de forma explicita.
CREATE SEQUENCE usuarios_id_seq;

CREATE TABLE usuarios (
    id               INTEGER      PRIMARY KEY DEFAULT nextval('usuarios_id_seq'),
    nombre           VARCHAR(100) NOT NULL,
    apellidos        VARCHAR(150) NOT NULL,
    correo           VARCHAR(150) NOT NULL UNIQUE,
    telefono         VARCHAR(20),
    -- Hash bcrypt generado con password_hash() de PHP; nunca texto plano.
    contrasena_hash  VARCHAR(255),
    creado_en        TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Ata la secuencia a la columna: se borra junto con la tabla y pg_dump la
-- exporta asociada, con su setval correspondiente.
ALTER SEQUENCE usuarios_id_seq OWNED BY usuarios.id;

INSERT INTO usuarios (nombre, apellidos, correo, telefono) VALUES
    ('Mario Eduardo', 'Sanchez Mejia',  'mario@tai.local',   '55 1234 5678'),
    ('Ana Lucia',     'Ramirez Torres', 'ana@tai.local',     '55 8765 4321'),
    ('Soporte',       'Soluciones TAI', 'soporte@tai.local', '55 0000 1111');
