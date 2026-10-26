#!/usr/bin/env bash
# Runs ON THE SERVER, piped in by scripts/refresh-staging.sh. Do not run locally.
set -euo pipefail

PROD="$HOME/public_html"
STG="$HOME/staging.gengiestastybites.ca"
PROD_URL="https://gengiestastybites.ca"
STG_URL="https://staging.gengiestastybites.ca"
TMP="$HOME/deploy/staging/tmp"
WPS="wp --path=$STG --skip-plugins --skip-themes"

# Created once by hand: DB, DBU, PW for the staging database (chmod 600).
source "$HOME/.staging-db.env"

[ -d "$STG" ] && [ "$STG" != "$PROD" ] || { echo "Staging dir missing or same as production"; exit 1; }
[[ "$DB" == *_staging ]] || { echo "Refusing: staging DB name '$DB' does not end in _staging"; exit 1; }
mkdir -p "$TMP"

echo "--> Copying files from production"
rsync -a --delete \
	--exclude=/wp-config.php --exclude=/.htaccess --exclude=/.well-known/ --exclude=/cgi-bin/ \
	--exclude=error_log --exclude=/wp-content/debug.log \
	--exclude=/wp-content/litespeed/ --exclude=/wp-content/cache/ \
	--exclude='/wp-content/upgrade*/' \
	--exclude=/wp-content/mu-plugins/zz-staging.php \
	--exclude=/wp-content/plugins/tastybites-assistant/ \
	"$PROD/" "$STG/"

echo "--> Writing wp-config.php"
cp "$PROD/wp-config.php" "$STG/wp-config.php"
chmod 600 "$STG/wp-config.php"
wp config set DB_NAME "$DB" --path="$STG" --quiet
wp config set DB_USER "$DBU" --path="$STG" --quiet
wp config set DB_PASSWORD "$PW" --path="$STG" --quiet
wp config set WP_HOME "$STG_URL" --path="$STG" --quiet
wp config set WP_SITEURL "$STG_URL" --path="$STG" --quiet
wp config set WP_ENVIRONMENT_TYPE staging --path="$STG" --quiet
wp config set WP_DEBUG_DISPLAY false --raw --path="$STG" --quiet
wp config set WP_AUTO_UPDATE_CORE false --raw --path="$STG" --quiet
wp config shuffle-salts --path="$STG" --quiet
# Secrets (the AI key) are never copied: each environment loads its own file outside the web root,
# ~/.tastybites-secrets-<environment>.php, chosen by WP_ENVIRONMENT_TYPE (set to staging above). Drop
# anything secret that ever ends up in production's wp-config, and refuse to go on if a key slipped through.
sed -i -E '/ANTHROPIC|tastybites-secrets/d' "$STG/wp-config.php"
! grep -q 'sk-ant-' "$STG/wp-config.php" || { echo "An API key is still in the staging wp-config, aborting"; exit 1; }
[ "$(wp config get WP_ENVIRONMENT_TYPE --path="$STG")" = staging ] || { echo "Staging WP_ENVIRONMENT_TYPE not set, aborting"; exit 1; }

# Double-check the copied config really points at the staging DB before writing to it.
[ "$(wp config get DB_NAME --path="$STG")" = "$DB" ] || { echo "Staging wp-config DB_NAME mismatch, aborting"; exit 1; }
[ "$(wp config get DB_NAME --path="$PROD")" != "$DB" ] || { echo "Production uses the staging DB name?! aborting"; exit 1; }

echo "--> Cloning database"
wp --path="$PROD" --skip-plugins --skip-themes db export "$TMP/prod.sql" --single-transaction --quick --quiet
$WPS db reset --yes --quiet
$WPS db import "$TMP/prod.sql" --quiet
rm -f "$TMP/prod.sql"

echo "--> Rewriting URLs"
for pair in "$PROD_URL|$STG_URL" "http://gengiestastybites.ca|$STG_URL" "https://www.gengiestastybites.ca|$STG_URL" \
            "https:\\/\\/gengiestastybites.ca|https:\\/\\/staging.gengiestastybites.ca"; do
	$WPS search-replace "${pair%%|*}" "${pair##*|}" --all-tables-with-prefix --skip-columns=guid --precise --quiet
done
$WPS option update blog_public 0 --quiet

echo "--> Scrubbing production-only data"
# Production's queued Assistant jobs must not run (and spend) on staging, and no key ever lives in the DB.
PREFIX="$($WPS db prefix)"
TABLES=",$($WPS db tables --all-tables-with-prefix --format=csv),"
{
	echo "DELETE FROM ${PREFIX}options WHERE option_name LIKE '%anthropic%' OR option_name = 'tba_runner_lock';"
	if [[ "$TABLES" == *",${PREFIX}tba_jobs,"* ]]; then echo "TRUNCATE ${PREFIX}tba_jobs;"; fi
} > "$TMP/scrub.sql"
$WPS db query < "$TMP/scrub.sql"
rm -f "$TMP/scrub.sql"

echo "--> Ensuring staging admin login"
# Production only has WHC's SSO admin (no known password), so staging gets its own
# admin. The cloned DB never contains it, so (re)create it on every refresh.
if [ ! -f "$HOME/.staging-admin" ]; then
	( umask 077; echo "staging-admin:$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)" > "$HOME/.staging-admin" )
fi
ADMIN_USER="$(cut -d: -f1 "$HOME/.staging-admin")" ADMIN_PASS="$(cut -d: -f2- "$HOME/.staging-admin")"
if $WPS user get "$ADMIN_USER" --field=ID >/dev/null 2>&1; then
	$WPS user update "$ADMIN_USER" --user_pass="$ADMIN_PASS" --role=administrator --skip-email --quiet
else
	$WPS user create "$ADMIN_USER" staging-admin@gengiestastybites.ca --role=administrator --user_pass="$ADMIN_PASS" --quiet
fi

echo "--> Installing staging safeguards"
cat > "$STG/wp-content/mu-plugins/zz-staging.php" <<'PHP'
<?php
/**
 * Staging-only safeguards (installed by scripts/refresh-staging.sh, not part of the theme).
 * - Blocks all outgoing email so customers never receive mail from staging.
 * - Forces noindex and shows a STAGING badge.
 */
add_filter( 'pre_wp_mail', function ( $return, $atts ) {
	error_log( '[staging] email suppressed: ' . ( is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : $atts['to'] ) . ' / ' . $atts['subject'] );
	return true;
}, 1, 2 );
add_filter( 'wp_robots', 'wp_robots_no_robots' );
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->add_node( array( 'id' => 'staging-env', 'title' => '⚠ STAGING' ) );
}, 1 );
add_action( 'wp_footer', function () {
	echo '<div style="position:fixed;bottom:8px;left:8px;z-index:99999;background:#c00;color:#fff;font:bold 12px/1 sans-serif;padding:6px 10px;border-radius:4px;opacity:.85;pointer-events:none">STAGING</div>';
} );
PHP

# Password-protect the whole staging site. Credentials are created once and kept in ~/.staging-basicauth.
if [ ! -f "$HOME/.staging-basicauth" ]; then
	( umask 077; echo "staging:$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)" > "$HOME/.staging-basicauth" )
fi
SERVER_IP="$(dig +short gengiestastybites.ca | tail -1)"
mkdir -p "$HOME/.htpasswds"
BA_USER="$(cut -d: -f1 "$HOME/.staging-basicauth")" BA_PASS="$(cut -d: -f2- "$HOME/.staging-basicauth")"
echo "$BA_USER:$(openssl passwd -apr1 "$BA_PASS")" > "$HOME/.htpasswds/staging"
chmod 644 "$HOME/.htpasswds/staging"
{
	echo "# BEGIN staging basic auth (managed by scripts/refresh-staging.sh)"
	echo "AuthType Basic"
	echo "AuthName \"Tasty Bites staging\""
	echo "AuthUserFile $HOME/.htpasswds/staging"
	echo "<RequireAny>"
	# The server itself (LiteSpeed cache purge, WP cron, loopback requests) skips the password.
	echo "Require ip $SERVER_IP"
	echo "Require valid-user"
	echo "</RequireAny>"
	echo "# END staging basic auth"
	echo
	cat "$PROD/.htaccess"
} > "$STG/.htaccess"

# Staging keeps its own deployed copy of the Assistant plugin (excluded from the file copy above); make
# sure it's active and set up on the cloned DB.
if [ -d "$STG/wp-content/plugins/tastybites-assistant" ]; then
	echo "--> Re-activating the Assistant plugin"
	wp --path="$STG" plugin activate tastybites-assistant --quiet
	wp --path="$STG" tba setup
fi

wp --path="$STG" litespeed-purge all >/dev/null 2>&1 || true
wp --path="$STG" rewrite flush --quiet 2>/dev/null || true

[ -f "$HOME/deploy/staging/manifest.md5" ] && rm -f "$HOME/deploy/staging/manifest.md5"
echo "==> Staging refreshed: $STG_URL"
