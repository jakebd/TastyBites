# Authoring experience and AI: research report

2026-10-25 · for Jacob and future coding sessions · covers ROADMAP §2 (quiet assistant) and §4 (AI Mode)

**Read this first, then the phase files for detail and sources.** How the research was run and refined is in
`PROCESS.md`; each phase (`phase-1-landscape.md`, `phase-2-deep-dives.md`, `phase-3-implementation.md`) was
reviewed by the organizer (`review-1.md`, `review-2.md`, `review-3.md`) before the next began. Source IDs below
(`[Sn]` Phase 1, `[Pn]` Phase 2, `[Qn]` Phase 3) point into those files.

> **Jacob's decisions after this report (2026-10-25) override it where they differ; see §10.**

---

## 1. The ten things to remember

1. **Shawn's real bottleneck is energy and the "technical bits", not skill.** Older bloggers write on laptops,
   find images and technical issues the hard part, and are helped most by a trusted person doing the fiddly work
   [P17]. Features that *remove* work (featured photo, alt text, layout) are well aimed; anything that *asks* him
   for something must be rare and one step.
2. **Nothing for Shawn to press.** Almost every AI tool for WordPress is a button the author presses [S8, S15,
   S19–S22]. Ours runs after Publish with no new buttons. That is the main thing that's new.
3. **Tiered after-publish design** (resolves "approval would arrive after the post is live"):
   Tier 0 at publish, no AI (featured photo, safe layout rules) · Tier 1 background within ~5 min, AI writes
   metadata only (alt text, excerpt, terms, draft reviews) · Tier 2 anything needing judgement goes to Jacob or a
   weekly question email to Shawn [Phase 2 §3]. His words are never changed without approval.
4. **Never invent, enforced in code, not in the prompt.** Every fact the AI extracts comes with a verbatim quote
   that PHP checks against his post; scores must appear inside the quote. Anything that fails becomes a question,
   never a guess [Phase 2 §11, Phase 3 §3.4]. Claude's Citations can't be combined with JSON output [P35], so we
   check quotes ourselves.
5. **Voice: "arrange, don't rewrite."** Models over-correct by default [P13–P15], and imitating informal blog
   style is their weakest area [P16]. AI Mode should return sentence IDs and headings, and PHP builds the post from
   Shawn's own sentences. Numbers, names, scores and swearing in the draft must trace back to his transcript
   [Phase 2 §4].
6. **The memoir apps already proved the AI Mode pattern for older people** (Remento, Storyworth, Storii): keep the
   recording and near-verbatim transcript forever, offer a rewrite dial defaulting to light, use no-login links,
   allow re-saying one part. Their users quit from effort and nagging, not from bad AI, so contact Shawn rarely and
   in batches [P1–P9].
7. **The email loop should start as links, not reply parsing.** A weekly email with at most 5 questions, each a
   button to a one-question page (signed single-use token, no login, big text). Reply-by-email can come later
   [Phase 2 §7, Phase 3 §2.4].
8. **Most of the structure fixer needs no AI.** "Day 2" → heading, image runs → gallery, empty headings/bullets,
   legacy rows are deterministic. A mechanical words-unchanged check (normalised word sequence + image IDs +
   hash) guards every change, and rules start in propose mode for Jacob [Phase 2 §5, Phase 3 §3.11].
9. **WordPress gives plumbing, not features.** 7.x core has the AI Client, Connectors and Abilities API, but no
   visible AI, no speech-to-text, and no native streaming [S2–S4]. The official "AI" plugin covers alt text,
   excerpts, classification and comment scoring, but it's button-driven and experimental with breaking renames [S8].
   Reuse its *designs*, not its code. The Claude API takes no audio [P31].
10. **Privacy and disclosure come before AI goes live.** The live Privacy page says nothing about AI and promises
    comments aren't sent to an outside service. 92% of over-50s want to know when text is AI-made [S52]; one-line
    disclosures don't hurt trust, detailed ones do [P44]. Shawn's public "I don't use any AI hacks" line needs a
    conversation (options in §6).

---

## 2. What's been done before vs. what's new

| Area | Already exists (reuse the idea) | New in our plan |
|---|---|---|
| Alt text | AI plugin ability + bulk, AltText.ai, Rank Math [S8, S11, S24] | Post context (dish/place names) checked against his text; on publish; batch backfill |
| Excerpt / meta | AI plugin, Yoast AI Generate [S8, S21] | **Extractive**: picks his own sentences, never writes new ones |
| Categories | AI plugin classification (confidence 0.6 floor, existing terms only) [S14] | Auto-apply only ≥0.8 and only on uncategorised posts; destination with a quote |
| Review extraction | Generic AI schema extractors [S25] | Scores pulled from narrative trip posts, quote-checked, as drafts |
| Content checks | PublishPress Checklists, Editoria11y ("bold line may be a heading") [S40, S42] | Fact gaps (city, price, score) asked in plain words by email |
| Post by email | Postie, core Post via Email [S36, S37] | One-question answer pages that update the post |
| Structure | Editoria11y detects; AI plugin rewrites with rollback [S13, S42] | Fixes layout but **provably** not words; propose → trust → auto |
| Voice to post | AudioPen, n8n pipelines, ClassifAI/AI Engine transcription [S19, S20, S27, S28] | Multi-day voice + photo sessions, arranged from his sentences, EXIF photo order |
| Older-adult storytelling | Remento, Storyworth, Storii [P1–P5] | Same pattern applied to an ongoing public blog, with a "never invent" rule they don't promise |

---

## 3. Authoring difficulties others hit, and what helped

| Difficulty (evidence) | What helped | How we apply it |
|---|---|---|
| Uploading images, technical settings, getting answers [P17] | A helper who does the technical part | The assistant + Jacob; Shawn never sees settings |
| Low energy limits posting [P17] | Shorter posts, lower expectations | Nothing added to his workflow; AI Mode lets him talk instead of type |
| Slower, more errors, missed error messages [S48, S49] | Fewer steps, flexible input, clear plain messages | No new editor UI; at most one informational pre-publish line ("I'll use your first photo") |
| Block editor feels distracting, "every paragraph is a block" [S57, P19b] | Curated editor, patterns, Classic for some | Keep his patterns; template ground rules absorb layout mistakes |
| Bold lines and typed dashes instead of headings/lists [S58] | Linters that explain in plain words [S42] | Structure fixer does it for him |
| Nagging and weekly "homework" make older users quit [P7, P9] | Few, batched, one-step requests | Weekly digest at most, skip empty weeks, ≤5 questions |
| Account access, logins [P7] | No-login links (Remento) [P2] | Signed one-question pages |
| Hard-to-read screens (macular degeneration) [P8] | Large text, high contrast | 20px+ text on any page we build for him |
| Wants to fix one bit, not redo all [P8] | Partial re-record | "Say it again" per part in AI Mode |
| Loss of ownership and voice with AI [S54, S55, P12] | Human control, keep originals | Light-touch default, raw transcript kept, code-checked budgets |
| Distrust of AI and "ambiguous authorship" [S52, S53] | Clear, short disclosure [P44] | Disclosure options for Jacob and Shawn (§6) |

---

## 4. Architecture (for coding agents)

- **Code location:** a regular plugin, `plugins/tastybites-assistant/`, in this repo. Themes shouldn't hold
  site-critical functionality [Q7]; a plugin can be deactivated from wp-admin in an emergency (Shawn is an Editor
  and can't). Deploy pipeline: `plugins export-ignore` in `.gitattributes`, a `TARGET` parameter in `deploy.sh`,
  a generalised `deploy-remote.sh` (own manifest/backups per target), workflow deploys both [Phase 3 §1.2].
- **Jobs:** a cPanel cron every 5 min running `wp cron event run --due-now` (CLI has no time limit, avoids
  LiteSpeed's abort and the host's self-request throttling) [P24, P26, P27]; `DISABLE_WP_CRON` only after it's
  confirmed. Jobs run as a dedicated "Assistant" Editor user.
- **Trigger:** `wp_after_insert_post` for published `post` / `review_content` / `recipes_content`, debounced into
  one publish-pass job [Q11]. Never on drafts (decision log).
- **AI wrapper `tba_ai()`:** core AI Client, JSON schema output, model allow-list, timeout raised only inside jobs
  [P23], every call logged (feature, model actually used, tokens, cost, outcome). Results from an unexpected model
  are discarded (the client may fall back silently [S2]). Message Batches API directly for the backfill [P29].
- **Guardrails:** kill switches (wp-config constant, per-feature option, plugin deactivate); daily spend cap;
  `wp_ai_client_prevent_prompt` lets only our runner prompt, and staging only with `TBA_STAGING_AI`; API outages
  retry then flag Jacob, never block publishing [Phase 3 §1.4].
- **Secrets:** keys as constants from a file outside the web root (`~/.tastybites-secrets.php`), separate staging
  key. **Must fix first:** `refresh-staging-remote.sh` copies production's `wp-config.php`, so it has to rewrite
  the secrets include for staging (review-3 §2).
- **Flags:** private CPT `tba_flag` (kind, audience jacob/shawn, plain question, choices, status, token hash,
  applied change for Undo). Jacob's list screen first.
- **Structure fixer:** deterministic rules; excludes pattern content, detected by group `metadata.name`
  (Ingredients, Instructions, Review, Final thoughts) and `mfb/meta-field-block` (review-3 §1). Approval screen is
  our own side-by-side rendered preview; WordPress's revisions screen on 7.1.3 is a raw code diff (organizer check),
  but Restore still works as undo.

Per-item specs (inputs, JSON schemas, PHP checks, tests, risks) for 2.1–2.12 and AI Mode: `phase-3-implementation.md` §3.

---

## 5. Recommended build order

| Step | What | AI? | Needs before production |
|---|---|---|---|
| 1 | Plugin skeleton, deploy target, cron, job + log tables, kill switches | No | n/a |
| 2 | **2.1 featured-photo fallback** + backfill (dry run first) | No | n/a |
| 3 | **2.11 structure fixer in propose mode** + words-unchanged check + preview screen | No | Dry run clean over all staging posts |
| 4 | Secrets, AI wrapper, cap, `prevent_prompt`; draft privacy migration | n/a | Fix staging-refresh key copying |
| 5 | **2.2 alt text** on publish; model eval on 30 images; then batch backfill (~1,060 images) | Yes | Privacy page approved |
| 6 | 2.3 extractive excerpt | Yes | " |
| 7 | Flags store + 2.9 deterministic checks + Jacob's list | Mostly no | n/a |
| 8 | 2.5 categories/destination (suggest 2 weeks, then auto ≥0.8) | Yes | " |
| 9 | 2.4 review extraction → drafts Jacob publishes | Yes | " |
| 10 | 2.10 digest + one-question pages (Jacob-only 2 weeks, then Shawn) | Parsing only | Shawn's check-in |
| 11 | 2.11 auto-apply for rules with clean records | No | ≥10 clean proposals per rule |
| 12 | 2.6 next stops (Jacob approves) | Yes | n/a |
| 13 | AI Mode experiments: A speech-to-text bake-off → B "arrange" pipeline → C capture screen with Shawn | Yes | Shawn's OK on recordings and disclosure |

Deferred: **2.7 comment triage** (breaks the privacy promise; tiny volume), **2.8 proofread** (voice risk),
**2.12 image tidy-up** (QUIC.cloud already chosen).

---

## 6. Decisions for Jacob (and Shawn at the check-in)

1. **Approve the tiered design and the build order** above (or reorder).
2. **Privacy page "AI helpers" section** before any AI runs on production. Draft wording: Phase 3 §1.1.
3. **Disclosure**, given his "I don't use any AI hacks" line. Options [Phase 2 §9]:
   (1) metadata-only AI, his line stays true; (2) light-touch AI Mode: "I talk, a computer types it up, I check
   every word"; (3) AI drafting with a one-line note per post; (4) no AI in writing ever. Keep any disclosure to
   one line [P44].
4. **Recordings:** where they go (speech-to-text vendor + Anthropic), retention (suggest delete on publish or after
   90 idle days, keep transcripts), and Shawn's written OK.
5. **Models:** chosen by tests, not price. Estimated cost stays **under ~$5/month even on Opus 5.5** at 2–4 posts a
   month; the alt-text backfill is ~$0.12 (Haiku 5.5) to ~$4.70 (Opus 5.5) in batch [Phase 3 §0, §3.2].
6. **Excerpts with swearing** on Facebook share cards: allowed, or pick another sentence?

---

## 7. Costs (Anthropic pricing page, quoted verbatim in Phase 3 §0)

| Model | Input / output per MTok | Batch |
|---|---|---|
| Opus 5.5 | $4 / $20 | $2 / $10 |
| Sonnet 5.5 | $2 / $10 | $1 / $5 |
| Haiku 5.5 (≤100k-token prompts) | $0.10 / $0.50 | $0.05 / $0.25 |

Speech-to-text (vendor pages): Deepgram Nova-3 $0.0043/min, AssemblyAI Universal $0.15–0.21/hour; set their
training opt-outs before sending any audio [Q3–Q6]. Batch results are kept 29 days and aren't zero-retention [P32].

---

## 8. Corrections made along the way (the refinement trail)

| Claim | Where | Correction | By |
|---|---|---|---|
| Core 7.1 has a Guidelines screen | Secondary source in Phase 1 | Contradicted by the developer blog; not in core | Agent, Phase 1 |
| Shawn may use the Classic editor | Phase 1 assumption | He uses the block editor with two patterns | Organizer, review 1 |
| Missing prior art | Phase 1 | Memoir products (Remento etc.) added; became the key precedent | Organizer, review 1 |
| Visual Revisions gives a rendered before/after | Phase 2 (blogs) | Not on our 7.1.3: raw code diff; build our own preview | Organizer, staging check |
| Pricing and tokenizer figures from "skill reference" | Phase 2 | Re-quoted from Anthropic's pricing and thinking docs | Agent, Phase 3 |
| Comment triage is a simple add-on | ROADMAP §2.7 | Breaks the live privacy promise; deferred | Organizer, review 2 → Phase 3 |
| Pattern blocks detectable as `tastybites/*` | Phase 3 | Not in saved content; detect by group names + meta-field block | Organizer, review 3 |
| Staging key leakage "to verify" | Phase 3 | Confirmed: refresh copies production `wp-config.php` | Organizer, review 3 |

---

## 9. Before building: verify on staging

The full checklist is `phase-3-implementation.md` §5. Already confirmed by the organizer: PHP `intl` is installed;
CLI upload limits are 100M; Shawn is an Editor; Visual Revisions is absent. Still open: the cron line, abilities
from the command line, the connector key constant name, LiteSpeed purge on background updates, web upload limits,
EXIF surviving 7.1 uploads, and MediaRecorder on Shawn's laptop browser.

---

## 10. Jacob's decisions after the report (2026-10-25)

These override the sections above where they differ. Details are in ROADMAP §2 and §4 and the decision log.

1. **Two features.** The quiet assistant (§2) works behind Shawn's normal editor workflow. AI Mode (§4) is a
   separate workflow with its own screen.
2. **Spelling is fixed automatically after publish (ROADMAP 2.8), no longer deferred.** The AI returns
   word-level fixes only; PHP applies a fix only if that word is in the post, only that word changes and the fix
   is a plausible spelling correction; a words-unchanged check guards the rest. Slang, swearing, Canadian
   spellings and names are left alone; confirmed names go on a site word list. Unsure words stay as written and
   trigger an immediate email to Jacob and Shawn. Report-only run over the archive first. In the build order,
   this goes straight after the AI wrapper (step 4), ahead of alt text.
3. **Question emails are per publish, not weekly, with no 5-question cap** (replaces §1.7's weekly digest).
   One email per publish pass, most important first; posts within the same hour share an email; none if there
   are no questions; unanswered questions stay on Jacob's list and are never re-sent.
4. **Email settings (ROADMAP 2.13):** Jacob controls per email type and per person who receives what, so he
   can turn Shawn's emails off and handle them himself. Questions always reach Jacob's list.
5. **AI Mode feedback step:** the AI Mode screen shows the rendered draft beside the AI's notes and questions;
   Shawn answers by voice or text, re-says a part, or skips, and only that part is redone. The §2 helpers run on
   the AI Mode draft before publish (a deliberate exception to "publish only"). Hands-on edits then happen in the
   normal editor; no custom editor.
