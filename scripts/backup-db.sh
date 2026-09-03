#!/usr/bin/env bash
#
# Dumps the production database to a compressed, timestamped file outside
# both the db_data Docker volume and this git checkout, so it survives
# container recreation, `docker compose down -v`, and `git pull` alike.
# Run as a cron job (see deploy/deploy.sh's sibling — this one isn't part
# of the deploy flow, it runs independently on a schedule).
#
# --single-transaction + --quick: a consistent InnoDB snapshot with no
# table locks, so this never blocks or interferes with normal reads/writes.
# Credentials come from the *db* container's own environment (already set
# there by docker-compose.prod.yml) — nothing is parsed from .env on the
# host, avoiding the word-splitting issue an ad-hoc `source .env` hit
# during the post-deployment audit (WP_SITE_TITLE contains a space).

set -euo pipefail

PROJECT_DIR="/srv/limak/admin"
BACKUP_DIR="/srv/limak/backups/db"
RETENTION_DAYS=14
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="$BACKUP_DIR/admin-db-$TIMESTAMP.sql.gz"

mkdir -p "$BACKUP_DIR"

cd "$PROJECT_DIR"

docker compose -f docker-compose.prod.yml exec -T db \
  sh -c 'exec mysqldump --single-transaction --quick -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$OUT_FILE"

if [ ! -s "$OUT_FILE" ]; then
  echo "backup-db.sh: $OUT_FILE is empty, dump likely failed" >&2
  rm -f "$OUT_FILE"
  exit 1
fi

echo "backup-db.sh: wrote $OUT_FILE ($(du -h "$OUT_FILE" | cut -f1))"

find "$BACKUP_DIR" -name 'admin-db-*.sql.gz' -mtime "+$RETENTION_DAYS" -print -delete
