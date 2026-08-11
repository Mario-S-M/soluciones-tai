#!/usr/bin/env bash
#
# Exporta la base de datos completa a db/backup/soluciones_tai_<fecha>.sql
#
#   ./db/backup.sh
#
# El dump es en texto plano (formato SQL), asi que se restaura con psql y se
# puede revisar con cualquier editor. Incluye la secuencia usuarios_id_seq y su
# setval, de modo que tras restaurar los ids siguen donde estaban.

set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

# Credenciales del .env, con los mismos valores por defecto que el compose.
POSTGRES_USER="${POSTGRES_USER:-tai}"
POSTGRES_DB="${POSTGRES_DB:-soluciones_tai}"
if [ -f .env ]; then
	POSTGRES_USER="$(grep -E '^POSTGRES_USER=' .env | cut -d= -f2-)"
	POSTGRES_DB="$(grep -E '^POSTGRES_DB=' .env | cut -d= -f2-)"
fi

DESTINO="db/backup/${POSTGRES_DB}_$(date +%Y%m%d_%H%M%S).sql"
mkdir -p db/backup

echo "Exportando ${POSTGRES_DB} -> ${DESTINO}"
docker compose exec -T db pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" > "$DESTINO"

echo "Listo: $(wc -l < "$DESTINO") lineas, $(du -h "$DESTINO" | cut -f1)"
echo "$DESTINO"
