#!/usr/bin/env bash
# Copy the theme currently on the server into your working tree, so edits made
# outside git (WP admin theme editor, cPanel File Manager, SFTP) can be reviewed
# with `git diff` and committed.
#
#   scripts/pull-live-theme.sh production

source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

ENVIRONMENT="${1:-production}"
load_env_config "$ENVIRONMENT"

cd "$REPO_ROOT"
remote "tar -cf - -C ~/$WP_ROOT/wp-content/themes/$THEME_SLUG ." | tar -xf - -C "$REPO_ROOT"
echo "Pulled live $ENVIRONMENT theme into the working tree. Review with: git status && git diff"
