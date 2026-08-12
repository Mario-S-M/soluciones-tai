-- Anhade contrasena_hash a usuarios en una base de datos YA inicializada.
-- Idempotente: se puede ejecutar mas de una vez sin fallar.
-- En una base NUEVA no hace falta: ya viene en db/init/01-init.sql.
--
-- Antes de ejecutar, respalda: ./db/backup.sh
--
-- Uso:
--   ./db/backup.sh
--   docker compose exec -T db psql -U tai -d soluciones_tai \
--       < db/migrations/2026-08-11-add-contrasena-hash.sql

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'usuarios' AND column_name = 'contrasena_hash'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN contrasena_hash VARCHAR(255);
    END IF;
END $$;
