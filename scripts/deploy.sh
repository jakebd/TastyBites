#!/usr/bin/env bash
# Deploy the committed theme and plugin (git HEAD) to an environment.
#
#   scripts/deploy.sh staging
#   scripts/deploy.sh production
#   scripts/deploy.sh staging plugin       # just one target: theme or plugin
#   FORCE=1 scripts/deploy.sh production   # deploy even if the live files were edited outside git
#
# What it does, per target (plugin first, then theme): packages HEAD with `git archive`,
# uploads it, lints every PHP file with the server's PHP, backs up the current files,
# syncs the new files in and purges LiteSpeed cache. Then it smoke-tests the site; if
# that fails, every target deployed in this run is restored automatically.

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

ENVIRONMENT="${1:-}"
WHAT="${2:-all}"
[ -n "$ENVIRONMENT" ] || { echo "usage: $0 staging|production [all|theme|plugin]" >&2; exit 2; }
case "$WHAT" in
	all)    TARGETS=(plugin theme) ;;
	theme)  TARGETS=(theme) ;;
	plugin) TARGETS=(plugin) ;;
	*) echo "Unknown target '$WHAT' (expected: all, theme or plugin)" >&2; exit 2 ;;
esac
load_env_config "$ENVIRONMENT"

cd "$REPO_ROOT"

if [ -z "${CI:-}" ]; then
	if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
		echo "Note: you have uncommitted changes. Only committed code (HEAD) is deployed." >&2
	fi
	if [ "$ENVIRONMENT" = "production" ] && [ "$(git rev-parse --abbrev-ref HEAD)" != "main" ]; then
		echo "Refusing to deploy to production from branch '$(git rev-parse --abbrev-ref HEAD)'. Check out main first." >&2
		exit 1
	fi
fi

SHA="$(git rev-parse --short HEAD)"
TARBALL="$(mktemp -t tastybites-XXXXXX).tar"
trap 'rm -f "$TARBALL"' EXIT

echo "==> Deploying ${TARGETS[*]} @ $SHA to $ENVIRONMENT ($SITE_URL)"
remote "mkdir -p ~/deploy/$ENVIRONMENT"
DEPLOYED=()
for target in "${TARGETS[@]}"; do
	if [ "$target" = plugin ]; then
		slug="$PLUGIN_SLUG"
		git archive --format=tar -o "$TARBALL" "HEAD:plugins/$PLUGIN_SLUG"
	else
		slug="$THEME_SLUG"
		git archive --format=tar -o "$TARBALL" HEAD
	fi
	upload "$TARBALL" "deploy/$ENVIRONMENT/incoming-$slug.tar"
	if ! remote "bash -s -- '$ENVIRONMENT' '$WP_ROOT' '$SITE_URL' '$target' '$slug' '$SHA' '${FORCE:-0}'" \
		<"$REPO_ROOT/scripts/remote/deploy-remote.sh"; then
		# This target restored itself; put back anything deployed earlier in this run.
		for done_target in "${DEPLOYED[@]}"; do TARGET="$done_target" bash "$REPO_ROOT/scripts/rollback.sh" "$ENVIRONMENT" run; done
		exit 1
	fi
	DEPLOYED+=("$target")
done

# Smoke test from here rather than from the server: the host rate limits requests
# the server makes to itself.
echo "--> Smoke testing $SITE_URL"
CURL_OPTS=(-sS -A "TastyBites-deploy-smoke/1.0")
if [ "$ENVIRONMENT" = staging ]; then
	CURL_OPTS+=(-u "$(remote 'cat ~/.staging-basicauth')")
fi
failed=0
for path in / /recipes/ /reviews/ /about/; do
	for attempt in 1 2 3; do
		body="$(curl "${CURL_OPTS[@]}" -w '\n%{http_code}' "$SITE_URL$path?nocache=$SHA" || true)"
		code="${body##*$'\n'}"
		[ "$code" = 429 ] || [ "$code" = 503 ] || break
		sleep $((attempt * 3))
	done
	if [ "$code" = 429 ]; then
		echo "    $path -> HTTP 429 (rate limited, not checked; verify in a browser)"
	elif [ "$code" != 200 ] || grep -qiE 'critical error|Fatal error|Parse error' <<<"$body"; then
		echo "    $path -> HTTP $code (FAILED)"
		failed=1
		break
	else
		echo "    $path -> HTTP $code"
	fi
done

if [ "$failed" = 1 ]; then
	echo "--> Smoke test failed, rolling back"
	for done_target in "${DEPLOYED[@]}"; do TARGET="$done_target" bash "$REPO_ROOT/scripts/rollback.sh" "$ENVIRONMENT" run; done
	exit 1
fi
echo "==> Deployed $SHA to $ENVIRONMENT"
