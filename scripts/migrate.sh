#!/usr/bin/env bash
# Run a content migration (a PHP file using WordPress APIs) against an environment.
# Takes a database backup first.
#
#   bash scripts/migrate.sh staging scripts/migrations/road-journal-content.php

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

ENVIRONMENT="${1:-}"
MIGRATION="${2:-}"
[ -n "$ENVIRONMENT" ] && [ -f "$MIGRATION" ] || { echo "usage: $0 staging|production <migration.php>" >&2; exit 2; }
load_env_config "$ENVIRONMENT"

if [ "$ENVIRONMENT" = production ] && [ -z "${YES:-}" ]; then
	read -r -p "Run $(basename "$MIGRATION") against PRODUCTION? [y/N] " answer
	[ "$answer" = y ] || [ "$answer" = Y ] || exit 1
fi

NAME="$(basename "$MIGRATION")"
STAMP="$(date +%Y%m%d-%H%M%S)"
remote "mkdir -p ~/deploy/$ENVIRONMENT/migrations ~/deploy/$ENVIRONMENT/db-backups"
upload "$MIGRATION" "deploy/$ENVIRONMENT/migrations/$NAME"

echo "==> Backing up the $ENVIRONMENT database"
remote "wp --path=~/$WP_ROOT --skip-plugins --skip-themes db export ~/deploy/$ENVIRONMENT/db-backups/before-$NAME-$STAMP.sql --single-transaction --quiet && ls -1t ~/deploy/$ENVIRONMENT/db-backups/*.sql | tail -n +11 | xargs -r rm -f"
echo "    ~/deploy/$ENVIRONMENT/db-backups/before-$NAME-$STAMP.sql"

echo "==> Running $NAME on $ENVIRONMENT"
remote "wp --path=~/$WP_ROOT eval-file ~/deploy/$ENVIRONMENT/migrations/$NAME 2>&1 | grep -v _load_textdomain_just_in_time"
remote "wp --path=~/$WP_ROOT litespeed-purge all >/dev/null 2>&1 || find ~/lscache -type f -delete 2>/dev/null || true"
