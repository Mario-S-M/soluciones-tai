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
    -- Formato oficial de 18 posiciones (fecha, sexo H/M, entidad, verificador).
    curp             VARCHAR(18)  NOT NULL UNIQUE,
    -- Acepta persona fisica (13) y persona moral (12).
    rfc              VARCHAR(13)  NOT NULL UNIQUE,
    sexo             VARCHAR(10)  NOT NULL CHECK (sexo IN ('M', 'F', 'Otro')),
    -- Hash bcrypt generado con password_hash() de PHP; nunca texto plano.
    contrasena_hash  VARCHAR(255),
    creado_en        TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Ata la secuencia a la columna: se borra junto con la tabla y pg_dump la
-- exporta asociada, con su setval correspondiente.
ALTER SEQUENCE usuarios_id_seq OWNED BY usuarios.id;

-- CURP, RFC y telefono son ficticios, no corresponden a personas reales.
INSERT INTO usuarios (nombre, apellidos, correo, telefono, curp, rfc, sexo) VALUES
    ('Mario Eduardo', 'Sanchez Mejia',  'mario@tai.local',   '5512345678', 'SAME850101HDFNRR08', 'SAME850101AB1', 'M'),
    ('Ana Lucia',     'Ramirez Torres', 'ana@tai.local',     '5587654321', 'RATA900215MDFMRN05', 'RATA900215XY2', 'F'),
    ('Soporte',       'Soluciones TAI', 'soporte@tai.local', '5500001111', 'SOTA000101HCMPRT02', 'SST000101QW4', 'Otro');
