#!/usr/bin/env bash
#
# Restaura un dump generado por db/backup.sh
#
#   ./db/restore.sh db/backup/soluciones_tai_20260810_193000.sql
#
# Recrea la base de datos desde cero antes de cargar el dump: primero corta las
# conexiones abiertas (pgAdmin y el contenedor web mantienen sesiones y
# PostgreSQL no deja borrar una base con clientes conectados), luego DROP +
# CREATE y finalmente carga el archivo.

set -euo pipefail

if [ $# -ne 1 ]; then
	echo "Uso: $0 <archivo.sql>" >&2
	exit 1
fi

DUMP="$1"
if [ ! -f "$DUMP" ]; then
	echo "No existe el archivo: $DUMP" >&2
	exit 1
fi

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

POSTGRES_USER="${POSTGRES_USER:-tai}"
POSTGRES_DB="${POSTGRES_DB:-soluciones_tai}"
if [ -f .env ]; then
	POSTGRES_USER="$(grep -E '^POSTGRES_USER=' .env | cut -d= -f2-)"
	POSTGRES_DB="$(grep -E '^POSTGRES_DB=' .env | cut -d= -f2-)"
fi

echo "Cerrando conexiones a ${POSTGRES_DB}"
docker compose exec -T db psql -U "$POSTGRES_USER" -d postgres -c \
	"SELECT pg_terminate_backend(pid) FROM pg_stat_activity
	 WHERE datname = '${POSTGRES_DB}' AND pid <> pg_backend_pid();" > /dev/null

echo "Borrando y recreando ${POSTGRES_DB}"
docker compose exec -T db psql -U "$POSTGRES_USER" -d postgres \
	-c "DROP DATABASE IF EXISTS ${POSTGRES_DB};" \
	-c "CREATE DATABASE ${POSTGRES_DB} OWNER ${POSTGRES_USER};"

echo "Cargando ${DUMP}"
docker compose exec -T db psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -q < "$DUMP"

echo "Restauracion terminada"
docker compose exec -T db psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" \
	-c "SELECT count(*) AS usuarios FROM usuarios;"
