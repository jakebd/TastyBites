# Phase 3: implementation guide

Research agent, 2026-10-25, responding to `review-2.md` and the organizer's staging findings. Written for future
coding agents. All web sources accessed 2026-10-25. Conventions as before: **Fact** (cited), **Inference** (mine),
**Verify** (must be checked on staging before relying on it). Phase 1 sources are `[Sn]`, Phase 2 `[Pn]`, new ones
`[Qn]`. Repo facts come from reading `functions.php`, `inc/`, `scripts/`, `DEPLOYMENT.md` (read only). I saw no
prompt-injection attempts.

---

## 0. Corrections to Phase 2

1. **Visual Revisions: don't rely on it.**
   - Phase 2 said core 7.0 shows a rendered before/after with Restore, based on Gutenberg Times and blogs [P20]. Further secondary sources say the same [Q1].
   - The organizer checked staging (WordPress 7.1.3): **the editor's Revisions link opens the classic `revision.php` screen**. That screen shows a raw HTML/block-comment diff with "Restore This Revision".
   - So either the feature didn't ship as described, or it isn't on by default, or it doesn't apply to this classic-theme setup.
   - **Do not rely on it.** §3.11 designs our own rendered preview. Revisions + Restore still work as the undo.
2. **Pricing.** Re-verified verbatim from Anthropic's pricing page [P30]. The unusual Haiku 5.5 figures are what the page says.
   - Standard table rows (base input / 5-min cache write / 1-h cache write / cache hit / output):
     - "Claude Opus 5.5 | $4 / MTok | $5 / MTok | $8 / MTok | $0.20 / MTok | $20 / MTok"
     - "Claude Sonnet 5.5 | $2 / MTok | $2.50 / MTok | $4 / MTok | $0.10 / MTok | $10 / MTok"
     - "Claude Haiku 5.5 (for prompts up to 100,000 tokens) | $0.10 / MTok | $0.125 / MTok | $0.20 / MTok | $0.01 / MTok | $0.50 / MTok"
     - Over 100k tokens, Haiku 5.5 is "$0.50 … $2.50 / MTok".
   - Batch table (input / output):
     - "Claude Opus 5.5 | $2 / MTok | $10 / MTok"
     - "Claude Sonnet 5.5 | $1 / MTok | $5 / MTok"
     - "Claude Haiku 5.5 (for prompts up to 100,000 tokens) | $0.05 / MTok | $0.25 / MTok"
   - Footnote: "Cache hits and refreshes on Claude Opus 5.5 and Claude Sonnet 5.5 are priced at 0.05x the base input price." [P30]
3. **Removed "skill reference" claims; replaced with primary sources:**
   - **Tokenizer:** "Claude 4.7 and later models … use a newer tokenizer that produces approximately 30 percent more tokens for the same text than earlier models" [P30]. This replaces the "1–1.35×" figure.
   - **Thinking billing:** thinking is billed as output; `usage.output_tokens_details.thinking_tokens` "reports how many of the billed output tokens were internal reasoning" [Q2].
   - **Effort:** "Claude Opus 5.5 and Claude Haiku 5.5 default to `"medium"`" effort. With adaptive thinking, at lower effort Claude "might skip thinking entirely on easy inputs" [Q2].
   - Dropped: the Phase 2 claim that Opus 5.5 "always thinks". I have no primary source for it.
   - **Image tokens:** "Claude 4.7 and later models" are on the high-resolution tier (max long edge 2,576 px, 4,784 visual tokens); a 1000×1000 image is 1,296 tokens on either tier [S30].
4. **STT prices and terms**, now from vendor pages:
   - **Deepgram** pricing: Nova-3 pre-recorded monolingual "$0.0043/min" (pay as you go); keyterm prompting "$0.0013/min" [Q3].
   - **Deepgram** Model Improvement Program: opt out per request with `mip_opt_out=true`; opted-out data "is retained only for the duration necessary to process the request" [Q4]. Unverified: the page doesn't state the price impact; community answers say opting out costs more.
   - **AssemblyAI** pricing (page dated 2026-09-28): Universal-2 "$0.15/hr", Universal-3.5 Pro "$0.21/hr", keyterms "+$0.05/hr" [Q5].
   - **AssemblyAI** retention and training: audio deletion "begins at 24 hours and is at most 48 hours"; transcripts from 30 days. Files may be used for training "as permitted by the applicable contract" unless opted out, on EU servers, or under a BAA [Q6].
5. **Server facts (organizer, staging):** PHP `intl` is installed, so `Normalizer` is available. CLI `upload_max_filesize` and `post_max_size` are 100M. Web limits: **verify**.

---

## 1. The five missed items

### 1.1 Privacy page conflicts

The live Privacy page (created by `scripts/migrations/comments-and-privacy.php`) promises:
- "There are no ads, no analytics and no tracking on this site."
- "Comments are checked for spam on this website's own server; nothing is sent to an outside spam service."
- "The site is hosted in Canada."

Antispam Bee is configured for local checks only (same migration, comment "nothing sent to outside services").

| Feature | Sends visitor data out? | Privacy page change needed? | Draft wording (for Jacob and Shawn to approve) |
|---|---|---|---|
| 2.1 featured photo, 2.11 structure fixer | No (no AI, no outside call) | No | n/a |
| 2.2 alt text, 2.3 excerpt, 2.4 reviews, 2.5 categories, 2.6 next stops, 2.9 flags | **Shawn's** published posts and photos go to Anthropic. No visitor data | Not legally about visitors, but the "no tracking / what happens with your information" tone suggests saying it plainly. **Recommended** | New section **"AI helpers"**: "To describe photos for people who use screen readers, write short summaries and file posts under the right place, the site sends Shawn's published posts and photos to Anthropic's Claude AI service. No information about visitors or commenters is sent. Every word of every post is Shawn's own." (Last sentence only if the disclosure option chosen keeps it true; see Phase 2 §9.) |
| 2.7 comment triage | **Yes:** commenters' text (and possibly name) leaves the site | **Required before enabling.** It contradicts "nothing is sent to an outside spam service" | Replace that sentence with: "Comments are checked for spam on this website's own server. If a comment contains a link, it's held, and its text (not your email or IP address) may be sent to Anthropic's Claude AI service to help decide whether it's genuine. Anthropic doesn't use it to train its AI." Update "Where your information is kept": "The site is hosted in Canada. Held comments checked by the AI helper are processed by Anthropic in the United States." (Processing location: **verify**; the API's default is global routing, and `inference_geo` exists [P30].) |
| 2.10 email loop | Shawn's own email, not visitors' | No (an inbound service like Postmark would see Shawn's replies: tell Shawn, not the page) | n/a |
| AI Mode recordings | Shawn's voice to an STT vendor and Anthropic | Not a visitor matter; needs **Shawn's written OK** (ROADMAP §4) | Internal note for Shawn: "Your recordings go to [vendor] to be typed up and are deleted from our site once the post is published. [Vendor] keeps them for at most [N] hours/days and doesn't use them for training (we turn that off)." Fill in from §3.13 terms |

- **Training claims must be accurate:** "Retained data is never used for model training without your express permission" [P32] supports the Anthropic sentence.
- **Change mechanism:** a new migration `scripts/migrations/privacy-ai.php`. The existing one deliberately leaves an existing page alone ("content left as is"), so the new script must replace specific paragraphs. It should update the "Last updated" line and be safe to run twice. Run on staging first, as usual.
- Inference (not legal advice): Canadian privacy law generally expects notice when personal data is processed outside Canada, which is why the location sentence is included for 2.7.

### 1.2 Where the code lives

**Fact:**
- WordPress's theme handbook: "Any theme that you create should not add site-critical functionality"; functionality belongs in a plugin so it survives a theme change [Q7].
- Must-use plugins load automatically and can't be disabled in wp-admin. Only top-level files in `mu-plugins/` load, so subfolders need a loader. They get no update notices, and **activation hooks don't run** [Q8].
- Repo fact: `scripts/deploy.sh` packages `git archive HEAD` into the theme directory and syncs it with a drift-check manifest. Docs, scripts etc. are excluded via `export-ignore`. Staging already uses an mu-plugin (`zz-staging.php`, installed by `refresh-staging.sh`) that **blocks all outgoing email**.

**Options:**

| | Regular plugin `tastybites-assistant` | mu-plugin (folder + loader) |
|---|---|---|
| Can be switched off in an emergency from wp-admin | **Yes** (Plugins → Deactivate) | No (must delete the file over SSH) |
| Can Shawn break it? | Only if he deactivates it (he's not an admin; verify his role) | No |
| Activation hook (create log table, schedule events) | Works | Doesn't run; do setup lazily |
| Survives theme change | Yes | Yes |

**Recommendation (inference): a regular plugin**, living in the same repo at `plugins/tastybites-assistant/`. The plugin code holds all functionality: jobs, AI calls, flags, the structure fixer, the featured-photo fallback. The theme keeps only presentation (the review info box, the rating, schema output it already has).

**Deploy pipeline changes (concrete):**
1. `.gitattributes`: add `plugins export-ignore` so the theme archive stops including it.
2. `scripts/deploy.sh`: add a `TARGET` (theme|plugin) parameter. For the plugin, `git archive --format=tar HEAD:plugins/tastybites-assistant` (archive of the subtree) and destination `wp-content/plugins/tastybites-assistant`.
3. `scripts/remote/deploy-remote.sh`: already parameterised by `THEME_SLUG`. Generalise to `TARGET_DIR` and give each target its own manifest, backup prefix and history line. Keep lint, drift check, backup, `rsync --delete`, cache purge and the WP-CLI boot check.
4. The GitHub Actions workflow deploys both targets (plugin first, then theme) when either path changes.
5. `refresh-staging.sh` copies production's plugins, so **redeploy the plugin after every refresh**, the same rule as the theme. The first production deploy also needs `wp plugin activate tastybites-assistant` (no spaces in the argument, so the server wrapper is fine).
6. If Jacob prefers mu-plugin robustness later, the same subtree can ship to `mu-plugins/tastybites-assistant/` plus a one-line loader file. Keep the code activation-hook-free so both work.

### 1.3 Secrets

**Fact:** connector keys are read from an **environment variable** (e.g. `ANTHROPIC_API_KEY`), then a **PHP constant**, then the database. DB keys "are not encrypted but are masked in the UI" [S3].

**Recommendation (inference):**
- Put keys in a file **outside the web root**, e.g. `~/.tastybites-secrets.php` (chmod 600). It defines `ANTHROPIC_API_KEY`, the STT key and `TBA_SIGNING_KEY` as constants. `wp-config.php` does `require_once` on it. This follows the existing `~/.staging-*` pattern.
  - Production `wp-config.php` stays server-only; the repo never contains keys.
  - Agents must never `cat` the file or print `wp config get` values, per CLAUDE.md.
- **Verify** the exact constant name core checks (the dev note says "a PHP constant" [S3]). Then confirm on staging that Settings → Connectors shows the key as configured without storing it in the DB.
- **Separate keys for staging and production**, ideally in separate Anthropic workspaces, so staging can be cut off independently. Whether workspace spend limits exist in the Console: **verify** (not researched).
- One-question-page tokens: sign with `hash_hmac('sha256', …, TBA_SIGNING_KEY)` (or `wp_salt('auth')`), and store only a hash of each token.
- `refresh-staging.sh` copies `public_html` → staging including `wp-config.php`? Read only: I didn't trace it. **Verify** that staging doesn't end up using the production key. If it does, staging's `wp-config.php` must load `~/.tastybites-secrets-staging.php` instead.

### 1.4 Guardrails

All in the plugin's AI wrapper (§2.2):
- **Kill switches, three levels:**
  1. constant `TBA_DISABLED` (wp-config; stops everything including cron jobs);
  2. option `tba_enabled` per feature (`alt`, `excerpt`, `reviews`, …);
  3. deactivate the plugin.
  - Tier 0 (no AI) has its own switch so it can stay on when AI is off.
- **Daily spend cap:** before each call, sum today's logged cost. If it's over `tba_daily_cap_usd` (suggest $2/day production, $0.50 staging), refuse with `WP_Error('tba_cap')`, requeue for tomorrow, and log once. Cost per call = `input_tokens × in_price + output_tokens × out_price` from a price table in code. Thinking tokens count as output [Q2]. Batch rows are halved.
- **Log every call** in a custom table `{prefix}tba_ai_log`: time, feature, post_id/attachment_id, requested model, **model actually used** (from the result's metadata, `providerMetadata`/`modelMetadata` [S2]), input/output tokens, cost, duration, outcome (ok/error/rejected-by-check/cap), error code. Keep 180 days. If the model used isn't on the allow-list (the AI Client may fall back to "any compatible model" [S2]), **discard the result** and log `wrong_model`.
- **`wp_ai_client_prevent_prompt` filter** [S2]:
  - Allow prompts only when called from our job runner (a static flag set by the runner) and `TBA_DISABLED` is unset.
  - On staging, additionally require `TBA_STAGING_AI` = true, so a fresh staging refresh never spends money by surprise.
  - This also blocks other plugins (e.g. the AI plugin if ever installed) from using the key unless we allow them.
- **When the API is down or errors:**
  - Never block or delay publishing; Tier 0 doesn't depend on AI.
  - Tier 1 jobs retry with backoff (5 min, 30 min, 2 h), then mark the job `failed` and create a Jacob-only flag ("Alt text couldn't be written for 'Post X'; will retry next publish/update").
  - Batch jobs: `expired` requests are unbilled and get resubmitted [P29].
- **Prompt-injection hygiene:** post text and comments are data. System prompts say so, and outputs are only accepted through JSON schema plus PHP checks (§3), never as instructions.

### 1.5 AI Mode capture UI (older user)

**Facts:**
- `MediaRecorder` is Baseline "widely available" since April 2021. Use `MediaRecorder.isTypeSupported()` to pick `audio/webm;codecs=opus` or `audio/mp4` (Safari), and save with `recorder.mimeType`. `start(timeslice)` emits chunks via `dataavailable` [Q9]. The Safari-specific table didn't load: **verify** on Shawn's actual browser.
- PHP `upload_max_filesize` default is 2M and `post_max_size` 8M, both changeable per directory (`.user.ini`). `post_max_size` must exceed `upload_max_filesize`, and an over-size POST arrives empty [Q10]. Server CLI is 100M (organizer); web: **verify**.
- WordPress 7.1 upload has retries and progress [S5].

**Design (inference):**
- **Where:** a **separate admin screen** "Talk to the blog" (one big page, our own plugin menu item at the top of his sidebar), not an editor panel. This follows ROADMAP §4 ("the simplest screen he can't get lost in") and NN/g's fewer-controls finding [S49].
- **Sessions:** a session = one future post. The screen lists "Your stories in progress" as big cards. Opening one shows previous recordings as numbered parts ("Part 1 · 12 min · Tuesday").
- **Controls:** three huge buttons only: **Record / Pause / Stop**. A visible timer and level meter so he knows it's hearing him. After stop: **Play back · Keep · Say it again**.
  - "Say it again" replaces only that part (the memoir reviewers wanted partial re-records [P8]). Parts can be deleted.
- **Chunked upload:** record with `start(10000)` (10 s chunks) and upload each chunk as it arrives via a REST endpoint (`POST /tba/v1/session/{id}/chunk`, nonce-protected, `edit_posts`). The server appends to a part file. A crash or closed laptop loses ≤10 s, and no single request comes near PHP limits.
  - Keep files in a private uploads subfolder with deny-all `.htaccess`, not the media library.
- **Autosave over days:** a session is a private CPT `tba_session` with parts as child records. Nothing expires while a session is open. Store the transcript per part as soon as STT returns (Tier 1 job).
- **Photos:** a "Add photos" button using the normal media uploader. The server reads EXIF time and GPS **before** any resizing.
  - **Verify** that 7.1's browser-side processing [S5] keeps EXIF in the original; Claude doesn't read metadata [S30].
  - Convert HEIC/AVIF to JPEG for Claude, which accepts JPEG/PNG/GIF/WebP only [S30].
- **Finish:** "I'm done, make my post" with three checkboxes (Blog post pre-ticked; Reviews; Recipe). The result arrives as **drafts** (ROADMAP §4) with an "unplaced photos" gallery at the end.
- **Accessibility:** 20px+ text, high contrast, no hover-only controls, plain words ("Recording…", "Saved"). Screen-reader labels on icon buttons. This fits the macular-degeneration complaint in [P8].

---

## 2. Shared infrastructure (build first)

### 2.1 Job runner

- **cPanel cron, every 5 minutes:** `cd ~/public_html && wp cron event run --due-now --quiet` (and the same for staging).
  - CLI PHP has no time limit by default [P27]. CLI is also immune to LiteSpeed's disconnect abort [P26] and to the host's throttling of server-to-self requests (CLAUDE.md).
  - Set `define('DISABLE_WP_CRON', true)` only after the cron is confirmed running, otherwise scheduled posts break [P24].
  - **Verify** that the cPanel cron line runs with the right PHP version (8.2) and the `wp` binary path.
- **Enqueue:** `wp_schedule_single_event( time(), 'tba_run_job', [ $job_id ] )`. Jobs are rows in a small `{prefix}tba_jobs` table or a private CPT: type, object id, attempts, status, next_run.
  - Dedupe: one pending job per (type, post).
- **Inside a job:**
  - `wp_set_current_user( <id of a dedicated "Assistant" user, role Editor> )`. Ability permission callbacks check the current user [P33], and revisions are then attributed to "Assistant".
  - Raise the AI Client timeout only here via `wp_ai_client_default_request_timeout` (e.g. 120) [P23].
- **Cache:** after background updates, LiteSpeed Cache normally purges the post on `save_post` (**verify**). If not, call the plugin's purge action for that URL.

### 2.2 AI wrapper `tba_ai()`

- Signature: `tba_ai( string $feature, array $content, array $schema, array $opts ) : array|WP_Error`.
- Steps:
  1. Kill-switch and cap checks.
  2. Build `wp_ai_client_prompt()` with `using_system_instruction()`, `with_file()` for images [S2], `as_json_response( $schema )`, and `using_model_preference( <allow-list> )`.
  3. Call `generate_text()`.
  4. `json_decode`, then validate against the schema again in PHP.
  5. Check the model used and log.
- Returns structured data or `WP_Error`. **No caller ever writes AI output to the DB without a feature-specific PHP check.**
- Batches (backfill only): a thin direct client (`wp_remote_post` to `/v1/messages/batches`) because the core AI Client documents no batch interface [S2]. Same logging, prices halved [P30].

### 2.3 Flags store

- **Private CPT `tba_flag`**: free admin list screen, REST off, no revisions. Meta:
  - `post_id`, `kind` (e.g. `review_missing_city`, `score_missing`, `heading_candidate`, `alt_failed`);
  - `audience` (`jacob`|`shawn`), `question` (plain words), `choices` (JSON), `status` (`open`/`answered`/`applied`/`dismissed`);
  - `answer`, `token_hash`, `token_expires`, `applied_change` (JSON before/after).
- Dedupe: one open flag per (post, kind, subject).
- Admin screen "Assistant → Flags" for Jacob, filterable by audience and status.

### 2.4 Digest email + one-question pages

- **Weekly digest** (cron event, Mondays 9:00 site time), only if there are open `shawn` flags. At most 5 questions per email; the rest wait. This follows the memoir-review lesson that cadence and nagging cause drop-off [P7, P9].
  - Reuse `inc/emails.php`'s branded layout and buttons (tomato button works in Outlook) by moving `tb_email_layout()` / `tb_email_action()` into the plugin, or calling them if present.
- **Each question = one button → one-question page:** `https://site/?tba_q=<flag_id>&t=<token>`.
  - Token: 32 random bytes, base64url; only `hash_hmac` of it is stored. It expires after 30 days, is single-use, and is invalidated if the flag is already answered.
  - The page is `noindex`, has no login, and shows large text: the question, the snippet of his post it's about (quoted), buttons for the likely answers, an "Other" box and a "Skip" button.
  - Logged-out users can't have nonces bound to a user, so the token is the capability. Rate-limit by token.
- **Free-text answers:** an AI call turns "it's in St. Albert, Alberta, about $20 a head" into field values (JSON: `{city, price}`). PHP checks that each value is a substring or near-substring of his answer, then applies.
- **Confirm back:** the confirmation goes into the next digest ("Done: added 'St. Albert, Alberta' to Stan's"), each with an Undo link (same token scheme).
- **Reply-by-email:** later and optional (Phase 2 §7): tokenised Reply-To, a cPanel pipe [P37] and `willdurand/email-reply-parser` [P39].
- **Staging blocks all outgoing email** (`zz-staging.php`). For testing, log the digest HTML to a file or an admin "last digest" preview instead of sending. Never relax the block.

### 2.5 Privacy-page migration

Per §1.1. It must be applied to production **before** any AI feature runs on production, and before 2.7 is even considered.

---

## 3. Per-item guide

Common to all Tier 1 items:
- **Trigger:** `wp_after_insert_post` (fires after post, terms **and meta** are saved [Q11]). Run when `$post->post_status === 'publish'`, `$post->post_type` is `post`, `review_content` or `recipes_content`, and the post isn't an autosave or revision.
  - This covers first publish and later updates of published posts. It never fires for drafts, per the decision log.
- **Job:** enqueue one `tba_publish_pass` job (debounced: if one is pending for the post, push its `next_run` 2 minutes later, so several quick updates cause one run).

### 3.1 (2.1) Featured photo fallback

- **Decision:** build (≈30 lines). Auto Post Thumbnail shows the approach (first image in content, bulk) [P45], but a plugin isn't needed.
- **Tier 0**, synchronous inside the `wp_after_insert_post` callback, so the first Facebook share card has a photo [P25].
- **Logic:**
  1. If `has_post_thumbnail()` is false, walk `parse_blocks( post_content )` recursively (Gallery inner Image blocks count [P21]) and take the first `core/image` with an `id` attr.
  2. Check `wp_attachment_is_image()` and width ≥ 600 (`wp_get_attachment_metadata`).
  3. Call `set_post_thumbnail()` and set meta `_tba_auto_thumb = 1`.
  4. **Never replace a thumbnail he chose.**
- **Backfill:** WP-CLI command `wp tba thumbs --dry-run` / `--apply` over published posts without `_thumbnail_id`.
- **Tests:**
  - On staging, list published posts lacking `_thumbnail_id` (dry-run output), apply, and spot-check 5 on the front end and the homepage cards.
  - Re-saving a post with a thumbnail must not change it.
- **Risks:** the first image may be a map or screenshot. Later "AI picks the best photo" (ROADMAP §2.1) is a Tier 1 upgrade gated by a Jacob flag.

### 3.2 (2.2) Alt text

- **Decision:** build with our own prompt via the AI Client. **Reuse option:** the AI plugin's `ai/alt-text-generation` accepts an optional `context` input and returns `alt_text` + `is_decorative` [S11]. It's experimental with recent breaking renames [S8], so don't depend on it for now. Backfill via Message Batches [P29].
- **Tier 1** (publish pass); backfill is a one-off batch.
- **Inputs per image:** the image file at ~1000 px long edge (the `large` size; 1000×1000 ≈ 1,296 tokens [S30]), post title, his caption, the paragraph before and after the image, and the destination name.
- **Output schema:** `{ "alt": string (5–150 chars), "decorative": boolean, "names_used": [string] }`.
- **Prompt rules:**
  - Describe what is visible, food first.
  - Use dish or place names only if they appear in the given text, and list them in `names_used`.
  - Never identify people (Claude refuses anyway [S30]).
  - No "image of".
- **PHP checks:**
  - Length bounds.
  - Strip quotes.
  - Every `names_used` item must appear in the supplied context (normalised, case-insensitive). If one doesn't, remove the name and use the generic alt, or flag it.
  - Reject outputs with URLs.
- **Apply:**
  - Set the attachment's `_wp_attachment_image_alt` if empty.
  - In `post_content`, fill only empty `alt=""` attributes on that image's block (attr and inner HTML).
  - Then run the **words-unchanged check** (§3.11). Alt is inside a tag, so visible words are unchanged. Save via `wp_update_post` (creates a revision).
  - Meta `_tba_alt_ai = model@date` on the attachment.
  - Never overwrite non-empty alt.
- **Model:** decide by eval. Run Haiku 5.5 vs Sonnet 5.5 on 30 staging images across 10 posts; Jacob grades accuracy and usefulness blind.
  - Cost per 1,060-image backfill in batch at ~1,700 input / ~100 output tokens each: Haiku 5.5 ≈ $0.12, Sonnet 5.5 ≈ $2.30, Opus 5.5 ≈ $4.70, before any thinking tokens (computed from [P30] batch prices).
- **Risks:** wrong dish names (mitigated by `names_used`); large PNGs (resize first).

### 3.3 (2.3) Summary + social preview

- **Decision:** build, **extractive** only. The theme already uses the excerpt, or else the lead sentence, for meta and OG (`inc/seo.php: tb_meta_description()`), and OG tags exist. So the only gap is choosing a better excerpt than "first sentence".
  - **Inference:** an extractive excerpt keeps 100% of his voice and is mechanically checkable, which is why it's preferred over the AI plugin's generated excerpt [P36].
- **Tier 1**, only if `post_excerpt` is empty.
- **Input:** the post text split into numbered sentences.
- **Output schema:** `{ "sentence_ids": [int, max 2] }`.
- **PHP:** join those sentences verbatim and check ≤ 160 chars (else take the first). Save to `post_excerpt`; meta `_tba_excerpt_ai = 1`.
- **Tests:** 10 posts; Jacob checks that each excerpt reads well out of context and contains no swearing he'd not want on Facebook. **Add a profanity check:** if the excerpt contains a word on a short list, choose another sentence. Shawn's voice in the body is fine; share cards are seen out of context (judgement for Jacob).

### 3.4 (2.4) Review extraction

- **Decision:** build. There's no off-the-shelf equivalent; generic schema extractors exist [S25].
- **Tier 1** creates **draft** reviews; **Tier 2** flags gaps. Jacob publishes drafts until trusted.
- **Input:** post text with paragraph indexes, plus the list of existing `review_content` titles and their destination (dedupe).
- **Output schema** (per item):
  ```json
  { "name": "string", "place_quote": "string", "score": "number|null", "score_quote": "string|null",
    "city": "string|null", "city_quote": "string|null", "cuisine": "string|null", "price": "string|null",
    "price_quote": "string|null", "paragraph_ids": ["int"], "existing_review_id": "int|null" }
  ```
- **PHP checks** (Phase 2 §11; Citations can't be combined with JSON output [P35]):
  - Every `*_quote` must be a substring of the normalised post text (§3.11 normaliser).
  - `name` must appear in `place_quote`.
  - `score` must appear literally in `score_quote` and lie in 0–10.
  - City, cuisine and price are kept only if their quote passes.
  - Anything failing is dropped and becomes a flag ("You mentioned Viking Café but no score: want to add one?"). **Never invent.**
- **Write:**
  - Create a `review_content` draft with the title `name`, meta `review_rating` = score, `tb_city`, `tb_cuisine`, `tb_price` (the existing keys in `inc/entry-tools.php`), and the `destination` term (existing terms only).
  - The body uses the review pattern, with his paragraphs copied **verbatim** plus a link "From my trip post: …".
  - Meta `_tba_source_post`, `_tba_quotes` (JSON). The source post gets `_tba_reviews` = IDs.
  - If `existing_review_id` is set, create a flag instead of a duplicate.
- **Tests:**
  - The Stan's "a solid 8.8/10" post.
  - A trip post with several places and partial scores.
  - Odd score formats ("8 out of 10", "a 9", "7.5/10 for the fries but…").
  - A post with no reviews (expect zero).
  - Measure precision by hand on 10 posts.
- **Risks:**
  - Ambiguous multi-item scores. The rule "score must appear in the quote with the name nearby" keeps them, so a human decides.
  - Duplicate reviews.

### 3.5 (2.5) Category / destination suggestion

- **Decision:** build our own, small. Copy the AI plugin's design: 0–1 confidence, floor 0.6, existing-only terms [S14].
- **Tier 1** auto-apply only if confidence ≥ 0.8 **and** the post has no category other than the default; otherwise a **Tier 2** Jacob flag. Destination: existing `destination` terms only. A new place becomes a Jacob flag.
- **Output schema:** `{ "categories": [{ "term_id": int, "confidence": number }], "destination": { "term_id": int|null, "confidence": number, "quote": string } }`. The destination quote must be a substring.
- **Never remove** a term Shawn set.
- **Tests:** 20 published posts with known correct terms; measure agreement before turning on auto-apply.

### 3.6 (2.6) Next stops suggestions

- **Decision:** build late; **Tier 2 only** (Jacob approves).
- **Data:** the homepage list is the Customizer setting `tb_next_stops` (textarea, `functions.php`).
- **Output:** `{ "trips": [{ "text": string, "month": "YYYY-MM"|null, "quote": string }] }`. Quote checked.
- **Apply:** Jacob's approve button appends to `tb_next_stops`.

### 3.7 (2.7) Comment triage

- **Decision: defer.**
  - It breaks the current privacy promise (§1.1).
  - Volume is tiny: only held comments, those with links.
  - The AI plugin's comment moderation scores *all* comments [S8], which would send every comment out. **Don't enable it.**
- **Non-AI first step:** an allow-list of link domains Shawn's commenters commonly use, or simply the held-comment email Jacob already gets (`inc/emails.php`).
- If built later: only held comments; only the comment text; privacy migration first; output `{ "genuine": bool, "reason": string }`; Tier 2 suggestion, Jacob clicks Approve.

### 3.8 (2.8) Proofread

- **Decision: defer** (voice risk). LLMs over-correct by default [P13–P15], and there's no publish-time need.
- If built, it's an editor button outside the tiers. Allowed edits: spelling of real words and obvious typos only. Return a list of `{ "from": string, "to": string, "sentence_id": int }`. PHP rejects changes to swear words, to words over 1 edit distance, and to proper nouns. Show each for accept/reject.

### 3.9 (2.9) Content flags

- **Decision:** build. **Mostly deterministic, no AI.** Runs in the publish pass.
- **Deterministic checks:**
  - review missing `tb_city` / `tb_price` / `tb_cuisine` / `tb_visited` / `review_rating`;
  - recipe missing `recipe_time` or servings (field name: check the recipe pattern);
  - images still without alt after 2.2;
  - empty headings;
  - more than 3 single images in a row that 2.11 couldn't fix.
- **AI-derived:** restaurants with no score (from 3.4 results).
- **Typos:** defer, as 2.8.
- **Output:** `tba_flag` rows with plain-words questions. Audience: `jacob` for technical, `shawn` only for facts only he knows (city, price, score, visit date).

### 3.10 (2.10) Flags dashboard + email loop

- **Decision:** build per §2.3–2.4.
- **Order:** the admin list (Jacob) first, then the digest to **Jacob** for 2 weeks, then turn on Shawn's digest after Jacob explains it to him at the batched check-in (decision log 2026-10-25).
- **Apply answers:** each `kind` has a PHP applier (e.g. `review_missing_city` → `update_post_meta( $review, 'tb_city', $value )`). Store `applied_change` for Undo.

### 3.11 (2.11) Structure fixer

- **Decision:** build. **Deterministic rules plus the words-unchanged check.** AI only to classify ambiguous heading candidates, later.
- **Tier 0 at publish, in propose mode first.** Store the proposal and notify Jacob. After ≥10 clean proposals for a rule, switch that rule to auto-apply.
- **Rules (in order):**
  1. **Unwrap legacy Columns/Row/Grid layouts** that hold only paragraphs/images: replace them with their inner blocks in order.
     - Careful: Shawn's **review and recipe patterns use Group/Grid blocks**, so never unwrap blocks inside `tastybites/*` pattern output or containing `mfb/meta-field-block`.
  2. **Day N → heading:**
     - Applies to a `core/paragraph` whose text (tags stripped) matches `^(Day\s+\d+|Lunch|Dinner|Breakfast|Brunch)\b` with ≤ 6 words and no ending `.`/`!`/`?`, or whose whole content is wrapped in `<strong>`/`<b>`. The bold pattern is the one Editoria11y flags [S42].
     - It becomes `core/heading` level 2 (level 3 if the previous heading is a Day heading and this is a meal name). The inner text is kept and bold wrappers removed.
  3. **Image runs → gallery:** ≥ 3 consecutive top-level `core/image` blocks become one `core/gallery` whose inner blocks are **the same image blocks unchanged**. Lossless since 5.9 [P21]. Gallery settings: default columns 3, `linkTo` none, so the core lightbox applies (check the theme setting).
  4. **Remove empty headings and empty list items.** Strip a leading `-`, `–` or `•` plus a space from list items.
- **Words-unchanged check (Phase 2 §5, final):**
  1. Text = `wp_strip_all_tags( $content )` (strips block comments and tags [P22]), then `html_entity_decode( ENT_QUOTES|ENT_HTML5 )` and `Normalizer::normalize( NFC )` (`intl` confirmed).
  2. Map curly quotes/apostrophes to straight, and en/em dash to `-` **only for comparison**. Collapse whitespace and split into words.
  3. Before and after word sequences must be **identical**, except that the after sequence may lack exactly the tokens removed by rule 4 (each a lone leading list marker). This is implemented by removing those markers from *before* in the same normaliser.
  4. The multiset of image attachment IDs (`id` attrs of every `core/image`, at any depth) must be identical.
  5. Store `sha256` of the after-stream in `_tba_words_hash`; log both hashes.
  6. Any mismatch: discard the proposal, create a Jacob flag `structure_check_failed`, and change nothing.
- **Approval screen (Visual Revisions isn't available, §0):**
  - Admin page "Assistant → Layout tidy-ups" lists proposals. Each shows **two rendered previews side by side** in iframes: the current post (`get_preview_post_link()`), and the same URL with `&tba_proposal=<id>`.
  - The second preview applies a `the_content` filter **for logged-in users with `edit_post` only** that swaps in the proposed content. Phone widths: reuse the existing iframe test approach (DEPLOYMENT.md).
  - Buttons: **Apply** (`wp_update_post`, which creates a revision; undo = Restore on `revision.php`) and **Reject** (rule-level reject counts toward trust).
- **Tests:**
  - Run propose-mode in a WP-CLI dry run across **all** staging posts. Expect zero check failures that aren't real bugs, and review 10 proposals rendered.
  - Must include: a "Day 2" trip post (§6 of ROADMAP), a post with 12 images in a row, the Italian Center post (heavy PNGs), and a review and a recipe (patterns must be untouched).
- **Risks:**
  - Patterns damaged (guarded by rule 1's exclusion).
  - A false heading ("Wow" as a short bold line): bold-only rule limited to ≤6 words, and proposals reviewed during the trust period.

### 3.12 (2.12) Image tidy-up on upload

- **Decision: defer** to the existing backlog decision (LiteSpeed/QUIC.cloud, decision log 2026-10-25). No AI needed.
- Note: 7.1 already compresses and generates sizes in the browser and adds AVIF/HEIC support [S5]. Re-check this item after testing what 7.1 does with his PNG uploads (staging checklist).

### 3.13 AI Mode (§4)

- **Decision:** experiments only, after the quiet assistant is live and after Jacob's conversation with Shawn about the "no AI" line (Phase 2 §9).
- **Experiment A, STT bake-off:**
  - One 10-minute recording of Shawn telling a trip story he already blogged, made on his laptop in his usual room.
  - Providers: Deepgram Nova-3 (with keyterms) [Q3], AssemblyAI Universal (keyterms) [Q5], and browser Web Speech [S32].
  - Keyterms come from his post corpus: restaurant names, towns, dishes.
  - **Pre-requisites:** set retention and training opt-outs before sending: Deepgram `mip_opt_out=true` [Q4]; AssemblyAI opt-out (paid plans) and TTL [Q6].
  - Metric: word error rate against a hand-corrected transcript, plus errors on names specifically.
  - Expected cost: < $0.10 per provider [Q3, Q5].
  - The Claude API takes no audio [P31].
- **Experiment B, light-touch pipeline (offline, no UI):**
  - "Arrange, don't rewrite" (Phase 2 §4). The AI receives numbered transcript sentences and photo descriptions (with EXIF time order).
  - Output: `{ "sections": [{ "heading": string, "sentence_ids": [int], "photo_ids": [int] }], "unplaced_photo_ids": [int], "questions": [string] }`.
  - PHP assembles the post from his sentences, after a filler/false-start cleanup step that is itself checked: only an allow-list of filler tokens may be removed.
  - Run Phase 2's three evals on 5 of his posts.
  - Model: Opus 5.5 vs Sonnet 5.5, measured. Effort default is `medium` on Opus 5.5 [Q2].
- **Experiment C, capture UI** on staging (§1.5), tested with Shawn in person, Jacob present. Pass criterion: he records two parts, re-says one, and adds photos without help on the second try.
- **Retention:** delete audio when the post is published, or after 90 days of inactivity, whichever first; keep transcripts with the draft (Phase 2 §1 lesson 1). Needs Shawn's OK.

---

## 4. Build order (small, shippable steps)

1. **Plumbing, no AI.** Plugin skeleton in `plugins/tastybites-assistant/`, deploy target, cPanel cron on staging, job table, log table, kill switches. Ship to staging, then production (does nothing visible).
2. **2.1 featured photo** (Tier 0) + backfill dry-run → apply. First visible win; no AI, no privacy change.
3. **2.11 structure fixer, propose mode** + words-unchanged check + the side-by-side preview screen. Dry-run over all staging posts; fix rules until clean. Production in propose mode only.
4. **Secrets + AI wrapper + `prevent_prompt` + spend cap** on staging with a staging key. **Privacy migration drafted** and sent to Jacob and Shawn for approval (batched check-in).
5. **2.2 alt text** on publish (staging). Model eval on 30 images. Then production (after privacy approval). Then the **batch backfill** of ~1,060 images, logged, with a dry-run first on 20 images.
6. **2.3 extractive excerpt.**
7. **Flags store + 2.9 deterministic checks + Jacob's flag list.**
8. **2.5 categories/destination** (suggest-only for 2 weeks, then auto ≥0.8).
9. **2.4 review extraction** to drafts, Jacob publishes.
10. **2.10 digest + one-question pages**, Jacob-only for 2 weeks, then Shawn.
11. **2.11 auto-apply** for rules with clean records.
12. **2.6 next stops** (Tier 2).
13. **AI Mode experiments A → B → C.**
- Deferred: 2.7 (privacy), 2.8 (voice risk), 2.12 (backlog decision).

Each step: branch → staging → check → merge (CLAUDE.md). Record decisions in ROADMAP.md's decision log.

---

## 5. "Verify on staging first" checklist

- [ ] **Cron:** the cPanel cron line runs `wp cron event run --due-now` with PHP 8.2 every 5 min (check `wp cron event list` timestamps move). Only then set `DISABLE_WP_CRON`. Scheduled posts still publish on time.
- [ ] **CLI abilities:** `wp_get_ability( 'core/get-site-info' )->execute()` works from a WP-CLI job after `wp_set_current_user()`. (If the AI plugin is ever trialled: also `ai/excerpt-generation`.) [P33, P34]
- [ ] **Connector key from constant:** Settings → Connectors shows Anthropic configured with the key only in `~/.tastybites-secrets.php`; nothing in `wp_options` (`connectors_ai_anthropic_api_key` absent) [S3]. Confirm the constant name.
- [ ] **Staging doesn't inherit the production key** after `refresh-staging.sh`.
- [ ] **`wp_ai_client_prevent_prompt`** blocks calls outside the runner and on staging without `TBA_STAGING_AI`.
- [ ] **AI Client timeout filter** takes effect in CLI jobs (a slow call over 30 s succeeds at 120) [P23].
- [ ] **Model metadata:** the result exposes the model actually used; log it.
- [ ] **`intl` Normalizer:** confirmed by the organizer; still run the words-unchanged unit test over all staging posts.
- [ ] **Revisions:** confirm `wp_update_post` by the Assistant user creates a revision and that Restore on `revision.php` undoes a structure change. (Visual Revisions: not present in our setup, per organizer.)
- [ ] **LiteSpeed purge** happens on background `wp_update_post` (fetch the page from this machine, slowly, per CLAUDE.md's rate limits).
- [ ] **Upload limits (web):** check `upload_max_filesize`/`post_max_size` as seen by PHP-FPM/LSAPI (a `phpinfo` behind admin, or `ini_get` in a WP-CLI-vs-web comparison). Chunked recording uploads stay far under them anyway.
- [ ] **EXIF survival in 7.1 uploads:** upload a phone photo with GPS via the block editor. Check whether the stored original keeps EXIF (`exif_read_data` on the file) given browser-side processing [S5]. Also check HEIC handling.
- [ ] **MediaRecorder** on Shawn's actual laptop browser: `isTypeSupported` result and a 15-minute test recording with 10 s chunks [Q9].
- [ ] **Patterns untouched:** the structure fixer dry-run makes no changes inside review/recipe pattern blocks.
- [ ] **Email:** the staging block (`zz-staging.php`) stays on; the digest is previewed in admin instead.
- [ ] **Shawn's role** is Author/Editor, not Administrator (affects who can deactivate the plugin).

---

## Sources added in Phase 3 (all accessed 2026-10-25)

- [Q1] https://publishpress.com/blog/revisions/wordpress-70-revisions/, https://www.dreamhost.com/blog/wordpress-7-0/, https://www.wpbeginner.com/news/whats-coming-in-wordpress-7-0-features-and-screenshots/ (2026, secondary): claim Visual Revisions shipped in 7.0; **contradicted on our staging (7.1.3) by the organizer.**
- [Q2] https://platform.claude.com/docs/en/build-with-claude/extended-thinking (current): thinking tokens are billed output; Opus 5.5 and Haiku 5.5 default effort `medium`; adaptive may skip thinking.
- [Q3] https://deepgram.com/pricing (undated, current): Nova-3 $0.0043/min, keyterms $0.0013/min.
- [Q4] https://developers.deepgram.com/docs/the-deepgram-model-improvement-partnership-program (current): `mip_opt_out=true`; opted-out retention.
- [Q5] https://www.assemblyai.com/pricing.md (2026-09-28): Universal prices, keyterms add-on.
- [Q6] https://www.assemblyai.com/docs/data-retention-and-model-training.md (current): deletion timelines, training and opt-out.
- [Q7] https://developer.wordpress.org/themes/getting-started/what-is-a-theme/ (current): themes shouldn't add site-critical functionality.
- [Q8] https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/ (current): mu-plugin behaviour and caveats.
- [Q9] https://developer.mozilla.org/en-US/docs/Web/API/MediaRecorder (mod. 2026-10-07): Baseline, isTypeSupported, timeslice chunks.
- [Q10] https://www.php.net/manual/en/ini.core.php (current): upload/post size defaults, INI_PERDIR, empty POST on overflow.
- [Q11] https://developer.wordpress.org/reference/hooks/wp_after_insert_post/ (current): fires after post, terms and meta are saved; since 5.6.
- Repo (read only, 2026-10-25): `scripts/migrations/comments-and-privacy.php` (Privacy text, Antispam Bee local-only), `inc/seo.php` (meta/OG from excerpt), `inc/entry-tools.php` (review meta keys), `functions.php` (`review_rating`, `tb_next_stops`, `destination`), `scripts/deploy.sh` + `scripts/remote/deploy-remote.sh` + `.gitattributes` (theme-only deploy, drift check), `DEPLOYMENT.md` (staging mu-plugin blocks email).
