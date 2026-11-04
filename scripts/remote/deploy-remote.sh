#!/usr/bin/env bash
# Runs ON THE SERVER, piped in by scripts/deploy.sh once per target. Do not run locally.
#   KIND is theme or plugin; SLUG is the folder name under wp-content/themes or wp-content/plugins.
set -euo pipefail

ENVIRONMENT="$1" WP_ROOT="$HOME/$2" SITE_URL="$3" KIND="$4" SLUG="$5" SHA="$6" FORCE="$7"

BASE="$HOME/deploy/$ENVIRONMENT"
case "$KIND" in
	theme)  TARGET_DIR="$WP_ROOT/wp-content/themes/$SLUG";  MANIFEST="$BASE/manifest.md5" ;;
	plugin) TARGET_DIR="$WP_ROOT/wp-content/plugins/$SLUG"; MANIFEST="$BASE/manifest-$SLUG.md5" ;;
	*) echo "Unknown deploy kind '$KIND'"; exit 2 ;;
esac
RELEASE="$BASE/release-$SLUG"
# Which backup this run took ("none" on a first deploy), so a failed smoke test restores exactly that.
RUN_BACKUP="$BASE/run-backup-$SLUG"
KEEP_BACKUPS=15

manifest_of() { (cd "$1" && find . -type f -print0 | sort -z | xargs -0 md5sum); }

purge_cache() {
	# The plugin purges via an HTTP request to the site, which the host sometimes rate
	# limits. Fall back to emptying the server cache store (shared by prod and staging).
	if ! wp --path="$WP_ROOT" litespeed-purge all >/dev/null 2>&1; then
		if find "$HOME/lscache" -type f -delete 2>/dev/null; then
			echo "    (purged LiteSpeed cache store directly)"
		else
			echo "    (LiteSpeed purge failed; if pages look stale, purge from WP admin)"
		fi
	fi
}

# 1. Unpack and lint the release before touching the live files.
rm -rf "$RELEASE" && mkdir -p "$RELEASE" "$BASE/backups"
tar -xf "$BASE/incoming-$SLUG.tar" -C "$RELEASE"
rm -f "$BASE/incoming-$SLUG.tar"

echo "--> Linting PHP ($SLUG)"
lint_failed=0
while IFS= read -r -d '' f; do
	php -l "$f" >/dev/null 2>"$BASE/lint.err" || { echo "    FAIL ${f#$RELEASE/}"; cat "$BASE/lint.err"; lint_failed=1; }
done < <(find "$RELEASE" -name '*.php' -print0)
[ "$lint_failed" = 0 ] || { echo "PHP lint failed; nothing was deployed."; exit 1; }

# 2. Drift check: has anyone edited the live files since the last deploy?
if [ -d "$TARGET_DIR" ] && [ -f "$MANIFEST" ]; then
	if ! diff -q <(manifest_of "$TARGET_DIR") "$MANIFEST" >/dev/null; then
		echo "--> WARNING: the live $KIND $SLUG was changed outside of this pipeline since the last deploy:"
		{ diff <(manifest_of "$TARGET_DIR") "$MANIFEST" || true; } | grep '^[<>]' | awk '{print "    " $3}' | sort -u
		if [ "$FORCE" != 1 ]; then
			echo "Aborting so those edits are not lost. Pull them into git (scripts/pull-live-theme.sh $ENVIRONMENT)"
			echo "or re-run with FORCE=1 to overwrite them (a backup is still taken)."
			exit 3
		fi
		echo "    FORCE=1 set, overwriting (backup will be taken)."
	fi
fi

# 3. Back up the current files.
BACKUP=""
if [ -d "$TARGET_DIR" ]; then
	BACKUP="$BASE/backups/$SLUG-$(date +%Y%m%d-%H%M%S)-before-$SHA.tgz"
	tar -czf "$BACKUP" -C "$(dirname "$TARGET_DIR")" "$SLUG"
	echo "--> Backed up current $KIND to ${BACKUP#$HOME/}"
	ls -1t "$BASE/backups/$SLUG"-*.tgz | tail -n +$((KEEP_BACKUPS + 1)) | xargs -r rm -f
fi
echo "${BACKUP:-none}" > "$RUN_BACKUP"

restore() {
	if [ -n "$BACKUP" ]; then
		echo "--> Rolling back to ${BACKUP#$HOME/}"
		rm -rf "$TARGET_DIR" && tar -xzf "$BACKUP" -C "$(dirname "$TARGET_DIR")"
	else
		echo "--> Removing the first deploy of $SLUG"
		rm -rf "$TARGET_DIR"
	fi
	purge_cache
}

# 4. Sync the release in.
echo "--> Syncing files"
mkdir -p "$TARGET_DIR"
rsync -a --delete --checksum --itemize-changes "$RELEASE/" "$TARGET_DIR/" | sed 's/^/    /'
purge_cache

# 5. Boot check: WordPress must load with the new files. (Page checks run from the
#    machine that started the deploy; this server's own IP gets rate limited.)
#    This host's wp wrapper re-splits arguments on spaces, so keep wp args space-free.
echo "--> Checking WordPress boots"
if ! wp --path="$WP_ROOT" option get home 2>/dev/null | grep -q "^$SITE_URL"; then
	echo "    WP-CLI could not boot WordPress with the new $KIND"
	restore
	exit 1
fi

manifest_of "$TARGET_DIR" > "$MANIFEST"
echo "$SHA $(date -u +%FT%TZ) $KIND:$SLUG" >> "$BASE/history.log"
if [ "$KIND" = plugin ] && ! wp --path="$WP_ROOT" plugin is-active "$SLUG" 2>/dev/null; then
	echo "    Note: plugin $SLUG is installed but not active (activate it once: wp plugin activate $SLUG)"
fi
echo "==> Deployed $SLUG $SHA to $ENVIRONMENT (server side)"
