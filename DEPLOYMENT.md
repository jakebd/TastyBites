# Deploying Gengies Tasty Bites

This repo is the **TastyBites theme only**. WordPress core, plugins, uploads and the
database live on the WHC (cPanel) server and are managed through WP admin.

| Environment | URL | Server path (from `~`) | Git branch |
|---|---|---|---|
| Production | https://gengiestastybites.ca | `public_html` | `main` |
| Staging | https://staging.gengiestastybites.ca (password protected) | `staging.gengiestastybites.ca` | `staging` |

Theme path in both: `wp-content/themes/TastyBites`.

## Day-to-day workflow

1. Branch off `main`, make changes, commit.
2. Merge/push to `staging` → GitHub Actions deploys to staging. Check it in the browser.
3. Merge to `main` → GitHub Actions deploys to production.

Deploys can also be run by hand from Git Bash (uses your local deploy key):

```bash
bash scripts/deploy.sh staging
bash scripts/deploy.sh production        # only from main
bash scripts/rollback.sh production      # list backups
bash scripts/rollback.sh production latest
bash scripts/refresh-staging.sh          # re-clone prod -> staging (files + DB)
bash scripts/pull-live-theme.sh production  # copy server theme into working tree
```

## What a deploy does

1. Packages the **committed** code (`git archive HEAD`, minus `export-ignore` files in `.gitattributes`).
2. Lints every PHP file with the server's PHP. Any syntax error aborts before anything changes.
3. **Drift check:** if the theme on the server was edited outside this pipeline (WP theme
   editor, File Manager, SFTP) since the last deploy, it aborts and lists the files. Run
   `scripts/pull-live-theme.sh <env>`, commit what you want to keep, and redeploy, or run with `FORCE=1`.
4. Backs up the current theme to `~/deploy/<env>/backups/` (last 15 kept).
5. `rsync --delete` the new theme in, purges LiteSpeed cache.
6. Smoke tests `/`, `/recipes/`, `/reviews/` and `/about/`. On failure it **automatically restores the backup**.

Deploy history: `~/deploy/<env>/history.log` on the server.

## Staging

A full clone of production (files, plugins, uploads, DB) on the same hosting account.
Built and refreshed by `scripts/refresh-staging.sh`, which:

- copies `public_html` → `~/staging.gengiestastybites.ca` and clones the DB into `<cpaneluser>_staging`
  (staging DB credentials: `~/.staging-db.env` on the server);
- rewrites URLs to the staging domain and sets "discourage search engines";
- installs `wp-content/mu-plugins/zz-staging.php`, which **blocks all outgoing email**, adds
  `noindex`, and shows a red STAGING badge;
- puts the whole site behind HTTP basic auth (username/password in `~/.staging-basicauth`
  on the server, also copied into your local `.env` as `staging_basic_auth`).

**WP admin on staging:** WHC's "log in to wp-admin" button is SSO for production only, so
staging has its own administrator, `staging-admin`. Its password lives in `~/.staging-admin` on the
server and in your local `.env` as `staging_wp_admin`. It is recreated on every refresh with the same password.

Refreshing overwrites everything on staging, so redeploy your branch afterwards.

## Credentials and access

- **SSH:** port 27, key `~/.ssh/tastybites_deploy` (authorized in the server's `~/.ssh/authorized_keys`).
  To revoke, remove the `tastybites-deploy` line from that file (or cPanel → SSH Access).
- `.env` (gitignored) holds host/port/user for the scripts. The password in it is not used by the scripts.
- **GitHub Actions secrets** (Settings → Secrets and variables → Actions):

  | Secret | Value |
  |---|---|
  | `SSH_HOST` | `host` from `.env` |
  | `SSH_PORT` | `27` |
  | `SSH_USER` | `user` from `.env` |
  | `SSH_PRIVATE_KEY` | contents of `~/.ssh/tastybites_deploy` (`cat ~/.ssh/tastybites_deploy \| clip`) |
  | `SSH_KNOWN_HOSTS` | output of `ssh-keyscan -p 27 <host> \| clip` |

  Create **Environments** named `staging` and `production` (Settings → Environments). Adding
  yourself as a required reviewer on `production` makes every prod deploy wait for a click.

## Database backups

`Prod DB Backups/` is gitignored. It contains user emails and password hashes, so never commit it.
Take a fresh one before risky changes:

```bash
source scripts/lib.sh && remote 'wp --path=public_html db export - --single-transaction --quiet' > "Prod DB Backups/$(date +%F).sql"
```
