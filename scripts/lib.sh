#!/usr/bin/env bash
# Shared helpers for the deploy scripts. Sourced, not executed.
#
# Connection settings come from environment variables (CI) or fall back to the
# host/port/user keys in the repo's .env file (local). The password in .env is
# never used: all connections authenticate with the deploy SSH key.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_SLUG="TastyBites"

# Read only the keys we need from .env (avoids sourcing the password line).
if [ -f "$REPO_ROOT/.env" ]; then
	while IFS='=' read -r key value; do
		value="${value%$'\r'}"
		case "$key" in
			host) : "${SSH_HOST:=$value}" ;;
			port) : "${SSH_PORT:=$value}" ;;
			user) : "${SSH_USER:=$value}" ;;
		esac
	done < "$REPO_ROOT/.env"
fi

: "${SSH_HOST:?SSH_HOST not set (or host= missing from .env)}"
: "${SSH_PORT:=22}"
: "${SSH_USER:?SSH_USER not set (or user= missing from .env)}"
: "${SSH_KEY:=$HOME/.ssh/tastybites_deploy}"

SSH_COMMON=(-o BatchMode=yes -o IdentitiesOnly=yes -o ConnectTimeout=20 -i "$SSH_KEY")

remote() {
	ssh "${SSH_COMMON[@]}" -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "$@"
}

upload() {
	scp -q "${SSH_COMMON[@]}" -P "$SSH_PORT" "$1" "$SSH_USER@$SSH_HOST:$2"
}

# Sets WP_ROOT (relative to the remote home dir) and SITE_URL for an environment.
load_env_config() {
	case "$1" in
		production)
			WP_ROOT="public_html"
			SITE_URL="https://gengiestastybites.ca"
			;;
		staging)
			WP_ROOT="staging.gengiestastybites.ca"
			SITE_URL="https://staging.gengiestastybites.ca"
			;;
		*)
			echo "Unknown environment '$1' (expected: staging or production)" >&2
			exit 2
			;;
	esac
}
