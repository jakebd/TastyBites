#!/usr/bin/env bash
# Rebuild staging as a fresh copy of production (files + database).
# Production is only read from. Everything on staging is overwritten,
# including any theme you deployed there, so redeploy afterwards if needed:
#
#   scripts/refresh-staging.sh && scripts/deploy.sh staging

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

if [ -z "${YES:-}" ]; then
	read -r -p "Overwrite staging with a copy of production? [y/N] " answer
	[ "$answer" = y ] || [ "$answer" = Y ] || exit 1
fi

remote "bash -s" < "$REPO_ROOT/scripts/remote/refresh-staging-remote.sh"
