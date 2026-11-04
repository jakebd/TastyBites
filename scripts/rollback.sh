#!/usr/bin/env bash
# Restore the theme (or plugin) from a backup taken by deploy.sh.
#
#   scripts/rollback.sh production          # list backups
#   scripts/rollback.sh production latest   # restore the most recent backup
#   scripts/rollback.sh production <file>   # restore a specific backup
#   TARGET=plugin scripts/rollback.sh production latest   # the plugin instead of the theme
#
# `run` (used by deploy.sh) restores the backup taken by the last deploy run, or removes the
# files if that run was the first deploy.

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

ENVIRONMENT="${1:-}"
CHOICE="${2:-}"
[ -n "$ENVIRONMENT" ] || { echo "usage: [TARGET=theme|plugin] $0 staging|production [latest|run|<backup file>]" >&2; exit 2; }
load_env_config "$ENVIRONMENT"

case "${TARGET:-theme}" in
	theme)  SLUG="$THEME_SLUG";  PARENT="themes";  MANIFEST="manifest.md5" ;;
	plugin) SLUG="$PLUGIN_SLUG"; PARENT="plugins"; MANIFEST="manifest-$PLUGIN_SLUG.md5" ;;
	*) echo "Unknown TARGET '$TARGET' (expected: theme or plugin)" >&2; exit 2 ;;
esac

if [ -z "$CHOICE" ]; then
	echo "$SLUG backups for $ENVIRONMENT (newest first):"
	remote "ls -1t ~/deploy/$ENVIRONMENT/backups/ 2>/dev/null | grep '^$SLUG-' || echo '  (none)'"
	exit 0
fi

remote "bash -s" <<EOF
set -euo pipefail
BASE=~/deploy/$ENVIRONMENT
DIR=~/$WP_ROOT/wp-content/$PARENT
case "$CHOICE" in
	latest) FILE=\$(ls -1t \$BASE/backups/$SLUG-*.tgz | head -1) ;;
	run)    FILE=\$(cat \$BASE/run-backup-$SLUG) ;;
	*)      FILE=\$BASE/backups/$CHOICE ;;
esac
if [ "\$FILE" = none ]; then
	rm -rf "\$DIR/$SLUG" "\$BASE/$MANIFEST"
	echo "Removed $SLUG from $ENVIRONMENT (it had not been deployed before)"
else
	[ -f "\$FILE" ] || { echo "Backup not found: \$FILE"; exit 1; }
	rm -rf "\$DIR/$SLUG" && tar -xzf "\$FILE" -C "\$DIR"
	(cd "\$DIR/$SLUG" && find . -type f -print0 | sort -z | xargs -0 md5sum) > \$BASE/$MANIFEST
	echo "Restored \$(basename "\$FILE") to $ENVIRONMENT"
fi
wp --path=$WP_ROOT litespeed-purge all >/dev/null 2>&1 || find ~/lscache -type f -delete 2>/dev/null || true
echo "rollback $SLUG \$(basename \$FILE) \$(date -u +%FT%TZ)" >> \$BASE/history.log
EOF
