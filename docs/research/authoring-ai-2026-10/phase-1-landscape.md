# Phase 1: landscape scan (authoring + AI)

Research agent, 2026-10-25. All web sources accessed 2026-10-25. **Fact** = stated by a source (cited);
**Inference** = my reading, marked as such. Sources older than ~2 years are flagged ⚠ old.

## Summary

- **Core WordPress gives us plumbing, not features.** WordPress 7.0 (May 2026) shipped the WP AI Client
  (`wp_ai_client_prompt()`), the Connectors screen (Anthropic is one of three featured providers) and a
  more capable Abilities API. 7.1 (19 Aug 2026) refined the Abilities API (execution lifecycle hooks,
  validation). The AI team says plainly that there is **no visible AI in core's UI**; features live in plugins.
  The client supports text, JSON-schema output and image *input*; it has **no speech-to-text**, streaming is not
  natively supported in WordPress yet, and database-stored API keys are **not encrypted**.
- **The official "AI" plugin (WordPress.org, v1.4.0, 2026-10-05) already implements much of ROADMAP §2:**
  alt text (with bulk), excerpt, meta description, category/tag suggestions with confidence scores, comment
  moderation with a value score, editorial review notes attached to blocks, an "apply the notes" rewriter, and
  a Guidelines store for site voice. But it is **button-driven, block-editor-only and labelled experimental**;
  nothing runs automatically on publish.
- **Most of what we want exists in pieces.** Alt-text bulk tools, SEO meta generators, publish checklists,
  accessibility linters that spot "bold paragraph that should be a heading", post-by-email, voice-memo-to-blog
  pipelines. What I did **not** find anywhere: (1) a quiet on-publish pipeline that never asks the author
  anything in the editor, (2) review/score extraction from narrative trip posts with a "never invent" rule,
  (3) an email loop where the author answers gap questions in plain words and the site applies them, (4) a
  structure-only fixer that changes markup but provably not words, (5) multi-session voice + photo authoring
  that keeps one author's voice. These are the genuinely new parts.
- **Research on older adults supports our direction** (no extra steps, plain-language prompts, safe undo, email as
  a channel) and adds two cautions: older adults strongly want to know whether text is AI-generated (92% in a
  2025 U.S. poll), and AI co-writing measurably lowers the writer's sense of ownership. That bears directly on
  Shawn's public "I don't use any AI" line.

---

## A. What has been done before

### A1. AI inside WordPress

**Core (6.9 → 7.1)**

- **WP AI Client (core since 7.0).** Fact: a provider-agnostic PHP layer. Entry point `wp_ai_client_prompt()`
  returns a fluent `WP_AI_Client_Prompt_Builder` with `generate_text()`, `using_system_instruction()`,
  `using_temperature()`, `using_max_tokens()`, `with_file()` (file/image input), `as_json_response( $schema )`
  for structured output, `using_model_preference()` ("a preference, not a requirement"), and deterministic,
  free `is_supported_for_*()` checks. Errors come back as `WP_Error`. A `wp_ai_client_prevent_prompt` filter can
  block prompts; the client-side JS API is limited to administrators by default [S2]. The dev note lists
  text-to-speech and speech *generation*, but **no speech-to-text** [S2].
- **Connectors API + Settings → Connectors (7.0).** Fact: Anthropic, Google and OpenAI are the featured
  connectors; the Anthropic example points to a separate provider plugin (`ai-provider-for-anthropic`). Keys
  are read from an environment variable, then a PHP constant, then the database; **DB keys are not encrypted**,
  only masked in the UI (ticket #64789 explores encryption). Auth types: `api_key` and `none` [S3]. A secondary
  source says 7.1 added application-password auth for connectors [S10]; unverified against a primary source.
- **Abilities API (6.9, extended in 7.0/7.1).** Fact: a registry of named, permission-checked actions with
  input/output schemas; 7.0 integrated it into the AI Client and added a client-side package and command-palette
  integration [S1]; 7.1 added a filterable execution lifecycle, custom validation and shared discovery [S5, S6].
- **What 7.1 did not add.** Fact: the AI team's 7.1 post says there is still no visible AI in core, streaming is
  added to the underlying PHP library but **not natively supported in WordPress** (most servers time out
  around 30 s), and embeddings are planned [S4]. **Guidelines did not merge into core in 7.1**; the
  "Guidelines/Knowledge merge proposal continues to evolve" [S6]. (One secondary source [S10] claims a core
  Settings → Guidelines screen in 7.1; the primary developer blog contradicts it. Treat [S10] as wrong on this.)
- **MCP Adapter.** Fact: exposes registered abilities as MCP tools so external clients (Claude Desktop, Claude
  Code, Cursor) can call them, authenticating as a WordPress user via application passwords; the article
  recommends least-privilege users [S7]. Relevant to us only if Jacob wants to drive maintenance from Claude
  Code; not for Shawn.

**The official AI plugin ("AI", formerly "AI Experiments", by WordPress.org)** [S8, S9, S11–S14]

- v1.4.0, updated 2026-10-05, 60,000+ installs, requires WP 7.0.3, 4.2★ with complaints about key handling.
  **Block Editor only; Classic Editor not supported and not planned** [S8].
- Features: alt text, excerpt, meta description (integrates with SEO plugins), content classification,
  summarisation, title/slug generation, translation, comment moderation (0–1 "value" score added in 1.4.0),
  Suggest Reply, Editorial Notes, Editorial Updates, Guidelines, image generation, type-ahead, request
  logging, key encryption, "connector approvals" [S8].
- How they're triggered (from the plugin's own docs):
  - **Alt text:** always user-initiated (button in the Image block, media modal, attachment screen) plus a
    Media Library bulk action capped at 100 per batch. Ability `ai/alt-text-generation` returns `alt_text` and
    `is_decorative`; post context is an *optional* input the UI does not appear to send [S11].
  - **Editorial Notes:** a button in the sidebar; reviews up to 25 blocks (skips blocks under 20 chars) for
    accessibility, readability, grammar, SEO; writes each suggestion as a core **Note** on the block, attributed
    to "WordPress AI" [S12].
  - **Editorial Updates:** a button that **rewrites block text automatically** to resolve pending Notes, then
    relies on a revision checkpoint for review/rollback. No approval step before the change [S13].
  - **Content classification:** button; suggestions as pills the user accepts; 0–1 confidence, floor 0.6,
    `existing_only` terms by default [S14].
- Churn signal (fact): 1.3.0 renamed all meta keys from `ai_` to `wpai_`; 1.4.0 renamed core read abilities [S8].
  Inference: building directly on its internals is risky; calling its abilities is less risky but still
  "experimental".

**Commercial / third-party plugins**

- **Jetpack AI** (Automattic): writing, titles, summaries, featured-image generation, "AI Feedback"; paid plan
  adds "Connected AI" (Claude/ChatGPT via MCP) [S15]. Alt text and captions are on-demand in the editor;
  automatic only for images Jetpack itself generates [S16]. **Write Brief (Breve) readability highlighter
  was retired in April 2026 (v15.7)** [S17]. A "voice-to-content" block exists in Jetpack's source code
  (record → transcribe → "simple draft") [S18]; I could not find its current support page (404), so its status
  is unverified.
- **ClassifAI** (10up, GitHub-distributed): excerpts, key takeaways, titles, resizing, full drafting,
  alt text, OCR, **audio transcripts** (OpenAI / ElevenLabs), comment moderation, classification. **No Anthropic
  provider and no mention of the WP AI Client** [S19].
- **AI Engine** (Meow Apps, v3.8.7, 2026-10-23, 90,000+): chatbots, Content Studio drafting, editor "Magic
  Wand" rewrite, MCP server with 130+ tools, built-in Anthropic connector, can manage WP Connectors,
  audio transcription via OpenAI [S20].
- **Yoast AI Generate** (Premium): SEO/social titles and meta descriptions; shows **several options to pick from**,
  editable, with a live SERP preview; works in Block, Classic and Elementor [S21].
- **Rank Math Content AI:** 40+ tools, one-click and bulk alt text and SEO meta, tiered monthly limits [S22].
  A 2026 review calls the AI writing output "generic" [S23] (opinion).
- **Alt-text specialists:** AltText.ai (vendor claims 20,000+ installs; bulk for existing libraries, SEO keyphrase
  integration) [S24]. Many similar plugins exist.
- **Schema extraction:** "Schema Engine AI" pre-fills schema from content with editor review, BYO key incl.
  Claude; "EasyContentFlow Recipe Schema" uses Claude to extract recipe fields into JSON-LD [S25]. Closest prior
  art to §2.4 review extraction, but both are generic schema tools, not "pull scores from trip-post prose".
- **On-publish pipelines:** at least one plugin (CloudScale SEO AI Optimizer) advertises an "AI Auto Pipeline"
  that runs on publish (meta, alt text, summary) [S16 search]. Vendor claim, unverified; it shows the pattern
  exists but is uncommon.
- **Outside WordPress:** Drupal's AI Initiative (June 2025) has alt text on upload/bulk with review, and
  "Automators" that fill fields from prompts, under an explicit "humans always in control" tenet [S26].

### A2. Voice-to-blog / dictation-to-draft

- **General tools:** AudioPen (voice notes → rewritten text; can imitate a supplied writing sample; rewrite is a
  paid cloud feature) [S27]; Voicepen, Taskade converters; n8n templates chaining transcription (Deepgram) → LLM
  rewrite → emailed draft for approval [S28]. Pressto (iOS) advertises voice-to-WordPress-post (marketing copy) [S29].
  A recurring practitioner tip: **keep the raw transcript next to the draft** so the rewrite can be checked [S28].
- **Inside WordPress:** ClassifAI and AI Engine transcribe audio attachments [S19, S20]; Jetpack's
  voice-to-content block (status unverified) [S18]; the WordPress mobile app's mic button is plain OS dictation [S29].
- **Platform constraints (facts):**
  - The core AI Client documents **no transcription** [S2]. Anthropic's vision docs list image inputs only and I
    found no primary source for audio input to the Claude API (secondary claims of "native audio" look unreliable)
    [S30, S31]. Inference: transcription needs a second provider or the browser.
  - The browser **Web Speech API is "Limited availability", not Baseline**; in Chrome audio is sent to a server
    for recognition; an on-device `processLocally` option exists in newer specs [S32].
  - ASR is measurably worse on elderly speech; fine-tuning Whisper on elderly Dutch speakers cut WER by ~65%
    relative [S33]. Inference: test with Shawn's actual voice before choosing (ROADMAP §4 already says this).
- **Photos (facts that matter for AI Mode):** Claude accepts JPEG, PNG, GIF, WebP only, up to 100–600 images per
  request, and **does not read image metadata** [S30]. So EXIF time/GPS must be read on the server before the
  AI call, and HEIC/AVIF need converting. WordPress 7.1 added native HEIC/AVIF support and moved image
  compression/thumbnailing into the browser (WebAssembly libvips) [S5]; whether that preserves EXIF in the
  original or sub-sizes is unverified. One forum report says core does not strip EXIF [S34] (unverified).
- **Prior art for photo-ordered trip stories:** Polarsteps builds a map/timeline journal from GPS tracking and
  photos and sells printed travel books; its AI is for itinerary planning, not writing [S35].

### A3. Post-by-email / reply-by-email

- **Core "Post via email"** has been slated for removal since 2013 (Trac #22942) but the ticket remains open
  [S36] ⚠ old; I did not confirm it still ships in 7.1.
- **Postie** (v1.9.88, 2026-10-23, 10,000+): IMAP/POP posting, subject-line overrides for category/tags/status;
  since 1.9.77 can ignore unknown senders; "email replies become comments" [S37]. No "reply to edit a post".
- **Sender trust (design fact):** SPF/DKIM results describe the envelope/signing domain, not the visible From
  address; treat a sender as authenticated only when the provider vouches for From (e.g. DMARC pass) [S38].
  Inference: a per-flag reply address carrying an unguessable token is the common pattern (GitHub, Basecamp,
  helpdesks); Phase 2 should confirm with primary docs.
- **Human-in-the-loop by email:** n8n/Flowise templates send drafts by email with approve/reject links;
  a Drupal 2026 session argues approval must be **enforced in code, outside the model**, not by prompt [S39].
- I found **no WordPress tool** where an author answers content-gap questions by replying in natural language.

### A4. Pre-publish checklists and content linting

- **PublishPress Checklists** (v2.29.0, 3,000+): tasks set to Disabled / Recommended (warning) / Required
  (blocks publishing); free tasks include featured image, featured-image alt, all-images alt, categories/tags
  counts, excerpt length; can run OpenAI prompts as a pass/fail task [S40].
- **Core hook:** `PluginPrePublishPanel` lets plugins add items to the "Are you ready to publish?" panel [S41];
  Human Made's Publication Checklist is a framework for blocking/non-blocking checks [S41].
- **Editoria11y** (v3.0.8, 2026-10-23): 50+ author-facing checks, plain-language tips, live in Block and Classic
  editors; **flags "all-bold paragraphs that may be headings" and "list-like text that should be a list"**, missing
  alt text, skipped heading levels; runs locally, no AI, nothing sent to third parties; site-wide dashboard [S42].
  This is the closest existing tool to §2.9/§2.11 detection.
- **Yoast readability** traffic lights: Yoast itself says chasing green lights "can be addictive, but it isn't
  necessarily the best way to create great copy" and advises checking only once writing is done [S43].
  Inference: a scoring UI risks nudging Shawn to flatten his voice; ours should flag facts and structure, not style.

### A5. Simplified editing for non-technical authors

- **Classic Editor plugin: 8+ million installs, 4.9★**, still maintained (updated May 2026) [S44]. In 2026 core
  planned to hide the Classic block from the inserter in 7.1, then **reversed the decision on 7 July 2026**
  after community pushback [S10]. Inference: a large share of authors still prefer a document-style editor.
- **Curation tools (facts):** `allowed_block_types_all` (since 5.8) limits the inserter; apply it per post type /
  capability or it breaks the Site Editor [S45]. Block locking (5.9) and `templateLock: "contentOnly"` (6.1) hide
  design tools and prevent moving/removing; no UI to manage content-only locks [S46]. **7.0 made contentOnly the
  default for unsynced patterns** [S1].
- **Notes (6.9 → 7.1):** block-level comments; 7.1 adds inline notes on passages, @mentions and notifications [S5].
  Inference: Notes are a native place for flags *inside* the editor, which is what the AI plugin uses [S12].
- **"Write mode" / simplified editing:** pre-release coverage expected Write/Design modes in 6.9 with more in 7.0;
  I found no primary confirmation of what shipped [S47]. Phase 2 item.
- The theme decision log already keeps Shawn away from the Site Editor (classic theme + `theme.json`).

---

## B. What authors actually struggle with

### B1. Older adults and technology (general)

- **Speed and errors:** users 65+ took ~43% longer on web tasks than 21–55-year-olds; web-use ability declines
  ~0.8%/year from 25 to 60 [S48] ⚠ old (2013). The 2019 update: older users make more mistakes, struggle to read
  and notice error messages, and are frustrated by interfaces that accept input only in one rigid form; digital
  literacy and confidence have risen [S49] ⚠ old (2019, but the most-cited primary).
- **Learning:** older adults prefer exact task steps over conceptual explanation; a simple task list raised
  motivation and lowered anxiety (Leung et al., via summary) [S50] ⚠ old (2012). Safe trial-and-error with no
  irreversible mistakes reduced anxiety (ExplorAR, 2025, n=18) [S50].
- **Voice interfaces:** a three-week field study with low-tech older adults found information lookup stuck, but
  reminder use was low because of **reliability concerns** (Pradhan et al., TOCHI 2020) [S51] ⚠ old.
  Inference: one bad transcription experience may end AI Mode for Shawn.
- **AI attitudes (2025):** 55% of U.S. adults 50+ have used AI they speak or type to; **92% want to know whether
  information is from a person or AI**; 46% have little or no trust in AI-generated information (n=2,883) [S52].
  Interviews (n=12) found older adults use GenAI "for the little things" such as editing text, worry about
  hallucinations and **"ambiguous authorship"**, and valued being able to direct output precisely [S53].

### B2. Writing with AI: voice and ownership

- **Ownership:** people feel less ownership of AI-generated text yet still call themselves the author and avoid
  disclosing AI; **more influence over the text raises ownership; manual writing scores highest** (Draxler et al.,
  TOCHI 2024) [S54].
- **Homogenisation:** AI suggestions pushed Indian writers toward Western styles and reduced cultural nuance
  (Agarwal et al., CHI 2025, n=118) [S55]. Inference: a generic model rewrite will drift toward bland, standard
  English; Shawn's swearing and phrasing are at risk unless constrained.
- **Spoken vs written:** speech carries errors listeners forgive and readers don't, so some rewrite is needed [S28].
  Inference: the tension between "fix it" and "keep his voice" is the central design problem for AI Mode.

### B3. Block editor pain points

- 2017–2019 Gutenberg tests: inserting blocks was hard, new blocks looked like whitespace, beginners struggled
  with basic block patterns, and confused preview vs edit [S56] ⚠ old.
- An informal 2024 roundup of ~50 bloggers' opinions (52% positive, ~40% negative): praise for ease and drag-in
  images; complaints that it is distracting, that every paragraph is a block, pasting quirks, lost keyboard habits;
  one blogger says it is "at least 50% the reason" they stopped blogging consistently [S57]. Not a survey.
- Accessibility guidance lists **bold/large text used instead of real headings** and hand-typed dash lists among
  the most common author mistakes [S58]. This matches Shawn's "Day 2" paragraphs and stray hyphens.

### B4. What demonstrably helped

- Fewer steps and flexible input (NN/g) [S49]; step-by-step guidance and reversible actions [S50]; plain-language,
  inline explanations with "mark as OK" escapes (Editoria11y's design) [S42]; choosing from options rather than
  writing from scratch (Yoast) [S21]; human control of AI output [S53, S54]. Evidence of *measured* benefit for
  older bloggers specifically: **I found none.** Most evidence is general usability research.

---

## C. Early read: what is new vs. already common

**Already common (reuse, don't reinvent)**

| Roadmap item | Existing equivalent | Note |
|---|---|---|
| 2.2 Alt text + backfill | AI plugin `ai/alt-text-generation` + bulk; AltText.ai; Rank Math | Ours adds post context (dish/place names) and on-publish trigger |
| 2.3 Excerpt / meta | AI plugin excerpt + meta; Yoast AI Generate | Commodity |
| 2.5 Category suggestions | AI plugin classification (confidence, existing-only) | Its 0.6 floor + existing-only is the same idea as "auto-apply when confident" |
| 2.7 Comment triage | AI plugin comment moderation (value score), ClassifAI, Antispam Bee | Commodity |
| 2.8 Proofread | AI plugin Editorial Notes; Jetpack AI | Theirs also judge readability/SEO, which we don't want |
| 2.9 detection of gaps | PublishPress Checklists, Editoria11y | Rule-based detection exists; *fact* gaps (no city/price) don't |
| Voice capture | AudioPen, n8n pipelines, ClassifAI/AI Engine transcripts | Transcription is solved; voice-preserving drafting is not |
| Site voice store | AI plugin / Gutenberg "Guidelines" | Could hold Shawn's style notes; not in core yet |

**Genuinely new or rare (inference)**

1. **Zero-interaction, on-publish operation.** Nearly every tool is a button the author presses. Ours assumes
   the author presses nothing. Rare (one vendor claim found).
2. **Review + score extraction from narrative posts into structured records, with a hard "never invent" rule.**
   Generic schema extractors exist; domain extraction tied to a rating system and linked records does not.
3. **Plain-language email answers that update the post.** Email posting exists; email *Q&A to fill gaps* does not
   in WordPress.
4. **Structure-only fixing with a words-unchanged guarantee.** Editoria11y *detects*; the AI plugin's Editorial
   Updates *rewrites text* with after-the-fact rollback. A fixer that changes blocks but provably not words, with
   before/after approval, is new. Inference: much of it (Day N → heading, image runs → gallery) may not need AI.
5. **Multi-day voice + photo sessions producing a draft in one specific person's voice**, with EXIF-based
   ordering and an "unplaced photos" fallback. Each piece exists; the combination doesn't.

**Where we might be reinventing**: alt text, excerpt/meta, classification and comment scoring. Option worth
weighing in Phase 2: install the AI plugin (or copy its prompts/abilities) and call its abilities from our
on-publish hook, rather than writing our own. Counterpoints: experimental status, recent breaking renames, and a
UI aimed at block-editor button-pressers that Shawn shouldn't see.

**Tension to resolve:** the on-publish model means AI runs *after* Shawn hits Publish, so any change Shawn must
approve arrives after the post is live. The pre-publish panel and Notes are the native in-editor alternatives,
but they add something to his screen.

---

## D. Open questions for Phase 2

1. **Core AI Client in practice:** can it run reliably from a background job (WP-Cron/Action Scheduler) on this
   shared LiteSpeed host given ~30 s limits and no streaming? Vision via `with_file()`: format, size limits, cost
   per image for the 1,060-image backfill?
2. **Can we call the AI plugin's abilities server-side** (e.g. `wp_get_ability('ai/alt-text-generation')->execute()`)
   from a publish hook without its UI? Is it stable enough, or should we copy the approach?
3. **Transcription choice:** which STT services work well with an older Canadian male voice and food/place names
   (custom vocabulary), and what are cost and retention terms? Is browser dictation good enough? Confirm from
   Anthropic primary docs that the Claude API has no audio input.
4. **Voice preservation:** what techniques (few-shot from his posts, "edit-distance budget", transcript-first
   "light touch" modes) keep a personal voice, and is there evaluation research beyond homogenisation studies?
5. **Disclosure and Shawn's "no AI" statement:** what do disclosure norms/research (incl. Google's guidance,
   reader trust studies) say about metadata-only AI vs AI-drafted posts?
6. **Email loop:** sender verification (DMARC, tokenised reply addresses), receiving mail on cPanel (piping vs IMAP
   polling vs inbound webhook services), parsing quoted replies, showing Shawn what changed; prior art from
   GitHub/helpdesk reply-by-email.
7. **Approval UX for the structure fixer:** before/after diff that a non-technical person can judge; can block
   changes be verified "words unchanged" mechanically (text-content hash before/after)? How much is rule-based?
8. **Where flags should surface:** email digest vs core Notes vs pre-publish panel vs admin dashboard; evidence on
   notification fatigue for older users.
9. **Photo metadata:** does Shawn's camera/phone keep EXIF time/GPS; does WP 7.1's browser-side processing strip it
   from originals; HEIC conversion before Claude.
10. **Review extraction accuracy:** benchmarks/patterns for extraction with citations back to source sentences
    ("quote the sentence the score came from") to enforce "never invent".
11. **Status of core "Write mode"/simplified editing and Post via Email in 7.1**, and whether Notes work for a
    single author as a to-do list.
12. **Cost model:** per-post and backfill cost estimates with current Claude pricing and the AI Client's model
    preference behaviour (it may silently fall back to another model).

---

## Notes on source quality

- No prompt-injection attempts noticed in fetched pages.
- Unreliable/contradicted: raidboxes.io on core Guidelines in 7.1 [S10] (contradicted by [S6]); a 2026 blog claiming
  Claude API audio input [S31] (no primary support); vendor install counts (AltText.ai) and app-store copy (Pressto).
- hostingadvice.com's 7.1 article returned 403; Jetpack voice-to-content support page returned 404.

## Sources (all accessed 2026-10-25)

- [S1] https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/ (2026-05-14): 7.0 AI Client, Connectors, client-side Abilities, contentOnly default for patterns, Notes.
- [S2] https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/ (2026-03-24): AI Client API, `with_file()`, JSON schema, prevent filter, no STT listed.
- [S3] https://make.wordpress.org/core/2026/03/18/introducing-the-connectors-api-in-wordpress-7-0/ (2026-03-18, mod. 04-13): providers, key lookup order, keys unencrypted.
- [S4] https://make.wordpress.org/ai/2026/06/19/whats-coming-to-the-ai-client-in-wp-7-1/ (2026-06-19): no visible core AI, streaming not native, ~30 s timeouts, embeddings.
- [S5] https://wordpress.org/news/?p=21420 (2026-08-19): WP 7.1 release: Abilities lifecycle, inline Notes, HEIC/AVIF, browser-side image processing.
- [S6] https://developer.wordpress.org/news/2026/08/whats-new-for-developers-august-2026/ (2026-08): Guidelines/Knowledge not merged in 7.1; Abilities changes.
- [S7] https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/ (2026-02-04): MCP Adapter, app-password auth, least privilege.
- [S8] https://wordpress.org/plugins/ai/ (v1.4.0, 2026-10-05): AI plugin features, block-editor-only, changelog renames.
- [S9] https://github.com/WordPress/ai (2026): experiment list and docs links.
- [S10] https://raidboxes.io/en/blog/wordpress/wordpress-7-1/ (updated 2026-10-15): Classic block U-turn (7 Jul 2026); claims on Guidelines/connector auth (partly unreliable).
- [S11] https://github.com/WordPress/ai/blob/develop/docs/experiments/alt-text-generation.md (2026): alt-text triggers, bulk cap, ability output.
- [S12] https://github.com/WordPress/ai/blob/develop/docs/experiments/editorial-notes.md (2026): review notes as core Notes.
- [S13] https://github.com/WordPress/ai/blob/develop/docs/experiments/editorial-updates.md (2026): auto-rewrite with revision rollback.
- [S14] https://github.com/WordPress/ai/blob/develop/docs/experiments/content-classification.md (2026): suggestions, confidence floor, existing-only.
- [S15] https://jetpack.com/ai/ (2026): Jetpack AI features, Connected AI via MCP, pricing.
- [S16] https://jetpack.com/support/generate-alt-text-and-captions-with-ai-in-the-editor/ (2026, via search): on-demand alt text; search also surfaced CloudScale SEO AI Optimizer claim.
- [S17] https://jetpack.com/support/publish-better-content-with-write-brief-with-ai/ (2026): Breve retired April 2026, v15.7.
- [S18] Jetpack `extensions/blocks/voice-to-content/edit.tsx` (copies seen via search, undated): voice-to-content block exists in code.
- [S19] https://github.com/10up/classifai (undated, WP 6.9+): ClassifAI features/providers, no Anthropic.
- [S20] https://wordpress.org/plugins/ai-engine/ (v3.8.7, 2026-10-23): AI Engine features, Anthropic, Connectors, transcription.
- [S21] https://yoast.com/features/ai-generate/ (2026, undated): AI Generate options-to-pick UI, editors supported.
- [S22] https://rankmath.com/content-ai/ (2026, undated): Content AI tools and limits.
- [S23] https://www.suganthan.com/blog/rank-math-seo-plugin-review-from-an-seo-guy/ (2026 update, via search): opinion on generic AI output.
- [S24] https://wordpress.org/plugins/alttext-ai/ and https://alttext.ai/integrations/wordpress/ (2026, via search): bulk alt text; vendor install claim.
- [S25] https://wordpress.org/plugins/review-schema/ and https://wordpress.org/plugins/easycontentflow-recipe-schema/ (2026, via search): AI schema extraction incl. Claude.
- [S26] https://www.drupal.de/en/node/39 and https://www.thedroptimes.com/node/54825 (2025): Drupal AI Initiative, human-in-control, alt text modules.
- [S27] https://www.audiopen.ai/voice-to-text-for-writers and https://josephnilo.com/blog/audiopen-review/ (2025–26, via search): style imitation; independent review.
- [S28] https://n8n.io/workflows/8246-convert-voice-memos-to-blog-posts-with-deepgram-gpt-35-and-google-sheets-dashboard/ and https://thepracticalai.digitalpress.blog/ai-voice-notes-to-clean-drafts/ (2025–26): voice-memo pipelines; keep transcript; spoken errors.
- [S29] https://apps.apple.com/mx/app/pressto/id6745399059 and https://www.hostgator.com/blog/?p=26314 (2025–26, via search): voice-to-post app (marketing); WP app dictation.
- [S30] https://platform.claude.com/docs/en/build-with-claude/vision (2026): image formats, limits, no metadata reading, costs.
- [S31] https://tkmxai.it.com/claude-api-in-2026-10 and https://the-decoder.com/anthropics-claude-uses-elevenlabs-technology-for-speech-features-rather-than-an-in-house-model/ (2025–26, via search): unreliable audio-input claim; voice mode uses third-party speech.
- [S32] https://developer.mozilla.org/en-US/docs/Web/API/SpeechRecognition (mod. 2026-08-19): limited availability, server-based in Chrome, `processLocally`.
- [S33] https://arxiv.org/abs/2502.17284 (2025): Whisper on elderly speech, WER reductions from fine-tuning.
- [S34] https://buddypress.org/?p=320696 (undated forum, via search): claim that WP doesn't strip EXIF (unverified).
- [S35] https://techcrunch.com/2016/03/22/polarsteps-travel-blog ⚠ old and https://apps.apple.com/us/app/polarsteps/id947925763 (2026): GPS/photo trip journals.
- [S36] https://core.trac.wordpress.org/ticket/22942 and https://wptavern.com/wordpress-3-7-to-remove-post-by-email-feature-in-favor-of-official-plugin (2013–) ⚠ old: Post-by-email removal history.
- [S37] https://wordpress.org/plugins/postie/ (v1.9.88, 2026-10-23): Postie features, unknown-sender setting.
- [S38] https://packagist.org/packages/getodden/inbound-mail (2026, via search): SPF/DKIM don't authenticate From; single-sender rule.
- [S39] https://events.drupal.org/rotterdam2026/session/ai-assists-humans-decide-agentic-workflows-drupal-drupal-ai-hackathon-story and https://docs.flowiseai.com/tutorials/human-in-the-loop (2026): approval enforced outside the model; email approve/reject.
- [S40] https://wordpress.org/plugins/publishpress-checklists/ (v2.29.0, 2026-08-19): checklist tasks, Required vs Recommended, AI checks.
- [S41] https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-pre-publish-panel/ and https://github.com/humanmade/publication-checklist/wiki (current): pre-publish slot; checklist framework.
- [S42] https://wordpress.org/plugins/editoria11y-accessibility-checker/ (v3.0.8, 2026-10-23): author-facing checks incl. "paragraph that may be a heading".
- [S43] https://yoast.com/yoast-seo-hates-my-writing-style/ (undated): Yoast on not chasing green lights.
- [S44] https://wordpress.org/plugins/classic-editor/ (mod. 2026-05-28): 8M+ installs, 4.9★.
- [S45] https://developer.wordpress.org/news/snippets/limiting-allowed-blocks-without-breaking-the-site-editor/ (2025) and https://kinsta.com/blog/disable-gutenberg-blocks/: allowed blocks scoped by context/capability.
- [S46] https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/ (current): locking, contentOnly, no UI for content locks.
- [S47] https://www.dreamhost.com/blog/?p=74919 (2025, pre-release): Write/Design modes plan (unconfirmed).
- [S48] https://www.nngroup.com/articles/usability-seniors-improvements/ (2013) ⚠ old: 43% slower; 0.8%/yr.
- [S49] https://www.nngroup.com/articles/usability-for-senior-citizens/ (2019-09-08) ⚠ old: errors, error messages, flexible input, rising confidence.
- [S50] https://arxiv.org/html/2508.01282v1 (2025) and summary of Leung et al. "How Older Adults Learn to Use Mobile Devices" (2012 ⚠ old): step-by-step, safe trial-and-error.
- [S51] https://thatlab.umd.edu/papers/2020_UseOf.pdf (TOCHI 2020) ⚠ old: older adults' voice assistant use; reliability concerns.
- [S52] https://ihpi.umich.edu/national-poll-healthy-aging/national-findings/how-older-adults-use-and-think-about-ai (Jul 2025): 55% used AI, 92% want disclosure, 46% low trust.
- [S53] https://pmc.ncbi.nlm.nih.gov/articles/PMC12761539/ (Innovation in Aging, 2025-12-31): value tensions, ambiguous authorship, "little things".
- [S54] https://arxiv.org/abs/2303.03283 (TOCHI 2024): AI Ghostwriter Effect, ownership vs influence.
- [S55] https://arxiv.org/abs/2409.11360 (CHI 2025): AI suggestions homogenise writing style.
- [S56] https://make.wordpress.org/design/2017/04/27/user-testing-the-gutenberg-editor/ and https://make.wordpress.org/design/2019/04/01/navigation-menu-block-usability-testing-results/ ⚠ old: Gutenberg usability tests.
- [S57] https://themeisle.com/blog/writers-bloggers-opinions-of-wordpress-block-editor/ (2024-12-02): bloggers' block editor opinions (informal).
- [S58] https://www.accessibilitychecker.org/ace/engine/ace-struct-no-p-as-heading/ and https://help.illinoisstate.edu/accessibility/website-and-digital/accessible-content-overview/page-structure/headings (current): bold text instead of headings, fake lists.
