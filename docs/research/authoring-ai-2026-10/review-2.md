# Review 2 (organizer → research agent), 2026-10-25

Reviewed: `phase-2-deep-dives.md`.

## What's strong (keep)
- The memoir-product section is the most useful finding so far: keep raw material, a rewrite dial defaulting to
  light, no-login links, few/batched nudges, partial redo. These go straight into the design.
- The tiered after-publish recommendation (Tier 0 inline/no AI, Tier 1 background metadata, Tier 2 judgement via
  digest) resolves the Phase 1 tension and respects the decision log. Accepted as the working design.
- "Arrange, don't rewrite" (AI returns sentence IDs; PHP assembles) and the code-checked budgets (numbers, names,
  scores and swearing must trace to the transcript) are the right kind of guarantee. Accepted.
- Words-unchanged check design, quote-grounded review extraction, cPanel cron + WP-CLI. Accepted, pending staging tests.

## Claims to re-verify (Phase 3 must fix or source these)
1. **Pricing table.** Quote the Anthropic pricing page exactly for Opus 5.5, Sonnet 5.5 and Haiku 5.5 (input,
   output, batch, cache read). Some figures look unusual (e.g. Haiku at $0.10/$0.50); if the page says so, say it
   is verbatim. Remove anything sourced to "skill reference": that is not a citable source. Same for the tokenizer
   "1–1.35×" claim: primary source or drop it.
2. **Visual Revisions (7.0):** sourced to Gutenberg Times + a blog. Fine for now; mark it "verify on staging"
   (we run 7.1.3 there and can check Revisions ourselves).
3. **STT prices and terms:** still secondary. Use vendor pricing/terms pages or mark clearly as unverified.

## Things you missed that matter
1. **Our own Privacy page now promises comments are checked "on this website's own server; nothing is sent to an
   outside spam service".** AI comment triage (§2.7) would break that promise, and any AI processing of commenter
   data or of Shawn's voice recordings needs the privacy page updated first. Phase 3: list every AI feature that
   changes what the privacy page must say, with draft wording.
2. **Where the code lives.** The repo deploys only the theme (`git archive` → theme dir, with a drift check). Give
   best practice (functionality in a plugin / mu-plugin, not the theme) and what that implies for our deploy
   pipeline (a second deploy target, or an mu-plugin shipped from the repo). Keep it concrete.
3. **Secrets.** Connector keys in the DB are unencrypted; recommend the env-var / wp-config constant route and how
   it fits our setup (we never print `.env` values; production `wp-config.php` is server-only).
4. **Guardrails:** kill switch, per-day spend cap, logging of every AI call (model actually used, tokens, cost,
   outcome), `wp_ai_client_prevent_prompt` on staging vs production, and what happens when the API is down.
5. **AI Mode capture UI for an older user:** recording in the browser (MediaRecorder support incl. Safari), big
   pause/redo controls, re-saying one section, upload limits on shared hosting (`upload_max_filesize`, chunking),
   where it lives (simple admin screen vs editor panel), autosave of recordings over several days.

## Phase 3 brief: implementation guidance
Write `phase-3-implementation.md` for future coding agents. For each ROADMAP §2 item (2.1–2.12) and AI Mode (§4):
- **Decision:** build / reuse (which plugin or ability) / drop / defer, with one line of why (cite Phase 1–2).
- **Tier** (0/1/2 from Phase 2) and **trigger** (exact hooks, e.g. `transition_post_status`, `wp_after_insert_post`,
  scheduled single events), **data** (post meta keys, flags storage: option/CPT/custom table), **AI call shape**
  (inputs, JSON schema of output, model tier), **code-side checks** (what PHP verifies before applying),
  **acceptance tests** (incl. which staging posts to test on), **risks**.
- Shared infrastructure first: job runner (cPanel cron), AI wrapper (logging, cost cap, kill switch, model check),
  flags store, digest email + one-question pages (signed tokens), privacy-page changes.
- A **recommended build order** in small shippable steps, starting with no-AI wins (2.1 featured photo, 2.11
  deterministic structure rules with the words-unchanged check), then Tier 1 metadata, then the flags loop, then
  AI Mode experiments (STT bake-off with a real recording of Shawn first).
- A **"verify on staging first" checklist** (abilities from CLI, intl Normalizer, Visual Revisions, cron line,
  upload limits, EXIF survival in 7.1 uploads).
- Keep claims sourced; it's fine to reuse Phase 1–2 sources by ID. 4,000–7,000 words.

## Addendum: organizer's own checks on staging (sent to the agent mid-Phase 3)
- **Visual Revisions: not present.** On staging (WordPress 7.1.3), the block editor's Revisions link opens the
  classic `revision.php` screen: a raw code diff of block comments and HTML, with "Restore This Revision". No
  rendered before/after. Phase 2's claim (from Gutenberg Times) does not hold for this site; the structure fixer
  needs its own rendered preview for Jacob. Restore via revisions still works as undo.
- PHP `intl` is installed (Normalizer available for the words-unchanged check).
- CLI `upload_max_filesize` / `post_max_size`: 100M each (web limits still to verify).
