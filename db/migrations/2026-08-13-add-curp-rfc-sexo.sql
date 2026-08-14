-- Anhade curp, rfc y sexo a usuarios en una base de datos YA inicializada.
-- Idempotente: se puede ejecutar mas de una vez sin fallar.
-- En una base NUEVA no hace falta: ya viene en db/init/01-init.sql.
--
-- Antes de ejecutar, respalda: ./db/backup.sh
--
-- Uso:
--   ./db/backup.sh
--   docker compose exec -T db psql -U tai -d soluciones_tai \
--       < db/migrations/2026-08-13-add-curp-rfc-sexo.sql

-- 1. Columnas nuevas, nullable de momento: no se puede meter NOT NULL de una
--    vez porque los usuarios de ejemplo ya existentes no tienen valor.
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'usuarios' AND column_name = 'curp'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN curp VARCHAR(18);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'usuarios' AND column_name = 'rfc'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN rfc VARCHAR(13);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'usuarios' AND column_name = 'sexo'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN sexo VARCHAR(10);
    END IF;
END $$;

-- 2. Rellena los usuarios de ejemplo que quedaron sin curp/rfc/sexo tras el
--    paso anterior, y normaliza sus telefonos a solo digitos (el formulario
--    ahora exige 10 digitos sin separadores). Datos ficticios, no corresponden
--    a personas reales.
UPDATE usuarios SET curp = 'SAME850101HDFNRR08', rfc = 'SAME850101AB1', sexo = 'M', telefono = '5512345678'
    WHERE correo = 'mario@tai.local' AND curp IS NULL;

UPDATE usuarios SET curp = 'RATA900215MDFMRN05', rfc = 'RATA900215XY2', sexo = 'F', telefono = '5587654321'
    WHERE correo = 'ana@tai.local' AND curp IS NULL;

UPDATE usuarios SET curp = 'SOTA000101HCMPRT02', rfc = 'SST000101QW4', sexo = 'Otro', telefono = '5500001111'
    WHERE correo = 'soporte@tai.local' AND curp IS NULL;

-- 3. Con todas las filas ya rellenas, se puede exigir NOT NULL. Repetible sin
--    error: SET NOT NULL sobre una columna que ya es NOT NULL no falla.
ALTER TABLE usuarios ALTER COLUMN curp SET NOT NULL;
ALTER TABLE usuarios ALTER COLUMN rfc  SET NOT NULL;
ALTER TABLE usuarios ALTER COLUMN sexo SET NOT NULL;

-- 4. Restricciones de unicidad y de valores permitidos, cada una guardada
--    contra su propio nombre para poder re-ejecutar el script.
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'usuarios_curp_key'
    ) THEN
        ALTER TABLE usuarios ADD CONSTRAINT usuarios_curp_key UNIQUE (curp);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'usuarios_rfc_key'
    ) THEN
        ALTER TABLE usuarios ADD CONSTRAINT usuarios_rfc_key UNIQUE (rfc);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'usuarios_sexo_check'
    ) THEN
        ALTER TABLE usuarios ADD CONSTRAINT usuarios_sexo_check CHECK (sexo IN ('M', 'F', 'Otro'));
    END IF;
END $$;
