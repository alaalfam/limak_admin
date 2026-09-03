#!/usr/bin/env bash
#
# Restores a backup produced by backup-db.sh. See docs/backup-restore.md
# for the full procedure and when to use this.
#
# Usage: scripts/restore-db.sh /srv/limak/backups/db/admin-db-20260903-030000.sql.gz

set -euo pipefail

PROJECT_DIR="/srv/limak/admin"
DUMP_FILE="${1:?Usage: restore-db.sh <path-to-backup.sql.gz>}"

if [ ! -f "$DUMP_FILE" ]; then
  echo "restore-db.sh: $DUMP_FILE not found" >&2
  exit 1
fi

cd "$PROJECT_DIR"

echo "==> Restoring $DUMP_FILE into the live database. This overwrites current data."
read -r -p "Type 'restore' to continue: " CONFIRM
if [ "$CONFIRM" != "restore" ]; then
  echo "Aborted."
  exit 1
fi

gunzip -c "$DUMP_FILE" | docker compose -f docker-compose.prod.yml exec -T db \
  sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'

echo "==> Restore complete."
