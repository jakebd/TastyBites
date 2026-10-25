# Review 3 (organizer), 2026-10-25

Reviewed: `phase-3-implementation.md`. This is the final review; corrections below are folded into `REPORT.md`
rather than sent back for another round (they are small and verified directly by the organizer).

## Accepted
- Plugin in the same repo (`plugins/tastybites-assistant/`) with a second deploy target; theme stays presentation-only.
- Secrets in a file outside the web root, loaded as constants; separate staging key; staging AI off unless a flag is set.
- Guardrails (three-level kill switch, daily cap, per-call log with the model actually used, `prevent_prompt` gate).
- Shared infrastructure first; tiers; per-item specs with JSON schemas and PHP checks; deferring 2.7, 2.8 and 2.12.
- Extractive excerpts (his own sentences), quote-checked review extraction, deterministic structure rules in
  propose mode, our own side-by-side preview (Visual Revisions correction handled well).
- Build order and the verify-on-staging checklist.
- Pricing now quoted verbatim from Anthropic's page; "skill reference" claims replaced with primary sources.

## Corrections (verified by the organizer against the repo and servers)
1. **Pattern detection is wrong as written.** §3.11 says "never unwrap blocks inside `tastybites/*` pattern
   output". Inserted (unsynced) patterns leave no `tastybites/*` marker in `post_content`. Detect them instead by:
   `core/group` blocks whose `metadata.name` is Ingredients, Instructions, Review or Final thoughts (the names the
   patterns set, already used by `tb_collect_group_texts()` in `inc/seo.php`), and any `mfb/meta-field-block`.
   Exclude any subtree containing those.
2. **Staging inherits production's key: confirmed, not "verify".** `scripts/remote/refresh-staging-remote.sh`
   copies production's `wp-config.php` to staging (then changes `DB_NAME`). If production's `wp-config.php`
   requires `~/.tastybites-secrets.php`, staging would load the production key. Fix in the refresh script: rewrite
   that include to `~/.tastybites-secrets-staging.php` (and refuse to finish if the production path remains),
   alongside the existing DB_NAME safety checks.
3. **Shawn's role is Editor** (checked on production). He cannot deactivate plugins. Editors can moderate comments,
   so WordPress also sends him the held-comment email (the branded one explains the buttons).
4. **Recipe "servings" field:** no such field exists today (only `recipe_time`). Treat "missing servings" as a
   content flag only if a servings field is added; don't invent the key.

## Not done (out of scope for desk research; on the checklist)
- Anything requiring a live AI key, cron changes or plugin installs. These belong to build step 1 on staging.
