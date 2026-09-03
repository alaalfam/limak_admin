#!/usr/bin/env bash
#
# Deploys limak_admin to the VPS: pulls the latest `main` on the server,
# rebuilds and restarts just the `wordpress` container via docker compose,
# then flushes rewrite rules (cheap and idempotent — needed so a newly
# added custom post type's permalinks work immediately, without a manual
# step). `db` and `phpmyadmin` are never touched.
#
# Requires: SSH key access to the VPS (deploy@185.10.75.150) already set
# up — same host as limak_website's deploy.sh, but this repo uses its own
# dedicated deploy key (see .github/workflows/deploy.yml), independent of
# the frontend's pipeline.

set -euo pipefail

VPS_USER_HOST="${VPS_USER_HOST:-deploy@185.10.75.150}"
REMOTE_PROJECT_DIR="/srv/limak/admin"
BRANCH="${BRANCH:-main}"

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519}"
SSH_KNOWN_HOSTS="${SSH_KNOWN_HOSTS:-$HOME/.ssh/known_hosts}"
SSH_OPTS="-o BatchMode=yes -o ConnectTimeout=15 -i $SSH_KEY -o UserKnownHostsFile=$SSH_KNOWN_HOSTS"

if [ ! -f "$SSH_KEY" ]; then
  echo "SSH key not found at $SSH_KEY (set SSH_KEY=... to override)" >&2
  exit 1
fi

echo "==> Deploying '$BRANCH' to $VPS_USER_HOST:$REMOTE_PROJECT_DIR ..."

# shellcheck disable=SC2087
ssh $SSH_OPTS "$VPS_USER_HOST" bash -s <<REMOTE
set -euo pipefail
cd '$REMOTE_PROJECT_DIR'

if [ -n "\$(git status --porcelain)" ]; then
  echo "==> Uncommitted changes found on the server — stashing before pull:" >&2
  git status --short
  git stash push -u -m "deploy.sh autostash \$(date -Iseconds)"
fi

echo "==> Fetching and checking out '$BRANCH'..."
git fetch origin '$BRANCH'
git checkout '$BRANCH'
git pull --ff-only origin '$BRANCH'

echo "==> Building wordpress image..."
docker compose -f docker-compose.prod.yml build wordpress

echo "==> Restarting wordpress container..."
docker compose -f docker-compose.prod.yml up -d --no-deps wordpress

echo "==> Flushing rewrite rules (throwaway wpcli container, db/wordpress untouched)..."
# --entrypoint wp overrides the wpcli service's own entrypoint (bootstrap.sh,
# used only for first-time install) so this runs the single wp command
# directly instead of the extra args being silently ignored by bootstrap.sh.
docker compose -f docker-compose.prod.yml run --rm --entrypoint wp wpcli rewrite flush --hard

echo "==> Pruning dangling images..."
docker image prune -f

echo "==> Deployed \$(git rev-parse --short HEAD) ($BRANCH)"

if [ -n "\$(git stash list)" ]; then
  echo "==> Note: stash(es) left on the server — review with 'git stash list' / 'git stash show -p':" >&2
  git stash list
fi
REMOTE

echo "==> Done."
