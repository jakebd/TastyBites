#!/usr/bin/env bash
# Restore the theme from a backup taken by deploy.sh.
#
#   scripts/rollback.sh production          # list backups
#   scripts/rollback.sh production latest   # restore the most recent backup
#   scripts/rollback.sh production <file>   # restore a specific backup

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

ENVIRONMENT="${1:-}"
CHOICE="${2:-}"
[ -n "$ENVIRONMENT" ] || { echo "usage: $0 staging|production [latest|<backup file>]" >&2; exit 2; }
load_env_config "$ENVIRONMENT"

if [ -z "$CHOICE" ]; then
	echo "Backups for $ENVIRONMENT (newest first):"
	remote "ls -1t ~/deploy/$ENVIRONMENT/backups/ 2>/dev/null || echo '  (none)'"
	exit 0
fi

remote "bash -s" <<EOF
set -euo pipefail
BASE=~/deploy/$ENVIRONMENT
THEMES=~/$WP_ROOT/wp-content/themes
if [ "$CHOICE" = latest ]; then FILE=\$(ls -1t \$BASE/backups/*.tgz | head -1); else FILE=\$BASE/backups/$CHOICE; fi
[ -f "\$FILE" ] || { echo "Backup not found: \$FILE"; exit 1; }
rm -rf "\$THEMES/$THEME_SLUG" && tar -xzf "\$FILE" -C "\$THEMES"
(cd "\$THEMES/$THEME_SLUG" && find . -type f -print0 | sort -z | xargs -0 md5sum) > \$BASE/manifest.md5
wp --path=$WP_ROOT litespeed-purge all >/dev/null 2>&1 || find ~/lscache -type f -delete 2>/dev/null || true
echo "rollback \$(basename \$FILE) \$(date -u +%FT%TZ)" >> \$BASE/history.log
echo "Restored \$(basename "\$FILE") to $ENVIRONMENT"
EOF
