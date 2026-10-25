# Deploying Gengies Tasty Bites

This repo holds the **TastyBites theme** and our own **Tasty Bites Assistant plugin**
(`plugins/tastybites-assistant/`, see *Assistant plugin* below). WordPress core, other plugins,
uploads and the database live on the WHC (cPanel) server and are managed through WP admin.

| Environment | URL | Server path (from `~`) | Deployed from |
|---|---|---|---|
| Production | https://gengiestastybites.ca | `public_html` | `main` branch |
| Staging | https://staging.gengiestastybites.ca (password protected) | `staging.gengiestastybites.ca` | `staging` branch |

Theme path in both: `wp-content/themes/TastyBites`; plugin path: `wp-content/plugins/tastybites-assistant`.
Never edit either on the server
(WP theme editor, cPanel File Manager, SFTP). Change it here and deploy, otherwise the
next deploy will stop and ask what to do with your edit (see *Drift check* below).

## Day-to-day workflow

```powershell
git checkout main
git pull
git checkout -b my-change            # 1. branch off main
# ...edit, then:
git add <files>
git commit -m "Describe the change"
git push -u origin my-change
git push origin my-change:staging    # 2. deploy it to staging (add --force if staging has someone else's branch)
```

3. Check https://staging.gengiestastybites.ca in the browser.
4. Open a pull request `my-change` → `main` on GitHub and merge it.
5. GitHub Actions deploys `main` to production. If the `production` environment has you as a
   required reviewer, approve it under **Actions → the run → Review deployments**.
6. Back home: `git checkout main && git pull`.

The **Actions** tab also has a **Run workflow** button (Deploy theme) to deploy any branch to
staging, or `main` to production, without pushing. The *force* tick box does the same as `FORCE=1` below.

### Deploying by hand

Every script runs from **Git Bash** in the repo folder and uses your local deploy key, so it works
without GitHub:

```bash
bash scripts/deploy.sh staging               # deploys the committed HEAD (plugin, then theme)
bash scripts/deploy.sh staging plugin        # just one target: theme or plugin
bash scripts/deploy.sh production            # only allowed from main
FORCE=1 bash scripts/deploy.sh production    # overwrite live edits made outside git (backup still taken)
bash scripts/rollback.sh production          # list backups
bash scripts/rollback.sh production latest   # restore the most recent theme backup
TARGET=plugin bash scripts/rollback.sh production latest   # the same for the plugin
bash scripts/refresh-staging.sh              # re-clone prod -> staging (files + DB)
bash scripts/pull-live-theme.sh production   # copy the server's theme into your working tree
bash scripts/migrate.sh staging scripts/migrations/<file>.php   # run a content migration (DB backup first)
```

### Content migrations

Theme deploys only ship files. Changes to content (categories, menus, Customizer settings) are PHP
scripts in `scripts/migrations/`, run with WordPress's APIs via `scripts/migrate.sh`. That script backs up
the database to `~/deploy/<env>/db-backups/` first (last 10 kept) and asks before touching production.
Write migrations so they are safe to run twice. Run on staging first, then production.

## What a deploy does

On the server (`scripts/remote/deploy-remote.sh`), once per target: the plugin first, then the theme.
Each target has its own fingerprint (`manifest.md5` for the theme, `manifest-tastybites-assistant.md5`)
and its own backups.

1. Unpacks the **committed** code. Theme: `git archive HEAD`, minus the `export-ignore` paths in
   `.gitattributes` (docs, scripts, workflow, plugins). Plugin: `git archive HEAD:plugins/tastybites-assistant`.
   Uncommitted changes are never deployed.
2. Lints every PHP file with the server's PHP. Any syntax error aborts before anything changes.
3. **Drift check.** Compares the live theme with a fingerprint taken at the last deploy. If someone
   edited it outside this pipeline, it aborts and lists the files. Run
   `scripts/pull-live-theme.sh <env>`, commit what you want to keep, and redeploy, or use `FORCE=1`.
4. Backs up the current theme to `~/deploy/<env>/backups/` (last 15 kept).
5. `rsync --delete`s the new theme in and purges the LiteSpeed cache.
6. Checks WordPress still boots (WP-CLI). If not, restores the backup immediately.

Then from the machine running the deploy (your PC or the GitHub runner), `scripts/deploy.sh`:

7. Smoke tests `/`, `/recipes/`, `/reviews/` and `/about/` for HTTP 200 with no PHP errors.
   On failure it runs `rollback.sh <env> run` for each target, which restores the backup that deploy
   took (or removes the plugin if that was its first deploy).

Deploy history: `~/deploy/<env>/history.log` on the server. A deploy takes about 5–30 seconds.

## Testing at phone widths

Chrome won't shrink its viewport below about 660px, so test phones by loading staging pages into
360, 390 and 414px wide iframes on a same-origin staging page (e.g. `/robots.txt`), via the browser's
JavaScript console or Claude's browser tools. Check every frame's `scrollWidth` equals its width (no
sideways scroll). Also check a real phone before production.

## Staging

A full clone of production (files, plugins, uploads, DB) on the same hosting account, built and
refreshed by `scripts/refresh-staging.sh`. That script only reads from production. It:

- copies `public_html` → `~/staging.gengiestastybites.ca` and clones the DB into `<cpaneluser>_staging`
  (staging DB credentials: `~/.staging-db.env` on the server). It refuses to run if that DB name
  doesn't end in `_staging`;
- rewrites URLs to the staging domain and sets "discourage search engines";
- installs `wp-content/mu-plugins/zz-staging.php`, which **blocks all outgoing email**, adds
  `noindex`, and shows a red STAGING badge;
- puts the whole site behind a password (HTTP basic auth). The server's own IP is exempt so cache
  purges and WP cron work;
- (re)creates the `staging-admin` WordPress administrator.

**Refreshing overwrites everything on staging**, including whatever branch you'd deployed there, so
redeploy afterwards. Until the Assistant plugin is on production, a refresh also removes it from
staging: redeploy, then `wp plugin activate tastybites-assistant` and `wp tba setup` on staging. Do it whenever staging content has drifted far from production, or before
testing plugin/WordPress updates.

### Assistant plugin

Our own plugin for the AI features (ROADMAP §2 and §4; read `docs/research/authoring-ai-2026-10/REPORT.md`
first). It deploys with the theme but must be activated once per environment:
`wp --path=<root> plugin activate tastybites-assistant` then `wp --path=<root> tba setup` (creates its
tables and the "Assistant" Editor user that background jobs run as).

- **Background jobs** run from a server cron line every 5 minutes (`crontab -l` over SSH). Staging:
  `cd $HOME/staging.gengiestastybites.ca && wp cron event run --due-now --quiet >> $HOME/deploy/staging/cron.log 2>&1`.
  Production gets the same line (with `public_html`) when the plugin goes live there.
- **Status:** Tools → Assistant in WP admin, or `wp tba status` / `wp tba jobs` / `wp tba run`.
- **Off switches:** `define( 'TBA_DISABLED', true );` in `wp-config.php` stops everything; each helper
  can be turned off on Tools → Assistant; or deactivate the plugin.

### Logging in to staging

| What | Username | Password |
|---|---|---|
| Site password prompt | `staging` | `staging_basic_auth` in your `.env` (after the `:`) |
| WP admin (`/wp-admin`) | `staging-admin` | `staging_wp_admin` in your `.env` (after the `:`) |

WHC's "log in to wp-admin" button is single sign-on for production only. Production's only admin
is WHC's random `whcrand` account, which has no known password, so staging has its own admin.
Both passwords also live on the server in `~/.staging-basicauth` and `~/.staging-admin`, and stay
the same across refreshes.

### DNS and SSL

DNS for `gengiestastybites.ca` is at **GoDaddy** (not WHC). Staging needs an A record there:
`staging` → `198.100.159.124` (the WHC server). The certificate is Let's Encrypt, issued and
renewed automatically by cPanel AutoSSL. If the server's IP ever changes, update that record.

## Credentials and access

- **SSH:** port 27, key `~/.ssh/tastybites_deploy` (authorized in the server's `~/.ssh/authorized_keys`).
  To revoke it, remove the `tastybites-deploy` line from that file (or cPanel → SSH Access), and
  delete the `SSH_PRIVATE_KEY` GitHub secret.
- `.env` (gitignored, template in `.env.example`) holds host/port/user for the scripts and the
  staging logins. The SFTP password in it is not used by the scripts.
- **GitHub Actions** (repo **Settings → Secrets and variables → Actions → Secrets** tab, as
  **Repository secrets**):

  | Secret | Value | Copy it with (PowerShell) |
  |---|---|---|
  | `SSH_HOST` | `host` from `.env` | |
  | `SSH_PORT` | `27` | |
  | `SSH_USER` | `user` from `.env` | |
  | `SSH_PRIVATE_KEY` | the deploy private key, including the BEGIN/END lines | `Get-Content "$HOME\.ssh\tastybites_deploy" -Raw \| Set-Clipboard` |
  | `SSH_KNOWN_HOSTS` | the server's SSH host keys | `ssh-keyscan -p 27 <host> \| Set-Clipboard` |

  **Environments** `staging` and `production` exist under Settings → Environments. `production`
  requires your approval before each deploy.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Actions log: `SSH_HOST not set` | Secrets missing or in the wrong place. They must be **Repository secrets** on the **Secrets** tab (not Variables, not Codespaces/Dependabot). After fixing: **Re-run failed jobs**, no push needed. |
| `the live theme was changed outside of this pipeline` | Someone edited the theme on the server. `bash scripts/pull-live-theme.sh <env>`, review with `git diff`, commit, redeploy. Or redeploy with `FORCE=1` to discard the edit (it's in the backup). |
| Pushing to `staging` didn't start a deploy | GitHub skips pushes that change no files compared to what it already has, and changes that only touch `*.md`. Use **Run workflow** in the Actions tab instead. |
| `HTTP 429 (rate limited, not checked)` | The host's bot protection throttled the smoke test. The deploy still happened; check the page in a browser. |
| Pages look stale after deploy | Purge in WP admin → LiteSpeed Cache → Toolbox → Purge All. |
| `post-quantum key exchange` warning in logs | Harmless: WHC's SSH server is older than GitHub's SSH client. |
| `wp` commands on the server fail with `Too many positional arguments` | This host's `wp` wrapper splits arguments on spaces, even inside quotes. Avoid spaces in `wp` arguments (e.g. use `wp option get home` rather than `wp eval '...'`). |

## Database backups

`Prod DB Backups/` is gitignored. It contains user emails and password hashes, so never commit it.
Take a fresh one before risky changes (plugin or WordPress updates on production):

```bash
source scripts/lib.sh && remote 'wp --path=public_html --skip-plugins --skip-themes db export - --single-transaction --quiet' > "Prod DB Backups/$(date +%F).sql"
```
