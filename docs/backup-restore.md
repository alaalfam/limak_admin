# Database backup & restore

## Backups

`scripts/backup-db.sh` runs daily at 03:00 UTC (~06:30 Iran time) via the
`deploy` user's crontab on the VPS. It writes a compressed `mysqldump` to
`/srv/limak/backups/db/admin-db-<timestamp>.sql.gz` — a plain host
directory outside the `admin_db_data` Docker volume and outside this git
checkout, so it survives container recreation, `docker compose down -v`,
and `git pull` alike.

- Uses `--single-transaction --quick`: a consistent InnoDB snapshot with
  no table locks, so it never blocks normal reads/writes.
- Retention: files older than 14 days are deleted automatically at the
  end of every run.
- Credentials come from the `db` container's own environment (already
  set there by `docker-compose.prod.yml`) — nothing is read from `.env`
  on the host.

To run it manually (e.g. right before a risky change):

```
ssh deploy@185.10.75.150
cd /srv/limak/admin
./scripts/backup-db.sh
```

Check recent runs: `tail /srv/limak/backups/db/backup.log` (cron redirects
both stdout and stderr there — see the crontab entry itself for the exact
path).

## Restore

`scripts/restore-db.sh <path-to-backup.sql.gz>` restores a dump into the
**live** database, overwriting current data. It asks for a typed
confirmation (`restore`) before doing anything — never run it as part of
an automated/non-interactive process.

```
ssh deploy@185.10.75.150
cd /srv/limak/admin
ls /srv/limak/backups/db/                       # pick a dump
./scripts/restore-db.sh /srv/limak/backups/db/admin-db-20260903-030000.sql.gz
```

After a restore, sanity-check the site (`https://admin.limakstudio.ir/wp-admin/`,
`/wp-json/limak/v1/products`) before considering it done — a mismatched
timestamp/dump wouldn't necessarily error out, it would just quietly
restore the wrong point in time.

### When to use this

- Recovering from a bad migration, accidental deletion, or plugin
  misbehavior.
- **Not** a substitute for testing changes on a non-production copy first
  where that's practical — restoring is the last resort, not routine.
